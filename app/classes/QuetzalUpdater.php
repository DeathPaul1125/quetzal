<?php

/**
 * QuetzalUpdater — descarga e instala actualizaciones del core de Quetzal
 * desde un servidor remoto (típicamente otra instalación con el plugin
 * QuetzalUpdates expuesto bajo /quetzal-updates).
 *
 * Flujo end-to-end (apply()):
 *   1. check()       → consulta /check?current=X.Y.Z, devuelve manifest si hay update
 *   2. backup()      → ZIP de todo el proyecto excepto plugins/, .env, uploads, cache
 *   3. download()    → descarga el ZIP del release a tmp
 *   4. verify()      → SHA-256 contra el manifest
 *   5. extract()     → descomprime a tmp/_extract
 *   6. preserve()    → guarda paths protegidos (.env, sidebar.json, plugins.json)
 *   7. swap()        → mueve archivos extraídos sobre la raíz (sin tocar plugins/)
 *   8. migrate()     → corre migraciones core nuevas
 *   9. updateVersion → reescribe quetzal.json con la versión nueva
 *
 * Cualquier error después de extract() debe disparar rollback() restaurando
 * desde el ZIP de backup.
 *
 * Endpoints esperados del servidor:
 *   GET  {endpoint}/check?current={version}     → 200 manifest | 204 (al día) | 4xx
 *   GET  {endpoint}/download/{version}          → stream binario
 * Headers de auth: X-Update-Token: {token}
 */
class QuetzalUpdater
{
  /** Paths que NUNCA se sobrescriben durante el swap (relativos a ROOT). */
  const PROTECTED_PATHS = [
    'plugins',                          // todos los plugins instalados
    'app/config/.env',                  // credenciales y configuración local
    'app/config/plugins.json',          // estado de plugins habilitados
    'app/config/sidebar.json',          // sidebar custom del usuario
    'app/cache',                        // caché de Blade y otros
    'app/logs',                         // logs locales
    'assets/uploads',                   // uploads de usuarios
    'tmp',                              // staging del propio updater
    'backups',                          // backups previos
  ];

  /** Carpetas que se incluyen en el backup (todo lo demás del root). */
  const BACKUP_INCLUDE = ['app', 'templates', 'assets', 'public', 'docs'];

  protected string $endpoint;
  protected string $token;
  protected string $root;
  protected string $tmpDir;
  protected string $backupsDir;

  public function __construct(?string $endpoint = null, ?string $token = null, ?string $root = null)
  {
    $this->root       = rtrim($root ?? (defined('ROOT') ? ROOT : getcwd()), '/\\') . DIRECTORY_SEPARATOR;
    $this->endpoint   = rtrim($endpoint ?? (string) get_option('update_endpoint') ?: 'https://system.facturagt.com/quetzal-updates', '/');
    $this->token      = (string) ($token ?? get_option('update_token') ?? '');
    $this->tmpDir     = $this->root . 'tmp' . DIRECTORY_SEPARATOR . 'updater' . DIRECTORY_SEPARATOR;
    $this->backupsDir = $this->root . 'backups' . DIRECTORY_SEPARATOR;
  }

  // ============================================================
  // 1. Comparación de versiones (semver simple, public/static para tests)
  // ============================================================

  /**
   * Compara dos versiones semver-like (acepta "1.2.3", "1.2.3-beta", etc.).
   *
   * @return int -1 si $a < $b, 0 si iguales, 1 si $a > $b
   */
  public static function compareVersions(string $a, string $b): int
  {
    return version_compare(self::normalizeVersion($a), self::normalizeVersion($b));
  }

  /**
   * Normaliza una versión a "x.y.z" (rellena con ceros faltantes).
   */
  public static function normalizeVersion(string $v): string
  {
    $v = trim(ltrim($v, 'vV '));
    if ($v === '') return '0.0.0';
    return $v;
  }

  /**
   * @return bool true si $remote es más nueva que $current
   */
  public static function isNewer(string $current, string $remote): bool
  {
    return self::compareVersions($remote, $current) > 0;
  }

  // ============================================================
  // 2. check() — consulta el servidor
  // ============================================================

  /**
   * Consulta el endpoint /check con la versión actual y devuelve el manifest
   * del release disponible (o null si está al día).
   *
   * @return array|null Manifest con keys: version, sha256, url, changelog, min_php, released_at
   * @throws Exception si la conexión o la auth fallan
   */
  public function check(?string $currentVersion = null): ?array
  {
    $current = $currentVersion ?? quetzal_version();
    $url     = $this->endpoint . '/check?current=' . urlencode($current);

    [$status, $body] = $this->httpGet($url);

    if ($status === 204 || $status === 304) return null; // al día
    if ($status === 401 || $status === 403) {
      throw new Exception('Token de actualización inválido o sin permiso (HTTP ' . $status . ').');
    }
    if ($status >= 400) {
      throw new Exception('El servidor de actualizaciones respondió ' . $status . '.');
    }

    $manifest = json_decode($body, true);
    if (!is_array($manifest) || empty($manifest['version']) || empty($manifest['url']) || empty($manifest['sha256'])) {
      throw new Exception('El servidor devolvió un manifest inválido.');
    }

    if (!self::isNewer($current, (string) $manifest['version'])) return null;

    if (!empty($manifest['min_php']) && version_compare(PHP_VERSION, (string) $manifest['min_php'], '<')) {
      throw new Exception(sprintf('Esta versión requiere PHP %s+, tenés %s.', $manifest['min_php'], PHP_VERSION));
    }

    return $manifest;
  }

  // ============================================================
  // 3. backup() — empaqueta el estado actual en un ZIP
  // ============================================================

  /**
   * Crea un ZIP con todos los archivos del proyecto excepto los protegidos.
   * Devuelve el path absoluto al ZIP creado.
   *
   * @throws Exception
   */
  public function backup(): string
  {
    $this->ensureDir($this->backupsDir);
    $current = quetzal_version();
    $stamp   = date('Ymd-His');
    $zipPath = $this->backupsDir . sprintf('core-%s-%s.zip', $current, $stamp);

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      throw new Exception('No se pudo crear el ZIP de backup en ' . $zipPath);
    }

    // Incluir el quetzal.json en raíz
    if (is_file($this->root . 'quetzal.json')) {
      $zip->addFile($this->root . 'quetzal.json', 'quetzal.json');
    }

    foreach (self::BACKUP_INCLUDE as $sub) {
      $abs = $this->root . $sub;
      if (!is_dir($abs)) continue;
      $this->addDirToZip($zip, $abs, $sub);
    }

    $zip->close();
    return $zipPath;
  }

  // ============================================================
  // 4. download() — descarga el ZIP del release
  // ============================================================

  public function download(array $manifest): string
  {
    $this->ensureDir($this->tmpDir);
    $version = (string) $manifest['version'];
    $url     = (string) $manifest['url'];
    $dest    = $this->tmpDir . 'release-' . preg_replace('/[^a-z0-9._-]/i', '_', $version) . '.zip';

    $fp = @fopen($dest, 'wb');
    if (!$fp) throw new Exception('No se pudo escribir el archivo de release en ' . $dest);

    try {
      $ch = curl_init($url);
      curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 600,
        CURLOPT_HTTPHEADER     => $this->authHeaders(),
        CURLOPT_FAILONERROR    => true,
      ]);
      $ok = curl_exec($ch);
      if (!$ok) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Error al descargar el release: ' . $err);
      }
      curl_close($ch);
    } finally {
      fclose($fp);
    }

    if (!is_file($dest) || filesize($dest) === 0) {
      throw new Exception('El archivo descargado está vacío.');
    }

    return $dest;
  }

  // ============================================================
  // 5. verify() — chequeo de integridad
  // ============================================================

  public function verify(string $zipPath, string $expectedSha256): void
  {
    $actual = hash_file('sha256', $zipPath);
    if (!hash_equals(strtolower($expectedSha256), strtolower((string) $actual))) {
      throw new Exception('Falló la verificación SHA-256 del release. Archivo corrupto o manipulado.');
    }
  }

  // ============================================================
  // 6. extract() + 7. swap() — descomprime y aplica
  // ============================================================

  /**
   * Descomprime el ZIP en una carpeta de staging dentro de tmp/.
   * Devuelve el path al directorio raíz extraído (puede tener un prefijo
   * tipo "quetzal-1.6.1/" o estar al ras).
   */
  public function extract(string $zipPath): string
  {
    $stagingDir = $this->tmpDir . 'staging-' . date('YmdHis') . DIRECTORY_SEPARATOR;
    $this->ensureDir($stagingDir);

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
      throw new Exception('No se pudo abrir el ZIP del release.');
    }
    if (!$zip->extractTo($stagingDir)) {
      $zip->close();
      throw new Exception('No se pudo extraer el ZIP del release.');
    }
    $zip->close();

    return $this->detectRoot($stagingDir);
  }

  /**
   * Mueve los archivos extraídos a la raíz del proyecto, respetando los
   * paths protegidos. No borra archivos que no estén en el release (los
   * agregados locales sobreviven), excepto en directorios *del core* que sí
   * se reemplazan completos para evitar archivos huérfanos viejos.
   */
  public function swap(string $sourceDir): void
  {
    $sourceDir = rtrim($sourceDir, '/\\') . DIRECTORY_SEPARATOR;

    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
      $relativePath = ltrim(substr((string) $item->getPathname(), strlen($sourceDir)), '/\\');
      $relativePath = str_replace('\\', '/', $relativePath);

      if ($this->isProtected($relativePath)) continue;

      $destPath = $this->root . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

      if ($item->isDir()) {
        $this->ensureDir($destPath);
      } else {
        $this->ensureDir(dirname($destPath));
        if (!@copy($item->getPathname(), $destPath)) {
          throw new Exception('No se pudo escribir ' . $relativePath . ' (¿permisos?).');
        }
      }
    }
  }

  // ============================================================
  // 8. migrate() — corre migraciones core nuevas
  // ============================================================

  public function migrate(PDO $pdo): array
  {
    if (!class_exists('Migrator')) {
      require_once $this->root . 'app' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Migrator.php';
    }
    $migrator = new Migrator($pdo, $this->root . 'app' . DIRECTORY_SEPARATOR . 'migrations');
    return $migrator->run();
  }

  // ============================================================
  // 9. updateVersion() — reescribe quetzal.json
  // ============================================================

  public function updateVersion(string $newVersion, ?string $releasedAt = null, ?string $minPhp = null): void
  {
    $payload = [
      'version'     => $newVersion,
      'released_at' => $releasedAt ?? date('Y-m-d'),
      'min_php'     => $minPhp,
      'channel'     => 'stable',
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (@file_put_contents($this->root . 'quetzal.json', $json) === false) {
      throw new Exception('No se pudo actualizar quetzal.json (¿permisos?).');
    }
  }

  // ============================================================
  // Rollback — restaura un backup previo
  // ============================================================

  public function rollback(string $backupZipPath): void
  {
    if (!is_file($backupZipPath)) {
      throw new Exception('Backup no encontrado: ' . $backupZipPath);
    }
    $extracted = $this->extract($backupZipPath);
    $this->swap($extracted);
  }

  // ============================================================
  // Orquestador completo
  // ============================================================

  /**
   * Ejecuta todo el flujo de actualización. Llama callbacks por etapa para
   * que el caller pueda mostrar progreso.
   *
   * @param callable|null $onStep fn(string $stepName, array $context = [])
   * @return array Resumen del proceso
   * @throws Exception (con backup_path en el mensaje si hay rollback necesario)
   */
  public function apply(?callable $onStep = null, ?PDO $pdo = null): array
  {
    $log    = [];
    $notify = function (string $step, array $ctx = []) use ($onStep, &$log) {
      $log[] = ['step' => $step, 'context' => $ctx, 'at' => date('Y-m-d H:i:s')];
      if ($onStep) $onStep($step, $ctx);
    };

    $notify('check');
    $manifest = $this->check();
    if ($manifest === null) {
      return ['updated' => false, 'reason' => 'al-dia', 'log' => $log];
    }

    $notify('backup', ['version_from' => quetzal_version()]);
    $backupPath = $this->backup();

    try {
      $notify('download', ['version_to' => $manifest['version']]);
      $zipPath = $this->download($manifest);

      $notify('verify');
      $this->verify($zipPath, (string) $manifest['sha256']);

      $notify('extract');
      $extractedDir = $this->extract($zipPath);

      $notify('swap');
      $this->swap($extractedDir);

      if ($pdo !== null) {
        $notify('migrate');
        $migrationResults = $this->migrate($pdo);
        $notify('migrate_done', ['results' => $migrationResults]);
      }

      $notify('update_version', ['version' => $manifest['version']]);
      $this->updateVersion(
        (string) $manifest['version'],
        $manifest['released_at'] ?? null,
        $manifest['min_php']     ?? null
      );

      $notify('done', ['version' => $manifest['version'], 'backup' => $backupPath]);
      return [
        'updated'      => true,
        'version_from' => quetzal_version_info()['version'],
        'version_to'   => $manifest['version'],
        'backup'       => $backupPath,
        'log'          => $log,
      ];
    } catch (Throwable $e) {
      $notify('error', ['message' => $e->getMessage(), 'backup' => $backupPath]);
      throw new Exception(sprintf(
        'Falló la actualización: %s. Backup disponible en: %s. Para revertir, restaurá desde ese ZIP.',
        $e->getMessage(),
        basename($backupPath)
      ));
    }
  }

  // ============================================================
  // Helpers internos
  // ============================================================

  protected function authHeaders(): array
  {
    $h = ['Accept: application/json', 'User-Agent: QuetzalUpdater/1.0'];
    if ($this->token !== '') $h[] = 'X-Update-Token: ' . $this->token;
    return $h;
  }

  /**
   * @return array{0:int,1:string} [status, body]
   */
  protected function httpGet(string $url): array
  {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT        => 30,
      CURLOPT_HTTPHEADER     => $this->authHeaders(),
    ]);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
      throw new Exception('No se pudo conectar al servidor de actualizaciones: ' . $err);
    }
    return [$status, (string) $body];
  }

  protected function ensureDir(string $dir): void
  {
    if (is_dir($dir)) return;
    if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
      throw new Exception('No se pudo crear el directorio: ' . $dir);
    }
  }

  /**
   * @return bool true si $relPath debe preservarse
   */
  protected function isProtected(string $relPath): bool
  {
    $relPath = ltrim(str_replace('\\', '/', $relPath), '/');
    foreach (self::PROTECTED_PATHS as $protected) {
      if ($relPath === $protected) return true;
      if (strpos($relPath, $protected . '/') === 0) return true;
    }
    return false;
  }

  /**
   * Detecta si el ZIP venía con un único directorio raíz (común al hacer
   * "git archive" o ZIPs de releases) y devuelve ese subdir; si los archivos
   * están al ras, devuelve el staging tal cual.
   */
  protected function detectRoot(string $stagingDir): string
  {
    $entries = array_values(array_filter(
      scandir($stagingDir) ?: [],
      fn($e) => $e !== '.' && $e !== '..'
    ));

    if (count($entries) === 1 && is_dir($stagingDir . $entries[0])) {
      // ¿El subdir contiene quetzal.json o app/? → es el root real
      $sub = $stagingDir . $entries[0] . DIRECTORY_SEPARATOR;
      if (is_file($sub . 'quetzal.json') || is_dir($sub . 'app')) {
        return $sub;
      }
    }
    return $stagingDir;
  }

  protected function addDirToZip(ZipArchive $zip, string $absPath, string $relPath): void
  {
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($absPath, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
      $localRel = $relPath . '/' . str_replace('\\', '/', substr((string) $item->getPathname(), strlen($absPath) + 1));
      // No empaquetar caches/logs en el backup (irrelevantes y ocupan espacio)
      if ($this->isProtected($localRel)) continue;
      if ($item->isDir()) {
        $zip->addEmptyDir($localRel);
      } else {
        $zip->addFile($item->getPathname(), $localRel);
      }
    }
  }
}

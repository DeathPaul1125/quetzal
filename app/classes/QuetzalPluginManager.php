<?php

/**
 * Gestor de plugins para Quetzal.
 *
 * Responsable de descubrir, cargar, habilitar, deshabilitar e
 * instalar/desinstalar plugins ubicados en el directorio /plugins.
 *
 * El estado de los plugins (habilitados, orden, versión instalada) se
 * persiste en app/config/plugins.json — esa es la única fuente de verdad.
 *
 * Cada plugin vive en plugins/<Nombre>/ y puede exponer:
 *   - plugin.json                Manifiesto (requerido)
 *   - Init.php                   Hook de arranque (opcional)
 *   - classes/ controllers/ models/ views/ migrations/ assets/ functions/
 */
class QuetzalPluginManager
{
  private static ?QuetzalPluginManager $instance = null;

  /**
   * Registro en memoria cargado desde plugins.json.
   * Estructura: ['plugins' => [ ['name' => ..., 'enabled' => ..., 'order' => ...], ... ]]
   *
   * @var array
   */
  private array $registry = ['plugins' => []];

  /**
   * Cache de manifiestos por nombre de plugin.
   *
   * @var array<string, array>
   */
  private array $manifests = [];

  /**
   * Nombres de plugins cuyo Init.php ya fue cargado.
   *
   * @var array<string>
   */
  private array $loaded = [];


  public static function getInstance(): self
  {
    if (self::$instance === null) {
      self::$instance = new self();
      self::$instance->loadRegistry();
    }

    return self::$instance;
  }

  /**
   * Ruta absoluta al directorio /plugins.
   */
  public static function pluginsDir(): string
  {
    return defined('PLUGINS_PATH') ? PLUGINS_PATH : (ROOT . 'plugins' . DS);
  }

  /**
   * Ruta absoluta al archivo plugins.json.
   */
  public static function registryFile(): string
  {
    return CONFIG . 'plugins.json';
  }

  /**
   * Carga el registro desde disco. Si no existe, crea uno vacío.
   */
  private function loadRegistry(): void
  {
    $file = self::registryFile();

    if (!is_file($file)) {
      $this->registry = ['plugins' => []];
      $this->saveRegistry();
      return;
    }

    $raw = @file_get_contents($file);
    $data = json_decode($raw, true);

    if (!is_array($data) || !isset($data['plugins']) || !is_array($data['plugins'])) {
      $data = ['plugins' => []];
    }

    $this->registry = $data;
  }

  /**
   * Persiste el registro en disco.
   */
  private function saveRegistry(): bool
  {
    $json = json_encode($this->registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return @file_put_contents(self::registryFile(), $json) !== false;
  }

  /**
   * Recorre /plugins y retorna todos los plugins con manifiesto válido.
   *
   * @return array<string, array> Manifiestos indexados por nombre
   */
  public function discover(): array
  {
    $dir = self::pluginsDir();
    $found = [];

    if (!is_dir($dir)) {
      return $found;
    }

    foreach (scandir($dir) as $entry) {
      if ($entry === '.' || $entry === '..') continue;

      $pluginDir = $dir . $entry . DS;
      $manifest  = $pluginDir . 'plugin.json';

      if (!is_dir($pluginDir) || !is_file($manifest)) continue;

      $data = json_decode(@file_get_contents($manifest), true);
      if (!is_array($data) || empty($data['name'])) continue;

      // Normaliza: el nombre en el manifiesto debe coincidir con el folder
      if ($data['name'] !== $entry) continue;

      $data['path'] = $pluginDir;
      $found[$data['name']] = $data;
      $this->manifests[$data['name']] = $data;
    }

    return $found;
  }

  /**
   * Retorna el registro de un plugin (record del plugins.json) o null.
   *
   * Desde v1.6: plugins.json SOLO guarda overrides (típicamente plugins
   * deshabilitados explícitamente o con orden custom). Si un plugin no
   * tiene record, se considera habilitado por default.
   */
  public function getRecord(string $name): ?array
  {
    foreach ($this->registry['plugins'] as $record) {
      if ($record['name'] === $name) return $record;
    }
    return null;
  }

  /**
   * ¿El plugin está habilitado?
   *
   * Regla: enabled por defecto. Solo está deshabilitado si hay un record
   * explícito en plugins.json con enabled=false.
   */
  public function isEnabled(string $name): bool
  {
    $record = $this->getRecord($name);
    if ($record === null) return true;                // no record = enabled
    return !array_key_exists('enabled', $record) || $record['enabled'] !== false;
  }

  /**
   * Retorna todos los plugins descubiertos, anotados con su estado.
   * Campo 'installed' siempre true (presencia en disco = instalado).
   */
  public function listAll(): array
  {
    $discovered = $this->discover();
    $result = [];

    foreach ($discovered as $name => $manifest) {
      $record = $this->getRecord($name);
      $result[] = array_merge($manifest, [
        'installed' => true,
        'enabled'   => $this->isEnabled($name),
        'order'     => $record['order'] ?? 0,
      ]);
    }

    usort($result, fn($a, $b) => $a['order'] <=> $b['order']);
    return $result;
  }

  /**
   * Retorna solo los plugins habilitados, ordenados por 'order' ascendente.
   * Default: TODOS los plugins descubiertos, excepto los marcados como
   * enabled=false en plugins.json.
   *
   * @return array<array>
   */
  public function getEnabled(): array
  {
    $discovered = $this->discover();
    $enabled = [];

    foreach ($discovered as $name => $manifest) {
      if (!$this->isEnabled($name)) continue;

      $record = $this->getRecord($name) ?? [];
      $enabled[] = array_merge($manifest, [
        'order' => $record['order'] ?? 0,
      ]);
    }

    usort($enabled, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return $enabled;
  }

  /**
   * @deprecated Desde v1.6 los plugins se instalan solos al copiarse a /plugins/.
   * Se mantiene por compatibilidad pero es un no-op.
   */
  public function install(string $name): bool
  {
    $discovered = $this->discover();
    if (!isset($discovered[$name])) {
      throw new Exception(sprintf('No se encontró el plugin "%s" en %s', $name, self::pluginsDir()));
    }
    // No-op: el plugin ya está "instalado" con su presencia en disco.
    return true;
  }

  /**
   * Desinstala un plugin. Desde v1.6 solo lo deshabilita y borra su record
   * del registry para dejar el archivo limpio.
   *
   * Si quieres eliminar los archivos, borra manualmente el directorio.
   */
  public function uninstall(string $name): bool
  {
    return $this->removeRecord($name);
  }

  /**
   * Habilita un plugin. Si no tenía record, ya estaba enabled (no-op).
   * Si tenía record con enabled=false, lo elimina del registry.
   */
  public function enable(string $name): bool
  {
    $manifest = $this->discover()[$name] ?? null;
    if ($manifest === null) {
      throw new Exception(sprintf('No se pudo leer el manifiesto del plugin "%s".', $name));
    }

    $this->validateCompatibility($manifest);
    $this->validateDependenciesEnabled($manifest);

    // Habilitar = simplemente quitar el record (o cambiar enabled a true).
    // Preferimos quitar para mantener plugins.json mínimo.
    $record = $this->getRecord($name);
    if ($record === null) return true;  // ya estaba enabled

    // Si tenía otro valor útil (ej. order custom), actualizar a enabled=true
    // en vez de borrar. Si solo tenía enabled=false, borrar el record entero.
    $hasCustomOrder = isset($record['order']) && $record['order'] !== 0;
    if ($hasCustomOrder) {
      foreach ($this->registry['plugins'] as &$r) {
        if ($r['name'] === $name) {
          unset($r['enabled']);
          break;
        }
      }
      unset($r);
      return $this->saveRegistry();
    }

    return $this->removeRecord($name);
  }

  /**
   * Deshabilita un plugin. Agrega un record con enabled=false al registry.
   * Falla si otros plugins habilitados dependen de él.
   */
  public function disable(string $name): bool
  {
    $manifest = $this->discover()[$name] ?? null;
    if ($manifest === null) {
      throw new Exception(sprintf('Plugin "%s" no encontrado.', $name));
    }

    foreach ($this->getEnabled() as $enabled) {
      if ($enabled['name'] === $name) continue;
      $deps = $enabled['requires'] ?? [];
      if (in_array($name, $deps, true)) {
        throw new Exception(sprintf('No se puede deshabilitar "%s": el plugin "%s" depende de él.', $name, $enabled['name']));
      }
    }

    // Si ya hay record, actualiza; si no, crea uno
    $existing = $this->getRecord($name);
    if ($existing !== null) {
      foreach ($this->registry['plugins'] as &$r) {
        if ($r['name'] === $name) {
          $r['enabled'] = false;
          break;
        }
      }
      unset($r);
    } else {
      $this->registry['plugins'][] = [
        'name'    => $name,
        'enabled' => false,
      ];
    }

    return $this->saveRegistry();
  }

  /**
   * Helper interno: elimina un record del registry.
   */
  private function removeRecord(string $name): bool
  {
    $before = count($this->registry['plugins']);
    $this->registry['plugins'] = array_values(array_filter(
      $this->registry['plugins'],
      fn($r) => $r['name'] !== $name
    ));
    if (count($this->registry['plugins']) === $before) {
      return true;  // no había nada que quitar
    }
    return $this->saveRegistry();
  }

  /**
   * Carga todos los plugins habilitados. Para cada uno:
   *   1. Registra paths de classes/controllers/models en el Autoloader
   *   2. Registra paths de views en View (Blade + Quetzal engine)
   *   3. Carga archivos de /functions (si existen)
   *   4. Ejecuta Init.php (si existe)
   *
   * Se llama desde Quetzal::init() temprano en el bootstrap.
   */
  public function load(): void
  {
    foreach ($this->getEnabled() as $plugin) {
      if (in_array($plugin['name'], $this->loaded, true)) continue;

      $base = $plugin['path'];

      // 1. Autoloader: los plugins sobrescriben clases del core (último habilitado gana)
      foreach (['controllers', 'models', 'classes'] as $sub) {
        $dir = $base . $sub;
        if (is_dir($dir)) {
          Autoloader::addPath($dir);
        }
      }

      // 2. Views: Blade resuelve 'views.ctrl.xView' → '<path>/views/ctrl/xView.blade.php'
      // Por eso registramos el directorio BASE del plugin (que contiene views/),
      // no la carpeta views/ misma (eso duplicaría el segmento).
      if (is_dir($base . 'views')) {
        View::addViewPath($base);
      }

      // 3. Archivos de funciones (auto-include de cualquier .php en /functions)
      $functionsDir = $base . 'functions';
      if (is_dir($functionsDir)) {
        foreach (glob($functionsDir . DS . '*.php') as $fnFile) {
          require_once $fnFile;
        }
      }

      // 4. Init.php — bootstrap personalizado del plugin
      $initFile = $base . 'Init.php';
      if (is_file($initFile)) {
        require_once $initFile;
      }

      $this->loaded[] = $plugin['name'];

      QuetzalHookManager::runHook('plugin_loaded', $plugin);
    }
  }

  /**
   * Valida versión mínima de Quetzal y PHP declarados en el manifiesto.
   */
  private function validateCompatibility(array $manifest): void
  {
    if (!empty($manifest['min_quetzal_version']) && defined('QUETZAL_VERSION')) {
      if (version_compare(QUETZAL_VERSION, $manifest['min_quetzal_version'], '<')) {
        throw new Exception(sprintf(
          'El plugin "%s" requiere Quetzal %s+, tienes %s.',
          $manifest['name'], $manifest['min_quetzal_version'], QUETZAL_VERSION
        ));
      }
    }

    if (!empty($manifest['min_php'])) {
      if (version_compare(PHP_VERSION, $manifest['min_php'], '<')) {
        throw new Exception(sprintf(
          'El plugin "%s" requiere PHP %s+, tienes %s.',
          $manifest['name'], $manifest['min_php'], PHP_VERSION
        ));
      }
    }
  }

  /**
   * Ejecuta las migraciones pendientes de un plugin. Cada plugin tiene su
   * propia tabla de tracking para aislar historial.
   *
   * @param PDO $pdo
   * @param string $name
   * @return array Log de resultados (igual formato que Migrator::run())
   */
  public function migrate(PDO $pdo, string $name): array
  {
    $manifest = $this->discover()[$name] ?? null;
    if ($manifest === null) {
      throw new Exception(sprintf('Plugin "%s" no encontrado.', $name));
    }

    $migrationsDir = $manifest['path'] . 'migrations';
    if (!is_dir($migrationsDir)) {
      return [['name' => null, 'status' => 'nothing', 'message' => 'El plugin no tiene migraciones.']];
    }

    $trackingTable = 'plugin_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $name)) . '_migrations';
    $migrator      = new Migrator($pdo, $migrationsDir, $trackingTable);

    return $migrator->run();
  }

  /**
   * Revierte todas las migraciones de un plugin (para uninstall completo).
   */
  public function rollbackMigrations(PDO $pdo, string $name): array
  {
    $manifest = $this->discover()[$name] ?? null;
    if ($manifest === null) return [];

    $migrationsDir = $manifest['path'] . 'migrations';
    if (!is_dir($migrationsDir)) return [];

    $trackingTable = 'plugin_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $name)) . '_migrations';
    $migrator      = new Migrator($pdo, $migrationsDir, $trackingTable);

    return $migrator->fresh();
  }

  /**
   * Valida que todas las dependencias declaradas en 'requires' estén habilitadas.
   */
  private function validateDependenciesEnabled(array $manifest): void
  {
    $deps = $manifest['requires'] ?? [];
    if (empty($deps)) return;

    $enabledNames = array_map(fn($p) => $p['name'], $this->getEnabled());

    foreach ($deps as $dep) {
      if (!in_array($dep, $enabledNames, true)) {
        throw new Exception(sprintf(
          'El plugin "%s" requiere "%s", pero no está habilitado.',
          $manifest['name'], $dep
        ));
      }
    }
  }

  /**
   * Instala un plugin desde un archivo ZIP subido.
   *
   * Flujo:
   *   1. Abre el ZIP y busca un plugin.json (raíz o primer subdirectorio)
   *   2. Valida el manifiesto (name, estructura básica)
   *   3. Verifica que no exista ya una carpeta de plugin con ese nombre
   *   4. Extrae los archivos con sanitización de path (previene zip-slip)
   *   5. Deja el plugin en estado "descubierto" (el usuario lo instala/habilita
   *      después con las acciones normales)
   *
   * @param string $zipPath Ruta absoluta al ZIP (típicamente $_FILES['x']['tmp_name'])
   * @return array{name:string, version:string, extracted:int, path:string}
   * @throws Exception en cualquier validación fallida
   */
  public function installFromZip(string $zipPath): array
  {
    if (!class_exists('ZipArchive')) {
      throw new Exception('La extensión ZipArchive no está disponible en este servidor.');
    }
    if (!is_file($zipPath)) {
      throw new Exception('El archivo ZIP no existe.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
      throw new Exception('No se pudo abrir el ZIP (formato inválido o corrupto).');
    }

    // Buscar plugin.json — aceptamos en la raíz o dentro de un único subdirectorio
    $manifestContent = null;
    $rootPrefix      = '';

    for ($i = 0; $i < $zip->numFiles; $i++) {
      $entry = $zip->getNameIndex($i);
      if (basename($entry) !== 'plugin.json') continue;

      $parts = explode('/', str_replace('\\', '/', trim($entry, '/')));
      if (count($parts) === 1) {
        // plugin.json en la raíz
        $manifestContent = $zip->getFromIndex($i);
        $rootPrefix      = '';
        break;
      }
      if (count($parts) === 2) {
        // plugin.json dentro de UN subdirectorio (ej. MyPlugin/plugin.json)
        $manifestContent = $zip->getFromIndex($i);
        $rootPrefix      = $parts[0] . '/';
        break;
      }
    }

    if ($manifestContent === null) {
      $zip->close();
      throw new Exception('El ZIP no contiene plugin.json en la raíz ni en el primer subdirectorio.');
    }

    $manifest = json_decode($manifestContent, true);
    if (!is_array($manifest) || empty($manifest['name'])) {
      $zip->close();
      throw new Exception('plugin.json es inválido o no declara el campo "name".');
    }

    $pluginName = (string) $manifest['name'];
    if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/', $pluginName)) {
      $zip->close();
      throw new Exception('El nombre del plugin debe ser alfanumérico (guiones y guiones bajos permitidos).');
    }

    $targetDir = self::pluginsDir() . $pluginName . DS;
    if (is_dir($targetDir)) {
      $zip->close();
      throw new Exception(sprintf(
        'Ya existe un plugin con el nombre "%s". Desinstálalo o elimínalo primero.',
        $pluginName
      ));
    }

    // Extracción segura
    $targetReal = realpath(self::pluginsDir());
    $extracted  = 0;
    $created    = [];

    try {
      if (!@mkdir($targetDir, 0775, true)) {
        throw new Exception('No se pudo crear el directorio del plugin. Revisa permisos.');
      }
      $created[] = $targetDir;

      for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        $entry = str_replace('\\', '/', $entry);

        if ($rootPrefix !== '' && strpos($entry, $rootPrefix) !== 0) {
          continue;
        }
        $relative = $rootPrefix !== '' ? substr($entry, strlen($rootPrefix)) : $entry;
        if ($relative === '' || $relative === '.' || $relative === '..') continue;

        // Prevención de zip-slip: ningún segmento puede ser '..' ni contener caracteres peligrosos
        $segments = explode('/', $relative);
        foreach ($segments as $seg) {
          if ($seg === '..' || strpos($seg, "\0") !== false) {
            throw new Exception(sprintf('Ruta no segura en ZIP: %s', $entry));
          }
        }

        $destPath = $targetDir . implode(DS, $segments);

        // Entrada de directorio
        if (substr($relative, -1) === '/') {
          if (!is_dir($destPath)) @mkdir($destPath, 0775, true);
          continue;
        }

        // Archivo
        $destDir = dirname($destPath);
        if (!is_dir($destDir)) @mkdir($destDir, 0775, true);

        // Verificación extra: realpath del destino debe estar dentro de plugins/
        $destCanonicalDir = realpath($destDir);
        if ($destCanonicalDir === false || strpos($destCanonicalDir, $targetReal) !== 0) {
          throw new Exception(sprintf('Ruta fuera del directorio de plugins: %s', $entry));
        }

        $content = $zip->getFromIndex($i);
        if ($content === false) continue;

        if (@file_put_contents($destPath, $content) !== false) {
          $extracted++;
        }
      }

      $zip->close();

      if ($extracted === 0) {
        throw new Exception('No se extrajo ningún archivo del ZIP.');
      }

      if (!is_file($targetDir . 'plugin.json')) {
        throw new Exception('La extracción no produjo un plugin.json válido.');
      }

      return [
        'name'      => $pluginName,
        'version'   => (string) ($manifest['version'] ?? '0.0.0'),
        'extracted' => $extracted,
        'path'      => $targetDir,
      ];

    } catch (Exception $e) {
      // Rollback: borrar carpeta parcialmente creada
      if (isset($zip) && $zip instanceof ZipArchive) {
        @$zip->close();
      }
      $this->removeDirectory($targetDir);
      throw $e;
    }
  }

  /**
   * Borra recursivamente un directorio. Usado como rollback si la extracción
   * de un ZIP falla a mitad.
   */
  private function removeDirectory(string $dir): void
  {
    if (!is_dir($dir)) return;
    $items = @scandir($dir);
    if ($items === false) return;

    foreach ($items as $item) {
      if ($item === '.' || $item === '..') continue;
      $path = $dir . DS . $item;
      if (is_dir($path) && !is_link($path)) {
        $this->removeDirectory($path);
      } else {
        @chmod($path, 0666);
        @unlink($path);
      }
    }
    @rmdir($dir);
  }

  /**
   * Limpia el cache compilado de Blade (plantillas compiladas a PHP).
   * Devuelve una entrada de log estandarizada.
   */
  public function clearBladeCache(): array
  {
    $cacheDir = defined('BLADE_CACHE') ? BLADE_CACHE : (ROOT . 'app' . DS . 'cache' . DS . 'blade');

    if (!is_dir($cacheDir)) {
      return ['step' => 'cache', 'status' => 'skipped', 'message' => 'El directorio de cache no existe.'];
    }

    $deleted = 0;
    $errors  = 0;
    foreach (glob($cacheDir . DS . '*.php') ?: [] as $file) {
      if (@unlink($file)) $deleted++;
      else $errors++;
    }

    return [
      'step'    => 'cache',
      'status'  => $errors === 0 ? 'ok' : 'partial',
      'message' => sprintf('Cache Blade: %d archivo(s) eliminados%s.', $deleted, $errors ? ", $errors con error" : ''),
      'deleted' => $deleted,
      'errors'  => $errors,
    ];
  }

  /**
   * Reconstruye todos los plugins habilitados: limpia cache, valida
   * manifiestos y dependencias, y ejecuta migraciones pendientes.
   *
   * Idempotente: correrlo varias veces es seguro. Solo procesa lo que
   * aún no esté aplicado.
   *
   * @param PDO $pdo Conexión a la base de datos del framework.
   * @return array{steps:array, summary:array} Log estructurado
   */
  public function rebuild(PDO $pdo): array
  {
    $steps   = [];
    $counters = [
      'cache_cleared'  => 0,
      'validated'      => 0,
      'validation_err' => 0,
      'migrated'       => 0,
      'migration_err'  => 0,
      'skipped'        => 0,
    ];

    // 1. Limpiar cache Blade
    $cacheStep = $this->clearBladeCache();
    $counters['cache_cleared'] = $cacheStep['deleted'] ?? 0;
    $steps[] = $cacheStep;

    // 2. Para cada plugin habilitado: validar + migrar
    foreach ($this->getEnabled() as $plugin) {
      $name = $plugin['name'];

      // 2a. Validación de compatibilidad
      try {
        $this->validateCompatibility($plugin);
        $this->validateDependenciesEnabled($plugin);
        $counters['validated']++;
        $steps[] = [
          'step'    => 'validate',
          'plugin'  => $name,
          'status'  => 'ok',
          'message' => 'Manifiesto válido. Dependencias habilitadas.',
        ];
      } catch (Exception $e) {
        $counters['validation_err']++;
        $steps[] = [
          'step'    => 'validate',
          'plugin'  => $name,
          'status'  => 'error',
          'message' => $e->getMessage(),
        ];
        // No continuar con migraciones si la validación falló
        continue;
      }

      // 2b. Migraciones del plugin
      $migDir = $plugin['path'] . 'migrations';
      if (!is_dir($migDir) || count(glob($migDir . DS . '*.php') ?: []) === 0) {
        $counters['skipped']++;
        $steps[] = [
          'step'    => 'migrate',
          'plugin'  => $name,
          'status'  => 'skipped',
          'message' => 'Sin migraciones en disco.',
        ];
        continue;
      }

      try {
        $migLog = $this->migrate($pdo, $name);
        $okCount   = count(array_filter($migLog, fn($r) => $r['status'] === 'ok'));
        $errCount  = count(array_filter($migLog, fn($r) => $r['status'] === 'error'));

        if ($errCount > 0) {
          $counters['migration_err']++;
          $firstErr = reset(array_filter($migLog, fn($r) => $r['status'] === 'error'));
          $steps[] = [
            'step'    => 'migrate',
            'plugin'  => $name,
            'status'  => 'error',
            'message' => sprintf('%d migración(es) fallaron. Primer error: %s', $errCount, $firstErr['message'] ?? '—'),
          ];
        } elseif ($okCount > 0) {
          $counters['migrated'] += $okCount;
          $steps[] = [
            'step'    => 'migrate',
            'plugin'  => $name,
            'status'  => 'ok',
            'message' => sprintf('%d migración(es) ejecutadas.', $okCount),
          ];
        } else {
          $counters['skipped']++;
          $steps[] = [
            'step'    => 'migrate',
            'plugin'  => $name,
            'status'  => 'skipped',
            'message' => 'Sin migraciones pendientes.',
          ];
        }
      } catch (Exception $e) {
        $counters['migration_err']++;
        $steps[] = [
          'step'    => 'migrate',
          'plugin'  => $name,
          'status'  => 'error',
          'message' => $e->getMessage(),
        ];
      }
    }

    // Hook para que código externo extienda el rebuild (ej. copiar assets públicos)
    QuetzalHookManager::runHook('plugins_rebuilt', $steps, $counters);

    return [
      'steps'   => $steps,
      'summary' => $counters,
    ];
  }
}

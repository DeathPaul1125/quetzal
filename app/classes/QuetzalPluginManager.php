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
   */
  public function getRecord(string $name): ?array
  {
    foreach ($this->registry['plugins'] as $record) {
      if ($record['name'] === $name) return $record;
    }
    return null;
  }

  /**
   * Retorna todos los plugins descubiertos, anotados con su estado (enabled, order, installed).
   */
  public function listAll(): array
  {
    $discovered = $this->discover();
    $result = [];

    foreach ($discovered as $name => $manifest) {
      $record = $this->getRecord($name);
      $result[] = array_merge($manifest, [
        'installed' => $record !== null,
        'enabled'   => $record['enabled'] ?? false,
        'order'     => $record['order'] ?? 0,
      ]);
    }

    usort($result, fn($a, $b) => $a['order'] <=> $b['order']);
    return $result;
  }

  /**
   * Retorna solo los plugins habilitados, ordenados por 'order' ascendente.
   *
   * @return array<array>
   */
  public function getEnabled(): array
  {
    $discovered = $this->discover();
    $enabled = [];

    foreach ($this->registry['plugins'] as $record) {
      if (empty($record['enabled'])) continue;
      if (!isset($discovered[$record['name']])) continue; // Manifiesto faltante

      $enabled[] = array_merge($discovered[$record['name']], $record);
    }

    usort($enabled, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
    return $enabled;
  }

  /**
   * Agrega un plugin al registro (sin habilitar). Valida manifiesto y deps.
   */
  public function install(string $name): bool
  {
    $discovered = $this->discover();

    if (!isset($discovered[$name])) {
      throw new Exception(sprintf('No se encontró el plugin "%s" en %s', $name, self::pluginsDir()));
    }

    if ($this->getRecord($name) !== null) {
      throw new Exception(sprintf('El plugin "%s" ya está instalado.', $name));
    }

    $manifest = $discovered[$name];
    $this->validateCompatibility($manifest);

    $this->registry['plugins'][] = [
      'name'         => $name,
      'version'      => $manifest['version'] ?? '0.0.0',
      'enabled'      => false,
      'order'        => count($this->registry['plugins']) * 10,
      'installed_at' => date('Y-m-d H:i:s'),
    ];

    return $this->saveRegistry();
  }

  /**
   * Desinstala un plugin (remueve el record, no borra archivos).
   */
  public function uninstall(string $name): bool
  {
    $filtered = array_values(array_filter(
      $this->registry['plugins'],
      fn($r) => $r['name'] !== $name
    ));

    if (count($filtered) === count($this->registry['plugins'])) {
      throw new Exception(sprintf('El plugin "%s" no está instalado.', $name));
    }

    $this->registry['plugins'] = $filtered;
    return $this->saveRegistry();
  }

  /**
   * Habilita un plugin (debe estar instalado y sus dependencias habilitadas).
   */
  public function enable(string $name): bool
  {
    $record = $this->getRecord($name);
    if ($record === null) {
      throw new Exception(sprintf('El plugin "%s" no está instalado. Ejecuta install primero.', $name));
    }

    $manifest = $this->discover()[$name] ?? null;
    if ($manifest === null) {
      throw new Exception(sprintf('No se pudo leer el manifiesto del plugin "%s".', $name));
    }

    $this->validateDependenciesEnabled($manifest);

    foreach ($this->registry['plugins'] as &$r) {
      if ($r['name'] === $name) {
        $r['enabled'] = true;
        break;
      }
    }
    unset($r);

    return $this->saveRegistry();
  }

  /**
   * Deshabilita un plugin. Falla si otros plugins habilitados dependen de él.
   */
  public function disable(string $name): bool
  {
    foreach ($this->getEnabled() as $enabled) {
      if ($enabled['name'] === $name) continue;
      $deps = $enabled['requires'] ?? [];
      if (in_array($name, $deps, true)) {
        throw new Exception(sprintf('No se puede deshabilitar "%s": el plugin "%s" depende de él.', $name, $enabled['name']));
      }
    }

    foreach ($this->registry['plugins'] as &$r) {
      if ($r['name'] === $name) {
        $r['enabled'] = false;
        break;
      }
    }
    unset($r);

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

      // 2. Views: registrar tanto para Blade como para motor quetzal
      $viewsDir = $base . 'views';
      if (is_dir($viewsDir)) {
        View::addViewPath($viewsDir);
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
}

<?php

/**
 * actualizacionesController — UI de actualizaciones del core de Quetzal.
 *
 * Endpoints:
 *   GET  /actualizaciones                → vista principal con versión actual
 *   POST /actualizaciones/post_check     → consulta el servidor (AJAX o redirect)
 *   POST /actualizaciones/post_apply     → ejecuta el flujo completo de update
 *   POST /actualizaciones/post_settings  → guarda endpoint + token
 *   POST /actualizaciones/post_rollback  → restaura un backup previo
 */
class actualizacionesController extends Controller implements ControllerInterface
{
  function __construct()
  {
    if (!Auth::validate()) {
      Flasher::new('Debes iniciar sesión primero.', 'danger');
      Redirect::to('login');
    }
    parent::__construct();
  }

  function index()
  {
    $this->guard();

    $endpoint  = (string) (get_option('update_endpoint') ?: 'https://system.facturagt.com/quetzal-updates');
    $tokenSet  = !empty(get_option('update_token'));
    $info      = quetzal_version_info();
    $available = $this->safeCheck($endpoint, $tokenSet);
    $backups   = $this->listBackups();

    $this->setTitle('Actualizaciones del sistema');
    $this->addToData('current',   $info);
    $this->addToData('endpoint',  $endpoint);
    $this->addToData('tokenSet',  $tokenSet);
    $this->addToData('available', $available['manifest']);
    $this->addToData('checkError', $available['error']);
    $this->addToData('backups',   $backups);
    $this->setView('index');
    $this->render();
  }

  function post_check()
  {
    $this->guard();
    if (!Csrf::validate($_POST['csrf'] ?? $_POST['_t'] ?? '')) {
      Flasher::error(get_quetzal_message(0));
      Redirect::to('actualizaciones');
      exit;
    }

    Redirect::to('actualizaciones');
  }

  function post_settings()
  {
    $this->guard();
    if (!Csrf::validate($_POST['csrf'] ?? $_POST['_t'] ?? '')) {
      Flasher::error(get_quetzal_message(0));
      Redirect::to('actualizaciones');
      exit;
    }

    $endpoint = trim(sanitize_input($_POST['endpoint'] ?? ''));
    $token    = trim((string) ($_POST['token'] ?? ''));

    if ($endpoint !== '' && !filter_var($endpoint, FILTER_VALIDATE_URL)) {
      Flasher::error('La URL del servidor es inválida.');
      Redirect::to('actualizaciones');
      exit;
    }

    if ($endpoint !== '') save_option('update_endpoint', rtrim($endpoint, '/'));
    if ($token !== '')    save_option('update_token', $token);

    Flasher::new('Configuración de actualizaciones guardada.', 'success');
    Redirect::to('actualizaciones');
  }

  function post_apply()
  {
    $this->guard();
    if (!Csrf::validate($_POST['csrf'] ?? $_POST['_t'] ?? '')) {
      Flasher::error(get_quetzal_message(0));
      Redirect::to('actualizaciones');
      exit;
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    try {
      $updater = new QuetzalUpdater();
      $pdo     = $this->getPdo();
      $result  = $updater->apply(null, $pdo);

      if (!empty($result['updated'])) {
        Flasher::new(sprintf(
          'Sistema actualizado de %s a %s. Backup guardado en %s.',
          $result['version_from'] ?? '',
          $result['version_to']   ?? '',
          basename((string) ($result['backup'] ?? ''))
        ), 'success');
      } else {
        Flasher::new('No hay actualizaciones disponibles. Estás en la última versión.', 'info');
      }
    } catch (Throwable $e) {
      Flasher::error($e->getMessage());
    }

    Redirect::to('actualizaciones');
  }

  function post_rollback()
  {
    $this->guard();
    if (!Csrf::validate($_POST['csrf'] ?? $_POST['_t'] ?? '')) {
      Flasher::error(get_quetzal_message(0));
      Redirect::to('actualizaciones');
      exit;
    }

    $file = basename((string) ($_POST['backup'] ?? ''));
    if (!preg_match('/^core-[a-z0-9._-]+-[0-9]{8}-[0-9]{6}\.zip$/i', $file)) {
      Flasher::error('Nombre de backup inválido.');
      Redirect::to('actualizaciones');
      exit;
    }

    $path = ROOT . 'backups' . DS . $file;
    if (!is_file($path)) {
      Flasher::error('No se encontró el backup seleccionado.');
      Redirect::to('actualizaciones');
      exit;
    }

    @set_time_limit(0);

    try {
      (new QuetzalUpdater())->rollback($path);
      Flasher::new('Sistema restaurado desde el backup ' . $file . '.', 'success');
    } catch (Throwable $e) {
      Flasher::error('Falló el rollback: ' . $e->getMessage());
    }

    Redirect::to('actualizaciones');
  }

  // ============================================================
  // Helpers privados
  // ============================================================

  private function guard(): void
  {
    if (!user_can('admin-access')) {
      Flasher::error('No tenés permiso para gestionar actualizaciones.');
      Redirect::to('admin');
      exit;
    }
  }

  /**
   * Hace check() sin tirar excepciones: si falla, devuelve el error como
   * string para mostrarlo en la vista.
   *
   * @return array{manifest:?array, error:?string}
   */
  private function safeCheck(string $endpoint, bool $tokenSet): array
  {
    if (!$tokenSet) {
      return ['manifest' => null, 'error' => null];
    }

    try {
      $manifest = (new QuetzalUpdater($endpoint))->check();
      return ['manifest' => $manifest, 'error' => null];
    } catch (Throwable $e) {
      return ['manifest' => null, 'error' => $e->getMessage()];
    }
  }

  private function listBackups(): array
  {
    $dir = ROOT . 'backups' . DS;
    if (!is_dir($dir)) return [];
    $files = glob($dir . 'core-*.zip') ?: [];
    rsort($files);
    return array_map(function ($f) {
      return [
        'name' => basename($f),
        'size' => filesize($f) ?: 0,
        'date' => date('Y-m-d H:i', (int) filemtime($f)),
      ];
    }, array_slice($files, 0, 10));
  }

  private function getPdo(): PDO
  {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    return new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_INIT_COMMAND  => "SET NAMES utf8mb4",
    ]);
  }
}

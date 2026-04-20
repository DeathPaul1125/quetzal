<?php

use Cocur\Slugify\Slugify;

/**
 * Plantilla general de controladores
 * @version 1.0.5
 *
 * Controlador de admin
 */
class adminController extends Controller implements ControllerInterface 
{
  function __construct()
  {
    // Validación de sesión de usuario, descomentar si requerida
    if (!Auth::validate()) {
      Flasher::new('Debes iniciar sesión primero.', 'danger');
      Redirect::to('login');
    }

    // Ejecutar la funcionalidad del Controller padre
    parent::__construct();
  }
  
  function index()
  {
    register_scripts([JS . 'admin/demo.js'], 'Chartjs gráficas para administración');

    $this->setTitle('Administración');
    $buttons =
    [
      [
        'url'   => 'admin',
        'class' => 'btn-danger text-white',
        'id'    => '',
        'icon'  => 'fas fa-download',
        'text'  => 'Descargar'
      ],
      [
        'url'   => 'admin',
        'class' => 'btn-success text-white',
        'id'    => '',
        'icon'  => 'fas fa-file-pdf',
        'text'  => 'Exportar'
      ]
    ];
    $this->addToData('buttons', $buttons);
    $this->render();
  }

  function perfil()
  {
    $user = get_user();
    $permissions = [];

    if (!empty($user['role'])) {
      try {
        $roleMgr     = new QuetzalRoleManager($user['role']);
        $permissions = $roleMgr->getPermissions();
      } catch (Exception $e) {
        // Role no existe o inválido — no bloqueamos la vista
      }
    }

    $this->setTitle('Perfil de usuario');
    $this->addToData('user'       , $user);
    $this->addToData('permissions', $permissions);
    $this->setView('perfil');
    $this->render();
  }

  /**
   * Actualiza el perfil del usuario loggeado.
   */
  function post_perfil()
  {
    try {
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $currentUser = get_user();
      if (empty($currentUser['id'])) {
        throw new Exception('No se pudo identificar al usuario en sesión.');
      }

      $username = sanitize_input($_POST['username'] ?? '');
      $email    = sanitize_input($_POST['email']    ?? '');
      $pwNew    = (string)($_POST['password']         ?? '');
      $pwConf   = (string)($_POST['password_confirm'] ?? '');

      if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
        throw new Exception('El nombre de usuario debe tener entre 3 y 50 caracteres alfanuméricos.');
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El email no es válido.');
      }

      // Unicidad (excluyendo al propio usuario)
      $sql = 'SELECT id FROM quetzal_users WHERE (username = :u OR email = :e) AND id != :id LIMIT 1';
      if (Model::query($sql, ['u' => $username, 'e' => $email, 'id' => $currentUser['id']])) {
        throw new Exception('Otro usuario ya usa ese nombre de usuario o email.');
      }

      $fields = [
        'username' => $username,
        'email'    => $email,
      ];

      if ($pwNew !== '' || $pwConf !== '') {
        if ($pwNew !== $pwConf) {
          throw new Exception('La contraseña y su confirmación no coinciden.');
        }
        if (strlen($pwNew) < 6) {
          throw new Exception('La contraseña debe tener al menos 6 caracteres.');
        }
        $fields['password'] = password_hash($pwNew . AUTH_SALT, PASSWORD_BCRYPT);
      }

      if (!Model::update('quetzal_users', ['id' => $currentUser['id']], $fields)) {
        throw new Exception('No se pudo actualizar el perfil.');
      }

      // Refrescar info en sesión
      $updated = Model::list('quetzal_users', ['id' => $currentUser['id']], 1);
      if ($updated) {
        $GLOBALS['Quetzal_User'] = $updated;
      }

      Flasher::success('Perfil actualizado con éxito.');
      Redirect::to('admin/perfil');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Vista de personalización de apariencia (colores del tema).
   * Requiere permiso admin-access.
   */
  function apariencia()
  {
    if (!user_can('admin-access')) {
      Flasher::error('No tienes permiso para acceder a esta sección.');
      Redirect::to('admin');
    }

    $this->setTitle('Apariencia del sistema');
    $this->addToData('colors', theme_colors());
    $this->addToData('presets', [
      ['label' => 'Guatemala (default)', 'primary' => '#4997D0', 'dark' => '#2D6CA3'],
      ['label' => 'Verde quetzal',       'primary' => '#059669', 'dark' => '#065f46'],
      ['label' => 'Rojo quetzal',        'primary' => '#B91C1C', 'dark' => '#7F1D1D'],
      ['label' => 'Ámbar',               'primary' => '#f59e0b', 'dark' => '#b45309'],
      ['label' => 'Azul',                'primary' => '#3b82f6', 'dark' => '#1d4ed8'],
      ['label' => 'Violeta',             'primary' => '#8b5cf6', 'dark' => '#6d28d9'],
      ['label' => 'Rosa',                'primary' => '#ec4899', 'dark' => '#be185d'],
      ['label' => 'Slate',               'primary' => '#475569', 'dark' => '#1e293b'],
    ]);
    $this->setView('apariencia');
    $this->render();
  }

  /**
   * Guarda los colores elegidos en la tabla options.
   */
  function post_apariencia()
  {
    try {
      if (!user_can('admin-access')) {
        throw new Exception('No tienes permiso para modificar la apariencia.');
      }
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $keys = ['primary', 'primary_dark', 'sidebar_bg', 'sidebar_fg'];
      foreach ($keys as $key) {
        $val = trim((string)($_POST[$key] ?? ''));
        if ($val === '') continue;
        if (!preg_match('/^#[0-9a-f]{3,8}$/i', $val)) {
          throw new Exception(sprintf('El color "%s" no es un hex válido.', $key));
        }
        save_option('theme_' . $key, $val);
      }

      Flasher::success('Apariencia actualizada.');
      Redirect::to('admin/apariencia');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  ////////////////////////////////////////////////////
  //////// PLUGINS
  ////////////////////////////////////////////////////

  /**
   * Panel de gestión de plugins.
   */
  function plugins()
  {
    $this->guardAdminAccess();

    $mgr      = QuetzalPluginManager::getInstance();
    $all      = $mgr->listAll(); // todos los descubiertos con estado anotado

    // Calcular resumen
    $summary = [
      'total'      => count($all),
      'installed'  => count(array_filter($all, fn($p) => !empty($p['installed']))),
      'enabled'    => count(array_filter($all, fn($p) => !empty($p['enabled']))),
    ];

    $this->setTitle('Plugins');
    $this->addToData('plugins' , $all);
    $this->addToData('summary' , $summary);
    $this->setView('plugins');
    $this->render();
  }

  /**
   * Helper interno: ejecuta la acción sobre un plugin con validación estándar.
   *
   * @param callable(QuetzalPluginManager, string): void $action
   * @param string $successMsg
   */
  private function handlePluginAction(callable $action, string $successMsg): void
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $name = sanitize_input($_POST['name'] ?? '');
      if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/', $name)) {
        throw new Exception('Nombre de plugin inválido.');
      }

      $mgr = QuetzalPluginManager::getInstance();
      $action($mgr, $name);

      Flasher::success(sprintf($successMsg, $name));
      Redirect::to('admin/plugins');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function post_plugin_install()
  {
    $this->handlePluginAction(
      fn($mgr, $name) => $mgr->install($name),
      'Plugin <b>%s</b> instalado. Habilítalo para que empiece a cargar.'
    );
  }

  function post_plugin_uninstall()
  {
    $this->handlePluginAction(
      function ($mgr, $name) {
        // Deshabilitar primero si está habilitado (para liberar deps)
        $record = $mgr->getRecord($name);
        if (!empty($record['enabled'])) {
          $mgr->disable($name);
        }
        $mgr->uninstall($name);
      },
      'Plugin <b>%s</b> desinstalado. Los archivos siguen en disco.'
    );
  }

  function post_plugin_enable()
  {
    $this->handlePluginAction(
      fn($mgr, $name) => $mgr->enable($name),
      'Plugin <b>%s</b> habilitado.'
    );
  }

  function post_plugin_disable()
  {
    $this->handlePluginAction(
      fn($mgr, $name) => $mgr->disable($name),
      'Plugin <b>%s</b> deshabilitado.'
    );
  }

  /**
   * Guía para desarrolladores: cómo crear plugins y extender el sistema.
   * Lista todos los hooks disponibles y ejemplos prácticos.
   */
  function plugins_guia()
  {
    $this->guardAdminAccess();
    $this->setTitle('Guía de plugins');
    $this->setView('plugins_guia');
    $this->render();
  }

  /**
   * Sube y extrae un plugin desde un archivo ZIP.
   * El plugin queda "descubierto" (disponible); el usuario lo instala y
   * habilita desde la UI después.
   */
  function post_plugin_upload()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      if (empty($_FILES['plugin_zip']['name'])) {
        throw new Exception('Selecciona un archivo ZIP.');
      }

      $file = $_FILES['plugin_zip'];

      if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
          UPLOAD_ERR_INI_SIZE   => 'El archivo excede el límite del servidor (upload_max_filesize).',
          UPLOAD_ERR_FORM_SIZE  => 'El archivo excede el límite del formulario.',
          UPLOAD_ERR_PARTIAL    => 'El archivo se subió incompleto.',
          UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
          UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
          UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo al disco.',
          UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.',
        ];
        throw new Exception($uploadErrors[$file['error']] ?? 'Error desconocido al subir el archivo.');
      }

      // Validar extensión
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
      if ($ext !== 'zip') {
        throw new Exception('El archivo debe tener extensión .zip');
      }

      // Límite 20 MB
      $maxSize = 20 * 1024 * 1024;
      if ($file['size'] > $maxSize) {
        throw new Exception(sprintf('El ZIP no puede superar %s MB.', $maxSize / 1024 / 1024));
      }

      // Verificar que es un ZIP real (magic bytes)
      $fh = @fopen($file['tmp_name'], 'rb');
      $magic = $fh ? fread($fh, 4) : '';
      if ($fh) fclose($fh);
      if (substr($magic, 0, 2) !== 'PK') {
        throw new Exception('El archivo no parece ser un ZIP válido.');
      }

      $info = QuetzalPluginManager::getInstance()->installFromZip($file['tmp_name']);

      Flasher::success(sprintf(
        'Plugin <b>%s</b> v%s subido (%d archivo(s) extraídos). Ahora puedes instalarlo y habilitarlo.',
        $info['name'], $info['version'], $info['extracted']
      ));
      Redirect::to('admin/plugins');

    } catch (Exception $e) {
      Flasher::error('Error al subir el plugin: ' . $e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Reconstruye todos los plugins habilitados: limpia cache Blade,
   * valida manifiestos/dependencias y ejecuta migraciones pendientes.
   */
  function post_plugin_rebuild()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $pdo    = $this->getPdo();
      $result = QuetzalPluginManager::getInstance()->rebuild($pdo);
      $s      = $result['summary'];

      // Resumen legible en el flash
      $parts = [];
      if ($s['cache_cleared']  > 0) $parts[] = sprintf('%d archivo(s) de cache limpiados', $s['cache_cleared']);
      if ($s['validated']      > 0) $parts[] = sprintf('%d plugin(s) validado(s)', $s['validated']);
      if ($s['migrated']       > 0) $parts[] = sprintf('%d migración(es) ejecutada(s)', $s['migrated']);
      if ($s['skipped']        > 0) $parts[] = sprintf('%d tarea(s) sin cambios', $s['skipped']);

      $hasErrors = ($s['validation_err'] + $s['migration_err']) > 0;

      // Guardamos el log detallado en sesión para mostrarlo en la vista
      $_SESSION['plugin_rebuild_log'] = $result;

      if ($hasErrors) {
        Flasher::error(sprintf(
          'Reconstrucción con errores: %d validación(es) fallaron, %d migración(es) fallaron. %s',
          $s['validation_err'], $s['migration_err'],
          $parts ? '(' . implode(', ', $parts) . ')' : ''
        ));
      } elseif (empty($parts)) {
        Flasher::new('No había nada que reconstruir — todo ya estaba al día.', 'info');
      } else {
        Flasher::success('Plugins reconstruidos: ' . implode(', ', $parts) . '.');
      }

      Redirect::to('admin/plugins');

    } catch (Exception $e) {
      Flasher::error('Error al reconstruir: ' . $e->getMessage());
      Redirect::back();
    }
  }

  ////////////////////////////////////////////////////
  //////// MIGRACIONES
  ////////////////////////////////////////////////////

  /**
   * Retorna la conexión PDO reusando la config del framework.
   */
  private function getPdo(): PDO
  {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    return new PDO($dsn, DB_USER, DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
  }

  /**
   * Construye el tracking table name que usa QuetzalPluginManager para cada plugin.
   */
  private function pluginTrackingTable(string $pluginName): string
  {
    return 'plugin_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $pluginName)) . '_migrations';
  }

  /**
   * Vista de migraciones: muestra estado del core + cada plugin habilitado.
   */
  function migraciones()
  {
    $this->guardAdminAccess();

    try {
      $pdo = $this->getPdo();

      // Core
      $coreMigrator = new Migrator($pdo, ROOT . 'app' . DS . 'migrations');
      $coreStatus   = $coreMigrator->status();
      $coreSummary  = $coreMigrator->summary();

      // Plugins habilitados
      $plugins = [];
      foreach (QuetzalPluginManager::getInstance()->getEnabled() as $plugin) {
        $migDir = $plugin['path'] . 'migrations';
        if (!is_dir($migDir)) continue;

        $trackingTable = $this->pluginTrackingTable($plugin['name']);
        $m             = new Migrator($pdo, $migDir, $trackingTable);
        $plugins[]     = [
          'name'    => $plugin['name'],
          'version' => $plugin['version'] ?? '',
          'status'  => $m->status(),
          'summary' => $m->summary(),
        ];
      }

      $this->setTitle('Migraciones');
      $this->addToData('coreStatus' , $coreStatus);
      $this->addToData('coreSummary', $coreSummary);
      $this->addToData('plugins'    , $plugins);
      $this->setView('migraciones');
      $this->render();

    } catch (Exception $e) {
      Flasher::error('Error al cargar migraciones: ' . $e->getMessage());
      Redirect::to('admin');
    }
  }

  /**
   * Ejecuta las migraciones pendientes del target ('core' o un plugin).
   */
  function post_migrate()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $target = sanitize_input($_POST['target'] ?? 'core');
      $pdo    = $this->getPdo();
      $log    = [];

      if ($target === 'core') {
        $migrator = new Migrator($pdo, ROOT . 'app' . DS . 'migrations');
        $log      = $migrator->run();
      } else {
        $log = QuetzalPluginManager::getInstance()->migrate($pdo, $target);
      }

      $ok     = array_filter($log, fn($r) => $r['status'] === 'ok');
      $errors = array_filter($log, fn($r) => $r['status'] === 'error');

      if (count($errors)) {
        Flasher::error(sprintf('%d migración(es) fallaron. Primer error: %s', count($errors), reset($errors)['message']));
      } elseif (count($ok)) {
        Flasher::success(sprintf('%d migración(es) ejecutadas en <b>%s</b>.', count($ok), $target));
      } else {
        Flasher::new(sprintf('No hay migraciones pendientes en <b>%s</b>.', $target), 'info');
      }

      Redirect::to('admin/migraciones');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Rollback del último batch del target.
   */
  function post_rollback()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $target = sanitize_input($_POST['target'] ?? 'core');
      $steps  = max(1, (int) ($_POST['steps'] ?? 1));
      $pdo    = $this->getPdo();

      if ($target === 'core') {
        $migrator = new Migrator($pdo, ROOT . 'app' . DS . 'migrations');
      } else {
        $manifest = QuetzalPluginManager::getInstance()->discover()[$target] ?? null;
        if (!$manifest) throw new Exception('Plugin no encontrado: ' . $target);
        $migrator = new Migrator($pdo, $manifest['path'] . 'migrations', $this->pluginTrackingTable($target));
      }

      $log     = $migrator->rollback($steps);
      $ok      = array_filter($log, fn($r) => $r['status'] === 'ok');
      $errors  = array_filter($log, fn($r) => $r['status'] === 'error');

      if (count($errors)) {
        Flasher::error(sprintf('Rollback parcial: %d fallaron. Primer error: %s', count($errors), reset($errors)['message']));
      } elseif (count($ok)) {
        Flasher::success(sprintf('%d migración(es) revertidas en <b>%s</b>.', count($ok), $target));
      } else {
        Flasher::new('Nada que revertir.', 'info');
      }

      Redirect::to('admin/migraciones');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  ////////////////////////////////////////////////////
  //////// ROLES Y PERMISOS
  ////////////////////////////////////////////////////

  /**
   * Guarda: usuario actual debe tener admin-access.
   */
  private function guardAdminAccess(string $msg = 'No tienes permiso para gestionar esta sección.'): void
  {
    if (!user_can('admin-access')) {
      Flasher::error($msg);
      Redirect::to('admin');
      exit;
    }
  }

  /**
   * Retorna todos los permisos + cuántos roles los usan (para la UI de permisos)
   * y todos los roles + cuántos permisos tienen (para la UI de roles).
   *
   * @return array [$roles, $permissions]
   */
  private function fetchRolesAndPermissions(): array
  {
    $roles = Model::query('
      SELECT r.*, COUNT(rp.id_permiso) AS permiso_count,
        (SELECT COUNT(*) FROM quetzal_users u WHERE u.role = r.slug) AS user_count
      FROM quetzal_roles r
      LEFT JOIN quetzal_roles_permisos rp ON rp.id_role = r.id
      GROUP BY r.id
      ORDER BY r.id ASC
    ') ?: [];

    $permissions = Model::query('
      SELECT p.*, COUNT(rp.id_role) AS role_count
      FROM quetzal_permisos p
      LEFT JOIN quetzal_roles_permisos rp ON rp.id_permiso = p.id
      GROUP BY p.id
      ORDER BY p.nombre ASC
    ') ?: [];

    return [$roles, $permissions];
  }

  /**
   * Listado de roles con búsqueda.
   */
  function roles()
  {
    $this->guardAdminAccess();

    $q = sanitize_input((string)($_GET['q'] ?? ''));

    $whereSql = '';
    $params   = [];
    if ($q !== '') {
      $whereSql = 'WHERE r.nombre LIKE :q OR r.slug LIKE :q';
      $params['q'] = '%' . $q . '%';
    }

    $sql = sprintf('
      SELECT r.*, COUNT(rp.id_permiso) AS permiso_count,
        (SELECT COUNT(*) FROM quetzal_users u WHERE u.role = r.slug) AS user_count
      FROM quetzal_roles r
      LEFT JOIN quetzal_roles_permisos rp ON rp.id_role = r.id
      %s
      GROUP BY r.id
      ORDER BY r.id ASC
    ', $whereSql);

    $roles = Model::query($sql, $params) ?: [];

    $this->setTitle('Roles');
    $this->addToData('roles'  , $roles);
    $this->addToData('filters', ['q' => $q]);
    $this->setView('roles/index');
    $this->render();
  }

  /**
   * Formulario para crear un nuevo role.
   */
  function crear_role()
  {
    $this->guardAdminAccess();
    $this->setTitle('Nuevo role');
    $this->setView('roles/crear');
    $this->render();
  }

  function post_role()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $nombre = sanitize_input($_POST['nombre'] ?? '');
      $slug   = sanitize_input($_POST['slug']   ?? '');

      if (strlen($nombre) < 3) throw new Exception('El nombre debe tener al menos 3 caracteres.');
      if (!preg_match('/^[a-z0-9-]{3,50}$/', $slug)) throw new Exception('El slug debe ser minúsculas, números y guiones (3-50 caracteres).');

      $mgr = new QuetzalRoleManager();
      $mgr->addRole($nombre, $slug);

      $created = Model::list('quetzal_roles', ['slug' => $slug], 1);
      Flasher::success(sprintf('Role <b>%s</b> creado. Ahora puedes asignarle permisos.', $nombre));
      Redirect::to('admin/editar_role/' . $created['id']);

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Detalle sólo lectura de un role.
   */
  function ver_role($id = null)
  {
    $this->guardAdminAccess();

    $id   = (int) $id;
    $role = Model::list('quetzal_roles', ['id' => $id], 1);
    if (!$role) {
      Flasher::error('No existe el role solicitado.');
      Redirect::to('admin/roles');
      exit;
    }

    $mgr         = new QuetzalRoleManager($role['slug']);
    $permissions = $mgr->getPermissions() ?: [];
    $users       = Model::query(
      'SELECT id, username, email FROM quetzal_users WHERE role = :r ORDER BY username ASC LIMIT 50',
      ['r' => $role['slug']]
    ) ?: [];
    $isProtected = in_array($role['slug'], ['admin', 'developer', 'worker'], true);

    $this->setTitle('Role: ' . $role['nombre']);
    $this->addToData('role'       , $role);
    $this->addToData('permissions', $permissions);
    $this->addToData('users'      , $users);
    $this->addToData('isProtected', $isProtected);
    $this->setView('roles/ver');
    $this->render();
  }

  function editar_role($id = null)
  {
    $this->guardAdminAccess();

    $id = (int) $id;
    $role = Model::list('quetzal_roles', ['id' => $id], 1);
    if (!$role) {
      Flasher::error('No existe el role solicitado.');
      Redirect::to('admin/roles');
      exit;
    }

    $mgr         = new QuetzalRoleManager($role['slug']);
    $allPerms    = Model::query('SELECT * FROM quetzal_permisos ORDER BY nombre ASC') ?: [];
    $rolePerms   = array_map(fn($p) => $p['slug'], $mgr->getPermissions() ?: []);
    $isProtected = in_array($role['slug'], ['admin', 'developer', 'worker'], true);

    $this->setTitle(sprintf('Editar role: %s', $role['nombre']));
    $this->addToData('role'        , $role);
    $this->addToData('allPerms'    , $allPerms);
    $this->addToData('rolePerms'   , $rolePerms);
    $this->addToData('isProtected' , $isProtected);
    $this->setView('roles/editar');
    $this->render();
  }

  function post_role_editar()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $id        = (int) ($_POST['id'] ?? 0);
      $nombre    = sanitize_input($_POST['nombre'] ?? '');
      $slug      = sanitize_input($_POST['slug']   ?? '');
      $permSlugs = $_POST['permisos'] ?? [];
      if (!is_array($permSlugs)) $permSlugs = [];

      if (!$role = Model::list('quetzal_roles', ['id' => $id], 1)) {
        throw new Exception('No existe el role.');
      }

      $isProtected = in_array($role['slug'], ['admin', 'developer', 'worker'], true);

      if (!$isProtected) {
        // Actualizar nombre/slug solo si no es protegido
        if (strlen($nombre) < 3) throw new Exception('El nombre debe tener al menos 3 caracteres.');
        if (!preg_match('/^[a-z0-9-]{3,50}$/', $slug)) throw new Exception('Slug inválido.');

        $mgr = new QuetzalRoleManager();
        $mgr->updateRole($id, $nombre, $slug);
        $role['slug'] = $slug; // para el sync de abajo
      }

      // Sincronizar permisos
      $mgr = new QuetzalRoleManager($role['slug']);
      $currentPerms = array_map(fn($p) => $p['slug'], $mgr->getPermissions() ?: []);

      // Quitar los que ya no están
      foreach ($currentPerms as $current) {
        if (!in_array($current, $permSlugs, true)) {
          $mgr->deny($current);
        }
      }
      // Agregar los nuevos
      foreach ($permSlugs as $slugToAdd) {
        if (!in_array($slugToAdd, $currentPerms, true)) {
          $mgr->allow($slugToAdd);
        }
      }

      Flasher::success('Role actualizado con éxito.');
      Redirect::to('admin/ver_role/' . $id);

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function borrar_role($id = null)
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_GET['_t'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $id = (int) $id;
      if (!$role = Model::list('quetzal_roles', ['id' => $id], 1)) {
        throw new Exception('No existe el role.');
      }

      // Verificar que no haya usuarios con ese role
      $usersWithRole = Model::query('SELECT COUNT(*) AS n FROM quetzal_users WHERE role = :r', ['r' => $role['slug']]);
      if (!empty($usersWithRole[0]['n'])) {
        throw new Exception(sprintf('No se puede borrar: hay %d usuario(s) con este role.', $usersWithRole[0]['n']));
      }

      $mgr = new QuetzalRoleManager();
      $mgr->removeRole($role['slug']);

      Flasher::success(sprintf('Role <b>%s</b> eliminado.', $role['nombre']));
      Redirect::to('admin/roles');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Listado de permisos con búsqueda.
   */
  function permisos()
  {
    $this->guardAdminAccess();

    $q = sanitize_input((string)($_GET['q'] ?? ''));

    $whereSql = '';
    $params = [];
    if ($q !== '') {
      $whereSql = 'WHERE p.nombre LIKE :q OR p.slug LIKE :q OR p.descripcion LIKE :q';
      $params['q'] = '%' . $q . '%';
    }

    $sql = sprintf('
      SELECT p.*, COUNT(rp.id_role) AS role_count
      FROM quetzal_permisos p
      LEFT JOIN quetzal_roles_permisos rp ON rp.id_permiso = p.id
      %s
      GROUP BY p.id
      ORDER BY p.nombre ASC
    ', $whereSql);

    $permissions = Model::query($sql, $params) ?: [];

    $this->setTitle('Permisos');
    $this->addToData('permissions', $permissions);
    $this->addToData('filters'    , ['q' => $q]);
    $this->setView('permisos/index');
    $this->render();
  }

  function crear_permiso()
  {
    $this->guardAdminAccess();
    $this->setTitle('Nuevo permiso');
    $this->setView('permisos/crear');
    $this->render();
  }

  function post_permiso()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $nombre      = sanitize_input($_POST['nombre']      ?? '');
      $slug        = sanitize_input($_POST['slug']        ?? '');
      $descripcion = sanitize_input($_POST['descripcion'] ?? '');

      if (strlen($nombre) < 3) throw new Exception('El nombre debe tener al menos 3 caracteres.');
      if (!preg_match('/^[a-z0-9-]{3,50}$/', $slug)) throw new Exception('El slug debe ser minúsculas, números y guiones (3-50 caracteres).');

      $mgr = new QuetzalRoleManager();
      $mgr->addPermission($nombre, $slug, $descripcion ?: null);

      Flasher::success(sprintf('Permiso <b>%s</b> creado.', $nombre));
      Redirect::to('admin/permisos');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function ver_permiso($id = null)
  {
    $this->guardAdminAccess();

    $id = (int) $id;
    $permiso = Model::list('quetzal_permisos', ['id' => $id], 1);
    if (!$permiso) {
      Flasher::error('No existe el permiso.');
      Redirect::to('admin/permisos');
      exit;
    }

    // Roles que usan este permiso
    $roles = Model::query('
      SELECT r.* FROM quetzal_roles r
      INNER JOIN quetzal_roles_permisos rp ON rp.id_role = r.id
      WHERE rp.id_permiso = :id
      ORDER BY r.nombre ASC
    ', ['id' => $id]) ?: [];

    $isProtected = in_array($permiso['slug'], ['admin-access'], true);

    $this->setTitle('Permiso: ' . $permiso['nombre']);
    $this->addToData('permiso'    , $permiso);
    $this->addToData('roles'      , $roles);
    $this->addToData('isProtected', $isProtected);
    $this->setView('permisos/ver');
    $this->render();
  }

  function editar_permiso($id = null)
  {
    $this->guardAdminAccess();

    $id = (int) $id;
    $permiso = Model::list('quetzal_permisos', ['id' => $id], 1);
    if (!$permiso) {
      Flasher::error('No existe el permiso.');
      Redirect::to('admin/permisos');
      exit;
    }

    $this->setTitle('Editar permiso: ' . $permiso['nombre']);
    $this->addToData('permiso', $permiso);
    $this->setView('permisos/editar');
    $this->render();
  }

  function post_permiso_editar()
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_POST['_t'] ?? $_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $id          = (int) ($_POST['id'] ?? 0);
      $nombre      = sanitize_input($_POST['nombre']      ?? '');
      $descripcion = sanitize_input($_POST['descripcion'] ?? '');

      if (!$p = Model::list('quetzal_permisos', ['id' => $id], 1)) {
        throw new Exception('No existe el permiso.');
      }
      if (strlen($nombre) < 3) throw new Exception('El nombre debe tener al menos 3 caracteres.');

      // El slug es inmutable porque otros lugares lo referencian por código
      if (!Model::update('quetzal_permisos', ['id' => $id], ['nombre' => $nombre, 'descripcion' => $descripcion ?: null])) {
        throw new Exception('No se pudo actualizar el permiso.');
      }

      Flasher::success('Permiso actualizado.');
      Redirect::to('admin/ver_permiso/' . $id);

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function borrar_permiso($id = null)
  {
    try {
      $this->guardAdminAccess();
      if (!Csrf::validate($_GET['_t'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $id = (int) $id;
      if (!$p = Model::list('quetzal_permisos', ['id' => $id], 1)) {
        throw new Exception('No existe el permiso.');
      }

      $mgr = new QuetzalRoleManager();
      $mgr->removePermission($p['slug']);

      Flasher::success(sprintf('Permiso <b>%s</b> eliminado.', $p['nombre']));
      Redirect::to('admin/permisos');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function botones()
  {
    $this->setTitle('Botones');
    $this->setView('botones');
    $this->render();
  }

  function cartas()
  {
    $this->setTitle('Cartas');
    $this->setView('cartas');
    $this->render();
  }

  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  //////// USUARIOS
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  /**
   * Listado de usuarios con búsqueda, ordenamiento y filtro por role.
   *
   * Query params:
   *  - q:     texto libre (busca en username/email)
   *  - role:  filtra por slug de role
   *  - sort:  columna (id|username|email|role|created_at)
   *  - dir:   asc|desc
   *  - page:  página
   */
  function usuarios()
  {
    $q    = sanitize_input((string)($_GET['q']    ?? ''));
    $role = sanitize_input((string)($_GET['role'] ?? ''));
    $sort = (string)($_GET['sort'] ?? 'id');
    $dir  = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

    $allowedSort = ['id', 'username', 'email', 'role', 'created_at'];
    if (!in_array($sort, $allowedSort, true)) $sort = 'id';

    $wheres = [];
    $params = [];
    if ($q !== '') {
      $wheres[] = '(username LIKE :q OR email LIKE :q)';
      $params['q'] = '%' . $q . '%';
    }
    if ($role !== '') {
      $wheres[] = 'role = :role';
      $params['role'] = $role;
    }
    $whereSql = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

    $sql   = sprintf('SELECT * FROM quetzal_users %s ORDER BY %s %s', $whereSql, $sort, $dir);
    $users = PaginationHandler::paginate($sql, $params, 15);

    $roles = (new QuetzalRoleManager())->getRoles() ?: [];

    $this->setTitle('Usuarios');
    $this->addToData('users'  , $users);
    $this->addToData('roles'  , $roles);
    $this->addToData('filters', ['q' => $q, 'role' => $role, 'sort' => $sort, 'dir' => $dir]);
    $this->setView('usuarios/index');
    $this->render();
  }

  /**
   * Formulario para crear un nuevo usuario.
   */
  function crear_usuario()
  {
    $this->setTitle('Nuevo usuario');
    $this->addToData('roles', (new QuetzalRoleManager())->getRoles() ?: []);
    $this->setView('usuarios/crear');
    $this->render();
  }

  /**
   * Procesa la creación de un nuevo usuario.
   */
  function post_crear_usuario()
  {
    try {
      if (!Csrf::validate($_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $username = sanitize_input($_POST['username'] ?? '');
      $email    = sanitize_input($_POST['email']    ?? '');
      $role     = sanitize_input($_POST['role']     ?? 'worker');
      $password = (string) ($_POST['password'] ?? '');

      if (!preg_match('/^[a-zA-Z0-9]{5,20}$/', $username)) {
        throw new Exception('El username debe tener entre 5-20 caracteres alfanuméricos.');
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido.');
      }
      if (function_exists('is_temporary_email') && is_temporary_email($email)) {
        throw new Exception('El dominio del correo no está autorizado.');
      }
      if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_-])[A-Za-z\d!@#$%^&*_-]{5,20}$/', $password)) {
        throw new Exception('La contraseña debe tener 5-20 caracteres e incluir: 1 minúscula, 1 mayúscula, 1 dígito y 1 especial de <b>!@#$%^&*_-</b>');
      }

      // Unicidad
      $exists = userModel::query('SELECT id FROM quetzal_users WHERE username = :u OR email = :e', ['u' => $username, 'e' => $email]);
      if ($exists) {
        throw new Exception('Ya existe un usuario con ese nombre o email.');
      }

      if (!$id = userModel::add(userModel::$t1, [
        'username'   => $username,
        'email'      => $email,
        'role'       => $role,
        'password'   => password_hash($password . AUTH_SALT, PASSWORD_BCRYPT),
        'created_at' => now(),
      ])) {
        throw new Exception('Hubo un problema al crear el usuario.');
      }

      Flasher::success(sprintf('Usuario <b>%s</b> creado con éxito.', $username));
      Redirect::to('admin/usuarios');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Detalle sólo lectura de un usuario.
   */
  function ver_usuario($id = null)
  {
    $id   = (int) $id;
    $user = userModel::by_id($id);
    if (empty($user)) {
      Flasher::error('El usuario no existe.');
      Redirect::to('admin/usuarios');
      exit;
    }

    // Permisos del role
    $permissions = [];
    if (!empty($user['role'])) {
      try {
        $mgr         = new QuetzalRoleManager($user['role']);
        $permissions = $mgr->getPermissions() ?: [];
      } catch (Exception $e) {}
    }

    $this->setTitle('Usuario: ' . $user['username']);
    $this->addToData('user'       , $user);
    $this->addToData('permissions', $permissions);
    $this->setView('usuarios/ver');
    $this->render();
  }

  /**
   * Formulario para editar un usuario.
   */
  function editar_usuario($id = null)
  {
    $id   = (int) $id;
    $user = userModel::by_id($id);
    if (empty($user)) {
      Flasher::error('El usuario no existe.');
      Redirect::to('admin/usuarios');
      exit;
    }

    $this->setTitle('Editar usuario: ' . $user['username']);
    $this->addToData('user' , $user);
    $this->addToData('roles', (new QuetzalRoleManager())->getRoles() ?: []);
    $this->setView('usuarios/editar');
    $this->render();
  }

  /**
   * Procesa la edición de un usuario. Password opcional (vacío = no cambiar).
   */
  function post_editar_usuario()
  {
    try {
      if (!Csrf::validate($_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $id       = (int) ($_POST['id'] ?? 0);
      $username = sanitize_input($_POST['username'] ?? '');
      $email    = sanitize_input($_POST['email']    ?? '');
      $role     = sanitize_input($_POST['role']     ?? 'worker');
      $password = (string) ($_POST['password'] ?? '');

      $user = userModel::by_id($id);
      if (empty($user)) throw new Exception('El usuario no existe.');

      if (!preg_match('/^[a-zA-Z0-9]{5,20}$/', $username)) {
        throw new Exception('El username debe tener entre 5-20 caracteres alfanuméricos.');
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido.');
      }

      // Unicidad excluyendo al propio usuario
      $dup = userModel::query(
        'SELECT id FROM quetzal_users WHERE (username = :u OR email = :e) AND id != :id LIMIT 1',
        ['u' => $username, 'e' => $email, 'id' => $id]
      );
      if ($dup) throw new Exception('Ya existe otro usuario con ese nombre o email.');

      $update = [
        'username' => $username,
        'email'    => $email,
        'role'     => $role,
      ];

      if ($password !== '') {
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*_-])[A-Za-z\d!@#$%^&*_-]{5,20}$/', $password)) {
          throw new Exception('La contraseña no cumple los requisitos. Déjala vacía si no quieres cambiarla.');
        }
        $update['password'] = password_hash($password . AUTH_SALT, PASSWORD_BCRYPT);
      }

      if (!userModel::update(userModel::$t1, ['id' => $id], $update)) {
        throw new Exception('No se pudo actualizar el usuario.');
      }

      Flasher::success(sprintf('Usuario <b>%s</b> actualizado.', $username));
      Redirect::to('admin/ver_usuario/' . $id);

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function borrar_usuario($id = null)
  {
    try {
      if (!Csrf::validate($_GET['_t'])) {
        throw new Exception(get_quetzal_message(0));
      }

      // Verificar que exista el usuario
      if (!$user = userModel::by_id($id)) {
        throw new Exception('No existe el usuario en la base de datos.');
      }

      // Validar que no sea el propio usuario que está solicitando la petición
      if ($id == get_user('id')) {
        throw new Exception('No puedes realizar esta acción sobre ti mismo.');
      }

      // Borrando el registro de la base de datos
      if (!userModel::remove(userModel::$t1, ['id' => $id], 1)) {
        throw new Exception('Hubo un problema al borrar el usuario.');
      }

      Flasher::success(sprintf('Usuario <b>%s</b> borrado con éxito.', $user['username']));
      Redirect::back();

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  function destruir_sesion($id = null)
  {
    try {
      if (!Csrf::validate($_GET['_t'])) {
        throw new Exception(get_quetzal_message(0));
      }

      // Verificar que exista el usuario
      if (!$user = userModel::by_id($id)) {
        throw new Exception('No existe el usuario en la base de datos.');
      }

      // Validar que no sea el propio usuario que está solicitando la petición
      if ($id == get_user('id')) {
        throw new Exception('No puedes realizar esta acción sobre ti mismo.');
      }

      // Verificar que el usuario tenga una sesión activa
      if (empty($user['auth_token']) || $user['auth_token'] == null) {
        throw new Exception('El usuario no tiene una sesión activa.');
      }

      // Cerrando su sesión
      if (!userModel::update(userModel::$t1, ['id' => $id], ['auth_token' => null])) {
        throw new Exception('Hubo un problema al actualizar el usuario.');
      }

      Flasher::success(sprintf('La sesión de <b>%s</b> ha sido cerrada con éxito.', $user['username']));
      Redirect::back();

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  //////// PRODUCTOS
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  ////////////////////////////////////////////////////
  /**
   * Listado de productos con búsqueda y orden.
   */
  function productos()
  {
    $q    = sanitize_input((string)($_GET['q'] ?? ''));
    $sort = (string)($_GET['sort'] ?? 'id');
    $dir  = strtolower((string)($_GET['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

    $allowedSort = ['id', 'nombre', 'sku', 'precio', 'stock', 'creado'];
    if (!in_array($sort, $allowedSort, true)) $sort = 'id';

    $whereSql = '';
    $params   = [];
    if ($q !== '') {
      $whereSql = 'WHERE nombre LIKE :q OR sku LIKE :q OR descripcion LIKE :q';
      $params['q'] = '%' . $q . '%';
    }

    $sql       = sprintf('SELECT * FROM productos %s ORDER BY %s %s', $whereSql, $sort, $dir);
    $productos = PaginationHandler::paginate($sql, $params, 15);

    $this->setTitle('Productos');
    $this->addToData('productos', $productos);
    $this->addToData('filters'  , ['q' => $q, 'sort' => $sort, 'dir' => $dir]);
    $this->setView('productos/index');
    $this->render();
  }

  /**
   * Formulario para crear un nuevo producto.
   */
  function crear_producto()
  {
    $this->setTitle('Nuevo producto');
    $this->setView('productos/crear');
    $this->render();
  }

  /**
   * Procesa creación de producto con upload de imagen.
   */
  function post_crear_producto()
  {
    try {
      if (!Csrf::validate($_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $nombre             = sanitize_input($_POST['nombre']             ?? '');
      $sku                = sanitize_input($_POST['sku']                ?? '');
      $descripcion        = sanitize_input($_POST['descripcion']        ?? '');
      $precio             = (float) ($_POST['precio']             ?? 0);
      $precio_comparacion = (float) ($_POST['precio_comparacion'] ?? 0);
      $rastrear_stock     = isset($_POST['rastrear_stock']) ? 1 : 0;
      $stock              = (int) ($_POST['stock'] ?? 0);

      if (strlen($nombre) < 3 || strlen($nombre) > 150) {
        throw new Exception('El nombre debe tener entre 3 y 150 caracteres.');
      }
      if ($precio <= 0) {
        throw new Exception('El precio debe ser mayor que 0.');
      }
      if ($precio_comparacion > 0 && $precio_comparacion < $precio) {
        throw new Exception('El precio de comparación debe ser mayor al precio principal.');
      }

      $slugify = new Cocur\Slugify\Slugify();
      $slug    = $slugify->slugify($nombre);

      // Unicidad
      $dup = productoModel::query(
        'SELECT id FROM productos WHERE (sku = :sku AND sku != "") OR nombre = :nombre OR slug = :slug',
        ['sku' => $sku, 'nombre' => $nombre, 'slug' => $slug]
      );
      if ($dup) throw new Exception('Ya existe un producto con ese SKU, nombre o slug.');

      // Procesar imagen (opcional)
      $imagenFilename = null;
      if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === 0) {
        $tmp       = $_FILES['imagen']['tmp_name'];
        $ext       = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $new_name  = generate_filename() . '.' . strtolower($ext);
        if (!move_uploaded_file($tmp, UPLOADS . $new_name)) {
          throw new Exception('No se pudo subir la imagen.');
        }
        $imagenFilename = $new_name;
      }

      $data = [
        'nombre'             => $nombre,
        'slug'               => $slug,
        'sku'                => $sku !== '' ? $sku : random_password(8, 'numeric'),
        'descripcion'        => $descripcion,
        'precio'             => $precio,
        'precio_comparacion' => $precio_comparacion,
        'rastrear_stock'     => $rastrear_stock,
        'stock'              => $stock,
        'imagen'             => $imagenFilename,
        'creado'             => now(),
      ];

      if (!$id = productoModel::insertOne($data)) {
        throw new Exception('Hubo un error al crear el producto.');
      }

      Flasher::success(sprintf('Producto <b>%s</b> creado.', $nombre));
      Redirect::to('admin/ver_producto/' . $id);

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Detalle sólo lectura.
   */
  function ver_producto($id = null)
  {
    $id       = (int) $id;
    $producto = productoModel::by_id($id);
    if (empty($producto)) {
      Flasher::error('El producto no existe.');
      Redirect::to('admin/productos');
      exit;
    }

    $this->setTitle($producto['nombre']);
    $this->addToData('producto', $producto);
    $this->setView('productos/ver');
    $this->render();
  }

  /**
   * Form de edición.
   */
  function editar_producto($id = null)
  {
    $id       = (int) $id;
    $producto = productoModel::by_id($id);
    if (empty($producto)) {
      Flasher::error('El producto no existe.');
      Redirect::to('admin/productos');
      exit;
    }

    $this->setTitle('Editar: ' . $producto['nombre']);
    $this->addToData('producto', $producto);
    $this->setView('productos/editar');
    $this->render();
  }

  /**
   * Procesa la edición. Imagen opcional (mantiene actual si no se sube una nueva).
   */
  function post_editar_producto()
  {
    try {
      if (!Csrf::validate($_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $id       = (int) ($_POST['id'] ?? 0);
      $producto = productoModel::by_id($id);
      if (empty($producto)) throw new Exception('El producto no existe.');

      $nombre             = sanitize_input($_POST['nombre']             ?? '');
      $sku                = sanitize_input($_POST['sku']                ?? '');
      $descripcion        = sanitize_input($_POST['descripcion']        ?? '');
      $precio             = (float) ($_POST['precio']             ?? 0);
      $precio_comparacion = (float) ($_POST['precio_comparacion'] ?? 0);
      $rastrear_stock     = isset($_POST['rastrear_stock']) ? 1 : 0;
      $stock              = (int) ($_POST['stock'] ?? 0);

      if (strlen($nombre) < 3 || strlen($nombre) > 150) {
        throw new Exception('El nombre debe tener entre 3 y 150 caracteres.');
      }
      if ($precio <= 0) throw new Exception('El precio debe ser mayor que 0.');
      if ($precio_comparacion > 0 && $precio_comparacion < $precio) {
        throw new Exception('El precio de comparación debe ser mayor al precio principal.');
      }

      $slugify = new Cocur\Slugify\Slugify();
      $slug    = $slugify->slugify($nombre);

      $dup = productoModel::query(
        'SELECT id FROM productos WHERE ((sku = :sku AND sku != "") OR nombre = :nombre OR slug = :slug) AND id != :id LIMIT 1',
        ['sku' => $sku, 'nombre' => $nombre, 'slug' => $slug, 'id' => $id]
      );
      if ($dup) throw new Exception('Otro producto ya tiene ese SKU, nombre o slug.');

      $update = [
        'nombre'             => $nombre,
        'slug'               => $slug,
        'sku'                => $sku !== '' ? $sku : $producto['sku'],
        'descripcion'        => $descripcion,
        'precio'             => $precio,
        'precio_comparacion' => $precio_comparacion,
        'rastrear_stock'     => $rastrear_stock,
        'stock'              => $stock,
      ];

      // Nueva imagen opcional
      if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === 0) {
        $ext      = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $new_name = generate_filename() . '.' . strtolower($ext);
        if (!move_uploaded_file($_FILES['imagen']['tmp_name'], UPLOADS . $new_name)) {
          throw new Exception('No se pudo subir la nueva imagen.');
        }
        // Borra la anterior si existe
        if (!empty($producto['imagen']) && is_file(UPLOADS . $producto['imagen'])) {
          @unlink(UPLOADS . $producto['imagen']);
        }
        $update['imagen'] = $new_name;
      }

      if (!productoModel::update(productoModel::$t1, ['id' => $id], $update)) {
        throw new Exception('No se pudo actualizar el producto.');
      }

      Flasher::success(sprintf('Producto <b>%s</b> actualizado.', $nombre));
      Redirect::to('admin/ver_producto/' . $id);

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }

  /**
   * Elimina un producto y su imagen asociada.
   */
  function borrar_producto($id = null)
  {
    try {
      if (!Csrf::validate($_GET['_t'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      $id       = (int) $id;
      $producto = productoModel::by_id($id);
      if (empty($producto)) throw new Exception('El producto no existe.');

      if (!productoModel::remove(productoModel::$t1, ['id' => $id], 1)) {
        throw new Exception('No se pudo eliminar el producto.');
      }

      // Limpiar imagen
      if (!empty($producto['imagen']) && is_file(UPLOADS . $producto['imagen'])) {
        @unlink(UPLOADS . $producto['imagen']);
      }

      Flasher::success(sprintf('Producto <b>%s</b> eliminado.', $producto['nombre']));
      Redirect::to('admin/productos');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }
}
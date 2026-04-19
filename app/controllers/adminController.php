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

  function roles()
  {
    $this->guardAdminAccess();
    [$roles, $permissions] = $this->fetchRolesAndPermissions();

    $this->setTitle('Roles y permisos');
    $this->addToData('roles'      , $roles);
    $this->addToData('permissions', $permissions);
    $this->setView('roles');
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

      Flasher::success(sprintf('Role <b>%s</b> creado con éxito.', $nombre));
      Redirect::to('admin/roles');

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
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
    $this->setView('editarRole');
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
      Redirect::to('admin/editar_role/' . $id);

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

  function permisos()
  {
    $this->guardAdminAccess();
    [, $permissions] = $this->fetchRolesAndPermissions();

    $this->setTitle('Permisos');
    $this->addToData('permissions', $permissions);
    $this->setView('permisos');
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

      // Actualizamos solo nombre y descripción; el slug es estable para no romper código que lo verifique.
      if (!Model::update('quetzal_permisos', ['id' => $id], ['nombre' => $nombre, 'descripcion' => $descripcion ?: null])) {
        throw new Exception('No se pudo actualizar el permiso.');
      }

      Flasher::success('Permiso actualizado.');
      Redirect::to('admin/permisos');

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
  function productos()
  {
    // Formulario para agregar nuevo registro
    $form= new QuetzalFormBuilder('agregar-producto', 'agregar-producto', ['needs-validation'], 'admin/post_productos', true, true);
    
    // Inputs
    $form->addCustomFields(insert_inputs());
    $form->addTextField('nombre', 'Nombre del producto', ['form-control'], 'product-name', true);
    $form->addTextField('sku', 'SKU o número de rastreo', ['form-control'], 'product-sku');
    $form->addTextareaField('descripcion', 'Descripción del producto', 4, 5, ['form-control'], 'product-description');
    $form->addNumberField('precio', 'Precio principal', 1, 999999999, 'any', null, ['form-control'], 'product-price', true);
    $form->addNumberField('precio_comparacion', 'Precio de comparación', 1, 999999999, 'any', null, ['form-control'], 'product-compare-price');

    $form->addFileField('imagen', 'Imagen principal del producto', ['form-control'], 'product-imagen', true);

    $form->addCustomFields('<hr>');

    $form->addCheckboxField('rastrear_stock', 'Seguimiento de stock', 'true', ['form-check-input'], 'trackStock', false);
    $form->addNumberField('stock', 'Unidades disponibles', 1, 999999999, 1, null, ['form-control'], 'stock', false);

    $form->addButton('submit', 'submit', 'Agregar producto', ['btn btn-success'], 'submit-button');

    $this->setTitle('Productos');
    $this->addToData('form'     , $form->getFormHtml());
    $this->addToData('productos', productoModel::all_paginated());
    $this->addToData('slug'     , 'productos');
    $this->setView('productos/productos');
    $this->render();
  }

  function post_productos()
  {
    try {
      if (!check_posted_data(['nombre','sku','descripcion','precio','precio_comparacion','stock'], $_POST)) {
        throw new Exception('Por favor completa el formulario.');
      }

      if (!Csrf::validate($_POST['csrf'])) {
        throw new Exception(get_quetzal_message(0));
      }

      // Definición de variables
      array_map('sanitize_input', $_POST);
      $nombre             = $_POST['nombre'];
      $sku                = $_POST["sku"];
      $descripcion        = $_POST["descripcion"];
      $precio             = (float) $_POST["precio"];
      $precio_comparacion = (float) $_POST["precio_comparacion"];
      $rastrear_stock     = isset($_POST["rastrear_stock"]) ? 1 : 0;
      $stock              = (int) $_POST["stock"];
      $imagen             = $_FILES["imagen"];
      $errorMessage       = '';
      $errors             = 0;

      // Crear slug con base al nombre del producto
      $slugify = new Slugify();
      $slug    = $slugify->slugify($nombre);

      // Verificar que no exista ya un producto con el sku si es que no está vacío
      $sql = 'SELECT * FROM productos WHERE sku = :sku OR nombre = :nombre OR slug = :slug';
      if (productoModel::query($sql, ['sku' => $sku, 'nombre' => $nombre, 'slug' => $slug])) {
        throw new Exception('Ya existe un producto registrado con el mismo SKU o nombre.');
      }

      // Validar longitud del nombre, no mayor a 150 caracteres
      if (strlen($nombre) > 150) {
        $errorMessage .= '- El nombre del producto debe ser menor a 150 caracteres.' . PHP_EOL;
        $errors++;
      }

      // Validar el precio regular del producto
      if ($precio == 0) {
        $errorMessage .= '- Ingresa un precio mayor a 0.' . PHP_EOL;
        $errors++;
      }

      // Validar el precio de comparación si no es igual a 0
      if ($precio_comparacion != 0 && $precio_comparacion < $precio) {
        $errorMessage .= '- El precio de comparación debe ser mayor al precio principal del producto.' . PHP_EOL;
        $errors++;
      }

      // Validación de la imagen
      if ($imagen['error'] !== 0) {
        $errorMessage .= '- Selecciona una imagen de producto válida por favor.' . PHP_EOL;
        $errors++;
      }

      // Procesar imagen
      $tmp_name = $imagen['tmp_name'];
      $filename = $imagen['name'];
      $type     = $imagen['type'];
      $ext      = pathinfo($filename, PATHINFO_EXTENSION);
      $new_name = generate_filename() . '.' . $ext;

      if (!move_uploaded_file($tmp_name, UPLOADS . $new_name)) {
        $errorMessage .= '- Hubo un problema al subir el archivo de imagen.' . PHP_EOL;
        $errors++;
      }

      if ($errors > 0) {
        if (is_file(UPLOADS . $new_name)) {
          unlink(UPLOADS . $new_name);
        }
        throw new Exception($errorMessage);
      }

      // Array de información del producto
      $data =
      [
        'nombre'             => $nombre,
        'slug'               => $slug,
        'sku'                => empty($sku) ? random_password(8, 'numeric') : $sku,
        'descripcion'        => $descripcion,
        'precio'             => $precio,
        'precio_comparacion' => $precio_comparacion,
        'rastrear_stock'     => $rastrear_stock,
        'stock'              => empty($stock) ? 0 : $stock,
        'imagen'             => $new_name,
        'creado'             => now()
      ];

      // Agregar producto a la base de datos
      if (!$id = productoModel::insertOne($data)) {
        throw new Exception('Hubo un error, intenta de nuevo.');
      }

      $producto = productoModel::by_id($id);

      Flasher::success(sprintf('Nuevo producto <b>%s</b> agregado con éxito.', $producto['nombre']));
      Redirect::back();

    } catch (Exception $e) {
      Flasher::error($e->getMessage());
      Redirect::back();
    }
  }
}
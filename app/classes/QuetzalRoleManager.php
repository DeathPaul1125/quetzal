<?php

class QuetzalRoleManager extends Model
{
  /**
   * El nombre de la tabla que almacena los roles
   *
   * @var string
   */
  private $rolesTableName            = 'quetzal_roles';

  /**
   * El nombre de la tabla que almacena los permisos
   *
   * @var string
   */
  private $permissionsTableName      = 'quetzal_permisos';

  /**
   * El nombre de la tabla que relaciona los roles y permisos
   *
   * @var string
   */
  private $rolesPermissionsTableName = 'quetzal_roles_permisos';
  
  /**
   * Toda la información del role
   *
   * @var array
   */
  private $role                      = [];

  /**
   * El ID del role en curso
   *
   * @var mixed
   */
  private $roleId;

  /**
   * El nombre del role
   *
   * @var string
   */
  private $roleName;

  /**
   * El slug del role
   *
   * @var string
   */
  private $roleSlug;

  /**
   * La lista de permisos del role
   *
   * @var array
   */
  private $permissions               = [];

  function __construct(?string $roleSlug = null)
  {
    if ($roleSlug !== null) {
      $this->roleSlug = $roleSlug;
      $this->loadRole();
    }
  }

  /**
   * Establece el slug del role en cuestión
   *
   * @param string $role El slug del role
   * @return void
   */
  function setRole(string $role)
  {
    $this->roleSlug = $role;
    $this->loadRole();
  }

  /**
   * Carga la información del role pasado en la instancia
   *
   * @return void
   */
  private function loadRole()
  {
    $result = parent::list($this->rolesTableName, ['slug' => $this->roleSlug], 1);

    if ($result === false) {
      throw new Exception(sprintf('No existe el role "%s".', $this->roleSlug));
    };

    // Role actual
    $this->role     = $result;
    $this->roleId   = $result['id'];
    $this->roleName = $result['nombre'];
    $this->roleSlug = $result['slug'];

    // Existe el role, buscamos los permisos que tiene asignados
    $sql = 
    'SELECT
      p.*
    FROM
      %s p
    INNER JOIN %s rp ON rp.id_role = :id AND rp.id_permiso = p.id';
    $sql     = sprintf($sql, $this->permissionsTableName, $this->rolesPermissionsTableName);
    $results = parent::query($sql, ['id' => $this->roleId]);

    $this->permissions = $result === false ? [] : $results;

    $this->role['permisos'] = $this->permissions;
  }

  /**
   * Agrega un permiso a un role
   *
   * @param string $permission
   * @return bool
   */
  function allow(string $permission)
  {
    // Verificar si el role ya tiene el permiso
    if ($this->can($permission)) return true;

    // Cargar información del permiso
    if (!$permission = parent::list($this->permissionsTableName, ['slug' => $permission], 1)) {
      return false;
    }

    // Asignar permiso al role
    if (!parent::add($this->rolesPermissionsTableName, ['id_role' => $this->roleId, 'id_permiso' => $permission['id']])) {
      return false;
    }

    $this->loadRole();
    return true;
  }

  /**
   * Remueve un permiso de un role
   *
   * @param string $permission
   * @return bool
   */
  function deny(string $permission)
  {
    // Verificar si el role ya tiene el permiso
    if (!$this->can($permission)) return true;

    // Cargar información del permiso
    if (!$permission = parent::list($this->permissionsTableName, ['slug' => $permission], 1)) {
      return false;
    }

    // Remover permiso al role
    if (!parent::remove($this->rolesPermissionsTableName, ['id_role' => $this->roleId, 'id_permiso' => $permission['id']])) {
      return false;
    }

    $this->loadRole();
    return true;
  }

  /**
   * Regresa todos los roles en la base de datos
   *
   * @return array
   */
  function getRoles()
  {
    $sql = 'SELECT * FROM %s ORDER BY id DESC';
    return Model::query(sprintf($sql, $this->rolesTableName));
  }

  /**
   * Regresa toda la información del role
   *
   * @return array
   */
  function getRole()
  {
    return $this->role;  
  }

  /**
   * Regresa el nombre del role
   *
   * @return string
   */
  function getRoleName()
  {
    return $this->roleName;
  }

  /**
   * Regresa el slug del role
   *
   * @return string
   */
  function getRoleSlug()
  {
    return $this->roleSlug;
  }

  /**
   * Regresa los permisos de un role
   *
   * @return array
   */
  function getPermissions()
  {
    return $this->permissions;
  }

  /**
   * Regresa el listado de permisos de un role
   *
   * @return array
   */
  private function formatPermissions()
  {
    if (empty($this->permissions)) return [];
    
    return array_map(function ($permission){ return $permission['slug']; }, $this->permissions);
  }

  /**
   * Verifica si un role tiene un permiso en específico
   *
   * @param string $permission
   * @return boolean
   */
  function can(string $permission)
  {
    // Si el role es desarrollador asignado como "developer"
    if (in_array($this->roleSlug, ['developer'])) return true;

    // Si el role tiene asignado acceso total de administrador sin necesidad de tener todos los permisos asignados
    if (in_array('admin-access', $this->formatPermissions())) return true;

    // Para verificaciones generales si no es administrador o desarrollador
    return in_array($permission, $this->formatPermissions());
  }

  /**
   * Agrega un role a la base de datos del sistema
   *
   * @param string $roleName
   * @param string $roleSlug
   * @return array
   */
  function addRole(string $name, string $slug)
  {
    $this->roleName = $name;
    $this->roleSlug = $slug;

    $data           =
    [
      'nombre' => $this->roleName,
      'slug'   => $this->roleSlug,
      'creado' => now()
    ];

    // Verificar que no exista ya un role con ese slug
    if (Model::list($this->rolesTableName, ['slug' => $this->roleSlug])) {
      throw new Exception(sprintf('Ya existe el role "%s".', $this->roleSlug));
    }

    // Añadir el role a la base de datos
    if (!$this->roleId = Model::add($this->rolesTableName, $data)) {
      throw new Exception('Hubo un problema al crear el nuevo role.');
    }

    return true; // Se agregó el role a la base de datos
  }

  /**
   * Actualiza un role de la base de datos
   *
   * @param integer $id
   * @param string $name
   * @param string $slug
   * @return bool
   */
  function updateRole(int $id, string $name, string $slug)
  {
    $this->roleId   = $id;
    $this->roleName = $name;
    $this->roleSlug = $slug;

    $data           =
    [
      'nombre' => $this->roleName,
      'slug'   => $this->roleSlug
    ];

    // Verificar que exista el role
    if (!$this->role = Model::list($this->rolesTableName, ['id' => $this->roleId], 1)) {
      throw new Exception(sprintf('No existe el role "%s".', $this->roleSlug));
    }

    // Verificar que no sea un role por defecto
    if (in_array($this->role['slug'], ['admin', 'developer', 'worker'])) {
      throw new Exception(sprintf('No puedes editar el role "%s", este un role defecto.', $this->role['nombre']));
    }

    // Verificar que no exista ya un role con ese slug
    $sql = 'SELECT * FROM %s WHERE id != :id AND slug = :slug';
    if (Model::query(sprintf($sql, $this->rolesTableName), ['id' => $this->roleId, 'slug' => $this->roleSlug])) {
      throw new Exception(sprintf('Ya existe el role "%s".', $this->roleSlug));
    }

    // Actualizar el role en la base de datos
    if (!Model::update($this->rolesTableName, ['id' => $this->roleId], $data)) {
      throw new Exception('Hubo un problema al actualizar el role.');
    }

    return true; // Se actualizó el role a la base de datos
  }

  /**
   * Elimina un role de la base de datos
   *
   * @param string $roleSlug
   * @return bool
   */
  function removeRole(string $roleSlug)
  {
    $this->roleSlug = $roleSlug;

    // Prevenir el borrado de roles por defecto de Quetzal
    if (in_array($this->roleSlug, ['admin', 'worker', 'developer'])) {
      throw new Exception(sprintf('No puedes borrar el role "%s", este es un role por defecto.', $this->roleSlug));
    }

    // Verificar si existe el role con ese slug
    if (!$this->role = Model::list($this->rolesTableName, ['slug' => $this->roleSlug], 1)) {
      throw new Exception(sprintf('No existe el role "%s".', $this->roleSlug));
    }

    $this->roleId   = $this->role['id'];
    $this->roleName = $this->role['nombre'];

    // Borrar el role y todos los permisos asignados
    $sql = 
    'DELETE r, rp
    FROM %s r
    JOIN %s rp ON rp.id_role = r.id
    WHERE r.id = :id';

    $sql = sprintf($sql, $this->rolesTableName, $this->rolesPermissionsTableName);

    return Model::query($sql, ['id' => $this->roleId]);
  }

  /**
   * Sincroniza los permisos declarados por plugins.
   *
   * Fuentes:
   *   1. Hook `plugin_permissions` — plugins retornan un array de permisos
   *   2. Clave `permissions` en plugin.json de cada plugin habilitado
   *
   * Shape de cada permiso:
   *   ['slug' => 'facturador.ventas.crear', 'nombre' => 'Crear ventas', 'descripcion' => '...']
   *
   * Comportamiento:
   *   - Inserta permisos nuevos (NO toca los existentes ni borra)
   *   - Auto-asigna los nuevos a roles 'admin' y 'developer'
   *   - Retorna resumen {added, skipped, assigned, errors}
   */
  public static function syncPermissions(): array
  {
    $summary = ['added' => [], 'skipped' => [], 'assigned' => [], 'errors' => []];

    // 1. Juntar permisos declarados
    $declared = [];

    if (class_exists('QuetzalHookManager')) {
      foreach (QuetzalHookManager::getHookData('plugin_permissions') as $list) {
        if (is_array($list)) foreach ($list as $p) if (is_array($p)) $declared[] = $p;
      }
    }

    if (class_exists('QuetzalPluginManager')) {
      foreach (QuetzalPluginManager::getInstance()->getEnabled() as $plugin) {
        if (!empty($plugin['permissions']) && is_array($plugin['permissions'])) {
          foreach ($plugin['permissions'] as $p) {
            if (is_array($p)) $declared[] = $p;
          }
        }
      }
    }

    // 2. Deduplicar por slug
    $bySlug = [];
    foreach ($declared as $p) {
      $slug = trim((string)($p['slug'] ?? ''));
      if ($slug === '') continue;
      $bySlug[$slug] = $p;
    }

    // 3. Roles automáticos a los que se asignan los nuevos permisos
    $autoRoles = [];
    foreach (['admin', 'developer'] as $roleSlug) {
      $r = Model::list('quetzal_roles', ['slug' => $roleSlug], 1);
      if ($r) $autoRoles[$roleSlug] = (int) $r['id'];
    }

    // 4. Insertar faltantes + asignar
    foreach ($bySlug as $slug => $p) {
      if (!preg_match('/^[a-z0-9_\-\.]+$/', $slug)) {
        $summary['errors'][] = $slug . ' → slug inválido (solo a-z, 0-9, _, -, .)';
        continue;
      }

      $exists = Model::list('quetzal_permisos', ['slug' => $slug], 1);
      if ($exists) {
        $summary['skipped'][] = $slug;
        continue;
      }

      $nombre = trim((string)($p['nombre'] ?? $slug));
      $desc   = trim((string)($p['descripcion'] ?? ''));

      $permId = Model::add('quetzal_permisos', [
        'nombre'      => $nombre,
        'slug'        => $slug,
        'descripcion' => $desc !== '' ? $desc : null,
        'creado'      => now(),
      ]);

      if (!$permId) {
        $summary['errors'][] = $slug . ' → INSERT falló';
        continue;
      }

      $summary['added'][] = $slug;

      // Auto-asignar a admin/developer
      foreach ($autoRoles as $roleSlug => $roleId) {
        $dup = Model::list('quetzal_roles_permisos', ['id_role' => $roleId, 'id_permiso' => $permId], 1);
        if ($dup) continue;
        if (Model::add('quetzal_roles_permisos', ['id_role' => $roleId, 'id_permiso' => $permId])) {
          $summary['assigned'][] = $slug . ' → ' . $roleSlug;
        }
      }
    }

    return $summary;
  }

  /**
   * Agrega un nuevo permiso a la base de datos
   *
   * @param string $name
   * @param string $slug
   * @param string|null $description
   * @return bool
   */
  function addPermission(string $name, string $slug, ?string $description = null)
  {
    $permission =
    [
      'nombre'      => $name,
      'slug'        => $slug,
      'descripcion' => $description,
      'creado'      => now()
    ];

    // Verificar que no exista ya un permiso con ese slug
    if (Model::list($this->permissionsTableName, ['slug' => $slug])) {
      throw new Exception(sprintf('Ya existe el permiso "%s".', $slug));
    }

    // Añadir el permiso a la base de datos
    if (!$permissionId = Model::add($this->permissionsTableName, $permission)) {
      throw new Exception('Hubo un problema al crear el nuevo permiso.');
    }

    return true; // Se agregó el permiso a la base de datos
  }

  /**
   * Borra un permiso de la base de datos y sus asignaciones
   *
   * @param string $permission
   * @return bool
   */
  function removePermission(string $permission)
  {
    // Prevenir el borrado de permisos por defecto de Quetzal
    if (in_array($permission, ['admin-access'])) {
      throw new Exception(sprintf('No puedes borrar el permiso "%s".', $permission));
    }

    // Verificar si existe el permiso con ese slug
    if (!$permission = Model::list($this->permissionsTableName, ['slug' => $permission], 1)) {
      throw new Exception(sprintf('No existe el permiso "%s".', $permission));
    }

    // Borrar el permiso y todos los permisos asignados
    $sql = 
    'DELETE p, rp
    FROM %s p
    LEFT JOIN %s rp ON rp.id_permiso = p.id
    WHERE p.id = :id';

    $sql = sprintf($sql, $this->permissionsTableName, $this->rolesPermissionsTableName);

    return Model::query($sql, ['id' => $permission['id']]);
  }
}
<?php

/**
 * Generador de artefactos CRUD. Crea modelos, migraciones, controladores
 * y vistas Blade siguiendo las convenciones de Quetzal.
 *
 * Uso típico (desde adminController):
 *   $gen = new QuetzalCrudGenerator(['target' => 'core']);
 *   $log = $gen->generateCrud('tareas', 'tareas', $fields);
 *
 * Protecciones:
 *   - Nunca sobrescribe archivos existentes (falla con error)
 *   - Valida nombres (alfanumérico, no palabras reservadas)
 *   - Valida tipos de campos contra whitelist
 */
class QuetzalCrudGenerator
{
  private const RESERVED_NAMES = [
    'admin', 'api', 'ajax', 'login', 'logout', 'quetzal', 'creator',
    'error', 'home', 'tienda', 'carrito', 'documentacion',
  ];

  private const FIELD_TYPES = [
    'string'   => 'varchar(%d)',
    'text'     => 'text',
    'int'      => 'int(11)',
    'bigint'   => 'bigint(20)',
    'decimal'  => 'decimal(10,2)',
    'boolean'  => 'tinyint(1)',
    'date'     => 'date',
    'datetime' => 'datetime',
    'select'   => 'varchar(100)',  // default; override a int(11) si source=table
  ];

  /**
   * Target del generador. Si 'core' → genera en app/, si es un plugin
   * habilitado → genera dentro de plugins/<name>/
   *
   * @var array
   */
  private array $paths;

  /**
   * Para mostrar al usuario en el log (ej. 'app/models/' o 'plugins/X/models/').
   * @var array
   */
  private array $displayPaths;


  public function __construct(array $options = [])
  {
    $target = $options['target'] ?? 'core';

    if ($target === 'core') {
      $this->paths = [
        'models'      => MODELS,
        'controllers' => CONTROLLERS,
        'views'       => VIEWS,
        'migrations'  => APP . 'migrations' . DS,
      ];
      $this->displayPaths = [
        'models'      => 'app/models/',
        'controllers' => 'app/controllers/',
        'views'       => 'templates/views/',
        'migrations'  => 'app/migrations/',
      ];
    } else {
      if (!class_exists('QuetzalPluginManager')) {
        throw new Exception('QuetzalPluginManager no disponible.');
      }
      $manifest = QuetzalPluginManager::getInstance()->discover()[$target] ?? null;
      if (!$manifest) {
        throw new Exception(sprintf('Plugin "%s" no encontrado.', $target));
      }
      $base = $manifest['path'];
      $this->paths = [
        'models'      => $base . 'models' . DS,
        'controllers' => $base . 'controllers' . DS,
        'views'       => $base . 'views' . DS,
        'migrations'  => $base . 'migrations' . DS,
      ];
      $rel = 'plugins/' . $target . '/';
      $this->displayPaths = [
        'models'      => $rel . 'models/',
        'controllers' => $rel . 'controllers/',
        'views'       => $rel . 'views/',
        'migrations'  => $rel . 'migrations/',
      ];
    }
  }

  // ============================================================
  //  Comandos públicos
  // ============================================================

  /**
   * Genera un modelo.
   * @return array{ok:bool, path:string, message:string}
   */
  public function generateModel(string $name, string $table): array
  {
    $name  = $this->validateName($name, 'modelo');
    $table = $this->validateTable($table);

    $file = $this->paths['models'] . $name . 'Model.php';
    if (is_file($file)) {
      return $this->err('El modelo "' . $name . 'Model" ya existe.');
    }

    $content = $this->modelTemplate($name, $table);
    return $this->write($file, $content, $this->displayPaths['models'] . $name . 'Model.php');
  }

  /**
   * Genera una migración que crea la tabla con los campos dados.
   */
  public function generateMigration(string $table, array $fields): array
  {
    $table = $this->validateTable($table);
    if (empty($fields)) {
      return $this->err('Debes definir al menos un campo para la migración.');
    }

    $fieldsSql = $this->buildFieldsSql($fields);
    $timestamp = date('Y_m_d_His');
    $fileName  = sprintf('%s_create_%s_table.php', $timestamp, $table);
    $file      = $this->paths['migrations'] . $fileName;

    if (is_file($file)) {
      return $this->err('La migración ya existe: ' . $fileName);
    }

    $content = $this->migrationTemplate($table, $fieldsSql);
    return $this->write($file, $content, $this->displayPaths['migrations'] . $fileName);
  }

  /**
   * Genera un controlador con los 7 métodos CRUD estándar.
   */
  public function generateController(string $name, string $table): array
  {
    $name  = $this->validateName($name, 'controlador');
    $table = $this->validateTable($table);

    $file = $this->paths['controllers'] . $name . 'Controller.php';
    if (is_file($file)) {
      return $this->err('El controlador "' . $name . 'Controller" ya existe.');
    }

    $content = $this->controllerTemplate($name, $table);
    return $this->write($file, $content, $this->displayPaths['controllers'] . $name . 'Controller.php');
  }

  /**
   * Genera las 4 vistas Blade: index, crear, editar, ver.
   */
  public function generateViews(string $name, array $fields): array
  {
    $name = $this->validateName($name, 'vista');
    $dir  = $this->paths['views'] . $name . DS;

    $results = [];

    if (!is_dir($dir)) {
      if (!@mkdir($dir, 0775, true)) {
        return $this->err('No se pudo crear el directorio: ' . $dir);
      }
    }

    $templates = [
      'indexView.blade.php'  => $this->viewIndexTemplate($name, $fields),
      'crearView.blade.php'  => $this->viewCrearTemplate($name, $fields),
      'editarView.blade.php' => $this->viewEditarTemplate($name, $fields),
      'verView.blade.php'    => $this->viewVerTemplate($name, $fields),
    ];

    foreach ($templates as $filename => $content) {
      $file = $dir . $filename;
      if (is_file($file)) {
        $results[] = $this->err('La vista ya existe: ' . $filename);
        continue;
      }
      $results[] = $this->write($file, $content, $this->displayPaths['views'] . $name . '/' . $filename);
    }

    return $results;
  }

  /**
   * Pipeline completo de CRUD. Genera modelo + migración + controlador + 4 vistas.
   * Cualquier falla individual se reporta pero continúa los demás.
   *
   * @return array Resultados en orden con type, ok, message, path
   */
  public function generateCrud(string $name, string $table, array $fields): array
  {
    $results = [];

    $results[] = ['type' => 'model'] + $this->generateModel($name, $table);
    $results[] = ['type' => 'migration'] + $this->generateMigration($table, $fields);
    $results[] = ['type' => 'controller'] + $this->generateController($name, $table);

    foreach ($this->generateViews($name, $fields) as $viewResult) {
      $results[] = ['type' => 'view'] + $viewResult;
    }

    return $results;
  }

  /**
   * Genera un CRUD maestro-detalle (factura + líneas).
   *
   * Crea dos CRUDs completos vinculados por una columna FK:
   *   - Master: nombre/tabla/campos definidos por el usuario
   *   - Detail: nombre/tabla propia + FK al master (auto-agregada como primer campo)
   *
   * El master genera un verView enriquecido que LISTA las líneas
   * asociadas y ofrece un botón "Agregar línea" → /detail/crear?parent_id=X.
   * El detail es un CRUD normal que filtra por parent_id en su index
   * y pre-rellena parent_id al crear si viene en la URL.
   *
   * @return array Resultados combinados con tags 'master-*' y 'detail-*'
   */
  public function generateMasterDetail(
    string $masterName, string $masterTable, array $masterFields,
    string $detailName, string $detailTable, array $detailFields,
    string $fkColumn = 'parent_id'
  ): array {
    $results = [];

    // Validar FK name
    if (!preg_match('/^[a-z][a-z0-9_]{0,49}$/', $fkColumn)) {
      throw new Exception('Nombre de columna FK inválido: ' . $fkColumn);
    }

    // Detail debe tener el FK agregado como PRIMER campo (int, required)
    $detailFieldsWithFk = [[
      'name'     => $fkColumn,
      'type'     => 'int',
      'length'   => 0,
      'width'    => 2,
      'required' => true,
      'unique'   => false,
    ]];
    foreach ($detailFields as $f) {
      if (($f['name'] ?? '') === $fkColumn) continue; // evitar duplicados
      $detailFieldsWithFk[] = $f;
    }

    // === MASTER ===
    $results[] = ['type' => 'master-model']      + $this->generateModel($masterName, $masterTable);
    $results[] = ['type' => 'master-migration']  + $this->generateMigration($masterTable, $masterFields);
    $results[] = ['type' => 'master-controller'] + $this->generateController($masterName, $masterTable);

    // Vistas del master: index/crear/editar normales + verView enriquecido
    foreach ($this->generateViews($masterName, $masterFields) as $viewResult) {
      $results[] = ['type' => 'master-view'] + $viewResult;
    }

    // Reemplazar verView del master con una versión que incluye listado de líneas
    $masterVerFile = $this->paths['views'] . $masterName . DS . 'verView.blade.php';
    if (is_file($masterVerFile)) {
      $enhancedVer = $this->viewVerWithDetailTemplate($masterName, $masterFields, $detailName, $fkColumn);
      if (@file_put_contents($masterVerFile, $enhancedVer) !== false) {
        $results[] = ['type' => 'master-view', 'ok' => true,
          'message' => 'verView mejorado con listado de líneas.',
          'path' => $this->displayPaths['views'] . $masterName . '/verView.blade.php'];
      }
    }

    // === DETAIL ===
    $results[] = ['type' => 'detail-model']      + $this->generateModel($detailName, $detailTable);
    $results[] = ['type' => 'detail-migration']  + $this->generateMigration($detailTable, $detailFieldsWithFk);
    $results[] = ['type' => 'detail-controller'] + $this->generateController($detailName, $detailTable);

    foreach ($this->generateViews($detailName, $detailFieldsWithFk) as $viewResult) {
      $results[] = ['type' => 'detail-view'] + $viewResult;
    }

    return $results;
  }

  /**
   * Template verView del master que incluye listado de líneas del detail.
   * Lista las filas del detail donde fk=master.id y ofrece un botón
   * "Agregar" que pre-rellena el parent_id al crear una línea.
   */
  private function viewVerWithDetailTemplate(string $masterName, array $masterFields, string $detailName, string $fkColumn): string
  {
    $rows = '';
    foreach ($masterFields as $f) {
      $v     = $this->validateField($f);
      $label = ucfirst(str_replace('_', ' ', $v['name']));
      $rows .= "        <div class=\"flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4 border-b border-slate-100 last:border-0\">\n"
            . "          <dt class=\"sm:w-1/3 text-slate-500\">{$label}</dt>\n"
            . "          <dd class=\"font-medium text-slate-800 break-all\">{{ \$row['{$v['name']}'] ?? '—' }}</dd>\n"
            . "        </div>\n";
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', '{$masterName} #' . \$row['id'])
@section('page_title', 'Detalle')

@php
  // Cargar líneas de detalle vinculadas por FK
  \$detail_rows = class_exists('{$detailName}Model')
    ? {$detailName}Model::query('SELECT * FROM ' . {$detailName}Model::\$t1 . ' WHERE {$fkColumn} = :id ORDER BY id ASC', ['id' => \$row['id']]) ?: []
    : [];
@endphp

@section('content')
<div class="space-y-4">

  <div class="flex items-center justify-between">
    <a href="{$masterName}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
    <div class="flex items-center gap-2">
      <a href="{$masterName}/editar/{{ \$row['id'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
        <i class="ri-edit-line"></i> Editar
      </a>
      <a href="{{ build_url('{$masterName}/borrar/' . \$row['id']) }}" onclick="return confirm('¿Eliminar este registro?')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
        <i class="ri-delete-bin-line"></i> Eliminar
      </a>
    </div>
  </div>

  {{-- Card del master --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100">
      <h3 class="font-semibold text-slate-800">Registro #{{ \$row['id'] }}</h3>
    </div>
    <dl>
{$rows}      <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4 border-t border-slate-100">
        <dt class="sm:w-1/3 text-slate-500">Creado</dt>
        <dd class="font-medium text-slate-800">{{ !empty(\$row['created_at']) ? date('d/m/Y H:i', strtotime(\$row['created_at'])) : '—' }}</dd>
      </div>
    </dl>
  </div>

  {{-- Líneas de detalle --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
      <h3 class="font-semibold text-slate-800 flex items-center gap-2">
        <i class="ri-list-check-2 text-primary"></i> Líneas de {$detailName}
        <span class="inline-flex items-center justify-center min-w-[1.5rem] px-1.5 py-0.5 rounded-full bg-slate-100 text-xs font-medium">{{ count(\$detail_rows) }}</span>
      </h3>
      <a href="{{ '{$detailName}/crear?{$fkColumn}=' . \$row['id'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg btn-primary text-sm font-semibold">
        <i class="ri-add-line"></i> Agregar línea
      </a>
    </div>

    @if(empty(\$detail_rows))
      <div class="p-8 text-center text-sm text-slate-500">
        No hay líneas. Agrega la primera con el botón superior.
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>
              <th class="text-left px-5 py-2 font-semibold">ID</th>
              @foreach(array_keys(\$detail_rows[0] ?? []) as \$col)
                @if(!in_array(\$col, ['id', '{$fkColumn}', 'created_at', 'updated_at']))
                  <th class="text-left px-5 py-2 font-semibold">{{ ucfirst(str_replace('_', ' ', \$col)) }}</th>
                @endif
              @endforeach
              <th class="px-5 py-2 w-20"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach(\$detail_rows as \$line)
              <tr class="hover:bg-slate-50/60">
                <td class="px-5 py-2 font-mono text-xs text-slate-400">#{{ \$line['id'] }}</td>
                @foreach(array_keys(\$line) as \$col)
                  @if(!in_array(\$col, ['id', '{$fkColumn}', 'created_at', 'updated_at']))
                    <td class="px-5 py-2 text-slate-700">{{ \$line[\$col] ?? '—' }}</td>
                  @endif
                @endforeach
                <td class="px-5 py-2 text-right whitespace-nowrap">
                  <a href="{$detailName}/editar/{{ \$line['id'] }}" class="text-slate-500 hover:text-primary text-xs mr-2"><i class="ri-edit-line"></i></a>
                  <a href="{{ build_url('{$detailName}/borrar/' . \$line['id']) }}" onclick="return confirm('¿Eliminar esta línea?')" class="text-red-500 hover:text-red-700 text-xs"><i class="ri-delete-bin-line"></i></a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection

BLADE;
  }

  // ============================================================
  //  Validaciones
  // ============================================================

  private function validateName(string $name, string $context): string
  {
    $name = strtolower(trim($name));
    if (!preg_match('/^[a-z][a-z0-9_]{1,49}$/', $name)) {
      throw new Exception(sprintf(
        'Nombre de %s inválido "%s". Debe empezar con letra, solo minúsculas, números y guiones bajos, 2-50 caracteres.',
        $context, $name
      ));
    }
    if (in_array($name, self::RESERVED_NAMES, true)) {
      throw new Exception(sprintf('Nombre "%s" reservado por el core.', $name));
    }
    return $name;
  }

  private function validateTable(string $table): string
  {
    $table = strtolower(trim($table));
    if (!preg_match('/^[a-z][a-z0-9_]{1,59}$/', $table)) {
      throw new Exception('Nombre de tabla inválido. Solo minúsculas, números y _ (2-60 caracteres).');
    }
    return $table;
  }

  private function validateField(array $field): array
  {
    $fname = $field['name'] ?? '';
    if (!preg_match('/^[a-z][a-z0-9_]{0,49}$/', $fname)) {
      throw new Exception(sprintf('Nombre de campo inválido: "%s"', $fname));
    }
    if (in_array($fname, ['id', 'created_at', 'updated_at'], true)) {
      throw new Exception(sprintf('El campo "%s" se genera automáticamente, no lo incluyas.', $fname));
    }

    $type = $field['type'] ?? 'string';
    if (!isset(self::FIELD_TYPES[$type])) {
      throw new Exception(sprintf('Tipo de campo desconocido: "%s"', $type));
    }

    // width: 1|2|3|4 columnas de 4 (default 2 = half)
    $width = (int) ($field['width'] ?? 2);
    if ($width < 1 || $width > 4) $width = 2;

    $out = [
      'name'     => $fname,
      'type'     => $type,
      'length'   => (int) ($field['length']   ?? 255),
      'width'    => $width,
      'required' => !empty($field['required']),
      'unique'   => !empty($field['unique']),
    ];

    // Metadata específica de select
    if ($type === 'select') {
      $out['select_source']    = in_array($field['select_source'] ?? 'static', ['static', 'table'], true)
                                 ? $field['select_source'] : 'static';
      $out['select_table']     = preg_match('/^[a-zA-Z0-9_]{0,64}$/', $field['select_table'] ?? '')
                                 ? ($field['select_table'] ?? '') : '';
      $out['select_value_col'] = preg_match('/^[a-zA-Z0-9_]{0,64}$/', $field['select_value_col'] ?? '')
                                 ? ($field['select_value_col'] ?? '') : '';
      $out['select_label_col'] = preg_match('/^[a-zA-Z0-9_]{0,64}$/', $field['select_label_col'] ?? '')
                                 ? ($field['select_label_col'] ?? '') : '';

      // Opciones estáticas: acepta string (una por línea) o array
      $rawOpts = $field['static_options'] ?? ($field['select_options'] ?? []);
      if (is_string($rawOpts)) {
        $rawOpts = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawOpts))));
      }
      $out['static_options'] = is_array($rawOpts) ? $rawOpts : [];
    }

    return $out;
  }

  // ============================================================
  //  Builders SQL
  // ============================================================

  private function buildFieldsSql(array $fields): string
  {
    $lines = [];
    $uniques = [];

    foreach ($fields as $raw) {
      $f = $this->validateField($raw);

      $sqlType = self::FIELD_TYPES[$f['type']];
      if (strpos($sqlType, '%d') !== false) {
        $sqlType = sprintf($sqlType, max(1, min(65535, $f['length'])));
      }

      // Select con source=table → es una FK, almacenar como int(11)
      if ($f['type'] === 'select' && ($f['select_source'] ?? 'static') === 'table') {
        $sqlType = 'int(11)';
      }

      $null = $f['required'] ? 'NOT NULL' : 'DEFAULT NULL';

      // Defaults sensatos por tipo
      if (!$f['required']) {
        switch ($f['type']) {
          case 'boolean': $null = "DEFAULT 0"; break;
        }
      }

      $lines[] = sprintf('            `%s` %s %s', $f['name'], $sqlType, $null);

      if ($f['unique']) {
        $uniques[] = sprintf('            UNIQUE KEY `%s_unique` (`%s`)', $f['name'], $f['name']);
      }
    }

    $parts = [
      '            `id` int(11) NOT NULL AUTO_INCREMENT',
    ];
    $parts = array_merge($parts, $lines);
    $parts[] = '            `created_at` datetime DEFAULT current_timestamp()';
    $parts[] = '            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()';
    $parts[] = '            PRIMARY KEY (`id`)';

    if ($uniques) {
      $parts = array_merge($parts, $uniques);
    }

    return implode(",\n", $parts);
  }

  // ============================================================
  //  Templates
  // ============================================================

  private function modelTemplate(string $name, string $table): string
  {
    return <<<PHP
<?php

/**
 * Modelo generado por QuetzalCrudGenerator.
 */
class {$name}Model extends Model
{
  public static \$t1 = '{$table}';

  static function all()
  {
    \$sql = sprintf('SELECT * FROM %s ORDER BY id DESC', self::\$t1);
    return parent::query(\$sql) ?: [];
  }

  static function all_paginated(int \$perPage = 15)
  {
    \$sql = sprintf('SELECT * FROM %s ORDER BY id DESC', self::\$t1);
    return PaginationHandler::paginate(\$sql, [], \$perPage);
  }

  static function by_id(int \$id)
  {
    \$sql = sprintf('SELECT * FROM %s WHERE id = :id LIMIT 1', self::\$t1);
    \$rows = parent::query(\$sql, ['id' => \$id]);
    return \$rows ? \$rows[0] : [];
  }

  static function insertOne(array \$data)
  {
    return parent::add(self::\$t1, \$data);
  }

  static function updateById(int \$id, array \$data)
  {
    return parent::update(self::\$t1, ['id' => \$id], \$data);
  }

  static function deleteById(int \$id)
  {
    return parent::remove(self::\$t1, ['id' => \$id]);
  }
}

PHP;
  }

  private function migrationTemplate(string $table, string $fieldsSql): string
  {
    return <<<PHP
<?php

/**
 * Migración generada por QuetzalCrudGenerator.
 */
return new class {
    public function up(PDO \$pdo): void {
        \$pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$table}` (
{$fieldsSql}
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO \$pdo): void {
        \$pdo->exec("DROP TABLE IF EXISTS `{$table}`");
    }
};

PHP;
  }

  private function controllerTemplate(string $name, string $table): string
  {
    $singular = $name;
    $model    = $name . 'Model';

    return <<<PHP
<?php

/**
 * Controlador generado por QuetzalCrudGenerator.
 * Ruta base: /{$singular}
 */
class {$singular}Controller extends Controller implements ControllerInterface
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
    \$q = sanitize_input((string)(\$_GET['q'] ?? ''));
    \$where = ''; \$params = [];
    if (\$q !== '') {
      \$where = "WHERE id = :id_q"; // Personaliza los campos de búsqueda
      \$params['id_q'] = \$q;
    }

    \$sql  = sprintf('SELECT * FROM %s %s ORDER BY id DESC', {$model}::\$t1, \$where);
    \$rows = PaginationHandler::paginate(\$sql, \$params, 15);

    \$this->setTitle('{$singular}');
    \$this->addToData('rows', \$rows);
    \$this->addToData('filters', ['q' => \$q]);
    \$this->setView('index');
    \$this->render();
  }

  function crear()
  {
    \$this->setTitle('Nuevo {$singular}');
    \$this->setView('crear');
    \$this->render();
  }

  function post_crear()
  {
    try {
      if (!Csrf::validate(\$_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      // TODO: validaciones según los campos de tu tabla
      array_map('sanitize_input', \$_POST);

      \$data = \$_POST;
      unset(\$data['csrf'], \$data['redirect_to'], \$data['timecheck']);
      \$data['created_at'] = now();

      if (!\$id = {$model}::insertOne(\$data)) {
        throw new Exception('No se pudo crear el registro.');
      }

      Flasher::success('Registro creado con éxito.');
      Redirect::to('{$singular}/ver/' . \$id);

    } catch (Exception \$e) {
      Flasher::error(\$e->getMessage());
      Redirect::back();
    }
  }

  function ver(\$id = null)
  {
    \$row = {$model}::by_id((int) \$id);
    if (empty(\$row)) {
      Flasher::error('Registro no encontrado.');
      Redirect::to('{$singular}');
      exit;
    }

    \$this->setTitle('{$singular} #' . \$id);
    \$this->addToData('row', \$row);
    \$this->setView('ver');
    \$this->render();
  }

  function editar(\$id = null)
  {
    \$row = {$model}::by_id((int) \$id);
    if (empty(\$row)) {
      Flasher::error('Registro no encontrado.');
      Redirect::to('{$singular}');
      exit;
    }

    \$this->setTitle('Editar {$singular}');
    \$this->addToData('row', \$row);
    \$this->setView('editar');
    \$this->render();
  }

  function post_editar()
  {
    try {
      if (!Csrf::validate(\$_POST['csrf'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      \$id = (int) (\$_POST['id'] ?? 0);
      \$row = {$model}::by_id(\$id);
      if (empty(\$row)) throw new Exception('Registro no encontrado.');

      array_map('sanitize_input', \$_POST);
      \$data = \$_POST;
      unset(\$data['csrf'], \$data['redirect_to'], \$data['timecheck'], \$data['id']);

      if (!{$model}::updateById(\$id, \$data)) {
        throw new Exception('No se pudo actualizar el registro.');
      }

      Flasher::success('Registro actualizado.');
      Redirect::to('{$singular}/ver/' . \$id);

    } catch (Exception \$e) {
      Flasher::error(\$e->getMessage());
      Redirect::back();
    }
  }

  function borrar(\$id = null)
  {
    try {
      if (!Csrf::validate(\$_GET['_t'] ?? '')) {
        throw new Exception(get_quetzal_message(0));
      }

      \$id = (int) \$id;
      \$row = {$model}::by_id(\$id);
      if (empty(\$row)) throw new Exception('Registro no encontrado.');

      if (!{$model}::deleteById(\$id)) {
        throw new Exception('No se pudo eliminar el registro.');
      }

      Flasher::success('Registro eliminado.');
      Redirect::to('{$singular}');

    } catch (Exception \$e) {
      Flasher::error(\$e->getMessage());
      Redirect::back();
    }
  }
}

PHP;
  }

  private function viewIndexTemplate(string $name, array $fields): string
  {
    $headers      = '<th class="text-left px-5 py-3 font-semibold">ID</th>';
    $cells        = '<td class="px-5 py-3 text-slate-400 font-mono text-xs">#{{ $r[\'id\'] }}</td>';
    $lookupBlocks = '';

    foreach ($fields as $f) {
      $v     = $this->validateField($f);
      $fname = $v['name'];
      $label = ucfirst(str_replace('_', ' ', $fname));
      $headers .= "\n              <th class=\"text-left px-5 py-3 font-semibold\">{$label}</th>";

      // Select con fuente de tabla: resolver label via lookup batched (una query por campo)
      $isSelectTable = (($v['type'] ?? '') === 'select'
        && ($v['select_source'] ?? 'static') === 'table'
        && !empty($v['select_table'])
        && !empty($v['select_value_col'])
        && !empty($v['select_label_col']));

      if ($isSelectTable) {
        $st = $v['select_table'];
        $vc = $v['select_value_col'];
        $lc = $v['select_label_col'];

        $lookupBlocks .= <<<PHP

  // Lookup {$fname} → {$st}.{$lc}
  \$__ids = array_values(array_unique(array_filter(
    array_column(\$__rows, '{$fname}'),
    fn(\$x) => \$x !== null && \$x !== ''
  )));
  \$lookups['{$fname}'] = [];
  if (!empty(\$__ids)) {
    \$__ph = implode(',', array_fill(0, count(\$__ids), '?'));
    \$__rs = Model::query(
      'SELECT `{$vc}` AS v, `{$lc}` AS l FROM `{$st}` WHERE `{$vc}` IN (' . \$__ph . ')',
      array_values(\$__ids)
    );
    if (is_array(\$__rs)) {
      foreach (\$__rs as \$__row) \$lookups['{$fname}'][\$__row['v']] = \$__row['l'];
    }
  }

PHP;

        // Celda con fallback: label → valor crudo → guion
        $cells .= "\n                <td class=\"px-5 py-3 text-slate-600\">{{ \$lookups['{$fname}'][\$r['{$fname}']] ?? (\$r['{$fname}'] ?? '—') }}</td>";
      } elseif (($v['type'] ?? '') === 'boolean') {
        $cells .= "\n                <td class=\"px-5 py-3\">@if(!empty(\$r['{$fname}']))<span class=\"inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs\"><i class=\"ri-check-line\"></i> Sí</span>@else<span class=\"inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs\"><i class=\"ri-close-line\"></i> No</span>@endif</td>";
      } else {
        $cells .= "\n                <td class=\"px-5 py-3 text-slate-600\">{{ \$r['{$fname}'] ?? '—' }}</td>";
      }
    }

    // Wrapper del bloque @php (solo si hay selects con lookup)
    $lookupSection = '';
    if ($lookupBlocks !== '') {
      $lookupSection = "@php\n  \$__rows = \$rows['rows'] ?? [];\n  \$lookups = [];{$lookupBlocks}@endphp\n\n";
    } else {
      $lookupSection = "@php \$lookups = []; @endphp\n\n";
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', '{$name}')
@section('page_title', ucfirst('{$name}'))

@section('content')
{$lookupSection}<div class="space-y-4">

  <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-2">
      <h2 class="font-semibold text-slate-800">Listado</h2>
      <span class="inline-flex items-center justify-center min-w-[1.75rem] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">{{ \$rows['total'] ?? 0 }}</span>
    </div>
    <a href="{$name}/crear" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
      <i class="ri-add-line"></i> Nuevo
    </a>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if(empty(\$rows['rows']))
      <div class="p-12 text-center">
        <i class="ri-inbox-line text-5xl text-slate-300 mb-2 block"></i>
        <p class="text-sm text-slate-500">No hay registros todavía.</p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-xs uppercase tracking-wider text-slate-500">
              {$headers}
              <th class="px-5 py-3 font-semibold w-12"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach(\$rows['rows'] as \$r)
              <tr class="hover:bg-slate-50/60 transition">
                {$cells}
                <td class="px-3 py-3 text-right">
                  <div class="hs-dropdown relative inline-flex">
                    <button type="button" class="hs-dropdown-toggle inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100">
                      <i class="ri-more-2-fill"></i>
                    </button>
                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[9rem] bg-white shadow-lg rounded-xl p-1 mt-2 border border-slate-200 z-20">
                      <a href="{$name}/ver/{{ \$r['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100"><i class="ri-eye-line text-slate-400"></i> Ver</a>
                      <a href="{$name}/editar/{{ \$r['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100"><i class="ri-edit-line text-slate-400"></i> Editar</a>
                      <div class="border-t border-slate-100 my-1"></div>
                      <a href="{{ build_url('{$name}/borrar/' . \$r['id']) }}" onclick="return confirm('¿Eliminar este registro?')" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-red-50 text-red-600"><i class="ri-delete-bin-line"></i> Eliminar</a>
                    </div>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if(!empty(\$rows['pagination']))
        <div class="px-5 py-3 border-t border-slate-100">{!! \$rows['pagination'] !!}</div>
      @endif
    @endif
  </div>
</div>
@endsection

BLADE;
  }

  /**
   * Genera el HTML del input para un campo dado (crear=true o editar=false).
   * Envuelto en un div con sm:col-span-N según width.
   */
  private function renderFieldInput(array $v, bool $isEdit = false, string $varName = 'row'): string
  {
    $label    = ucfirst(str_replace('_', ' ', $v['name']));
    $req      = $v['required'] ? 'required' : '';
    $star     = ($v['required'] && !$isEdit) ? ' <span class="text-red-500">*</span>' : '';
    $colSpan  = 'sm:col-span-' . $v['width'];
    $valueAttr= $isEdit ? " value=\"{{ \$row['{$v['name']}'] ?? '' }}\"" : '';

    if ($v['type'] === 'text') {
      $content = $isEdit ? "{{ \$row['{$v['name']}'] ?? '' }}" : '';
      return "      <div class=\"{$colSpan}\">\n"
           . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
           . "        <textarea name=\"{$v['name']}\" rows=\"3\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">{$content}</textarea>\n"
           . "      </div>\n";
    }
    if ($v['type'] === 'boolean') {
      $checked = $isEdit ? " @if(!empty(\$row['{$v['name']}'])) checked @endif" : '';
      return "      <div class=\"{$colSpan}\">\n"
           . "        <label class=\"flex items-center gap-2 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer h-full\">\n"
           . "          <input type=\"checkbox\" name=\"{$v['name']}\" value=\"1\"{$checked} class=\"rounded border-slate-300 text-primary focus:ring-primary\">\n"
           . "          <span class=\"text-sm font-medium\">{$label}</span>\n"
           . "        </label>\n"
           . "      </div>\n";
    }
    if (in_array($v['type'], ['int', 'bigint', 'decimal'])) {
      $step = $v['type'] === 'decimal' ? '0.01' : '1';
      return "      <div class=\"{$colSpan}\">\n"
           . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
           . "        <input type=\"number\" name=\"{$v['name']}\" step=\"{$step}\"{$valueAttr} {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
           . "      </div>\n";
    }
    if ($v['type'] === 'date' || $v['type'] === 'datetime') {
      $type = $v['type'] === 'date' ? 'date' : 'datetime-local';
      return "      <div class=\"{$colSpan}\">\n"
           . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
           . "        <input type=\"{$type}\" name=\"{$v['name']}\"{$valueAttr} {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
           . "      </div>\n";
    }
    if ($v['type'] === 'select') {
      return $this->renderSelectInput($v, $isEdit, $colSpan, $label, $star, $req);
    }
    // string (default)
    return "      <div class=\"{$colSpan}\">\n"
         . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
         . "        <input type=\"text\" name=\"{$v['name']}\" maxlength=\"{$v['length']}\"{$valueAttr} {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
         . "      </div>\n";
  }

  /**
   * Renderiza un campo <select>. Dos modos:
   *   - source=static: opciones literales hardcodeadas en el template
   *   - source=table:  un @php inline que carga las opciones del schema al render
   */
  private function renderSelectInput(array $v, bool $isEdit, string $colSpan, string $label, string $star, string $req): string
  {
    $name      = $v['name'];
    $currentPhp= $isEdit ? "\$row['{$name}'] ?? ''" : "''";

    if (($v['select_source'] ?? 'static') === 'table'
        && !empty($v['select_table'])
        && !empty($v['select_value_col'])
        && !empty($v['select_label_col'])
    ) {
      $table   = $v['select_table'];
      $valCol  = $v['select_value_col'];
      $labelCol= $v['select_label_col'];

      // @php inline que carga las opciones al render. No requiere tocar el controller.
      $phpBlock = "        @php\n"
                . "          \$__opts_{$name} = class_exists('Model')\n"
                . "            ? Model::query('SELECT `{$valCol}`, `{$labelCol}` FROM `{$table}` ORDER BY `{$labelCol}` ASC') ?: []\n"
                . "            : [];\n"
                . "          \$__cur_{$name} = {$currentPhp};\n"
                . "        @endphp\n";

      $optionsBlock = "          <option value=\"\">— elige —</option>\n"
                    . "          @foreach(\$__opts_{$name} as \$__opt)\n"
                    . "            <option value=\"{{ \$__opt['{$valCol}'] }}\" @if((string) \$__opt['{$valCol}'] === (string) \$__cur_{$name}) selected @endif>{{ \$__opt['{$labelCol}'] }}</option>\n"
                    . "          @endforeach\n";

      return "      <div class=\"{$colSpan}\">\n"
           . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
           . $phpBlock
           . "        <select name=\"{$name}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
           . $optionsBlock
           . "        </select>\n"
           . "        <p class=\"text-xs text-slate-400 mt-1\">Opciones desde <code>{$table}</code>.</p>\n"
           . "      </div>\n";
    }

    // Modo static — opciones hardcodeadas
    $options = $v['static_options'] ?? [];
    if (empty($options)) $options = ['opcion 1', 'opcion 2'];

    $optionsHtml = "          <option value=\"\">— elige —</option>\n";
    foreach ($options as $opt) {
      $optEsc = htmlspecialchars($opt, ENT_QUOTES);
      $optionsHtml .= "          <option value=\"{$optEsc}\" @if((string)({$currentPhp}) === '{$optEsc}') selected @endif>{$optEsc}</option>\n";
    }

    return "      <div class=\"{$colSpan}\">\n"
         . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
         . "        <select name=\"{$name}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
         . $optionsHtml
         . "        </select>\n"
         . "      </div>\n";
  }

  private function viewCrearTemplate(string $name, array $fields): string
  {
    $inputs = '';
    foreach ($fields as $f) {
      $v = $this->validateField($f);
      $inputs .= $this->renderFieldInput($v, false);
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', 'Nuevo {$name}')
@section('page_title', 'Nuevo {$name}')

@section('content')
<div class="space-y-4">
  <div>
    <a href="{$name}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
  </div>

  <form method="post" action="{$name}/post_crear" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-5">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
{$inputs}    </div>
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{$name}" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Crear
      </button>
    </div>
  </form>
</div>
@endsection

BLADE;
  }

  private function viewEditarTemplate(string $name, array $fields): string
  {
    $inputs = '';
    foreach ($fields as $f) {
      $v = $this->validateField($f);
      $inputs .= $this->renderFieldInput($v, true);
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', 'Editar {$name} #' . \$row['id'])
@section('page_title', 'Editar {$name}')

@section('content')
<div class="space-y-4">
  <div>
    <a href="{$name}/ver/{{ \$row['id'] }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al detalle
    </a>
  </div>

  <form method="post" action="{$name}/post_editar" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-5">
    @csrf
    <input type="hidden" name="id" value="{{ \$row['id'] }}">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
{$inputs}    </div>
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="{$name}/ver/{{ \$row['id'] }}" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Guardar
      </button>
    </div>
  </form>
</div>
@endsection

BLADE;
  }

  private function viewVerTemplate(string $name, array $fields): string
  {
    $rows         = '';
    $lookupBlocks = '';

    foreach ($fields as $f) {
      $v     = $this->validateField($f);
      $fname = $v['name'];
      $label = ucfirst(str_replace('_', ' ', $fname));

      $isSelectTable = (($v['type'] ?? '') === 'select'
        && ($v['select_source'] ?? 'static') === 'table'
        && !empty($v['select_table'])
        && !empty($v['select_value_col'])
        && !empty($v['select_label_col']));

      if ($isSelectTable) {
        $st = $v['select_table'];
        $vc = $v['select_value_col'];
        $lc = $v['select_label_col'];

        $lookupBlocks .= <<<PHP

  // Lookup {$fname} → {$st}.{$lc}
  \$lookups['{$fname}'] = null;
  if (!empty(\$row['{$fname}'])) {
    \$__r = Model::query(
      'SELECT `{$lc}` AS l FROM `{$st}` WHERE `{$vc}` = ? LIMIT 1',
      [\$row['{$fname}']]
    );
    if (is_array(\$__r) && isset(\$__r[0]['l'])) \$lookups['{$fname}'] = \$__r[0]['l'];
  }

PHP;

        $rows .= "        <div class=\"flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4 border-b border-slate-100 last:border-0\">\n"
              . "          <dt class=\"sm:w-1/3 text-slate-500\">{$label}</dt>\n"
              . "          <dd class=\"font-medium text-slate-800 break-all\">{{ \$lookups['{$fname}'] ?? (\$row['{$fname}'] ?? '—') }}"
              . "            @if(!empty(\$lookups['{$fname}']) && !empty(\$row['{$fname}']))<span class=\"ml-2 text-xs text-slate-400 font-mono\">#{{ \$row['{$fname}'] }}</span>@endif\n"
              . "          </dd>\n"
              . "        </div>\n";
      } elseif (($v['type'] ?? '') === 'boolean') {
        $rows .= "        <div class=\"flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4 border-b border-slate-100 last:border-0\">\n"
              . "          <dt class=\"sm:w-1/3 text-slate-500\">{$label}</dt>\n"
              . "          <dd>@if(!empty(\$row['{$fname}']))<span class=\"inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs\"><i class=\"ri-check-line\"></i> Sí</span>@else<span class=\"inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs\"><i class=\"ri-close-line\"></i> No</span>@endif</dd>\n"
              . "        </div>\n";
      } else {
        $rows .= "        <div class=\"flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4 border-b border-slate-100 last:border-0\">\n"
              . "          <dt class=\"sm:w-1/3 text-slate-500\">{$label}</dt>\n"
              . "          <dd class=\"font-medium text-slate-800 break-all\">{{ \$row['{$fname}'] ?? '—' }}</dd>\n"
              . "        </div>\n";
      }
    }

    $lookupSection = '';
    if ($lookupBlocks !== '') {
      $lookupSection = "@php\n  \$lookups = [];{$lookupBlocks}@endphp\n\n";
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', '{$name} #' . \$row['id'])
@section('page_title', 'Detalle')

@section('content')
{$lookupSection}<div class="space-y-4">

  <div class="flex items-center justify-between">
    <a href="{$name}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
    <div class="flex items-center gap-2">
      <a href="{$name}/editar/{{ \$row['id'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
        <i class="ri-edit-line"></i> Editar
      </a>
      <a href="{{ build_url('{$name}/borrar/' . \$row['id']) }}" onclick="return confirm('¿Eliminar este registro?')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
        <i class="ri-delete-bin-line"></i> Eliminar
      </a>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b border-slate-100">
      <h3 class="font-semibold text-slate-800">Registro #{{ \$row['id'] }}</h3>
    </div>
    <dl>
{$rows}
      <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4 border-t border-slate-100">
        <dt class="sm:w-1/3 text-slate-500">Creado</dt>
        <dd class="font-medium text-slate-800">{{ !empty(\$row['created_at']) ? date('d/m/Y H:i', strtotime(\$row['created_at'])) : '—' }}</dd>
      </div>
    </dl>
  </div>
</div>
@endsection

BLADE;
  }

  // ============================================================
  //  Helpers internos
  // ============================================================

  private function write(string $file, string $content, string $displayPath): array
  {
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
      return $this->err('No se pudo crear el directorio: ' . $dir);
    }

    if (@file_put_contents($file, $content) === false) {
      return $this->err('No se pudo escribir: ' . $displayPath);
    }

    return [
      'ok'      => true,
      'path'    => $displayPath,
      'message' => 'Creado: ' . $displayPath,
    ];
  }

  private function err(string $msg): array
  {
    return ['ok' => false, 'path' => null, 'message' => $msg];
  }
}

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

    return [
      'name'     => $fname,
      'type'     => $type,
      'length'   => (int) ($field['length']   ?? 255),
      'width'    => $width,
      'required' => !empty($field['required']),
      'unique'   => !empty($field['unique']),
    ];
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
    $headers = '<th class="text-left px-5 py-3 font-semibold">ID</th>';
    $cells   = '<td class="px-5 py-3 text-slate-400 font-mono text-xs">#{{ $r[\'id\'] }}</td>';

    foreach ($fields as $f) {
      $fname = $this->validateField($f)['name'];
      $label = ucfirst(str_replace('_', ' ', $fname));
      $headers .= "\n              <th class=\"text-left px-5 py-3 font-semibold\">{$label}</th>";
      $cells   .= "\n                <td class=\"px-5 py-3 text-slate-600\">{{ \$r['{$fname}'] ?? '—' }}</td>";
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', '{$name}')
@section('page_title', ucfirst('{$name}'))

@section('content')
<div class="space-y-4">

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

  private function viewCrearTemplate(string $name, array $fields): string
  {
    $inputs = '';
    foreach ($fields as $f) {
      $v     = $this->validateField($f);
      $label = ucfirst(str_replace('_', ' ', $v['name']));
      $req   = $v['required'] ? 'required' : '';
      $star  = $v['required'] ? ' <span class="text-red-500">*</span>' : '';

      if ($v['type'] === 'text') {
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
                . "        <textarea name=\"{$v['name']}\" rows=\"3\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\"></textarea>\n"
                . "      </div>\n";
      } elseif ($v['type'] === 'boolean') {
        $inputs .= "      <div>\n"
                . "        <label class=\"flex items-center gap-2 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer\">\n"
                . "          <input type=\"checkbox\" name=\"{$v['name']}\" value=\"1\" class=\"rounded border-slate-300 text-primary focus:ring-primary\">\n"
                . "          <span class=\"text-sm font-medium\">{$label}</span>\n"
                . "        </label>\n"
                . "      </div>\n";
      } elseif (in_array($v['type'], ['int', 'bigint', 'decimal'])) {
        $step = $v['type'] === 'decimal' ? '0.01' : '1';
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
                . "        <input type=\"number\" name=\"{$v['name']}\" step=\"{$step}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
                . "      </div>\n";
      } elseif ($v['type'] === 'date' || $v['type'] === 'datetime') {
        $type = $v['type'] === 'date' ? 'date' : 'datetime-local';
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
                . "        <input type=\"{$type}\" name=\"{$v['name']}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
                . "      </div>\n";
      } else {
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}{$star}</label>\n"
                . "        <input type=\"text\" name=\"{$v['name']}\" maxlength=\"{$v['length']}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
                . "      </div>\n";
      }
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', 'Nuevo {$name}')
@section('page_title', 'Nuevo {$name}')

@section('content')
<div class="max-w-3xl space-y-4">
  <div>
    <a href="{$name}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
  </div>

  <form method="post" action="{$name}/post_crear" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-5">
    @csrf
{$inputs}
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
      $v     = $this->validateField($f);
      $label = ucfirst(str_replace('_', ' ', $v['name']));
      $req   = $v['required'] ? 'required' : '';

      if ($v['type'] === 'text') {
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}</label>\n"
                . "        <textarea name=\"{$v['name']}\" rows=\"3\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">{{ \$row['{$v['name']}'] ?? '' }}</textarea>\n"
                . "      </div>\n";
      } elseif ($v['type'] === 'boolean') {
        $inputs .= "      <div>\n"
                . "        <label class=\"flex items-center gap-2 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer\">\n"
                . "          <input type=\"checkbox\" name=\"{$v['name']}\" value=\"1\" @if(!empty(\$row['{$v['name']}'])) checked @endif class=\"rounded border-slate-300 text-primary focus:ring-primary\">\n"
                . "          <span class=\"text-sm font-medium\">{$label}</span>\n"
                . "        </label>\n"
                . "      </div>\n";
      } elseif (in_array($v['type'], ['int', 'bigint', 'decimal'])) {
        $step = $v['type'] === 'decimal' ? '0.01' : '1';
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}</label>\n"
                . "        <input type=\"number\" name=\"{$v['name']}\" step=\"{$step}\" value=\"{{ \$row['{$v['name']}'] ?? '' }}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
                . "      </div>\n";
      } elseif ($v['type'] === 'date' || $v['type'] === 'datetime') {
        $type = $v['type'] === 'date' ? 'date' : 'datetime-local';
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}</label>\n"
                . "        <input type=\"{$type}\" name=\"{$v['name']}\" value=\"{{ \$row['{$v['name']}'] ?? '' }}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
                . "      </div>\n";
      } else {
        $inputs .= "      <div>\n"
                . "        <label class=\"block text-sm font-medium text-slate-700 mb-1.5\">{$label}</label>\n"
                . "        <input type=\"text\" name=\"{$v['name']}\" maxlength=\"{$v['length']}\" value=\"{{ \$row['{$v['name']}'] ?? '' }}\" {$req} class=\"w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm\">\n"
                . "      </div>\n";
      }
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', 'Editar {$name} #' . \$row['id'])
@section('page_title', 'Editar {$name}')

@section('content')
<div class="max-w-3xl space-y-4">
  <div>
    <a href="{$name}/ver/{{ \$row['id'] }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al detalle
    </a>
  </div>

  <form method="post" action="{$name}/post_editar" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-5">
    @csrf
    <input type="hidden" name="id" value="{{ \$row['id'] }}">
{$inputs}
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
    $rows = '';
    foreach ($fields as $f) {
      $v     = $this->validateField($f);
      $label = ucfirst(str_replace('_', ' ', $v['name']));
      $rows .= "        <div class=\"flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4 border-b border-slate-100 last:border-0\">\n"
            . "          <dt class=\"sm:w-1/3 text-slate-500\">{$label}</dt>\n"
            . "          <dd class=\"font-medium text-slate-800 break-all\">{{ \$row['{$v['name']}'] ?? '—' }}</dd>\n"
            . "        </div>\n";
    }

    return <<<BLADE
@extends('includes.admin.layout')

@section('title', '{$name} #' . \$row['id'])
@section('page_title', 'Detalle')

@section('content')
<div class="max-w-3xl space-y-4">

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

<?php

/**
 * Tests de las extensiones nuevas del QuetzalCrudGenerator:
 *  - Tipos: image, color, range, tags, richtext
 *  - Metadata genérica: placeholder, help_text, default_value
 *  - Validaciones: min, max, min_length, regex, step
 *  - Toggles globales: soft_delete, search, export_csv
 */

foreach (['MODELS', 'CONTROLLERS', 'VIEWS', 'APP', 'DS', 'PLUGINS_PATH', 'ROOT'] as $c) {
  if (!defined($c)) define($c, sys_get_temp_dir() . DIRECTORY_SEPARATOR);
}

require_once __DIR__ . '/../../app/classes/QuetzalCrudGenerator.php';

if (!function_exists('vcb_invoke2')) {
  function vcb_invoke2(string $method, array $args = [], ?array $globalOpts = null) {
    $gen = new QuetzalCrudGenerator(['target' => 'core']);
    if ($globalOpts !== null) $gen->setGlobalOptions($globalOpts);
    $rm = new ReflectionMethod($gen, $method);
    $rm->setAccessible(true);
    return $rm->invoke($gen, ...$args);
  }
}

return [

  // =========== Tipos nuevos ===========
  'tipo image: validateField asigna file_accept=image/*' => function () {
    $out = vcb_invoke2('validateField', [['type' => 'image', 'name' => 'avatar']]);
    q_assert_eq('image', $out['type']);
    q_assert_eq('image/*', $out['file_accept']);
  },

  'tipo image: renderFieldInput emite input file con accept=image/*' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'image', 'name' => 'avatar']]),
      false,
    ]);
    q_assert_contains('type="file"', $html);
    q_assert_contains('accept="image/*"', $html);
  },

  'tipo color: emite input type=color' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'color', 'name' => 'tema']]),
      false,
    ]);
    q_assert_contains('type="color"', $html);
  },

  'tipo range: defaults min=0, max=100, step=1' => function () {
    $out = vcb_invoke2('validateField', [['type' => 'range', 'name' => 'volumen']]);
    q_assert_eq('0', $out['min']);
    q_assert_eq('100', $out['max']);
    q_assert_eq('1', $out['step']);

    $html = vcb_invoke2('renderFieldInput', [$out, false]);
    q_assert_contains('type="range"', $html);
    q_assert_contains('min="0"', $html);
    q_assert_contains('max="100"', $html);
    q_assert_contains('step="1"', $html);
  },

  'tipo range: respeta min/max/step custom' => function () {
    $out = vcb_invoke2('validateField', [[
      'type' => 'range', 'name' => 'meta', 'min' => '10', 'max' => '200', 'step' => '5',
    ]]);
    q_assert_eq('10',  $out['min']);
    q_assert_eq('200', $out['max']);
    q_assert_eq('5',   $out['step']);
  },

  'tipo tags: input text con maxlength 500' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'tags', 'name' => 'etiquetas']]),
      false,
    ]);
    q_assert_contains('type="text"', $html);
    q_assert_contains('maxlength="500"', $html);
    q_assert_contains('Separá los tags con coma', $html);
  },

  'tipo richtext: textarea con data-richtext=1' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'richtext', 'name' => 'contenido']]),
      false,
    ]);
    q_assert_contains('<textarea', $html);
    q_assert_contains('data-richtext="1"', $html);
    q_assert_contains('rows="6"', $html);
  },

  // =========== Metadata genérica ===========
  'placeholder se renderiza en input text' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'string', 'name' => 'nombre', 'placeholder' => 'Tu nombre']]),
      false,
    ]);
    q_assert_contains('placeholder="Tu nombre"', $html);
  },

  'help_text se renderiza como <p> debajo del input' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'string', 'name' => 'sku', 'help_text' => 'Sin espacios']]),
      false,
    ]);
    q_assert_contains('<p class="text-xs text-slate-500 mt-1">Sin espacios</p>', $html);
  },

  'default_value se aplica cuando no hay valor en row ni URL' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'string', 'name' => 'estado', 'default_value' => 'activo']]),
      false,
    ]);
    // El valor llega como template Blade resuelto en runtime; verificamos el literal
    q_assert_contains("'activo'", $html);
  },

  'default_value en boolean → checkbox checked al crear' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [['type' => 'boolean', 'name' => 'activo', 'default_value' => '1']]),
      false,
    ]);
    q_assert_contains('checked', $html);
  },

  // =========== Validaciones ===========
  'min/max/step se aplican a input number' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [[
        'type' => 'int', 'name' => 'edad', 'min' => '18', 'max' => '99', 'step' => '1',
      ]]),
      false,
    ]);
    q_assert_contains('min="18"', $html);
    q_assert_contains('max="99"', $html);
    q_assert_contains('step="1"', $html);
  },

  'min_length y pattern se aplican a string' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [[
        'type' => 'string', 'name' => 'cui', 'min_length' => 13, 'regex' => '^[0-9]{13}$',
      ]]),
      false,
    ]);
    q_assert_contains('minlength="13"', $html);
    q_assert_contains('pattern="^[0-9]{13}$"', $html);
  },

  'date acepta min/max para rangos de fecha' => function () {
    $html = vcb_invoke2('renderFieldInput', [
      vcb_invoke2('validateField', [[
        'type' => 'date', 'name' => 'cumple', 'min' => '1900-01-01', 'max' => '2099-12-31',
      ]]),
      false,
    ]);
    q_assert_contains('min="1900-01-01"', $html);
    q_assert_contains('max="2099-12-31"', $html);
  },

  // =========== Toggles globales ===========
  'soft_delete: SQL incluye deleted_at en la tabla' => function () {
    $sql = vcb_invoke2('buildFieldsSql', [[
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['soft_delete' => true]);
    q_assert_contains('deleted_at', $sql);
    q_assert_contains('datetime NULL DEFAULT NULL', $sql);
  },

  'soft_delete OFF: SQL no incluye deleted_at' => function () {
    $sql = vcb_invoke2('buildFieldsSql', [[
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['soft_delete' => false]);
    q_assert_eq(false, str_contains($sql, 'deleted_at'));
  },

  'soft_delete: el modelo incluye softDelete() y restore()' => function () {
    $code = vcb_invoke2('modelTemplate', ['x', 'x_table'], ['soft_delete' => true]);
    q_assert_contains('softDelete', $code);
    q_assert_contains('restore', $code);
  },

  'soft_delete: el controller llama a softDelete() en vez de deleteById()' => function () {
    $code = vcb_invoke2('controllerTemplate', ['x', 'x_table', [
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['soft_delete' => true]);
    q_assert_contains('::softDelete(', $code);
  },

  'search: el controller arma WHERE LIKE sobre campos texto' => function () {
    $code = vcb_invoke2('controllerTemplate', ['x', 'x_table', [
      ['type' => 'string', 'name' => 'nombre'],
      ['type' => 'email',  'name' => 'correo'],
      ['type' => 'int',    'name' => 'edad'],   // no debe entrar al LIKE
    ]], ['search' => true]);
    q_assert_contains('`nombre` LIKE :q_q', $code);
    q_assert_contains('`correo` LIKE :q_q', $code);
    q_assert_eq(false, str_contains($code, '`edad` LIKE'), 'int no debe estar en el LIKE');
  },

  'export_csv: el controller agrega método export()' => function () {
    $code = vcb_invoke2('controllerTemplate', ['x', 'x_table', [
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['export_csv' => true]);
    q_assert_contains('function export()', $code);
    q_assert_contains('Content-Type: text/csv', $code);
    q_assert_contains('fputcsv(', $code);
  },

  'export_csv OFF: no hay método export en el controller' => function () {
    $code = vcb_invoke2('controllerTemplate', ['x', 'x_table', [
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['export_csv' => false]);
    q_assert_eq(false, str_contains($code, 'function export()'));
  },

  'export+soft_delete combinados: el WHERE del export filtra deleted_at' => function () {
    $code = vcb_invoke2('controllerTemplate', ['x', 'x_table', [
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['export_csv' => true, 'soft_delete' => true]);
    q_assert_contains('deleted_at', $code);
    q_assert_contains('function export()', $code);
  },

  'viewIndexTemplate con search=true incluye <input name="q">' => function () {
    $blade = vcb_invoke2('viewIndexTemplate', ['x', [
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['search' => true]);
    q_assert_contains('name="q"', $blade);
    q_assert_contains('Buscar...', $blade);
  },

  'viewIndexTemplate con export_csv=true incluye botón Exportar CSV' => function () {
    $blade = vcb_invoke2('viewIndexTemplate', ['x', [
      ['type' => 'string', 'name' => 'nombre'],
    ]], ['export_csv' => true]);
    q_assert_contains('Exportar CSV', $blade);
    q_assert_contains('x/export', $blade);
  },

  // =========== Bug pre-existente que también detectamos ===========
  'isset()→array_key_exists fix: button todavía es válido' => function () {
    $out = vcb_invoke2('validateField', [[
      'type' => 'button', 'name' => 'enviar', 'button_label' => 'OK',
    ]]);
    q_assert_eq('button', $out['type']);
    q_assert_eq('OK', $out['button_label']);
  },
];

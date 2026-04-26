<?php

/**
 * Tests del soporte de grupos (secciones con título) en QuetzalCrudGenerator.
 *
 * Validamos que:
 *  - validateField acepta type=group con title/icon y NO requiere nombre válido de columna
 *  - buildFieldsSql / generateMigration omiten los grupos (no crean columnas)
 *  - renderFieldInput emite un divisor full-width cuando type=group
 *  - viewIndexTemplate omite los grupos en la tabla
 *  - viewVerTemplate los renderiza como divider de sección dentro de <dl>
 */

// Stubs de constantes que el generador toca al construirse (no las usamos en
// los tests pero el constructor las lee).
foreach (['MODELS', 'CONTROLLERS', 'VIEWS', 'APP', 'DS', 'PLUGINS_PATH'] as $c) {
  if (!defined($c)) define($c, sys_get_temp_dir() . DIRECTORY_SEPARATOR);
}

require_once __DIR__ . '/../../app/classes/QuetzalCrudGenerator.php';

// Acceso a métodos privados via reflection
function vcb_invoke(string $method, array $args = []) {
  $gen = new QuetzalCrudGenerator(['target' => 'core']);
  $rm  = new ReflectionMethod($gen, $method);
  $rm->setAccessible(true);
  return $rm->invoke($gen, ...$args);
}

return [

  'validateField acepta group con title' => function () {
    $out = vcb_invoke('validateField', [['type' => 'group', 'title' => 'Datos Personales']]);
    q_assert_eq('group', $out['type']);
    q_assert_eq('Datos Personales', $out['title']);
    q_assert_eq(4, $out['width'], 'grupos siempre full-width');
    q_assert_eq(false, $out['required']);
  },

  'validateField sin title usa "Sección" como default' => function () {
    $out = vcb_invoke('validateField', [['type' => 'group']]);
    q_assert_eq('Sección', $out['title']);
  },

  'validateField permite icono Remix válido en group' => function () {
    $out = vcb_invoke('validateField', [['type' => 'group', 'title' => 'X', 'icon' => 'ri-user-line']]);
    q_assert_eq('ri-user-line', $out['icon']);
  },

  'validateField rechaza icono no-Remix en group (lo limpia a "")' => function () {
    $out = vcb_invoke('validateField', [['type' => 'group', 'title' => 'X', 'icon' => 'javascript:alert(1)']]);
    q_assert_eq('', $out['icon']);
  },

  'validateField sintetiza nombre seguro para group sin name' => function () {
    $out = vcb_invoke('validateField', [['type' => 'group', 'title' => 'Datos']]);
    q_assert_true(strpos($out['name'], '__group_') === 0, 'nombre empieza con __group_');
  },

  'group con name sintetizado es ÚNICO entre dos llamadas seguidas' => function () {
    $a = vcb_invoke('validateField', [['type' => 'group', 'title' => 'A']]);
    usleep(1000); // microtime cambia
    $b = vcb_invoke('validateField', [['type' => 'group', 'title' => 'B']]);
    q_assert_true($a['name'] !== $b['name'], 'nombres distintos');
  },

  'buildFieldsSql ignora grupos (no genera columnas)' => function () {
    $sql = vcb_invoke('buildFieldsSql', [[
      ['type' => 'group',  'title' => 'Datos personales'],
      ['type' => 'string', 'name' => 'nombre', 'length' => 100],
      ['type' => 'group',  'title' => 'Contacto'],
      ['type' => 'email',  'name' => 'email'],
    ]]);
    q_assert_contains('nombre', $sql);
    q_assert_contains('email',  $sql);
    q_assert_eq(false, str_contains($sql, '__group_'),  'no debe haber columna __group_');
    q_assert_eq(false, str_contains($sql, 'Datos'),      'no debe haber columna Datos');
  },

  'renderFieldInput de group → div sm:col-span-4 con título y borde top' => function () {
    $html = vcb_invoke('renderFieldInput', [
      vcb_invoke('validateField', [['type' => 'group', 'title' => 'Datos Personales', 'icon' => 'ri-user-line']]),
      false
    ]);
    q_assert_contains('sm:col-span-4', $html);
    q_assert_contains('Datos Personales', $html);
    q_assert_contains('ri-user-line', $html);
    q_assert_contains('border-t', $html, 'tiene divider visual');
    q_assert_eq(false, str_contains($html, '<input '), 'no incluye inputs');
    q_assert_eq(false, str_contains($html, 'name='),   'no tiene name=');
  },

  'renderFieldInput escapa HTML del título de un grupo' => function () {
    $html = vcb_invoke('renderFieldInput', [
      vcb_invoke('validateField', [['type' => 'group', 'title' => '<script>alert(1)</script>']]),
      false
    ]);
    q_assert_eq(false, str_contains($html, '<script>'), 'script tag escapado');
    q_assert_contains('&lt;script&gt;', $html);
  },

  'controllerTemplate unsetea los nombres de groups en $data' => function () {
    $code = vcb_invoke('controllerTemplate', [
      'pruebas',
      'pruebas',
      [
        ['type' => 'group',  'name' => '__group_xxx', 'title' => 'X'],
        ['type' => 'string', 'name' => 'nombre'],
      ],
    ]);
    q_assert_contains('__group_xxx', $code, 'el código incluye el unset del nombre del grupo');
    q_assert_contains("unset(\$data[\$__b])", $code, 'tiene la línea de unset');
  },
];

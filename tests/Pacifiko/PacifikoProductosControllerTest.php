<?php

/**
 * Smoke test del listado pacifikoproductosController::_loadRows().
 *
 * Stubbeamos Model::query() para devolver distintos shapes (array, true, false,
 * null) y verificamos que _loadRows() siempre devuelve array — atrapando bugs
 * como `Model::query() ?: []` cuando el driver devuelve `true`.
 */

require_once __DIR__ . '/../lib/ModelStub.php';

// Stub de constantes/clases para que pacifikoproductosController se cargue
foreach (['ROOT', 'APP', 'DS'] as $c) if (!defined($c)) define($c, sys_get_temp_dir() . DIRECTORY_SEPARATOR);
if (!class_exists('Controller'))           { eval('class Controller { protected array $data = []; public function setTitle($t) {} public function addToData($k, $v) { $this->data[$k] = $v; } public function setView($v) {} public function render() {} }'); }
if (!interface_exists('ControllerInterface')) { eval('interface ControllerInterface {}'); }
if (!class_exists('Auth'))                 { eval('class Auth { public static function validate() { return true; } }'); }
if (!class_exists('Csrf'))                 { eval('class Csrf { public static function validate($t) { return true; } }'); }
if (!class_exists('Flasher'))              { eval('class Flasher { public static function new($m, $l = "info") {} public static function error($m) {} public static function success($m) {} }'); }
if (!class_exists('Redirect'))             { eval('class Redirect { public static function to($u) {} public static function back() {} }'); }
if (!function_exists('user_can'))          { eval('function user_can($p) { return true; }'); }
if (!function_exists('sanitize_input'))    { eval('function sanitize_input($s) { return is_string($s) ? trim($s) : $s; }'); }
if (!function_exists('get_quetzal_message')){ eval('function get_quetzal_message($i) { return "msg"; }'); }
if (!function_exists('build_url'))         { eval('function build_url($p) { return $p; }'); }
if (!defined('DB_NAME'))                   { define('DB_NAME', 'test_db'); }

require_once __DIR__ . '/../../plugins/Pacifiko/controllers/pacifikoproductosController.php';

if (!function_exists('q_invoke_load_rows')) {
  function q_invoke_load_rows(string $q, bool $hasVar): array {
    $rc = new ReflectionClass('pacifikoproductosController');
    $ctrl = $rc->newInstanceWithoutConstructor();
    $rm = $rc->getMethod('_loadRows');
    $rm->setAccessible(true);
    return $rm->invoke($ctrl, $q, $hasVar);
  }
}

// Una fila "tipo" para no repetirla 10 veces
function q_row_simple(int $id, string $codigo, string $nombre, float $precio = 0): array
{
  return [
    'producto_id' => $id, 'variante_id' => null, 'codigo' => $codigo, 'nombre' => $nombre,
    'precio_local' => $precio, 'variante_atrs' => null, 'variante_sku' => null,
    'link_id' => null, 'upc' => null, 'model' => null, 'last_status' => null,
    'last_pushed_at' => null, 'last_pulled_at' => null, 'last_error' => null,
    'last_known_price' => null, 'last_known_special' => null, 'last_known_quantity' => null,
  ];
}
function q_row_var(int $pid, int $vid, string $codigo, string $nombre, ?string $atrsJson = null, ?string $sku = null): array
{
  return [
    'producto_id' => $pid, 'variante_id' => $vid, 'codigo' => $codigo, 'nombre' => $nombre,
    'precio_local' => 0, 'variante_atrs' => $atrsJson, 'variante_sku' => $sku,
    'link_id' => null, 'upc' => null, 'model' => null, 'last_status' => null,
    'last_pushed_at' => null, 'last_pulled_at' => null, 'last_error' => null,
    'last_known_price' => null, 'last_known_special' => null, 'last_known_quantity' => null,
  ];
}

return [

  // ============= Bugs de regresión específicos del incidente =============
  'BUG REGRESIÓN: Model::query devuelve true → _loadRows no debe tronar' => function () {
    q_model_reset();
    q_model_push(true); // simula PDO::execute() que devolvió true sin filas
    $rows = q_invoke_load_rows('', false);
    q_assert_eq([], $rows, 'devuelve array vacío en lugar de tirar foreach error');
  },

  'BUG REGRESIÓN: query devuelve false → no truena' => function () {
    q_model_reset();
    q_model_push(false);
    q_assert_eq([], q_invoke_load_rows('', false));
  },

  'BUG REGRESIÓN: query devuelve null → no truena' => function () {
    q_model_reset();
    q_model_push(null);
    q_assert_eq([], q_invoke_load_rows('', false));
  },

  'BUG REGRESIÓN: query devuelve string raro → no truena' => function () {
    q_model_reset();
    q_model_push('error message');
    q_assert_eq([], q_invoke_load_rows('', false));
  },

  // ============= Comportamiento normal =============
  'sin variantes: hace 1 query y devuelve los rows tal cual' => function () {
    q_model_reset();
    q_model_push([q_row_simple(1, 'P1', 'Producto Uno', 100)]);
    $rows = q_invoke_load_rows('', false);
    q_assert_eq(1, count($rows));
    q_assert_eq('P1', $rows[0]['codigo']);
    q_assert_eq([], $rows[0]['variante_atrs_arr']);
    q_assert_eq(1, count(q_model_calls()), 'sólo 1 query si hasVar=false');
  },

  'con variantes: hace 2 queries (simples + variantes) y combina ordenado' => function () {
    q_model_reset();
    q_model_push([q_row_simple(1, 'AAA', 'Simple', 50)]);
    q_model_push([
      q_row_var(2, 10, 'BBB', 'Camisa', '{"talla":"M"}', 'BBB-M'),
      q_row_var(2, 11, 'BBB', 'Camisa', '{"talla":"L"}', 'BBB-L'),
    ]);
    $rows = q_invoke_load_rows('', true);
    q_assert_eq(3, count($rows));
    q_assert_eq(2, count(q_model_calls()), '2 queries: simples + variantes');
    q_assert_eq('AAA', $rows[0]['codigo']);
    q_assert_eq(null,  $rows[0]['variante_id']);
    q_assert_eq('BBB', $rows[1]['codigo']);
    q_assert_eq(10,    $rows[1]['variante_id']);
    q_assert_eq(11,    $rows[2]['variante_id']);
  },

  'variante_atrs JSON se decodifica a variante_atrs_arr' => function () {
    q_model_reset();
    q_model_push([]); // simples vacío
    q_model_push([q_row_var(1, 5, 'X', 'X', '{"talla":"S","color":"Rojo"}', 'X-S-R')]);
    $rows = q_invoke_load_rows('', true);
    q_assert_eq(1, count($rows));
    q_assert_eq('S',    $rows[0]['variante_atrs_arr']['talla']);
    q_assert_eq('Rojo', $rows[0]['variante_atrs_arr']['color']);
  },

  'variante_atrs JSON inválido → variante_atrs_arr queda []' => function () {
    q_model_reset();
    q_model_push([]);
    q_model_push([q_row_var(1, 1, 'X', 'X', '{not json', null)]);
    $rows = q_invoke_load_rows('', true);
    q_assert_eq([], $rows[0]['variante_atrs_arr']);
  },

  'búsqueda q: incluye param :q en ambas queries con hasVar=true' => function () {
    q_model_reset();
    q_model_push([]);
    q_model_push([]);
    q_invoke_load_rows('martillo', true);
    $calls = q_model_calls();
    q_assert_eq(2, count($calls));
    foreach ($calls as $call) {
      q_assert_array_has_key('q', $call['params']);
      q_assert_eq('%martillo%', $call['params']['q']);
      q_assert_contains('LIKE :q', $call['sql']);
    }
  },

  'sin búsqueda: las queries no llevan param :q' => function () {
    q_model_reset();
    q_model_push([]);
    q_invoke_load_rows('', false);
    $call = q_model_calls()[0];
    q_assert_eq(false, isset($call['params']['q']));
    q_assert_eq(false, str_contains($call['sql'], 'LIKE :q'));
  },

  'hasVar=false: WHERE en simples es 1=1 (no toca columna inexistente)' => function () {
    q_model_reset();
    q_model_push([]);
    q_invoke_load_rows('', false);
    $sql = q_model_calls()[0]['sql'];
    q_assert_contains('WHERE 1=1', $sql);
    q_assert_eq(false, str_contains($sql, 'tiene_variantes'),
      'no debe referenciar tiene_variantes cuando la columna no existe');
  },

  'hasVar=true: WHERE en simples usa COALESCE y en variantes = 1' => function () {
    q_model_reset();
    q_model_push([]);
    q_model_push([]);
    q_invoke_load_rows('', true);
    $calls = q_model_calls();
    q_assert_contains('COALESCE(p.tiene_variantes, 0) = 0', $calls[0]['sql']);
    q_assert_contains('p.tiene_variantes = 1',              $calls[1]['sql']);
  },

  'mezcla resultados parciales: 1ra OK, 2da devuelve true → no truena' => function () {
    q_model_reset();
    q_model_push([q_row_simple(1, 'A', 'A')]);
    q_model_push(true);
    $rows = q_invoke_load_rows('', true);
    q_assert_eq(1, count($rows), 'la 1ra query cuenta, la 2da se ignora silenciosamente');
  },
];

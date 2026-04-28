<?php

/**
 * Stub compartido de la clase `Model` para los tests.
 *
 * Mantiene una cola de respuestas en $GLOBALS para que múltiples tests dentro
 * del mismo proceso puedan controlar lo que devuelve `Model::query()`.
 *
 * API:
 *   q_model_reset()              limpia cola y log de llamadas
 *   q_model_push($response)      apila una respuesta
 *   q_model_push_many([$r,$r])   apila varias en orden
 *   q_model_calls()              devuelve [['sql'=>..., 'params'=>...], ...]
 *
 * Si la clase Model ya estaba definida por otro test con un stub diferente,
 * este archivo NO la redefine — pero los helpers q_model_* siguen funcionando
 * para tests que sí escriban su Model contra $GLOBALS.
 */

if (!isset($GLOBALS['_model_responses'])) $GLOBALS['_model_responses'] = [];
if (!isset($GLOBALS['_model_calls']))     $GLOBALS['_model_calls']     = [];

if (!function_exists('q_model_reset')) {
  function q_model_reset(): void { $GLOBALS['_model_responses'] = []; $GLOBALS['_model_calls'] = []; }
  function q_model_push($r): void { $GLOBALS['_model_responses'][] = $r; }
  function q_model_push_many(array $rs): void { foreach ($rs as $r) $GLOBALS['_model_responses'][] = $r; }
  function q_model_calls(): array { return $GLOBALS['_model_calls']; }
}

/**
 * Saca el siguiente valor de la cola si hay; si la cola está vacía, devuelve
 * el default. Permite que cada test prepare respuestas específicas para
 * query/add/update/remove con un solo método: q_model_push().
 */
if (!function_exists('_q_model_next')) {
  function _q_model_next($default) {
    if (!empty($GLOBALS['_model_responses'])) return array_shift($GLOBALS['_model_responses']);
    return $default;
  }
}

if (!class_exists('Model')) {
  eval('class Model {
    public static function query($sql, $params = []) {
      $GLOBALS["_model_calls"][] = ["sql" => $sql, "params" => $params];
      return _q_model_next([]);
    }
    public static function list($table, $where = [], $limit = null) {
      $GLOBALS["_model_calls"][] = ["op" => "list", "table" => $table, "where" => $where, "limit" => $limit];
      return _q_model_next(null);
    }
    public static function add($t, $d) {
      $GLOBALS["_model_calls"][] = ["op" => "add", "table" => $t, "data" => $d];
      return _q_model_next(0);
    }
    public static function update($t, $w, $d) {
      $GLOBALS["_model_calls"][] = ["op" => "update", "table" => $t, "where" => $w, "data" => $d];
      return _q_model_next(false);
    }
    public static function remove($t, $w, $l = null) {
      $GLOBALS["_model_calls"][] = ["op" => "remove", "table" => $t, "where" => $w, "limit" => $l];
      return _q_model_next(false);
    }
  }');
}

<?php

/**
 * Tests del engine de rollback.
 *
 * Stubbeamos Model con la cola compartida ($GLOBALS) — verificamos que el
 * AuditRollback emite las llamadas correctas a Model::add/update/remove
 * según el tipo de evento.
 */

require_once __DIR__ . '/../lib/ModelStub.php';
require_once __DIR__ . '/../../plugins/Audit/classes/AuditWriter.php';
require_once __DIR__ . '/../../plugins/Audit/classes/AuditQuery.php';
require_once __DIR__ . '/../../plugins/Audit/classes/AuditRollback.php';

// Stub de audit_writer() para que rollback no escriba a disco real.
$GLOBALS['_audit_writes'] = [];
if (!function_exists('audit_writer')) {
  // Definimos un writer fake que captura las llamadas en lugar de escribir.
  eval('class FakeAuditWriter {
    public function write($action, $payload) {
      $GLOBALS["_audit_writes"][] = ["action" => $action, "payload" => $payload];
      return true;
    }
  }');
  function audit_writer() {
    static $w = null;
    if ($w === null) $w = new FakeAuditWriter();
    return $w;
  }
}

if (!function_exists('q_rollback_reset')) {
  function q_rollback_reset(): void {
    q_model_reset();
    $GLOBALS['_audit_writes'] = [];
  }
  function q_audit_writes(): array { return $GLOBALS['_audit_writes']; }
}

return [

  // ============= rollbackEvent: create → delete =============
  'rollback de CREATE → llama Model::remove con id correcto' => function () {
    q_rollback_reset();
    q_model_push(true); // simula remove() exitoso
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'create',
      'table'  => 'fact_ventas',
      'row_id' => 42,
      'after'  => ['id' => 42, 'numero' => 'V-001'],
    ]);
    q_assert_eq(true, $r['ok']);
    q_assert_eq('fact_ventas', $r['affected_table']);
    q_assert_eq(42, $r['affected_row_id']);
    $calls = q_model_calls();
    q_assert_eq('remove', $calls[0]['op']);
    q_assert_eq('fact_ventas', $calls[0]['table']);
    q_assert_eq(['id' => 42], $calls[0]['where']);
  },

  'rollback de CREATE sin row_id → falla limpiamente' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'create', 'table' => 'foo', 'row_id' => null,
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('row_id', $r['message']);
  },

  // ============= rollbackEvent: update → restore =============
  'rollback de UPDATE → restaura columnas del before (sin id/timestamps)' => function () {
    q_rollback_reset();
    q_model_push(true);
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'update',
      'table'  => 'fact_ventas',
      'row_id' => 7,
      'before' => ['id' => 7, 'total' => 100.00, 'estado' => 'borrador', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
      'after'  => ['id' => 7, 'total' => 200.00, 'estado' => 'confirmada'],
    ]);
    q_assert_eq(true, $r['ok']);
    $call = q_model_calls()[0];
    q_assert_eq('update', $call['op']);
    q_assert_eq(['id' => 7], $call['where']);
    q_assert_eq(false, isset($call['data']['id']),         'no incluye id');
    q_assert_eq(false, isset($call['data']['created_at']), 'no incluye created_at');
    q_assert_eq(false, isset($call['data']['updated_at']), 'no incluye updated_at');
    q_assert_eq_loose(100.00,    $call['data']['total']);
    q_assert_eq('borrador',      $call['data']['estado']);
  },

  'rollback de UPDATE sin before → falla' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'update', 'table' => 'foo', 'row_id' => 1, 'before' => [],
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('antes', $r['message']);
  },

  // ============= rollbackEvent: delete → restore =============
  'rollback de DELETE → re-inserta el before completo' => function () {
    q_rollback_reset();
    q_model_push(99); // simula add() devolviendo nuevo id
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'delete',
      'table'  => 'fact_clientes',
      'row_id' => 99,
      'before' => ['id' => 99, 'nombre' => 'Juan', 'nit' => '12345', 'created_at' => '2025-12-25'],
    ]);
    q_assert_eq(true, $r['ok']);
    q_assert_eq(99, $r['affected_row_id']);
    $call = q_model_calls()[0];
    q_assert_eq('add', $call['op']);
    q_assert_eq('fact_clientes', $call['table']);
    q_assert_eq('Juan',  $call['data']['nombre']);
    q_assert_eq('12345', $call['data']['nit']);
    q_assert_eq(99,      $call['data']['id'], 'preserva id viejo');
  },

  'rollback de DELETE: si add() falla → reporta error' => function () {
    q_rollback_reset();
    q_model_push(0); // simula add fallido
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'delete', 'table' => 'foo', 'row_id' => 1,
      'before' => ['id' => 1, 'col' => 'val'],
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('INSERT falló', $r['message']);
  },

  // ============= Tablas prohibidas =============
  'rollback rechaza tabla en lista de prohibidas' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'create', 'table' => 'sessions', 'row_id' => 1,
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('bloqueada', $r['message']);
  },

  'rollback rechaza nombre de tabla con caracteres raros' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'create', 'table' => 'users; DROP TABLE x', 'row_id' => 1,
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('inválido', $r['message']);
  },

  'rollback rechaza acción "rollback" (no se anida)' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'rollback', 'table' => 'foo', 'row_id' => 1,
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('rollback del rollback', $r['message']);
  },

  'rollback rechaza acción desconocida' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'auth.login', 'table' => 'users', 'row_id' => 1,
    ]);
    q_assert_eq(false, $r['ok']);
  },

  // ============= Logging del rollback =============
  'rollback exitoso registra un nuevo evento de auditoría con strategy' => function () {
    q_rollback_reset();
    q_model_push(true);
    (new AuditRollback())->rollbackEvent([
      'action' => 'create', 'table' => 'fact_ventas', 'row_id' => 42,
      'request_id' => 'abc123', 'ts' => '2026-04-28T10:00:00-06:00', 'username' => 'admin',
    ]);
    $writes = q_audit_writes();
    q_assert_eq(1, count($writes));
    q_assert_eq('rollback', $writes[0]['action']);
    q_assert_eq('create→delete', $writes[0]['payload']['rollback']['strategy']);
    q_assert_eq('abc123', $writes[0]['payload']['rollback']['original_request_id']);
    q_assert_eq('admin',  $writes[0]['payload']['rollback']['original_username']);
  },

  'rollback fallido NO registra evento de auditoría' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'create', 'table' => 'foo', 'row_id' => null,
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_eq(0, count(q_audit_writes()), 'no debe haber log si el rollback no se ejecutó');
  },

  // ============= rollbackRequest: orden inverso =============
  'rollbackRequest valida formato del request_id' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackRequest('xx; DROP TABLE');
    q_assert_eq(0, $r['ok']);
    q_assert_contains('inválido', $r['error']);
  },

  // ============= update sin diff real (id only) =============
  'rollback de UPDATE: si después de filtrar id/timestamps no queda nada → falla' => function () {
    q_rollback_reset();
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'update', 'table' => 'foo', 'row_id' => 1,
      'before' => ['id' => 1, 'created_at' => 'X', 'updated_at' => 'Y'],
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('filtrar', $r['message']);
  },

  'rollback de UPDATE: si Model::update devuelve false → reporta fallo' => function () {
    q_rollback_reset();
    q_model_push(false); // update no encontró el row
    $r = (new AuditRollback())->rollbackEvent([
      'action' => 'update', 'table' => 'foo', 'row_id' => 99,
      'before' => ['id' => 99, 'col' => 'old'],
    ]);
    q_assert_eq(false, $r['ok']);
    q_assert_contains('UPDATE falló', $r['message']);
  },
];

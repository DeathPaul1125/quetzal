<?php

/**
 * Tests del AuditQuery sobre archivos JSONL.
 */

require_once __DIR__ . '/../../plugins/Audit/classes/AuditWriter.php';
require_once __DIR__ . '/../../plugins/Audit/classes/AuditQuery.php';

if (!function_exists('q_audit_seed')) {
  /**
   * Crea un directorio con archivos jsonl de prueba: 3 días con eventos
   * variados.
   */
  function q_audit_seed(): string {
    $d = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'q_aq_' . bin2hex(random_bytes(3));
    mkdir($d, 0775, true);

    $events = [
      ['ts' => '2026-04-26T10:00:00-06:00', 'action' => 'create', 'table' => 'users',    'user_id' => 1, 'username' => 'admin',   'request_id' => 'r1', 'controller' => 'admin', 'method' => 'crear'],
      ['ts' => '2026-04-26T10:05:00-06:00', 'action' => 'update', 'table' => 'users',    'user_id' => 1, 'username' => 'admin',   'request_id' => 'r2', 'controller' => 'admin', 'method' => 'editar'],
      ['ts' => '2026-04-27T11:00:00-06:00', 'action' => 'delete', 'table' => 'productos','user_id' => 2, 'username' => 'cajero1', 'request_id' => 'r3', 'controller' => 'productos', 'method' => 'borrar'],
      ['ts' => '2026-04-28T12:00:00-06:00', 'action' => 'create', 'table' => 'productos','user_id' => 2, 'username' => 'cajero1', 'request_id' => 'r4', 'controller' => 'productos', 'method' => 'crear'],
      ['ts' => '2026-04-28T12:05:00-06:00', 'action' => 'auth.login','table' => null,    'user_id' => 1, 'username' => 'admin',   'request_id' => 'r5', 'controller' => 'login', 'method' => 'post'],
    ];

    // Escribir cada evento al archivo del día correspondiente
    foreach ($events as $ev) {
      $day = substr($ev['ts'], 0, 10);
      file_put_contents(
        $d . DIRECTORY_SEPARATOR . $day . '.jsonl',
        json_encode($ev) . "\n",
        FILE_APPEND
      );
    }
    return $d;
  }
  function q_audit_seed_clean(string $d): void {
    foreach (glob($d . '/*') as $f) @unlink($f);
    @rmdir($d);
  }
}

return [

  'days: lista archivos descendente' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $days = $q->days();
    q_assert_eq(3, count($days));
    q_assert_true(strpos(basename($days[0]), '2026-04-28') === 0, 'más reciente primero');
    q_audit_seed_clean($d);
  },

  'search: sin filtros devuelve todo' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $r = $q->search([]);
    q_assert_eq(5, $r['total']);
    q_assert_eq(5, count($r['rows']));
    q_assert_eq('r5', $r['rows'][0]['request_id'], 'más reciente primero');
    q_audit_seed_clean($d);
  },

  'search: filtra por action' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $r = $q->search(['action' => 'create']);
    q_assert_eq(2, $r['total']);
    q_audit_seed_clean($d);
  },

  'search: filtra por table' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $r = $q->search(['table' => 'productos']);
    q_assert_eq(2, $r['total']);
    foreach ($r['rows'] as $row) q_assert_eq('productos', $row['table']);
    q_audit_seed_clean($d);
  },

  'search: filtra por user_id' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $r = $q->search(['user_id' => 2]);
    q_assert_eq(2, $r['total']);
    q_audit_seed_clean($d);
  },

  'search: filtro q matchea username/controller/method' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $r = $q->search(['q' => 'cajero1']);
    q_assert_eq(2, $r['total']);
    q_audit_seed_clean($d);
  },

  'search: rango de fechas' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $r = $q->search(['date_from' => '2026-04-27', 'date_to' => '2026-04-27']);
    q_assert_eq(1, $r['total'], 'sólo el del día 27');
    q_audit_seed_clean($d);
  },

  'search: paginación' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $page1 = $q->search([], 1, 2);
    $page2 = $q->search([], 2, 2);
    $page3 = $q->search([], 3, 2);
    q_assert_eq(2, count($page1['rows']));
    q_assert_eq(2, count($page2['rows']));
    q_assert_eq(1, count($page3['rows']));
    // No solapados
    q_assert_true($page1['rows'][0]['request_id'] !== $page2['rows'][0]['request_id']);
    q_audit_seed_clean($d);
  },

  'summary: cuenta agrupado por action y table' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $s = $q->summary();
    q_assert_eq(5, $s['total']);
    q_assert_eq(2, $s['by_action']['create']);
    q_assert_eq(1, $s['by_action']['update']);
    q_assert_eq(1, $s['by_action']['delete']);
    q_assert_eq(1, $s['by_action']['auth.login']);
    q_assert_eq(2, $s['by_table']['productos']);
    q_audit_seed_clean($d);
  },

  'findOne: encuentra evento por (rid, ts)' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    $ev = $q->findOne('r3', '2026-04-27T11:00:00-06:00');
    q_assert_true($ev !== null);
    q_assert_eq('delete', $ev['action']);
    q_assert_eq('productos', $ev['table']);
    q_audit_seed_clean($d);
  },

  'findOne: rid inexistente → null' => function () {
    $d = q_audit_seed();
    $q = new AuditQuery($d);
    q_assert_eq(null, $q->findOne('rXX', '2026-04-27T11:00:00-06:00'));
    q_audit_seed_clean($d);
  },
];

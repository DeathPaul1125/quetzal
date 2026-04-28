<?php

/**
 * Tests del AuditWriter:
 *  - append-only a archivos JSONL diarios
 *  - redacción de columnas sensibles
 *  - cálculo de diff before/after
 *  - tablas ignoradas no escriben
 *  - flock seguro (un archivo no se corrompe con writes seguidos)
 */

require_once __DIR__ . '/../../plugins/Audit/classes/AuditWriter.php';

if (!function_exists('q_audit_tmp_dir')) {
  function q_audit_tmp_dir(): string {
    $d = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'q_audit_test_' . bin2hex(random_bytes(3));
    if (!is_dir($d)) mkdir($d, 0775, true);
    return $d;
  }
  function q_audit_clean(string $d): void {
    if (!is_dir($d)) return;
    foreach (glob($d . '/*') as $f) @unlink($f);
    @rmdir($d);
  }
  function q_audit_read_lines(string $d): array {
    $files = glob($d . '/*.jsonl');
    if (!$files) return [];
    $out = [];
    foreach ($files as $file) {
      $h = fopen($file, 'rb');
      while (!feof($h)) {
        $l = fgets($h);
        if (is_string($l) && trim($l) !== '') $out[] = json_decode($l, true);
      }
      fclose($h);
    }
    return $out;
  }
}

return [

  'write: crea archivo del día y escribe 1 evento' => function () {
    $dir = q_audit_tmp_dir();
    $w = new AuditWriter($dir);
    $ok = $w->write('create', ['table' => 'foo', 'after' => ['x' => 1]]);
    q_assert_true($ok);
    $files = glob($dir . '/*.jsonl');
    q_assert_eq(1, count($files), '1 archivo creado');
    q_assert_true(strpos(basename($files[0]), date('Y-m-d')) === 0, 'nombre = fecha actual');
    $lines = q_audit_read_lines($dir);
    q_assert_eq(1, count($lines));
    q_assert_eq('foo', $lines[0]['table']);
    q_audit_clean($dir);
  },

  'write: append-only — múltiples writes al mismo archivo' => function () {
    $dir = q_audit_tmp_dir();
    $w = new AuditWriter($dir);
    for ($i = 0; $i < 10; $i++) {
      $w->write('update', ['table' => 'bar', 'row_id' => $i, 'diff' => ['n' => ['from' => $i, 'to' => $i + 1]]]);
    }
    $lines = q_audit_read_lines($dir);
    q_assert_eq(10, count($lines));
    q_assert_eq(0, $lines[0]['row_id']);
    q_assert_eq(9, $lines[9]['row_id']);
    q_audit_clean($dir);
  },

  'write: tabla ignorada no escribe' => function () {
    $dir = q_audit_tmp_dir();
    $w = new AuditWriter($dir);
    $r = $w->write('update', ['table' => 'sessions', 'row_id' => 1]);
    q_assert_eq(false, $r);
    q_assert_eq(0, count(q_audit_read_lines($dir)));
    q_audit_clean($dir);
  },

  'redact: enmascara password en users' => function () {
    $w = new AuditWriter(q_audit_tmp_dir());
    $r = $w->redact('users', ['username' => 'admin', 'password' => 'super-secret']);
    q_assert_eq('admin', $r['username']);
    q_assert_eq('***', $r['password']);
  },

  'redact: enmascara api_key en pacifiko_config' => function () {
    $w = new AuditWriter(q_audit_tmp_dir());
    $r = $w->redact('pacifiko_config', ['api_user' => 'u', 'api_key' => 'sk_real']);
    q_assert_eq('u', $r['api_user']);
    q_assert_eq('***', $r['api_key']);
  },

  'redact: tabla sin reglas devuelve datos sin tocar' => function () {
    $w = new AuditWriter(q_audit_tmp_dir());
    $orig = ['nombre' => 'X', 'precio' => 100];
    q_assert_eq($orig, $w->redact('cualquier_tabla', $orig));
  },

  'write: redacta automáticamente before/after antes de persistir' => function () {
    $dir = q_audit_tmp_dir();
    $w = new AuditWriter($dir);
    $w->write('update', [
      'table' => 'users',
      'before' => ['username' => 'a', 'password' => 'old'],
      'after'  => ['username' => 'a', 'password' => 'new'],
    ]);
    $lines = q_audit_read_lines($dir);
    q_assert_eq('***', $lines[0]['before']['password']);
    q_assert_eq('***', $lines[0]['after']['password']);
    q_audit_clean($dir);
  },

  // ============= diff =============
  'diff: detecta valores cambiados' => function () {
    $d = AuditWriter::diff(
      ['a' => 1, 'b' => 'old'],
      ['a' => 2, 'b' => 'old', 'c' => 'new']
    );
    q_assert_array_has_key('a', $d);
    q_assert_eq(1, $d['a']['from']);
    q_assert_eq(2, $d['a']['to']);
    q_assert_array_has_key('c', $d);
    q_assert_eq(null, $d['c']['from']);
    q_assert_eq('new', $d['c']['to']);
    q_assert_eq(false, isset($d['b']));
  },

  'diff: 1 vs "1" no es cambio (compara como string)' => function () {
    $d = AuditWriter::diff(['n' => 1], ['n' => '1']);
    q_assert_eq([], $d);
  },

  'diff: arrays vacíos → diff vacío' => function () {
    q_assert_eq([], AuditWriter::diff([], []));
  },

  'diff: campo eliminado aparece con to=null' => function () {
    $d = AuditWriter::diff(['x' => 'value'], []);
    q_assert_array_has_key('x', $d);
    q_assert_eq('value', $d['x']['from']);
    q_assert_eq(null, $d['x']['to']);
  },

  // ============= isIgnored =============
  'isIgnored: tablas default + custom' => function () {
    $w = new AuditWriter(q_audit_tmp_dir(), ['my_secret_table']);
    q_assert_true($w->isIgnored('sessions'));
    q_assert_true($w->isIgnored('my_secret_table'));
    q_assert_eq(false, $w->isIgnored('users'));
  },
];

<?php

/**
 * Runner mínimo de tests — sin dependencia de phpunit.
 *
 * Auto-descubre todos los `tests/{**}/*Test.php`. Cada archivo de test debe
 * retornar un array asociativo `['nombre del caso' => fn()...]`. El runner
 * los ejecuta en orden, reporta pass/fail con tiempo, y devuelve exit code
 * != 0 si hubo algún fallo.
 *
 * Uso:
 *   php tests/run.php            # corre todo
 *   php tests/run.php WooCommerce # corre sólo carpetas que matchean
 *   php tests/run.php --filter mapper
 */

chdir(__DIR__ . '/..');

require __DIR__ . '/lib/Assert.php';
// Stub global de Model — debe cargarse antes que cualquier test, para que el
// `if (!class_exists("Model"))` de los tests viejos no instale un stub que
// no respete $GLOBALS['_model_responses'].
require __DIR__ . '/lib/ModelStub.php';

$argv0 = $argv[0] ?? '';
array_shift($argv);

$filter = '';
$folderFilter = '';
foreach ($argv as $a) {
  if ($a === '--filter') continue;
  if (str_starts_with($a, '--filter=')) { $filter = substr($a, 9); continue; }
  if ($folderFilter === '') { $folderFilter = $a; }
  else { $filter = $a; }
}

$root = __DIR__;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
$archivos = [];
foreach ($it as $f) {
  if ($f->isDir()) continue;
  $name = $f->getFilename();
  if (!str_ends_with($name, 'Test.php')) continue;
  $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
  if ($folderFilter !== '' && stripos($rel, $folderFilter) === false) continue;
  $archivos[] = $f->getPathname();
}
sort($archivos);

if (empty($archivos)) {
  fwrite(STDERR, "No se encontraron archivos *Test.php\n");
  exit(2);
}

$totales = ['ok' => 0, 'fail' => 0, 'skip' => 0];
$fails = [];
$tStart = microtime(true);

foreach ($archivos as $file) {
  $rel = str_replace('\\', '/', substr($file, strlen($root) + 1));
  $tests = require $file;
  if (!is_array($tests)) {
    fwrite(STDERR, "  [WARN] $rel no devolvió array de tests\n");
    continue;
  }

  echo "\n\033[36m== $rel\033[0m\n";

  foreach ($tests as $titulo => $closure) {
    if ($filter !== '' && stripos($titulo, $filter) === false) {
      $totales['skip']++;
      continue;
    }
    $t0 = microtime(true);
    try {
      $closure();
      $ms = (int) ((microtime(true) - $t0) * 1000);
      echo "  \033[32m✓\033[0m " . $titulo . " \033[90m(" . $ms . "ms)\033[0m\n";
      $totales['ok']++;
    } catch (Throwable $e) {
      $ms = (int) ((microtime(true) - $t0) * 1000);
      echo "  \033[31m✗\033[0m " . $titulo . " \033[90m(" . $ms . "ms)\033[0m\n";
      echo "      \033[31m" . $e->getMessage() . "\033[0m\n";
      echo "      \033[90m" . $e->getFile() . ':' . $e->getLine() . "\033[0m\n";
      $totales['fail']++;
      $fails[] = "$rel :: $titulo — " . $e->getMessage();
    }
  }
}

$elapsedMs = (int) ((microtime(true) - $tStart) * 1000);

echo "\n";
echo str_repeat('─', 60) . "\n";
echo sprintf("\033[32m%d ok\033[0m · \033[31m%d fail\033[0m · \033[90m%d skip\033[0m · \033[90m%dms total\033[0m\n",
  $totales['ok'], $totales['fail'], $totales['skip'], $elapsedMs);

if (!empty($fails)) {
  echo "\nFallaron:\n";
  foreach ($fails as $m) echo "  · " . $m . "\n";
  exit(1);
}
exit(0);

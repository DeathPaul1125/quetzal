<?php

/**
 * Sanity tests por plugin:
 *   - cada *.php compila (php -l)
 *   - cada *.blade.php compila vía Blade engine y luego php -l del output
 *   - plugin.json válido + claves mínimas
 *   - cada migración devuelve un objeto con up()/down()
 *
 * Cada plugin se reporta como un caso de test individual para que el output
 * sea legible.
 */

$pluginsDir = __DIR__ . '/../../plugins';
$plugins = [];
foreach (new DirectoryIterator($pluginsDir) as $f) {
  if ($f->isDir() && !$f->isDot() && file_exists($f->getPathname() . '/plugin.json')) {
    $plugins[$f->getFilename()] = $f->getPathname();
  }
}
ksort($plugins);

$tests = [];

// ---- LINT PHP ----
foreach ($plugins as $nombre => $path) {
  $tests["[$nombre] todos los .php compilan (php -l)"] = function () use ($path, $nombre) {
    $bad = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($it as $f) {
      if ($f->isDir()) continue;
      $name = $f->getFilename();
      if (!str_ends_with($name, '.php') || str_ends_with($name, '.blade.php')) continue;
      $out = shell_exec('php -l ' . escapeshellarg($f->getPathname()) . ' 2>&1');
      if ($out === null || strpos($out, 'No syntax errors') === false) {
        $bad[] = $f->getPathname() . ' → ' . trim((string)$out);
      }
    }
    if (!empty($bad)) throw new AssertionError("$nombre: " . count($bad) . " archivos con error:\n  " . implode("\n  ", $bad));
  };
}

// ---- BLADE COMPILE ----
foreach ($plugins as $nombre => $path) {
  $tests["[$nombre] todas las vistas .blade.php compilan"] = function () use ($path, $nombre) {
    $vistasDir = $path . '/views';
    if (!is_dir($vistasDir)) return; // plugin sin vistas

    require_once __DIR__ . '/../../app/vendor/autoload.php';
    require_once __DIR__ . '/../../app/classes/QuetzalBladeEngine.php';

    $blade    = new QuetzalBladeEngine([__DIR__ . '/../../templates/views', $path], __DIR__ . '/../../app/cache/blade');
    $compiler = $blade->compiler();

    $bad = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vistasDir));
    foreach ($it as $f) {
      if ($f->isDir() || !str_ends_with($f->getFilename(), '.blade.php')) continue;
      try {
        $compiled = $compiler->compileString(file_get_contents($f->getPathname()));
        $tmp = tempnam(sys_get_temp_dir(), 'qx') . '.php';
        file_put_contents($tmp, $compiled);
        $lint = shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');
        @unlink($tmp);
        if (strpos((string)$lint, 'No syntax errors') === false) {
          $bad[] = $f->getPathname() . ' → ' . trim((string)$lint);
        }
      } catch (Throwable $e) {
        $bad[] = $f->getPathname() . ' → THREW: ' . $e->getMessage();
      }
    }
    if (!empty($bad)) throw new AssertionError("$nombre: " . count($bad) . " vistas mal:\n  " . implode("\n  ", $bad));
  };
}

// ---- plugin.json ----
foreach ($plugins as $nombre => $path) {
  $tests["[$nombre] plugin.json válido + claves mínimas"] = function () use ($path) {
    $j = file_get_contents($path . '/plugin.json');
    q_assert_true($j !== false, 'plugin.json debe leerse');
    $m = json_decode($j, true);
    if (!is_array($m)) throw new AssertionError('plugin.json no es JSON válido: ' . json_last_error_msg());

    foreach (['name', 'version'] as $k) {
      q_assert_array_has_key($k, $m, 'plugin.json debe tener "' . $k . '"');
    }
    if (isset($m['requires']) && !is_array($m['requires'])) {
      throw new AssertionError('"requires" debe ser array');
    }
  };
}

// ---- migraciones ----
foreach ($plugins as $nombre => $path) {
  $migDir = $path . '/migrations';
  if (!is_dir($migDir)) continue;

  $tests["[$nombre] migraciones retornan up()/down()"] = function () use ($migDir, $nombre) {
    $bad = [];
    foreach (glob($migDir . '/*.php') as $mig) {
      try {
        $obj = require $mig;
        if (!is_object($obj)) {
          $bad[] = basename($mig) . ' → no retornó object';
          continue;
        }
        if (!method_exists($obj, 'up') || !method_exists($obj, 'down')) {
          $bad[] = basename($mig) . ' → falta up() o down()';
        }
      } catch (Throwable $e) {
        $bad[] = basename($mig) . ' → THREW: ' . $e->getMessage();
      }
    }
    if (!empty($bad)) throw new AssertionError("$nombre: " . count($bad) . " migración(es):\n  " . implode("\n  ", $bad));
  };
}

return $tests;

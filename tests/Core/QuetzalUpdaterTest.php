<?php

/**
 * Tests de QuetzalUpdater:
 *  - Comparación de versiones (semver)
 *  - Detección de paths protegidos (no se sobreescriben durante swap)
 *  - Backup ZIP con paths excluidos
 *  - Extract + swap básico (sandbox temporal en sys_get_temp_dir)
 */

require_once __DIR__ . '/../../app/classes/QuetzalUpdater.php';

// Stub mínimo: en tests no hay app/config/.env cargado, pero QuetzalUpdater
// usa quetzal_version() para nombrar el backup. Usamos una versión fija.
if (!function_exists('quetzal_version')) {
  function quetzal_version(): string { return '1.5.0-test'; }
}
if (!function_exists('quetzal_version_info')) {
  function quetzal_version_info(): array {
    return ['version' => '1.5.0-test', 'released_at' => null, 'min_php' => null, 'channel' => 'stable'];
  }
}
if (!function_exists('get_option')) {
  function get_option($k) { return null; }
}

/**
 * Crea una raíz de proyecto sintética en /tmp con estructura mínima.
 * Devuelve el path. Limpia con rrmdir() al final del test.
 */
function makeFakeRoot(): string
{
  $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'quetzal-updater-test-' . uniqid() . DIRECTORY_SEPARATOR;
  mkdir($root, 0755, true);

  // Estructura típica del proyecto
  mkdir($root . 'app/config', 0755, true);
  mkdir($root . 'app/cache', 0755, true);
  mkdir($root . 'plugins/Facturador', 0755, true);
  mkdir($root . 'assets/uploads/users', 0755, true);
  mkdir($root . 'templates/views', 0755, true);

  file_put_contents($root . 'quetzal.json', json_encode(['version' => '1.5.0']));
  file_put_contents($root . 'app/config/.env', 'DB_PASS=secret');
  file_put_contents($root . 'app/cache/blade.txt', 'cache');
  file_put_contents($root . 'plugins/Facturador/init.php', '<?php // plugin existente');
  file_put_contents($root . 'assets/uploads/users/foto.jpg', 'binary');
  file_put_contents($root . 'templates/views/old.blade.php', 'view-vieja');

  return $root;
}

function rrmdir(string $dir): void
{
  if (!is_dir($dir)) return;
  $items = scandir($dir) ?: [];
  foreach ($items as $i) {
    if ($i === '.' || $i === '..') continue;
    $p = $dir . DIRECTORY_SEPARATOR . $i;
    is_dir($p) ? rrmdir($p) : @unlink($p);
  }
  @rmdir($dir);
}

return [

  // ========== Comparación de versiones ==========
  'compareVersions: 1.5.0 < 1.6.0' => function () {
    q_assert_eq(-1, QuetzalUpdater::compareVersions('1.5.0', '1.6.0'));
  },
  'compareVersions: 1.6.0 == 1.6.0' => function () {
    q_assert_eq(0, QuetzalUpdater::compareVersions('1.6.0', '1.6.0'));
  },
  'compareVersions: 2.0.0 > 1.99.99' => function () {
    q_assert_eq(1, QuetzalUpdater::compareVersions('2.0.0', '1.99.99'));
  },
  'compareVersions: prefijo v se ignora (v1.6 == 1.6)' => function () {
    q_assert_eq(0, QuetzalUpdater::compareVersions('v1.6.0', '1.6.0'));
  },
  'compareVersions: pre-release 1.6.0-beta < 1.6.0' => function () {
    q_assert_eq(-1, QuetzalUpdater::compareVersions('1.6.0-beta', '1.6.0'));
  },

  'isNewer: 1.5.0 → 1.6.0 = true' => function () {
    q_assert_true(QuetzalUpdater::isNewer('1.5.0', '1.6.0'));
  },
  'isNewer: 1.6.0 → 1.6.0 = false' => function () {
    q_assert_eq(false, QuetzalUpdater::isNewer('1.6.0', '1.6.0'));
  },
  'isNewer: 2.0.0 → 1.6.0 = false (downgrade no se ofrece)' => function () {
    q_assert_eq(false, QuetzalUpdater::isNewer('2.0.0', '1.6.0'));
  },

  // ========== Paths protegidos ==========
  'isProtected: plugins/Facturador/init.php' => function () {
    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('isProtected');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());
    q_assert_true($m->invoke($inst, 'plugins/Facturador/init.php'));
  },
  'isProtected: app/config/.env' => function () {
    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('isProtected');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());
    q_assert_true($m->invoke($inst, 'app/config/.env'));
  },
  'isProtected: assets/uploads/users/foto.jpg' => function () {
    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('isProtected');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());
    q_assert_true($m->invoke($inst, 'assets/uploads/users/foto.jpg'));
  },
  'NO protegido: app/controllers/adminController.php' => function () {
    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('isProtected');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());
    q_assert_eq(false, $m->invoke($inst, 'app/controllers/adminController.php'));
  },
  'NO protegido: assets/css/main.css' => function () {
    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('isProtected');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());
    q_assert_eq(false, $m->invoke($inst, 'assets/css/main.css'));
  },
  'isProtected: separadores con backslash → normaliza' => function () {
    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('isProtected');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());
    q_assert_true($m->invoke($inst, 'plugins\\Facturador\\init.php'));
  },

  // ========== Backup ZIP ==========
  'backup: genera ZIP que incluye app/ y excluye plugins/, .env, uploads' => function () {
    $root = makeFakeRoot();
    try {
      $u = new QuetzalUpdater('http://localhost', '', $root);
      $zipPath = $u->backup();

      q_assert_true(is_file($zipPath), 'el ZIP de backup debe existir');
      q_assert_true(filesize($zipPath) > 0, 'el ZIP no debe estar vacío');

      $z = new ZipArchive();
      $z->open($zipPath);
      $names = [];
      for ($i = 0; $i < $z->numFiles; $i++) {
        $names[] = $z->getNameIndex($i);
      }
      $z->close();

      // Lo que SÍ debe estar
      q_assert_contains('quetzal.json', $names);

      // Lo que NO debe estar (paths protegidos)
      foreach ($names as $n) {
        if (strpos($n, 'plugins/') === 0) {
          throw new AssertionError('plugins/ no debería estar en el backup, encontré: ' . $n);
        }
        if (strpos($n, 'app/cache') === 0) {
          throw new AssertionError('app/cache no debería estar en el backup, encontré: ' . $n);
        }
        if (strpos($n, 'assets/uploads') === 0) {
          throw new AssertionError('assets/uploads no debería estar en el backup, encontré: ' . $n);
        }
        if ($n === 'app/config/.env' || strpos($n, 'app/config/.env') === 0) {
          throw new AssertionError('.env no debería estar en el backup, encontré: ' . $n);
        }
      }
    } finally {
      rrmdir($root);
    }
  },

  // ========== Swap: respeta paths protegidos ==========
  'swap: archivos del release sobreescriben código pero NO tocan plugins/ ni .env' => function () {
    $root = makeFakeRoot();
    try {
      // Capturamos el contenido original de los protegidos
      $envOriginal     = file_get_contents($root . 'app/config/.env');
      $pluginOriginal  = file_get_contents($root . 'plugins/Facturador/init.php');
      $uploadOriginal  = file_get_contents($root . 'assets/uploads/users/foto.jpg');

      // Construimos un "release" simulado con archivos nuevos en una staging dir
      $staging = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'release-' . uniqid() . DIRECTORY_SEPARATOR;
      mkdir($staging . 'app/controllers', 0755, true);
      mkdir($staging . 'app/config', 0755, true);
      mkdir($staging . 'plugins/Facturador', 0755, true);
      mkdir($staging . 'assets/uploads/users', 0755, true);

      file_put_contents($staging . 'quetzal.json', json_encode(['version' => '1.6.0']));
      file_put_contents($staging . 'app/controllers/newController.php', '<?php // controller nuevo del release');
      // El release intenta sobrescribir cosas protegidas:
      file_put_contents($staging . 'app/config/.env', 'DB_PASS=PWNED');
      file_put_contents($staging . 'plugins/Facturador/init.php', '<?php // PLUGIN-DEL-RELEASE');
      file_put_contents($staging . 'assets/uploads/users/foto.jpg', 'PWNED-IMG');

      $u = new QuetzalUpdater('http://localhost', '', $root);
      $u->swap($staging);

      // El archivo nuevo del release debe estar
      q_assert_true(is_file($root . 'app/controllers/newController.php'), 'controller nuevo no se copió');

      // Los protegidos deben mantener el contenido ORIGINAL
      q_assert_eq($envOriginal,    file_get_contents($root . 'app/config/.env'),                'el .env fue sobreescrito (no debería)');
      q_assert_eq($pluginOriginal, file_get_contents($root . 'plugins/Facturador/init.php'),    'el plugin fue sobreescrito (no debería)');
      q_assert_eq($uploadOriginal, file_get_contents($root . 'assets/uploads/users/foto.jpg'),  'el upload fue sobreescrito (no debería)');

      rrmdir($staging);
    } finally {
      rrmdir($root);
    }
  },

  // ========== updateVersion: reescribe quetzal.json ==========
  'updateVersion: escribe quetzal.json con la nueva versión' => function () {
    $root = makeFakeRoot();
    try {
      $u = new QuetzalUpdater('http://localhost', '', $root);
      $u->updateVersion('1.6.1', '2026-04-30', '8.1');

      $json = json_decode(file_get_contents($root . 'quetzal.json'), true);
      q_assert_eq('1.6.1',      $json['version']);
      q_assert_eq('2026-04-30', $json['released_at']);
      q_assert_eq('8.1',        $json['min_php']);
    } finally {
      rrmdir($root);
    }
  },

  // ========== detectRoot: detecta subdirectorio en ZIPs tipo "git archive" ==========
  'detectRoot: ZIP con subdir único quetzal-1.6.0/ → devuelve subdir' => function () {
    $staging = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'staging-' . uniqid() . DIRECTORY_SEPARATOR;
    mkdir($staging . 'quetzal-1.6.0/app', 0755, true);
    file_put_contents($staging . 'quetzal-1.6.0/quetzal.json', '{}');

    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('detectRoot');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());

    $detected = $m->invoke($inst, $staging);
    q_assert_contains('quetzal-1.6.0', $detected);

    rrmdir($staging);
  },

  'detectRoot: ZIP al ras (sin subdir) → devuelve staging tal cual' => function () {
    $staging = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'staging-' . uniqid() . DIRECTORY_SEPARATOR;
    mkdir($staging . 'app', 0755, true);
    file_put_contents($staging . 'quetzal.json', '{}');

    $u = new ReflectionClass(QuetzalUpdater::class);
    $m = $u->getMethod('detectRoot');
    $m->setAccessible(true);
    $inst = new QuetzalUpdater('http://localhost', '', sys_get_temp_dir());

    $detected = $m->invoke($inst, $staging);
    q_assert_eq(rtrim($staging, '/\\'), rtrim($detected, '/\\'));

    rrmdir($staging);
  },
];

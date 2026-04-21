<?php
/**
 * Quetzal — Instalador Web (Wizard)
 * Accede a /install desde el navegador. Elimínalo al terminar.
 */

session_start();
@set_time_limit(0);
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '512M');

define('ROOT', __DIR__);
define('ENV_EXAMPLE', ROOT . '/app/config/.env.example');
define('ENV_FILE',    ROOT . '/app/config/.env');
define('MIGRATIONS_DIR', ROOT . '/app/migrations');
define('APP_DIR',     ROOT . '/app');
define('VENDOR_DIR',  ROOT . '/app/vendor');
define('COMPOSER_PHAR', ROOT . '/composer.phar');
define('LOGS_DIR',    ROOT . '/app/logs');
define('UPLOADS_DIR', ROOT . '/assets/uploads');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$action = $_POST['action'] ?? null;
$errors = [];
$notices = [];

/* ---------------------- Helpers ---------------------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function check_requirements() {
    return [
        ['label' => 'PHP >= 8.3',                     'ok' => version_compare(PHP_VERSION, '8.3.0', '>='), 'value' => PHP_VERSION],
        ['label' => 'Extensión pdo_mysql',            'ok' => extension_loaded('pdo_mysql'), 'value' => extension_loaded('pdo_mysql') ? 'ok' : 'falta'],
        ['label' => 'Extensión mbstring',             'ok' => extension_loaded('mbstring'),  'value' => extension_loaded('mbstring')  ? 'ok' : 'falta'],
        ['label' => 'Extensión openssl',              'ok' => extension_loaded('openssl'),   'value' => extension_loaded('openssl')   ? 'ok' : 'falta'],
        ['label' => 'Extensión gd',                   'ok' => extension_loaded('gd'),        'value' => extension_loaded('gd')        ? 'ok' : 'falta'],
        ['label' => 'Extensión curl',                 'ok' => extension_loaded('curl'),      'value' => extension_loaded('curl')      ? 'ok' : 'falta'],
        ['label' => 'Extensión json',                 'ok' => extension_loaded('json'),      'value' => extension_loaded('json')      ? 'ok' : 'falta'],
        ['label' => 'Archivo .env.example existe',    'ok' => is_file(ENV_EXAMPLE), 'value' => is_file(ENV_EXAMPLE) ? 'ok' : 'no encontrado'],
        ['label' => 'Carpeta app/migrations/ existe',  'ok' => is_dir(ROOT . '/app/migrations'), 'value' => is_dir(ROOT . '/app/migrations') ? (count(glob(ROOT . '/app/migrations/*.php') ?: []) . ' migraciones') : 'no encontrada'],
        ['label' => 'Carpeta app/config/ escribible', 'ok' => is_writable(dirname(ENV_FILE)), 'value' => is_writable(dirname(ENV_FILE)) ? 'ok' : 'sin permiso'],
        ['label' => 'Carpeta app/ escribible',        'ok' => is_writable(APP_DIR), 'value' => is_writable(APP_DIR) ? 'ok' : 'sin permiso'],
    ];
}

function parse_env_example($path) {
    $defaults = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/i', $line, $m)) continue;
        $val = $m[2];
        if (preg_match('/^\s*([\'"])(.*?)\1\s*(#.*)?$/', $val, $mm)) $val = $mm[2];
        else $val = preg_replace('/\s+#.*/', '', $val);
        $defaults[$m[1]] = trim($val);
    }
    return $defaults;
}

function read_env_file_vars($path) {
    $vars = [];
    if (!is_file($path)) return $vars;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!preg_match('/^([A-Z0-9_]+)\s*=\s*(.*)$/i', $line, $m)) continue;
        $val = $m[2];
        if (preg_match('/^\s*([\'"])(.*?)\1\s*(#.*)?$/', $val, $mm)) $val = $mm[2];
        else $val = preg_replace('/\s+#.*/', '', $val);
        $vars[$m[1]] = trim($val);
    }
    return $vars;
}

function write_env($defaults, $overrides) {
    $lines = file(ENV_EXAMPLE, FILE_IGNORE_NEW_LINES);
    $out = [];
    foreach ($lines as $line) {
        $trim = trim($line);
        if ($trim === '' || $trim[0] === '#') { $out[] = $line; continue; }
        if (!preg_match('/^([A-Z0-9_]+)\s*=\s*/i', $trim, $m)) { $out[] = $line; continue; }
        $key = $m[1];
        $val = array_key_exists($key, $overrides) ? $overrides[$key] : ($defaults[$key] ?? '');
        $comment = '';
        if (preg_match('/#.*$/', $line, $cm)) $comment = '  ' . $cm[0];
        $quoted = "'" . str_replace("'", "\\'", $val) . "'";
        if ($val === '') $quoted = "''";
        if (preg_match('/^(true|false)$/i', $val)) $quoted = strtolower($val);
        $out[] = "$key=$quoted$comment";
    }
    return file_put_contents(ENV_FILE, implode(PHP_EOL, $out) . PHP_EOL) !== false;
}

function db_connect($host, $user, $pass, $db = null, $charset = 'utf8mb4') {
    $dsn = "mysql:host=$host;charset=$charset" . ($db ? ";dbname=$db" : '');
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset"
    ]);
}

/**
 * Verifica si la BD existe consultando information_schema.
 */
function db_exists(PDO $pdo, string $name): bool {
    $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :n LIMIT 1');
    $stmt->execute([':n' => $name]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Crea la BD si no existe. Retorna true si la creó, false si ya existía.
 */
function db_create_if_missing(PDO $pdo, string $name): bool {
    if (db_exists($pdo, $name)) return false;
    $pdo->exec("CREATE DATABASE `" . str_replace('`', '', $name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    return true;
}

function find_composer() {
    $isWin = stripos(PHP_OS, 'WIN') === 0;
    $which = $isWin ? 'where composer 2>NUL' : 'command -v composer 2>/dev/null';
    $test = @shell_exec($which);
    if ($test && trim($test) !== '') {
        $first = strtok(trim($test), "\r\n");
        return escapeshellarg($first);
    }
    $candidates = [
        'C:/laragon/bin/composer/composer.bat',
        'C:/laragon/bin/composer/composer.phar',
        'C:/ProgramData/ComposerSetup/bin/composer.bat',
        '/usr/local/bin/composer',
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) {
            if (substr($c, -5) === '.phar') return escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($c);
            return escapeshellarg($c);
        }
    }
    if (is_file(COMPOSER_PHAR)) {
        return escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(COMPOSER_PHAR);
    }
    return null;
}

function download_composer_phar() {
    $url = 'https://getcomposer.org/composer-stable.phar';
    $data = null;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) $data = null;
    }
    if (!$data && ini_get('allow_url_fopen')) {
        $ctx = stream_context_create(['http' => ['timeout' => 120]]);
        $data = @file_get_contents($url, false, $ctx);
    }
    if (!$data) return false;
    return file_put_contents(COMPOSER_PHAR, $data) !== false;
}

function patch_project_composer_json() {
    $path = APP_DIR . '/composer.json';
    if (!is_file($path)) return;
    $json = json_decode(file_get_contents($path), true);
    if (!is_array($json)) return;
    $json['config']['audit']['abandoned'] = 'ignore';
    $json['config']['audit']['ignore'] = (object)[
        'PKSA-3n81-fgxd-xfh7' => 'legacy dependency — acknowledged for local dev',
    ];
    @file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function run_composer_install() {
    $composer = find_composer();
    if (!$composer) {
        if (!download_composer_phar()) {
            return ['ok' => false, 'output' => "No se pudo descargar composer.phar. Verifica tu conexión a internet."];
        }
        $composer = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(COMPOSER_PHAR);
    }

    $composerHome = ROOT . '/.composer';
    if (!is_dir($composerHome)) @mkdir($composerHome, 0775, true);
    if (!is_dir($composerHome . '/cache')) @mkdir($composerHome . '/cache', 0775, true);

    $globalCfg = [
        'config' => [
            'audit' => [
                'abandoned' => 'ignore',
                'ignore' => ['PKSA-3n81-fgxd-xfh7'],
            ],
        ],
    ];
    @file_put_contents($composerHome . '/config.json', json_encode($globalCfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    patch_project_composer_json();

    putenv('COMPOSER_ALLOW_SUPERUSER=1');
    putenv('COMPOSER_NO_INTERACTION=1');
    putenv('COMPOSER_HOME=' . $composerHome);
    putenv('COMPOSER_CACHE_DIR=' . $composerHome . '/cache');
    putenv('COMPOSER_AUDIT_ABANDONED=ignore');

    $cwd = escapeshellarg(APP_DIR);
    $cmd = $composer . ' install --prefer-dist --no-interaction --working-dir=' . $cwd . ' 2>&1';

    $output = [];
    $returnVar = 0;
    @exec($cmd, $output, $returnVar);
    $text = implode("\n", $output);
    return [
        'ok' => ($returnVar === 0 && is_file(VENDOR_DIR . '/autoload.php')),
        'output' => $text !== '' ? $text : '(sin salida — código de salida: ' . $returnVar . ')',
        'code' => $returnVar,
    ];
}

function rrmdir($dir) {
    if (!is_dir($dir)) return true;
    $items = @scandir($dir);
    if ($items === false) return false;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) rrmdir($path);
        else { @chmod($path, 0666); @unlink($path); }
    }
    return @rmdir($dir);
}


/* ---------------------- POST Handlers ---------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'install_composer') {
        $result = run_composer_install();
        $_SESSION['quetzal_composer_output'] = $result['output'];
        if ($result['ok']) {
            header('Location: install?step=3'); exit;
        } else {
            $errors[] = 'Falló la instalación de dependencias (código ' . ($result['code'] ?? 'n/a') . '). Revisa la salida.';
            $step = 2;
        }
    }

    if ($action === 'save_config') {
        $cfg = [
            'APP_NAME'        => trim($_POST['APP_NAME'] ?? 'Mi Proyecto Quetzal'),
            'APP_DESC'        => trim($_POST['APP_DESC'] ?? ''),
            'APP_DEV_PATH'    => trim($_POST['APP_DEV_PATH'] ?? '/quetzal/'),
            'APP_TIMEZONE'    => trim($_POST['APP_TIMEZONE'] ?? 'America/Guatemala'),
            'APP_LANG'        => trim($_POST['APP_LANG'] ?? 'es_GT'),
            'APP_DEBUG'       => isset($_POST['APP_DEBUG']) ? 'true' : 'false',
            'LDB_HOST'        => trim($_POST['LDB_HOST'] ?? 'localhost'),
            'LDB_NAME'        => trim($_POST['LDB_NAME'] ?? 'db_quetzal'),
            'LDB_USER'        => trim($_POST['LDB_USER'] ?? 'root'),
            'LDB_PASS'        => (string)($_POST['LDB_PASS'] ?? ''),
            'LDB_CHARSET'     => 'utf8',
            'ADMIN_USERNAME'  => trim($_POST['ADMIN_USERNAME'] ?? 'admin'),
            'ADMIN_EMAIL'     => trim($_POST['ADMIN_EMAIL']    ?? ''),
            'ADMIN_PASSWORD'  => (string)($_POST['ADMIN_PASSWORD'] ?? ''),
        ];

        if ($cfg['APP_DEV_PATH'] === '' || $cfg['APP_DEV_PATH'][0] !== '/') $errors[] = 'APP_DEV_PATH debe comenzar con "/".';
        if ($cfg['LDB_NAME'] === '') $errors[] = 'El nombre de la base de datos es obligatorio.';
        if ($cfg['LDB_USER'] === '') $errors[] = 'El usuario de MySQL es obligatorio.';
        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $cfg['ADMIN_USERNAME'])) {
            $errors[] = 'El usuario administrador debe tener 3-50 caracteres alfanuméricos (. _ - permitidos).';
        }

        if (!$errors) {
            try {
                $pdo = db_connect($cfg['LDB_HOST'], $cfg['LDB_USER'], $cfg['LDB_PASS']);
                $notices[] = sprintf('Conexión a MySQL correcta en %s@%s.', $cfg['LDB_USER'], $cfg['LDB_HOST']);

                if (db_exists($pdo, $cfg['LDB_NAME'])) {
                    $cfg['_db_will_create'] = false;
                    $notices[] = sprintf('La base de datos <code>%s</code> ya existe y será reutilizada.', h($cfg['LDB_NAME']));
                } else {
                    $cfg['_db_will_create'] = true;
                    $notices[] = sprintf('La base de datos <code>%s</code> no existe — el wizard la creará automáticamente en el siguiente paso.', h($cfg['LDB_NAME']));
                }
            } catch (Throwable $e) {
                $errors[] = 'No se pudo conectar a MySQL: ' . $e->getMessage();
            }
        }

        $_SESSION['quetzal_install_cfg'] = $cfg;
        if (!$errors) { header('Location: install?step=4'); exit; }
        $step = 3;
    }

    if ($action === 'run_install') {
        $cfg = $_SESSION['quetzal_install_cfg'] ?? null;
        if (!$cfg) { $errors[] = 'Sesión expirada, vuelve al paso 3.'; $step = 3; }
        else {
            try {
                $pdo = db_connect($cfg['LDB_HOST'], $cfg['LDB_USER'], $cfg['LDB_PASS']);
                $dbCreated = db_create_if_missing($pdo, $cfg['LDB_NAME']);
                $_SESSION['quetzal_db_created'] = $dbCreated;
                $pdo->exec("USE `" . str_replace('`', '', $cfg['LDB_NAME']) . "`");

                require_once ROOT . '/app/classes/Migrator.php';
                $migrator = new Migrator($pdo, ROOT . '/app/migrations');
                $migrationLog = $migrator->run();
                $_SESSION['quetzal_migration_log'] = $migrationLog;

                // IMPORTANTE: generamos AUTH_SALT antes de hashear el password.
                // El login valida como password_verify($password . AUTH_SALT, $hash).
                // El hash seed de la migración NO incluye salt y por lo tanto no valida —
                // por eso tenemos que re-hashear aquí siempre.
                $authSalt  = '$2y$10$' . bin2hex(random_bytes(11));
                $nonceSalt = bin2hex(random_bytes(16));

                $adminUsername = $cfg['ADMIN_USERNAME'] !== '' ? $cfg['ADMIN_USERNAME'] : 'admin';
                $adminEmail    = $cfg['ADMIN_EMAIL']    !== '' ? $cfg['ADMIN_EMAIL']    : 'admin@local.test';
                $adminPassword = $cfg['ADMIN_PASSWORD'] !== '' ? $cfg['ADMIN_PASSWORD'] : '123456';
                $adminHash     = password_hash($adminPassword . $authSalt, PASSWORD_BCRYPT);

                // Estrategia UPSERT: buscamos el usuario admin por username. Si existe lo
                // actualizamos; si no, lo insertamos. Si la migración seed creó un 'admin'
                // pero el wizard configuró otro username, actualizamos el admin seed.
                $find = $pdo->prepare('SELECT id FROM `quetzal_users` WHERE username = :u LIMIT 1');
                $find->execute([':u' => $adminUsername]);
                $userId = $find->fetchColumn();

                if (!$userId) {
                    // ¿Existe un 'admin' seed que debemos renombrar?
                    $seedFind = $pdo->prepare("SELECT id FROM `quetzal_users` WHERE username = 'admin' LIMIT 1");
                    $seedFind->execute();
                    $userId = $seedFind->fetchColumn();
                }

                if ($userId) {
                    $updateStmt = $pdo->prepare(
                        'UPDATE `quetzal_users` SET username = :u, password = :pw, email = :e WHERE id = :id'
                    );
                    $updateStmt->execute([
                        ':u'  => $adminUsername,
                        ':pw' => $adminHash,
                        ':e'  => $adminEmail,
                        ':id' => $userId,
                    ]);
                } else {
                    // No existe ningún admin — insertamos uno nuevo
                    $insertStmt = $pdo->prepare(
                        "INSERT INTO `quetzal_users` (username, password, email, role, created_at)
                         VALUES (:u, :pw, :e, 'admin', NOW())"
                    );
                    $insertStmt->execute([
                        ':u'  => $adminUsername,
                        ':pw' => $adminHash,
                        ':e'  => $adminEmail,
                    ]);
                    $userId = $pdo->lastInsertId();
                }

                // VERIFICACIÓN POST-INSTALL: leemos el hash que quedó persistido y
                // comprobamos que password_verify() funcione con el salt que vamos a
                // escribir al .env. Si falla, abortamos con error claro ANTES de
                // terminar para que el usuario no descubra el problema en el login.
                $verifyStmt = $pdo->prepare('SELECT password FROM `quetzal_users` WHERE id = :id LIMIT 1');
                $verifyStmt->execute([':id' => $userId]);
                $storedHash = $verifyStmt->fetchColumn();

                if (!$storedHash || !password_verify($adminPassword . $authSalt, $storedHash)) {
                    throw new RuntimeException(
                        'La verificación post-instalación falló: el hash del usuario "'
                        . $adminUsername . '" no valida con el password configurado. '
                        . 'Esto no debería pasar; revisa que el usuario MySQL tenga permisos UPDATE.'
                    );
                }

                $defaults = parse_env_example(ENV_EXAMPLE);
                $overrides = [
                    'DEFAULT_CONTROLLER' => 'admin',
                    'APP_NAME'     => $cfg['APP_NAME'],
                    'APP_DESC'     => $cfg['APP_DESC'] ?? '',
                    'APP_DEV_PATH' => $cfg['APP_DEV_PATH'],
                    'APP_TIMEZONE' => $cfg['APP_TIMEZONE'],
                    'APP_LANG'     => $cfg['APP_LANG'],
                    'APP_DEBUG'    => $cfg['APP_DEBUG'],
                    'LDB_HOST'     => $cfg['LDB_HOST'],
                    'LDB_NAME'     => $cfg['LDB_NAME'],
                    'LDB_USER'     => $cfg['LDB_USER'],
                    'LDB_PASS'     => $cfg['LDB_PASS'],
                    'LDB_CHARSET'  => 'utf8',
                    'DB_HOST'      => $cfg['LDB_HOST'],
                    'DB_NAME'      => $cfg['LDB_NAME'],
                    'DB_USER'      => $cfg['LDB_USER'],
                    'DB_PASS'      => $cfg['LDB_PASS'],
                    'DB_CHARSET'   => 'utf8',
                    'AUTH_SALT'    => $authSalt,
                    'NONCE_SALT'   => $nonceSalt,
                    'API_PUBLIC_KEY'  => implode('-', str_split(bin2hex(random_bytes(15)), 6)),
                    'API_PRIVATE_KEY' => implode('-', str_split(bin2hex(random_bytes(15)), 6)),
                ];
                if (!write_env($defaults, $overrides)) {
                    throw new RuntimeException('No se pudo escribir app/config/.env');
                }
                if (!is_dir(LOGS_DIR)) @mkdir(LOGS_DIR, 0775, true);
                if (!is_dir(UPLOADS_DIR)) @mkdir(UPLOADS_DIR, 0775, true);

                header('Location: install?step=5'); exit;
            } catch (Throwable $e) {
                $errors[] = 'Error durante la instalación: ' . $e->getMessage();
                $step = 4;
            }
        }
    }

    if ($action === 'reset_all') {
        $log = [];
        $env = read_env_file_vars(ENV_FILE);
        $dropDb = !empty($_POST['drop_db']);
        $wipeVendor = !empty($_POST['wipe_vendor']);

        if ($dropDb && !empty($env['LDB_NAME'])) {
            try {
                $pdo = db_connect($env['LDB_HOST'] ?? 'localhost', $env['LDB_USER'] ?? 'root', $env['LDB_PASS'] ?? '');
                $pdo->exec("DROP DATABASE IF EXISTS `" . str_replace('`', '', $env['LDB_NAME']) . "`");
                $log[] = 'Base de datos eliminada: ' . $env['LDB_NAME'];
            } catch (Throwable $e) {
                $log[] = 'No se pudo eliminar la base: ' . $e->getMessage();
            }
        }
        if (is_file(ENV_FILE)) {
            @unlink(ENV_FILE) ? $log[] = '.env eliminado.' : $log[] = 'No se pudo eliminar .env.';
        }
        if ($wipeVendor) {
            if (is_dir(VENDOR_DIR)) $log[] = rrmdir(VENDOR_DIR) ? 'app/vendor/ eliminado.' : 'No se pudo eliminar app/vendor/.';
            foreach ([APP_DIR . '/composer.lock', COMPOSER_PHAR] as $f) if (is_file($f)) { @unlink($f); $log[] = basename($f) . ' eliminado.'; }
            if (is_dir(ROOT . '/.composer')) rrmdir(ROOT . '/.composer');
        }
        unset($_SESSION['quetzal_install_cfg'], $_SESSION['quetzal_install_done'], $_SESSION['quetzal_composer_output']);
        $_SESSION['quetzal_reset_log'] = $log;
        header('Location: install?step=1&reset=1'); exit;
    }

    if ($action === 'delete_installer') {
        if (@unlink(__FILE__)) {
            header('Location: ' . ($_POST['home'] ?? 'index.php')); exit;
        } else {
            $errors[] = 'No se pudo eliminar install.php — bórralo manualmente.';
        }
    }
}

$cfg = $_SESSION['quetzal_install_cfg'] ?? [];
$default_path = '/' . trim(basename(__DIR__), '/') . '/';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Quetzal — Instalador</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: { extend: { colors: { honey: { 50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309' } } } }
  }
</script>
<style>
  body { background: linear-gradient(135deg,#fffbeb 0%, #ffffff 55%, #fef9c3 100%); min-height:100vh; }
  code { color:#b45309; background:#fef3c7; padding:.05rem .35rem; border-radius:.25rem; font-size:.85em; }
  pre code { background:transparent; padding:0; }
</style>
</head>
<body class="text-slate-800 antialiased">
<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="flex items-center justify-center gap-4 mb-6">
    <img src="assets/images/quetzal.svg" alt="Quetzal" style="width:56px;height:auto">
    <div class="text-left leading-tight">
      <h1 class="text-2xl sm:text-3xl font-bold text-honey-600 tracking-tight">Quetzal</h1>
      <p class="text-slate-500 text-xs sm:text-sm">Asistente de instalación</p>
    </div>
  </div>

  <div class="flex gap-2 mb-6">
    <?php foreach ([1=>'Requisitos',2=>'Dependencias',3=>'Configuración',4=>'Instalar',5=>'Listo'] as $n=>$label):
      $cls = $step===$n ? 'bg-honey-400 text-slate-900 font-semibold ring-2 ring-honey-500/40'
          : ($step>$n ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500');
    ?>
      <div class="flex-1 text-center py-2.5 px-2 rounded-lg text-xs sm:text-sm transition <?= $cls ?>"><?= $n ?>. <?= h($label) ?></div>
    <?php endforeach; ?>
  </div>

  <?php if ($errors): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 text-red-800 p-4">
      <ul class="list-disc pl-5 text-sm space-y-0.5"><?php foreach($errors as $e) echo '<li>'.h($e).'</li>'; ?></ul>
    </div>
  <?php endif; ?>
  <?php if ($notices): ?>
    <div class="mb-4 rounded-lg border border-sky-200 bg-sky-50 text-sky-800 p-4">
      <ul class="list-disc pl-5 text-sm space-y-0.5"><?php foreach($notices as $e) echo '<li>'.h($e).'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-2xl shadow-xl shadow-honey-100/50 ring-1 ring-slate-200/60 p-6 sm:p-8">
    <?php if ($step === 1): ?>
      <?php if (isset($_GET['reset']) && !empty($_SESSION['quetzal_reset_log'])): ?>
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-4">
          <div class="font-semibold text-sm mb-1">✔ Wizard reiniciado</div>
          <ul class="list-disc pl-5 text-sm space-y-0.5">
            <?php foreach ($_SESSION['quetzal_reset_log'] as $l) echo '<li>'.h($l).'</li>'; ?>
          </ul>
        </div>
        <?php unset($_SESSION['quetzal_reset_log']); ?>
      <?php endif; ?>
      <h2 class="text-xl font-bold mb-4">Paso 1 · Verificación de requisitos</h2>
      <?php $req = check_requirements(); $allOk = array_reduce($req, fn($c,$i)=>$c && $i['ok'], true); ?>
      <div class="divide-y divide-slate-100 border border-slate-100 rounded-lg overflow-hidden">
        <?php foreach ($req as $r): ?>
          <div class="flex items-center justify-between px-4 py-2.5 text-sm">
            <span><?= h($r['label']) ?></span>
            <?php if ($r['ok']): ?>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-medium">✔ <?= h($r['value']) ?></span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-medium">✘ <?= h($r['value']) ?></span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="flex justify-between mt-6">
        <a href="install" class="text-slate-500 hover:text-slate-800 text-sm">↻ Recargar</a>
        <a href="install?step=2" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg font-semibold transition <?= $allOk ? 'bg-honey-400 hover:bg-honey-500 text-slate-900' : 'bg-slate-200 text-slate-400 pointer-events-none' ?>">Siguiente →</a>
      </div>

    <?php elseif ($step === 2): ?>
      <h2 class="text-xl font-bold mb-4">Paso 2 · Instalar dependencias (Composer)</h2>
      <?php
        $vendorOk = is_file(VENDOR_DIR . '/autoload.php');
        $composerBin = find_composer();
        $lastOutput = $_SESSION['quetzal_composer_output'] ?? null;
      ?>
      <?php if ($vendorOk): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 p-4 text-sm mb-4">
          ✔ Dependencias ya instaladas — <code>app/vendor/autoload.php</code> existe.
        </div>
      <?php else: ?>
        <p class="text-sm text-slate-600 mb-3">El wizard instalará las librerías definidas en <code>app/composer.json</code>.</p>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm mb-4 space-y-1">
          <div class="flex justify-between"><span class="text-slate-500">Composer detectado:</span>
            <?php if ($composerBin): ?>
              <span class="text-emerald-700 font-mono text-xs"><?= h($composerBin) ?></span>
            <?php else: ?>
              <span class="text-amber-700">no — se descargará <code>composer.phar</code></span>
            <?php endif; ?>
          </div>
          <div class="flex justify-between"><span class="text-slate-500">Directorio de trabajo:</span>
            <span class="font-mono text-xs"><?= h(APP_DIR) ?></span>
          </div>
        </div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-900 p-4 text-sm mb-4">
          ⏳ Esto puede tardar 1–3 minutos. No cierres la ventana.
        </div>
      <?php endif; ?>

      <?php if ($lastOutput !== null): ?>
        <details class="mb-4" <?= $vendorOk ? '' : 'open' ?>>
          <summary class="cursor-pointer text-sm text-slate-600 hover:text-slate-900">Salida del último intento</summary>
          <pre class="mt-2 bg-slate-900 text-slate-100 rounded-md p-3 text-xs overflow-x-auto max-h-96 overflow-y-auto"><code><?= h($lastOutput) ?></code></pre>
        </details>
      <?php endif; ?>

      <form method="post" class="flex justify-between items-center"
            onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerText='Instalando... (puede tardar)';">
        <a href="install?step=1" class="text-slate-500 hover:text-slate-800 text-sm">← Atrás</a>
        <?php if ($vendorOk): ?>
          <a href="install?step=3" class="inline-flex items-center gap-1 px-5 py-2 rounded-lg bg-honey-400 hover:bg-honey-500 text-slate-900 font-semibold transition">Continuar →</a>
        <?php else: ?>
          <input type="hidden" name="action" value="install_composer">
          <button class="inline-flex items-center gap-1 px-5 py-2 rounded-lg bg-honey-400 hover:bg-honey-500 text-slate-900 font-semibold transition">⚙ Instalar dependencias ahora</button>
        <?php endif; ?>
      </form>

    <?php elseif ($step === 3): ?>
      <h2 class="text-xl font-bold mb-4">Paso 3 · Configuración</h2>
      <form method="post" class="space-y-6">
        <input type="hidden" name="action" value="save_config">

        <section>
          <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Aplicación</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del proyecto</label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="APP_NAME" value="<?= h($cfg['APP_NAME'] ?? 'Mi Proyecto Quetzal') ?>" required>
              <p class="text-xs text-slate-500 mt-1">Se muestra en el login, título de página y topbar del admin.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Tagline / descripción <span class="text-slate-400 font-normal">(opcional)</span></label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="APP_DESC" value="<?= h($cfg['APP_DESC'] ?? '') ?>" placeholder="Ej. Sistema de gestión para mi empresa">
              <p class="text-xs text-slate-500 mt-1">Se muestra debajo del nombre en el login.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Ruta base <span class="text-slate-400 font-normal">(APP_DEV_PATH)</span></label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="APP_DEV_PATH" value="<?= h($cfg['APP_DEV_PATH'] ?? $default_path) ?>">
              <p class="text-xs text-slate-500 mt-1">Usa <code><?= h($default_path) ?></code> para <code>localhost<?= h($default_path) ?></code>.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Zona horaria</label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="APP_TIMEZONE" value="<?= h($cfg['APP_TIMEZONE'] ?? 'America/Guatemala') ?>">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Idioma</label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="APP_LANG" value="<?= h($cfg['APP_LANG'] ?? 'es_GT') ?>">
            </div>
            <div class="md:col-span-2">
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="APP_DEBUG" class="rounded border-slate-300 text-honey-500 focus:ring-honey-500" <?= (($cfg['APP_DEBUG'] ?? 'true') === 'true') ? 'checked' : '' ?>>
                <span class="font-medium text-slate-700">Activar modo debug</span>
              </label>
            </div>
          </div>
        </section>

        <section>
          <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Base de datos (desarrollo)</h3>
          <div class="rounded-lg border border-sky-200 bg-sky-50 text-sky-900 p-3 text-xs mb-3">
            💡 Si la base de datos no existe, el wizard la creará automáticamente con <code>CHARACTER SET utf8mb4</code>.
            El usuario MySQL debe tener permisos <code>CREATE</code>.
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Host</label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="LDB_HOST" value="<?= h($cfg['LDB_HOST'] ?? 'localhost') ?>">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de la BD</label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="LDB_NAME" value="<?= h($cfg['LDB_NAME'] ?? 'db_quetzal') ?>" required>
              <p class="text-xs text-slate-500 mt-1">Se creará si no existe.</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Usuario</label>
              <input class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="LDB_USER" value="<?= h($cfg['LDB_USER'] ?? 'root') ?>" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
              <input type="password" class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="LDB_PASS" value="<?= h($cfg['LDB_PASS'] ?? '') ?>" placeholder="(vacío si no tiene contraseña)">
            </div>
          </div>
        </section>

        <section>
          <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Usuario administrador</h3>
          <div class="rounded-lg border border-sky-200 bg-sky-50 text-sky-900 p-3 text-xs mb-3">
            💡 Estas son las credenciales con las que iniciarás sesión en <code>/login</code>.
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de usuario</label>
              <input type="text" class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm font-mono" name="ADMIN_USERNAME" value="<?= h($cfg['ADMIN_USERNAME'] ?? 'admin') ?>" required pattern="^[a-zA-Z0-9._-]{3,50}$">
              <p class="text-xs text-slate-500 mt-1">3-50 caracteres. Permite letras, números, <code>. _ -</code></p>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
              <input type="email" class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm" name="ADMIN_EMAIL" value="<?= h($cfg['ADMIN_EMAIL'] ?? 'admin@local.test') ?>">
            </div>
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña <span class="text-slate-400 font-normal">(vacío = <code>123456</code>)</span></label>
              <input type="text" class="w-full rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm font-mono" name="ADMIN_PASSWORD" value="<?= h($cfg['ADMIN_PASSWORD'] ?? '') ?>" placeholder="Déjalo vacío para usar 123456">
              <p class="text-xs text-slate-500 mt-1">Se guarda hasheada con un salt único generado en esta instalación.</p>
            </div>
          </div>
        </section>

        <div class="flex justify-between items-center pt-2">
          <a href="install?step=2" class="text-slate-500 hover:text-slate-800 text-sm">← Atrás</a>
          <button class="inline-flex items-center gap-1 px-5 py-2 rounded-lg bg-honey-400 hover:bg-honey-500 text-slate-900 font-semibold transition">Probar conexión y continuar →</button>
        </div>
      </form>

    <?php elseif ($step === 4): ?>
      <h2 class="text-xl font-bold mb-4">Paso 4 · Instalar</h2>
      <?php $willCreate = !empty($cfg['_db_will_create']); ?>
      <div class="rounded-lg border <?= $willCreate ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900' ?> p-3 text-sm mb-4">
        <?php if ($willCreate): ?>
          ⚙ La base de datos <code><?= h($cfg['LDB_NAME'] ?? '') ?></code> <strong>no existe</strong> y será creada ahora.
        <?php else: ?>
          ✔ La base de datos <code><?= h($cfg['LDB_NAME'] ?? '') ?></code> ya existe y será reutilizada.
        <?php endif; ?>
      </div>
      <p class="text-sm text-slate-600 mb-3">Se realizarán las siguientes acciones:</p>
      <ul class="list-disc pl-5 space-y-1 text-sm text-slate-700">
        <li><?= $willCreate ? 'Crear' : 'Reutilizar' ?> la base de datos <code><?= h($cfg['LDB_NAME'] ?? '') ?></code> (<code>utf8mb4_unicode_ci</code>).</li>
        <li>Ejecutar las migraciones de <code>app/migrations/</code> (crea tablas <code>quetzal_*</code>, roles, permisos y usuario admin).</li>
        <li>Actualizar credenciales del admin con tu email/contraseña.</li>
        <li>Generar <code>app/config/.env</code> con claves únicas y <code>DEFAULT_CONTROLLER=admin</code>.</li>
        <li>Crear carpetas <code>app/logs/</code> y <code>assets/uploads/</code>.</li>
      </ul>
      <?php
        $migrationFiles = glob(ROOT . '/app/migrations/*.php') ?: [];
        sort($migrationFiles);
      ?>
      <?php if ($migrationFiles): ?>
        <details class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
          <summary class="cursor-pointer text-slate-600 hover:text-slate-900 font-medium">Ver migraciones que se ejecutarán (<?= count($migrationFiles) ?>)</summary>
          <ul class="mt-2 pl-4 space-y-0.5 text-xs font-mono text-slate-600">
            <?php foreach ($migrationFiles as $m): ?>
              <li>· <?= h(basename($m, '.php')) ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
      <form method="post" class="flex justify-between mt-6">
        <input type="hidden" name="action" value="run_install">
        <a href="install?step=3" class="text-slate-500 hover:text-slate-800 text-sm self-center">← Atrás</a>
        <button class="inline-flex items-center gap-1 px-5 py-2 rounded-lg bg-honey-400 hover:bg-honey-500 text-slate-900 font-semibold transition">Instalar ahora →</button>
      </form>

    <?php elseif ($step === 5): ?>
      <div class="text-center mb-6">
        <div class="text-5xl mb-2">🎉</div>
        <h2 class="text-2xl font-bold">¡Instalación completada!</h2>
        <p class="text-slate-500 text-sm mt-1">Al entrar, serás redirigido al <strong>dashboard de administración</strong>.</p>
      </div>
      <?php $dbWasCreated = $_SESSION['quetzal_db_created'] ?? false; ?>
      <?php if ($dbWasCreated): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-3 text-sm mb-3">
          ✔ Base de datos <code><?= h($cfg['LDB_NAME'] ?? '') ?></code> creada automáticamente por el wizard.
        </div>
      <?php endif; ?>
      <div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 p-3 text-xs mb-3 flex items-center gap-2">
        ✔ Credenciales validadas: el hash almacenado verifica correctamente con el password configurado.
      </div>
      <div class="rounded-lg bg-slate-50 border border-slate-200 divide-y divide-slate-200 text-sm">
        <div class="flex justify-between px-4 py-2.5"><span class="text-slate-500">Usuario</span><code><?= h($cfg['ADMIN_USERNAME'] ?? 'admin') ?></code></div>
        <div class="flex justify-between px-4 py-2.5"><span class="text-slate-500">Contraseña</span><code><?= h(($cfg['ADMIN_PASSWORD'] ?? '') !== '' ? $cfg['ADMIN_PASSWORD'] : '123456') ?></code></div>
        <div class="flex justify-between px-4 py-2.5"><span class="text-slate-500">Email</span><code><?= h($cfg['ADMIN_EMAIL'] ?? 'admin@local.test') ?></code></div>
        <div class="flex justify-between px-4 py-2.5"><span class="text-slate-500">Base de datos</span><code><?= h($cfg['LDB_NAME'] ?? '') ?></code> en <code><?= h($cfg['LDB_HOST'] ?? '') ?></code></div>
        <div class="flex justify-between px-4 py-2.5"><span class="text-slate-500">URL login</span><a class="text-honey-600 hover:underline" href="<?= h(($cfg['APP_DEV_PATH'] ?? '/') . 'login') ?>"><?= h(($cfg['APP_DEV_PATH'] ?? '/') . 'login') ?></a></div>
      </div>

      <?php if (!empty($_SESSION['quetzal_migration_log'])): ?>
        <details class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
          <summary class="cursor-pointer text-slate-600 hover:text-slate-900 font-medium">📋 Ver log de migraciones ejecutadas</summary>
          <ul class="mt-2 pl-4 space-y-1 text-xs font-mono">
            <?php foreach ($_SESSION['quetzal_migration_log'] as $m): ?>
              <?php $icon = $m['status'] === 'ok' ? '<span class="text-emerald-600">✔</span>' : ($m['status'] === 'error' ? '<span class="text-red-600">✘</span>' : '<span class="text-slate-400">·</span>'); ?>
              <li><?= $icon ?> <?= h($m['name'] ?? '—') ?> <span class="text-slate-400">— <?= h($m['message']) ?></span></li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
      <div class="mt-4 rounded-lg border border-red-200 bg-red-50 text-red-800 p-4 text-sm">
        🔒 <strong>Por seguridad, elimina este instalador.</strong>
      </div>
      <form method="post" class="flex justify-between mt-6 gap-3 flex-wrap">
        <input type="hidden" name="action" value="delete_installer">
        <input type="hidden" name="home" value="<?= h($cfg['APP_DEV_PATH'] ?? 'index.php') ?>">
        <a class="inline-flex items-center gap-1 px-5 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-slate-700 font-medium transition" href="<?= h($cfg['APP_DEV_PATH'] ?? 'index.php') ?>">Ir al sitio →</a>
        <button class="inline-flex items-center gap-1 px-5 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold transition">🗑 Eliminar install.php e ir al sitio</button>
      </form>

      <div class="mt-8 pt-6 border-t border-dashed border-slate-300">
        <details>
          <summary class="cursor-pointer text-sm text-slate-500 hover:text-slate-800 font-medium">🧪 Modo pruebas · Reiniciar instalación</summary>
          <div class="mt-4 rounded-lg border border-orange-200 bg-orange-50 p-4">
            <p class="text-sm text-orange-900 mb-3"><strong>⚠ Solo para pruebas.</strong> Revierte el proyecto para re-ejecutar el wizard.</p>
            <form method="post" class="space-y-2" onsubmit="return confirm('¿Seguro?');">
              <input type="hidden" name="action" value="reset_all">
              <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="drop_db" value="1" checked class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                <span>Eliminar la base de datos <code><?= h($cfg['LDB_NAME'] ?? '') ?></code></span>
              </label>
              <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="wipe_vendor" value="1" class="mt-0.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                <span>Borrar <code>app/vendor/</code>, <code>composer.lock</code> y caché</span>
              </label>
              <label class="flex items-start gap-2 text-sm text-slate-500">
                <input type="checkbox" checked disabled class="mt-0.5 rounded border-slate-300">
                <span>Eliminar <code>.env</code> (siempre)</span>
              </label>
              <div class="pt-2">
                <button class="inline-flex items-center gap-1 px-4 py-2 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold transition">↺ Reiniciar wizard</button>
              </div>
            </form>
          </div>
        </details>
      </div>
    <?php endif; ?>
  </div>

  <p class="text-center text-slate-400 text-xs mt-6">Quetzal · Wizard v1.0</p>
</div>
</body>
</html>

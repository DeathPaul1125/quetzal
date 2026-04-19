<?php
/**
 * Quetzal — CLI de migraciones
 *
 * Uso:
 *   php migrate.php              # corre migraciones pendientes
 *   php migrate.php up           # alias de lo anterior
 *   php migrate.php status       # lista migraciones y su estado
 *   php migrate.php rollback [N] # revierte los últimos N batches (default 1)
 *   php migrate.php fresh        # borra todas las tablas y vuelve a migrar
 *   php migrate.php make <name>  # crea un archivo de migración vacío
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo corre por CLI.');
}

$root = __DIR__;

if (!is_file($root . '/app/config/.env')) {
    fwrite(STDERR, "Error: app/config/.env no encontrado. Ejecuta el wizard primero.\n");
    exit(1);
}
if (!is_file($root . '/app/vendor/autoload.php')) {
    fwrite(STDERR, "Error: dependencias no instaladas. Ejecuta composer install o el wizard.\n");
    exit(1);
}

require_once $root . '/app/vendor/autoload.php';
require_once $root . '/app/classes/Migrator.php';

$dotenv = Dotenv\Dotenv::createImmutable($root . '/app/config');
$dotenv->load();

$env     = $_ENV;
$isLocal = !isset($_SERVER['REMOTE_ADDR']) || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
$host    = $isLocal ? ($env['LDB_HOST'] ?? 'localhost') : ($env['DB_HOST'] ?? 'localhost');
$dbname  = $isLocal ? ($env['LDB_NAME'] ?? '')          : ($env['DB_NAME'] ?? '');
$user    = $isLocal ? ($env['LDB_USER'] ?? 'root')      : ($env['DB_USER'] ?? 'root');
$pass    = $isLocal ? ($env['LDB_PASS'] ?? '')          : ($env['DB_PASS'] ?? '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a MySQL: " . $e->getMessage() . "\n");
    exit(1);
}

$migrator = new Migrator($pdo, $root . '/app/migrations');

$cmd = $argv[1] ?? 'up';

switch ($cmd) {
    case 'up':
    case 'migrate':
        echo "→ Ejecutando migraciones pendientes...\n";
        foreach ($migrator->run() as $r) {
            $icon = $r['status'] === 'ok' ? '✔' : ($r['status'] === 'error' ? '✘' : '·');
            echo sprintf("  %s %s — %s\n", $icon, $r['name'] ?? '(ninguna)', $r['message']);
        }
        break;

    case 'status':
        echo "Estado de migraciones:\n";
        foreach ($migrator->status() as $r) {
            $icon = $r['status'] === 'ran' ? '[✔ ran    ]' : '[· pending]';
            echo "  $icon $r[name]\n";
        }
        break;

    case 'rollback':
        $steps = isset($argv[2]) ? (int)$argv[2] : 1;
        echo "→ Revertiendo los últimos $steps batch(es)...\n";
        foreach ($migrator->rollback($steps) as $r) {
            $icon = $r['status'] === 'ok' ? '✔' : ($r['status'] === 'error' ? '✘' : '·');
            echo sprintf("  %s %s — %s\n", $icon, $r['name'] ?? '(ninguna)', $r['message']);
        }
        break;

    case 'fresh':
        echo "→ Revirtiendo todas las migraciones y recreando desde cero...\n";
        foreach ($migrator->fresh() as $r) {
            echo "  · Drop $r[name]\n";
        }
        foreach ($migrator->run() as $r) {
            $icon = $r['status'] === 'ok' ? '✔' : '✘';
            echo sprintf("  %s %s\n", $icon, $r['name']);
        }
        break;

    case 'make':
        $name = $argv[2] ?? null;
        if (!$name) { fwrite(STDERR, "Uso: php migrate.php make <descripcion>\n"); exit(1); }
        $stamp = date('Y_m_d_His');
        $slug  = preg_replace('/[^a-z0-9_]/', '_', strtolower($name));
        $file  = $root . "/app/migrations/{$stamp}_{$slug}.php";
        $tpl   = "<?php\n\nreturn new class {\n    public function up(PDO \$pdo): void {\n        // \$pdo->exec(\"CREATE TABLE ...\");\n    }\n\n    public function down(PDO \$pdo): void {\n        // \$pdo->exec(\"DROP TABLE ...\");\n    }\n};\n";
        file_put_contents($file, $tpl);
        echo "✔ Creada: " . basename($file) . "\n";
        break;

    default:
        fwrite(STDERR, "Comando desconocido: $cmd\n");
        fwrite(STDERR, "Uso: php migrate.php [up|status|rollback [N]|fresh|make <name>]\n");
        exit(1);
}

<?php

/**
 * Migrator — sistema de migraciones estilo Laravel para Quetzal
 *
 * Ejecuta archivos PHP en app/migrations/ en orden alfabético.
 * Cada archivo debe devolver una clase anónima con métodos up(PDO) y down(PDO).
 *
 * Lleva control en la tabla `quetzal_migrations`.
 */
class Migrator
{
    const TRACKING_TABLE = 'quetzal_migrations';

    protected PDO $pdo;
    protected string $path;
    protected string $trackingTable;

    public function __construct(PDO $pdo, string $path, ?string $trackingTable = null)
    {
        $this->pdo            = $pdo;
        $this->path           = rtrim($path, '/\\');
        $this->trackingTable  = $trackingTable ?: self::TRACKING_TABLE;
    }

    /**
     * Crea la tabla de control si no existe.
     */
    public function ensureTrackingTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . $this->trackingTable . '` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int(11) NOT NULL,
            `executed_at` datetime DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `migration_unique` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        $this->pdo->exec($sql);
    }

    /**
     * Lee los archivos de migración ordenados alfabéticamente.
     * @return array<string,string> name => full path
     */
    public function discover(): array
    {
        if (!is_dir($this->path)) return [];
        $files = glob($this->path . '/*.php') ?: [];
        sort($files);
        $out = [];
        foreach ($files as $f) {
            $out[basename($f, '.php')] = $f;
        }
        return $out;
    }

    /**
     * Migraciones ya ejecutadas.
     * @return string[] nombres
     */
    public function executed(): array
    {
        $stmt = $this->pdo->query('SELECT migration FROM `' . $this->trackingTable . '` ORDER BY id ASC');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    /**
     * Migraciones pendientes de ejecutar.
     */
    public function pending(): array
    {
        $executed = array_flip($this->executed());
        return array_diff_key($this->discover(), $executed);
    }

    /**
     * Ejecuta todas las pendientes. Devuelve un array con el log de resultados.
     */
    public function run(): array
    {
        $this->ensureTrackingTable();
        $pending = $this->pending();
        if (empty($pending)) return [['name' => null, 'status' => 'nothing', 'message' => 'Sin migraciones pendientes.']];

        $batch = $this->nextBatch();
        $log   = [];

        foreach ($pending as $name => $file) {
            try {
                $migration = require $file;
                if (!is_object($migration) || !method_exists($migration, 'up')) {
                    throw new RuntimeException("La migración $name no devuelve un objeto con método up().");
                }
                $migration->up($this->pdo);
                $stmt = $this->pdo->prepare('INSERT INTO `' . $this->trackingTable . '` (migration, batch) VALUES (:m, :b)');
                $stmt->execute([':m' => $name, ':b' => $batch]);
                $log[] = ['name' => $name, 'status' => 'ok', 'message' => 'Migrada'];
            } catch (Throwable $e) {
                $log[] = ['name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
                throw new RuntimeException("Error en migración $name: " . $e->getMessage(), 0, $e);
            }
        }
        return $log;
    }

    /**
     * Rollback del último batch (o de los últimos N).
     */
    public function rollback(int $steps = 1): array
    {
        $this->ensureTrackingTable();
        $stmt = $this->pdo->prepare('SELECT DISTINCT batch FROM `' . $this->trackingTable . '` ORDER BY batch DESC LIMIT :n');
        $stmt->bindValue(':n', $steps, PDO::PARAM_INT);
        $stmt->execute();
        $batches = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($batches)) return [['name' => null, 'status' => 'nothing', 'message' => 'Nada para revertir.']];

        $in = implode(',', array_map('intval', $batches));
        $stmt = $this->pdo->query('SELECT migration FROM `' . $this->trackingTable . '` WHERE batch IN (' . $in . ') ORDER BY id DESC');
        $toRollback = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $discovered = $this->discover();
        $log = [];
        foreach ($toRollback as $name) {
            if (!isset($discovered[$name])) {
                $log[] = ['name' => $name, 'status' => 'skip', 'message' => 'Archivo no encontrado, no se puede revertir.'];
                continue;
            }
            try {
                $migration = require $discovered[$name];
                if (is_object($migration) && method_exists($migration, 'down')) {
                    $migration->down($this->pdo);
                }
                $this->pdo->prepare('DELETE FROM `' . $this->trackingTable . '` WHERE migration = :m')->execute([':m' => $name]);
                $log[] = ['name' => $name, 'status' => 'ok', 'message' => 'Revertida'];
            } catch (Throwable $e) {
                $log[] = ['name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }
        return $log;
    }

    /**
     * Reset completo: revierte todo y borra la tabla de control.
     */
    public function fresh(): array
    {
        $this->ensureTrackingTable();
        $executed   = array_reverse($this->executed());
        $discovered = $this->discover();
        $log = [];
        foreach ($executed as $name) {
            if (!isset($discovered[$name])) continue;
            try {
                $m = require $discovered[$name];
                if (is_object($m) && method_exists($m, 'down')) $m->down($this->pdo);
                $log[] = ['name' => $name, 'status' => 'ok', 'message' => 'Drop'];
            } catch (Throwable $e) {
                $log[] = ['name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
            }
        }
        $this->pdo->exec('DROP TABLE IF EXISTS `' . $this->trackingTable . '`');
        return $log;
    }

    /**
     * Estado de migraciones: cuáles corridas, cuáles no.
     */
    public function status(): array
    {
        $this->ensureTrackingTable();
        $executed   = array_flip($this->executed());
        $discovered = $this->discover();
        $out = [];
        foreach ($discovered as $name => $_) {
            $out[] = ['name' => $name, 'status' => isset($executed[$name]) ? 'ran' : 'pending'];
        }
        return $out;
    }

    protected function nextBatch(): int
    {
        $row = $this->pdo->query('SELECT COALESCE(MAX(batch), 0) + 1 FROM `' . $this->trackingTable . '`')->fetchColumn();
        return (int)$row;
    }
}

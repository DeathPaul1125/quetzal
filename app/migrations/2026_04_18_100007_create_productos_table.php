<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE `productos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `sku` varchar(100) DEFAULT NULL,
                `nombre` varchar(255) DEFAULT '',
                `slug` varchar(255) DEFAULT NULL,
                `descripcion` varchar(255) DEFAULT NULL,
                `precio` decimal(10,2) DEFAULT NULL,
                `precio_comparacion` decimal(10,2) DEFAULT NULL,
                `stock` int(10) DEFAULT NULL,
                `rastrear_stock` tinyint(5) DEFAULT 0,
                `imagen` varchar(255) DEFAULT NULL,
                `creado` datetime DEFAULT current_timestamp(),
                `actualizado` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug_unique` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `productos`");
    }
};

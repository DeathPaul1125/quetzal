<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE `quetzal_sessions` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_usuario` int(10) unsigned NOT NULL,
                `token` varchar(255) DEFAULT '',
                `navegador` varchar(100) DEFAULT NULL,
                `sistema_operativo` varchar(100) DEFAULT NULL,
                `ip` varchar(50) DEFAULT NULL,
                `validez` int(10) unsigned DEFAULT NULL,
                `creado` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `id_usuario` (`id_usuario`),
                KEY `token` (`token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `quetzal_sessions`");
    }
};

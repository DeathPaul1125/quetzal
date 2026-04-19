<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE `posts` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `tipo` varchar(100) DEFAULT '',
                `id_padre` bigint(20) DEFAULT NULL,
                `id_usuario` bigint(20) DEFAULT NULL,
                `id_ref` bigint(20) DEFAULT NULL,
                `titulo` varchar(255) DEFAULT NULL,
                `permalink` varchar(255) DEFAULT NULL,
                `contenido` text DEFAULT NULL,
                `status` varchar(255) DEFAULT NULL,
                `mime_type` varchar(255) DEFAULT NULL,
                `creado` datetime DEFAULT current_timestamp(),
                `actualizado` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `tipo` (`tipo`),
                KEY `permalink` (`permalink`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE `posts_meta` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_post` int(11) NOT NULL,
                `meta` varchar(255) DEFAULT NULL,
                `valor` text DEFAULT NULL,
                `creado` datetime DEFAULT current_timestamp(),
                `actualizado` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `id_post` (`id_post`),
                KEY `meta` (`meta`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `posts_meta`");
        $pdo->exec("DROP TABLE IF EXISTS `posts`");
    }
};

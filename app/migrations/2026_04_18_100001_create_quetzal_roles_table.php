<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE `quetzal_roles` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `nombre` varchar(100) DEFAULT NULL,
                `slug` varchar(100) DEFAULT NULL,
                `creado` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `slug_unique` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            INSERT INTO `quetzal_roles` (`nombre`, `slug`, `creado`) VALUES
            ('Administrador general', 'admin',  NOW()),
            ('Trabajador',            'worker', NOW())
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `quetzal_roles`");
    }
};

<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE `quetzal_users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `auth_token` varchar(255) DEFAULT NULL,
                `username` varchar(255) DEFAULT NULL,
                `password` varchar(255) DEFAULT NULL,
                `email` varchar(255) DEFAULT NULL,
                `role` varchar(100) DEFAULT 'admin',
                `created_at` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `username_unique` (`username`),
                UNIQUE KEY `email_unique` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Usuario admin por defecto: admin / 123456
        $pdo->exec("
            INSERT INTO `quetzal_users` (`username`, `password`, `email`, `role`, `created_at`)
            VALUES ('admin', '\$2y\$10\$xHEI5cJ3q7rBJaL.M9qBRe909ahHvIZVTfRRxlLqfnWwAYwWQE/Wu', 'admin@local.test', 'admin', NOW())
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `quetzal_users`");
    }
};

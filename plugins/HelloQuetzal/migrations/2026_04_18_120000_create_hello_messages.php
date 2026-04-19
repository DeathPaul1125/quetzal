<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `hello_messages` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `message` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("INSERT INTO `hello_messages` (`message`) VALUES ('¡Bienvenido a HelloQuetzal!')");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `hello_messages`");
    }
};

<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE `quetzal_roles_permisos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `id_role` int(11) NOT NULL,
                `id_permiso` int(11) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `role_permiso_unique` (`id_role`, `id_permiso`),
                KEY `id_role` (`id_role`),
                KEY `id_permiso` (`id_permiso`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // admin → admin-access
        $pdo->exec("
            INSERT INTO `quetzal_roles_permisos` (`id_role`, `id_permiso`)
            SELECT r.id, p.id FROM `quetzal_roles` r CROSS JOIN `quetzal_permisos` p
            WHERE r.slug = 'admin' AND p.slug = 'admin-access'
        ");
        // worker → list + add
        $pdo->exec("
            INSERT INTO `quetzal_roles_permisos` (`id_role`, `id_permiso`)
            SELECT r.id, p.id FROM `quetzal_roles` r CROSS JOIN `quetzal_permisos` p
            WHERE r.slug = 'worker' AND p.slug IN ('list-all-products', 'add-products')
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `quetzal_roles_permisos`");
    }
};

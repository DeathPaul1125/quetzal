<?php

/**
 * Quetzal — punto de entrada
 *
 * Framework PHP ligero y flexible.
 */

// Si la instalación está incompleta y existe el wizard, redirige al instalador
if (is_file(__DIR__ . '/install.php')
    && (!is_file(__DIR__ . '/app/config/.env') || !is_file(__DIR__ . '/app/vendor/autoload.php'))) {
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    header('Location: ' . ($scriptDir === '' ? '' : $scriptDir) . '/install');
    exit;
}

// Requerir la clase principal del framework
require_once 'app/classes/Quetzal.php';

// Ejecutar Quetzal
Quetzal::fly();
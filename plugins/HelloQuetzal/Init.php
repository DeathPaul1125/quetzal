<?php

/**
 * Bootstrap del plugin HelloQuetzal.
 *
 * Este archivo se ejecuta automáticamente cuando el plugin está habilitado,
 * después de que el Autoloader y View ya tienen registrados los paths del plugin.
 *
 * Úsalo para registrar hooks, rutas, directivas Blade o cualquier setup
 * que requiera el plugin al arrancar.
 */

// Hook de ejemplo: agrega un header a la respuesta cuando el plugin está activo
QuetzalHookManager::registerHook('plugin_loaded', function ($plugin) {
  if ($plugin['name'] === 'HelloQuetzal' && !headers_sent()) {
    header('X-Plugin-HelloQuetzal: active');
  }
});

// Hook de ejemplo: registra una directiva Blade personalizada
QuetzalHookManager::registerHook('on_blade_setup', function ($blade, $compiler) {
  $compiler->directive('hello', function ($expression) {
    return "<?php echo '¡Hola ' . ($expression) . '!'; ?>";
  });
});

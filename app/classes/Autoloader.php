<?php

class Autoloader
{
  /**
   * Paths adicionales de búsqueda para clases, registrados por plugins.
   * Se recorren antes de los paths del core (último plugin habilitado gana).
   *
   * @var array<string>
   */
  private static array $pluginPaths = [];

  /**
   * Método encargado de ejecutar el autocargador de forma estática.
   *
   * @return void
   */
  public static function init()
  {
    spl_autoload_register([__CLASS__, 'autoload']);
  }

  /**
   * Registra un nuevo directorio donde el autoloader buscará clases.
   * Llamado típicamente desde el Init.php de un plugin.
   *
   * @param string $path Ruta absoluta (debe terminar con separador o sin él)
   * @return void
   */
  public static function addPath(string $path): void
  {
    $path = rtrim($path, '/\\') . DS;
    if (!in_array($path, self::$pluginPaths, true)) {
      array_unshift(self::$pluginPaths, $path);
    }
  }

  /**
   * Se ejecuta cada que se requiere cargar una clase.
   *
   * Estrategia: primero busca en paths registrados por plugins (prioridad
   * alta, último en registrarse gana), luego en el core.
   *
   * @param string $class_name
   * @return void
   */
  private static function autoload($class_name)
  {
    foreach (self::$pluginPaths as $base) {
      $file = $base . $class_name . '.php';
      if (is_file($file)) {
        require_once $file;
        return;
      }
    }

    if (is_file(CLASSES . $class_name . '.php')) {
      require_once CLASSES . $class_name . '.php';
    } elseif (is_file(CONTROLLERS . $class_name . '.php')) {
      require_once CONTROLLERS . $class_name . '.php';
    } elseif (is_file(MODELS . $class_name . '.php')) {
      require_once MODELS . $class_name . '.php';
    }
  }
}

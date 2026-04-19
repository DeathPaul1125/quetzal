<?php

use Jenssegers\Blade\Blade;

/**
 * Motor de vistas de Quetzal.
 *
 * Blade es el único motor soportado desde la v1.6. Todas las vistas deben
 * ser archivos `.blade.php` ubicados en:
 *   - templates/views/<controlador>/<nombre>View.blade.php
 *   - plugins/<Plugin>/views/<controlador>/<nombre>View.blade.php
 *
 * Uso típico desde un controlador:
 *   $this->setView('index');
 *   $this->render();
 *
 * Para renderizar ad-hoc:
 *   View::render('index', $data);
 *   View::render('admin.perfil', $data);        // notación con puntos
 *   View::render('MyPlugin::home', $data);      // namespace de plugin
 */
class View
{
  /**
   * Directorio base de templates.
   */
  private $baseDir = null;

  /**
   * Directorio base de vistas (templates/views).
   */
  private $viewsDir = null;

  /**
   * Controlador actual para resolver vistas relativas.
   */
  private $controller = null;

  /**
   * Separador de directorios.
   */
  private $DS = null;

  /**
   * Instancia del motor Blade.
   *
   * @var Blade
   */
  private $bladeInstance = null;

  /**
   * Directorios raíz donde Blade busca plantillas.
   * El primero tiene mayor prioridad; los plugins se registran desde su Init.php.
   *
   * @var array
   */
  private static $bladeViewPaths = [];

  /**
   * Nombre de la vista actual a renderizar.
   */
  private $currentView = null;


  function __construct($engine = null)
  {
    // Resolvemos constantes en el constructor porque algunas (CONTROLLER)
    // se definen en fases tardías del bootstrap y los métodos estáticos
    // de esta clase pueden llamarse antes (desde QuetzalPluginManager::load).
    $this->baseDir    = defined('TEMPLATES')  ? TEMPLATES  : '';
    $this->viewsDir   = defined('VIEWS')      ? VIEWS      : '';
    $this->controller = defined('CONTROLLER') ? CONTROLLER : '';
    $this->DS         = defined('DS')         ? DS         : DIRECTORY_SEPARATOR;

    // El parámetro $engine se mantiene por compatibilidad con código viejo,
    // pero se ignora: Blade es el único motor soportado.

    $this->setUpBladeEngine();
  }

  /**
   * Registra un directorio adicional de vistas Blade (útil para plugins).
   *
   * @param string $path
   * @return void
   */
  public static function addBladeViewPath(string $path)
  {
    $path = rtrim($path, '/\\');
    if (!in_array($path, self::$bladeViewPaths, true)) {
      array_unshift(self::$bladeViewPaths, $path);
    }
  }

  /**
   * Alias de addBladeViewPath — se mantiene para compatibilidad.
   *
   * @param string $path
   * @return void
   * @deprecated 1.6 usar addBladeViewPath()
   */
  public static function addQuetzalViewPath(string $path)
  {
    self::addBladeViewPath($path);
  }

  /**
   * Registra un directorio de vistas (alias de addBladeViewPath).
   *
   * @param string $path
   * @return void
   */
  public static function addViewPath(string $path)
  {
    self::addBladeViewPath($path);
  }

  /**
   * Inicializa el motor Blade con los directorios de vistas registrados
   * y el directorio de cache.
   *
   * @return void
   */
  private function setUpBladeEngine()
  {
    $viewPaths = array_values(array_unique(array_merge(
      self::$bladeViewPaths,
      [$this->baseDir, $this->viewsDir]
    )));
    $cachePath = defined('BLADE_CACHE') ? BLADE_CACHE : ROOT . 'app' . DS . 'cache' . DS . 'blade';

    if (!is_dir($cachePath)) {
      @mkdir($cachePath, 0775, true);
    }

    $this->bladeInstance = new Blade($viewPaths, $cachePath);

    // jenssegers/blade solo setea el contenedor para facades.
    // Illuminate\View compila echos que llaman a app('blade.compiler'),
    // por lo que necesitamos el contenedor también como instancia global.
    $container = (new \ReflectionClass($this->bladeInstance))->getProperty('container');
    $container->setAccessible(true);
    \Illuminate\Container\Container::setInstance($container->getValue($this->bladeInstance));

    $this->registerBladeExtensions();
  }

  /**
   * Registra directivas personalizadas en Blade.
   *
   * @return void
   */
  private function registerBladeExtensions()
  {
    $compiler = $this->bladeInstance->compiler();

    // Directiva @csrf — imprime el input oculto con el token actual
    $compiler->directive('csrf', function () {
      return "<?php echo '<input type=\"hidden\" name=\"_t\" value=\"' . (defined('CSRF_TOKEN') ? CSRF_TOKEN : '') . '\">'; ?>";
    });

    // Directivas @auth / @guest
    $compiler->directive('auth', function () {
      return "<?php if (function_exists('is_logged') && is_logged()): ?>";
    });
    $compiler->directive('endauth', function () {
      return "<?php endif; ?>";
    });
    $compiler->directive('guest', function () {
      return "<?php if (!(function_exists('is_logged') && is_logged())): ?>";
    });
    $compiler->directive('endguest', function () {
      return "<?php endif; ?>";
    });

    // Directiva @can('slug') — verifica permisos del usuario loggeado
    $compiler->directive('can', function ($expression) {
      return "<?php if (function_exists('user_can') && user_can($expression)): ?>";
    });
    $compiler->directive('endcan', function () {
      return "<?php endif; ?>";
    });

    // Hook para que los plugins registren directivas/filtros Blade
    if (class_exists('QuetzalHookManager')) {
      QuetzalHookManager::runHook('on_blade_setup', $this->bladeInstance, $compiler);
    }
  }

  /**
   * Renderiza una vista con Blade. Si la vista no existe, lanza error claro.
   *
   * @param string $view
   * @param array $data
   * @return void
   */
  function renderBladeTemplate(string $view, array $data = [])
  {
    try {
      $viewName = $this->resolveBladeViewName($view);

      if (!$this->bladeInstance->exists($viewName)) {
        die(sprintf(
          '<pre style="font-family:monospace;padding:1rem;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;margin:1rem;">'
          . '<b>No existe la vista Blade:</b> %s'
          . "\n" . '<b>Archivo esperado:</b> templates/views/%s/%sView.blade.php'
          . "\n\n" . 'Desde Quetzal 1.6 todas las vistas deben ser <code>.blade.php</code>.</pre>',
          htmlspecialchars($viewName),
          htmlspecialchars($this->controller),
          htmlspecialchars($view)
        ));
      }

      echo $this->bladeInstance->render($viewName, $data);

    } catch (Exception $e) {
      die('Error al renderizar Blade: ' . htmlspecialchars($e->getMessage()));
    }
  }

  /**
   * Convierte un nombre de vista (posiblemente con namespace::) a la
   * notación con puntos que Blade espera, añadiendo el sufijo "View"
   * para mantener la convención de Quetzal (xxxView.blade.php).
   *
   * @param string $view
   * @return string
   */
  private function resolveBladeViewName(string $view)
  {
    // Namespace de plugin: "MyPlugin::home"
    if (strpos($view, '::') !== false) {
      return $view;
    }

    // Vista ya con puntos (ej. "admin.perfil")
    if (strpos($view, '.') !== false) {
      return 'views.' . $view;
    }

    // Por defecto: carpeta del controlador actual + sufijo View
    return 'views.' . $this->controller . '.' . $view . 'View';
  }

  /**
   * Renderiza una vista. Blade es el único motor; el tercer parámetro se
   * mantiene por compatibilidad pero se ignora.
   *
   * @param string $view
   * @param array $data
   * @param string|null $templateEngine (ignorado)
   * @return void
   */
  public static function render(string $view, array $data = [], ?string $templateEngine = null)
  {
    $engine = new self();
    $engine->renderBladeTemplate($view, $data);
  }

  /**
   * Alias explícito (retrocompatibilidad).
   *
   * @param string $view
   * @param array $data
   * @return void
   */
  public static function render_blade(string $view, array $data = [])
  {
    self::render($view, $data);
  }
}

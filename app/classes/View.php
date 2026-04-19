<?php

use Jenssegers\Blade\Blade;

class View {

  /**
   * El path a la carpeta de vistas del controlador actual
   *
   * @var string
   */
  private $path           = null;

  /**
   * El directorio base para el cargador de recursos
   *
   * @var string
   */
  private $baseDir        = null;

  /**
   * El directorio base para el directorio de las vistas
   *
   * @var string
   */
  private $viewsDir       = null;

  /**
   * El controlador actual cargado
   *
   * @var string
   */
  private $controller     = null;

  /**
   * Separador de directorios
   *
   * @var string
   */
  private $DS             = null;

  /**
   * Instancia del motor Blade
   *
   * @var Blade
   */
  private $bladeInstance  = null;

  /**
   * Directorios raíz desde donde Blade busca plantillas. El primero
   * tiene mayor prioridad; los plugins se registran desde su Init.php.
   *
   * @var array
   */
  private static $bladeViewPaths = [];

  /**
   * Directorios raíz para la búsqueda de vistas del motor 'quetzal'
   * (PHP plano). Orden de mayor a menor prioridad.
   *
   * @var array
   */
  private static $quetzalViewPaths = [];

  /**
   * El motor de plantillas a ser utilizado
   *
   * @var string
   */
  private $templateEngine = 'quetzal';

  /**
   * La vista a ser renderizada
   *
   * @var string
   */
  private $currentView    = null;

  function __construct($engine = null)
  {
    // Resolvemos constantes en el constructor porque algunas (CONTROLLER)
    // se definen en fases tardías del bootstrap y los métodos estáticos
    // de esta clase pueden llamarse antes (desde QuetzalPluginManager::load).
    $this->baseDir    = defined('TEMPLATES')  ? TEMPLATES  : '';
    $this->viewsDir   = defined('VIEWS')      ? VIEWS      : '';
    $this->controller = defined('CONTROLLER') ? CONTROLLER : '';
    $this->DS         = defined('DS')         ? DS         : DIRECTORY_SEPARATOR;

    if ($engine !== null) {
      $this->templateEngine = $engine;
    }

    if ((defined('USE_BLADE') && USE_BLADE === true) || $this->templateEngine == 'blade') {
      $this->templateEngine = 'blade';
      $this->setUpBladeEngine();
    }

    // Definimos el path directo a la carpeta de vistas de la instancia de la clase
    $this->path = 'views' . $this->DS . $this->controller . $this->DS;
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
   * Registra un directorio adicional de vistas para el motor 'quetzal' (PHP plano).
   *
   * @param string $path Ruta al directorio que contiene carpetas de vistas por controlador
   * @return void
   */
  public static function addQuetzalViewPath(string $path)
  {
    $path = rtrim($path, '/\\') . DS;
    if (!in_array($path, self::$quetzalViewPaths, true)) {
      array_unshift(self::$quetzalViewPaths, $path);
    }
  }

  /**
   * Atajo: registra el mismo directorio para ambos motores (útil para plugins
   * que traen vistas en varios formatos dentro del mismo folder).
   *
   * @param string $path
   * @return void
   */
  public static function addViewPath(string $path)
  {
    self::addBladeViewPath($path);
    self::addQuetzalViewPath($path);
  }

  /**
   * Inicializa el motor Blade con los directorios de vistas registrados
   * y el directorio de cache.
   *
   * @return void
   */
  private function setUpBladeEngine()
  {
    $viewPaths   = array_values(array_unique(array_merge(self::$bladeViewPaths, [$this->baseDir, $this->viewsDir])));
    $cachePath   = defined('BLADE_CACHE') ? BLADE_CACHE : ROOT . 'app' . DS . 'cache' . DS . 'blade';

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
   * Registra funciones y directivas personalizadas en Blade.
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

    // Directivas @auth / @guest como if estándar (evitan container lookup de blade.compiler)
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

    // Hook para que los plugins registren directivas/filtros Blade
    QuetzalHookManager::runHook('on_blade_setup', $this->bladeInstance, $compiler);
  }

  /**
   * Renderiza una vista con el motor nativo de Quetzal (PHP plano).
   *
   * @param string $view
   * @param array $data
   * @return void
   */
  function renderQuetzalTemplate(string $view, array $data = [])
  {
    $this->currentView = sprintf('%sView.php', $view);

    $resolved = $this->resolveQuetzalView($view);
    if ($resolved === false) {
      die(sprintf('No existe la vista "%sView" resolvible en el controlador "%s" o en plugins registrados.', $view, $this->controller));
    }

    if (is_array($data) && !is_object($data)) {
      $d = to_object($data); // $d disponible como objeto dentro de la vista
    }

    // Extraer variables del array de datos para que estén disponibles por nombre
    extract($data, EXTR_SKIP);

    require $resolved;
  }

  /**
   * Busca la vista PHP en plugins registrados y luego en el core.
   *
   * @param string $view
   * @return string|false
   */
  private function resolveQuetzalView(string $view)
  {
    $relative = $this->controller . $this->DS . sprintf('%sView.php', $view);

    foreach (self::$quetzalViewPaths as $base) {
      $candidate = $base . $relative;
      if (is_file($candidate)) return $candidate;
    }

    $overrides = QuetzalHookManager::getHookData('resolve_view_path', $relative, 'quetzal');
    foreach ($overrides as $candidate) {
      if (is_string($candidate) && is_file($candidate)) {
        return $candidate;
      }
    }

    $default = $this->viewsDir . $relative;
    return is_file($default) ? $default : false;
  }

  /**
   * Renderiza una vista con Blade.
   *
   * @param string $view
   * @param array $data
   * @return void
   */
  function renderBladeTemplate(string $view, array $data = [])
  {
    try {
      // Notación con puntos: "controller.view" o "namespace::controller.view"
      $viewName = $this->resolveBladeViewName($view);

      if (!$this->bladeInstance->exists($viewName)) {
        die(sprintf('No existe la vista Blade "%s" (buscada como "%s") en el controlador "%s".', $view, $viewName, $this->controller));
      }

      echo $this->bladeInstance->render($viewName, $data);

    } catch (Exception $e) {
      die('Error al renderizar Blade: ' . $e->getMessage());
    }
  }

  /**
   * Convierte un nombre de vista (posiblemente con namespace::) a la
   * notación con puntos que Blade espera.
   *
   * @param string $view
   * @return string
   */
  private function resolveBladeViewName(string $view)
  {
    // Soporte para namespaces de plugins: "MyPlugin::home"
    if (strpos($view, '::') !== false) {
      return $view;
    }

    // Permite que el controlador pase la vista ya con puntos
    if (strpos($view, '.') !== false) {
      return 'views.' . $view;
    }

    // Convención Quetzal: sufijo "View" en el nombre de archivo (indexView.blade.php)
    return 'views.' . $this->controller . '.' . $view . 'View';
  }

  /**
   * Renderiza una vista con el motor por defecto configurado o también
   * usando blade de forma explícita.
   *
   * @param string $view
   * @param array $data
   * @param string $templateEngine
   * @return mixed
   */
  public static function render(string $view, array $data = [], ?string $templateEngine = null)
  {
    $engine = new self($templateEngine);

    switch ($engine->templateEngine) {
      case 'blade':
        $engine->renderBladeTemplate($view, $data);
        break;

      case 'quetzal':
        $engine->renderQuetzalTemplate($view, $data);
        break;

      default:
        die("Motor de plantillas no válido");
        break;
    }
  }

  /**
   * Renderiza una vista usando el motor Blade explícitamente.
   *
   * @param string $view
   * @param array $data
   * @return mixed
   */
  public static function render_blade(string $view, array $data = [])
  {
    $engine = new self('blade');
    $engine->renderBladeTemplate($view, $data);
  }
}

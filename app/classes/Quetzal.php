<?php

/**
 * Quetzal — núcleo del framework
 *
 * Framework PHP ligero, flexible y fácil de implementar.
 */
class Quetzal
{
  /**
   * Nombre del framework
   * @var string
   */
  private $framework;

  /**
   *
   * @var string
   */
  private $version;

  /**
   * Logo del framework
   *
   * @var string
   */
  private $logo;

  /**
   *
   * @var string
   */
  private $lng;

  /**
   * La URL completa que se recibe para procesar las peticiones
   * @var string
   */
  private $uri                 = '';

  /**
   * Define si es requerido el uso de librerías externas en el proyecto
   * @var boolean
   * @deprecated 1.5.8
   * 
   */
  private $use_composer        = true;

  /**
   * @since 1.1.4
   * @var string
   */
  private $current_controller  = null;
  private $requestedController = null;
  private $controller          = null;
  private $current_method      = null;
  private $method              = null;
  private $params              = [];
  private $cont_not_found      = false;
  private $method_not_found    = false;
  private $missing_params      = false;
  private $is_ajax             = false;
  private $is_endpoint         = false;
  private $endpoints           = ['api']; // Rutas o endpoints autorizados de la API por defecto
  private $ajaxes              = ['ajax']; // Rutas o controladores para procesar peticiones asíncronas o AJAX
  private $settings            = []; // Cargados desde .env

  // La función principal que se ejecuta al instanciar nuestra clase
  function __construct()
  {
  }

  function __destruct()
  {
  }

  /**
   * Agrega un nuevo endpoint a la lista
   *
   * @param string $endpoint Nombre del controlador a agregar
   * @return void
   */
  function addEndpoint(string $endpoint)
  {
    $this->endpoints[] = $endpoint;
  }

  /**
   * Agrega un nuevo controlador ajax a la lista de autorización
   *
   * @param string $controller Nombre del controlador a agregar
   * @return void
   */
  function addAjax(string $ajax)
  {
    $this->ajaxes[] = $ajax;
  }

  /**
   * Método para ejecutar cada "método" de forma subsecuente
   *
   * @return void
   */
  private function init()
  {
    // Todos los métodos que queremos ejecutar consecutivamente
    $this->init_framework_properties();
    $this->init_session();
    $this->init_load_composer(); // Carga las dependencias de composer
    $this->init_load_config();
    $this->init_autoload(); // Inicializa el cargador de nuestras clases
    $this->init_load_functions();
    $this->init_load_plugins(); // Descubre y carga plugins habilitados

    try {
      QuetzalHookManager::runHook('init_set_up', $this);
      QuetzalHookManager::runHook('after_functions_loaded');
      
      // Añadir valores de configuraciós propios y estén disponibles
      QuetzalHookManager::runHook('settings_loaded', $this->settings);

      /**
       * Se ha actualizado el orden de ejecución para poder
       * filtrar las peticiones en caso de ser necesario
       * como un middleware, así se tiene ya disponible desde el inicio que controlador, método y parámetros
       * pasa el usuario, y pueden ser usados desde antes
       * @since 1.1.4
       */
      $this->init_filter_url();
      QuetzalHookManager::runHook('after_init_filter_url', $this->uri);
      $this->init_set_defaults();
  
      /**
       * Inicialización de globales del framework, token csrf y autentificación
       */
      $this->init_csrf();
      $this->init_globals();
      QuetzalHookManager::runHook('after_init_globals');
      $this->init_authentication();
      $this->init_set_globals();
      QuetzalHookManager::runHook('after_set_globals');
      $this->init_custom();
      QuetzalHookManager::runHook('after_init_custom');
  
      /**
       * Se hace ejecución de todo nuestro framework
       */
      QuetzalHookManager::runHook('before_init_dispatch', $this->current_controller, $this->current_method, $this->params);
      $this->init_dispatch();

    } catch (Exception $e) {
      quetzal_die($e->getMessage());
    }
  }

  /**
   * Método para iniciar la sesión en el sistema
   * 
   * @return void
   */
  private function init_session()
  {
    if (session_status() == PHP_SESSION_NONE) {
      session_start();
    }
  }

  /**
   * Método para cargar la configuración del sistema
   *
   * @return void
   */
  private function init_load_config()
  {
    // Carga del archivo de settings inicialmente para establecer las constantes personalizadas
    // desde un comienzo en la ejecución del sitio
    $file = 'quetzal_config.php';
    if (!is_file('app/config/' . $file)) {
      die(sprintf('El archivo %s no se encuentra, es requerido para que el sitio funcione.', $file));
    }

    // Cargando el archivo de configuración
    require_once 'app/config/' . $file;

    // @deprecated 1.6.0
    // $file = 'settings.php';
    // if (!is_file('app/core/' . $file)) {
    //   die(sprintf('El archivo %s no se encuentra, es requerido para que el sitio funcione.', $file));
    // }

    // // Cargando el archivo de configuración
    // require_once 'app/core/' . $file;
  }

  /**
   * Setea los valores del nombre del framework
   * La versión del framework y el lenguaje
   * Requiere que se hayan cargado quetzal_config.php y settings.php
   * @return void
   */
  private function init_framework_properties()
  {
    $this->framework = 'Quetzal';
    $this->version   = '1.6.0';
    $this->logo      = 'quetzal_logo.png';
    $this->lng       = 'es';

    define('QUETZAL_NAME'      , $this->framework);
    define('QUETZAL_VERSION'   , $this->version);
    define('QUETZAL_LOGO'      , $this->logo);
    define('QUETZAL_DEVS'      , 'Quetzal Team');
    define('QUETZAL_SUPPORT'   , '');
    define('QUETZAL_DONATIONS' , '');
    define('QUETZAL_URL'       , '');
  }

  /**
   * Método para cargar todas las funciones del sistema y del usuario
   *
   * @return void
   */
  private function init_load_functions()
  {
    $file = 'quetzal_core_functions.php';
    if (!is_file(FUNCTIONS . $file)) {
      die(sprintf('El archivo %s no se encuentra, es requerido para que el sitio funcione.', $file));
    }

    // Cargando el archivo de funciones core
    require_once FUNCTIONS . $file;

    $file = 'quetzal_custom_functions.php';
    if (!is_file(FUNCTIONS . $file)) {
      die(sprintf('El archivo %s no se encuentra, es requerido para que el sitio funcione.', $file));
    }

    // Cargando el archivo de funciones custom
    require_once FUNCTIONS . $file;
  }

  /**
   * Inicializa composer
   */
  private function init_load_composer()
  {
    $file = 'app/vendor/autoload.php';
    if (!is_file($file)) {
      die(sprintf('El archivo %s no se encuentra, es requerido para que el sitio funcione.', $file));
    }

    // Cargando el archivo de configuración
    require_once $file;
  }

  /**
   * Método para cargar todos los archivos de forma automática
   *
   * @return void
   */
  private function init_autoload()
  {
    require_once CLASSES . 'Autoloader.php';
    Autoloader::init();
  }

  /**
   * Descubre y carga los plugins habilitados. Ejecuta el Init.php de cada uno
   * para permitir que registren hooks, rutas, vistas y migraciones antes de
   * que arranque el dispatch de la petición.
   *
   * @return void
   */
  private function init_load_plugins()
  {
    if (!class_exists('QuetzalPluginManager')) return;

    try {
      QuetzalPluginManager::getInstance()->load();
      QuetzalHookManager::runHook('plugins_loaded');
    } catch (Exception $e) {
      if (function_exists('logger')) {
        logger('Error al cargar plugins: ' . $e->getMessage());
      }
    }
  }

  /**
   * Método para crear un nuevo token de la sesión del usuario
   *
   * @return void
   */
  private function init_csrf()
  {
    $csrf = new Csrf();
    define('CSRF_TOKEN', $csrf->get_token()); // Versión 1.0.2 para uso en aplicaciones
  }

  /**
   * Inicializa las globales del sistema
   *
   * @return void
   */
  private function init_globals()
  {
    //////////////////////////////////////////////
    // Globales generales usadas en el framework
    //////////////////////////////////////////////

    // Cookies del sitio
    $GLOBALS['Quetzal_Cookies']  = [];

    // Define si un usuario está loggeado o no y su información actual
    $GLOBALS['Quetzal_User']     = [];

    // Define las configuraciones generales del sistema
    $GLOBALS['Quetzal_Settings'] = [];

    // Objeto Quetzal que será insertado en el footer como script javascript dinámico para fácil acceso
    $GLOBALS['Quetzal_Object']   = [];

    // Define los mensajes por defecto para usar en notificaciones o errores
    $GLOBALS['Quetzal_Messages'] = [];

    //////////////////////////////////////////////
    // Globales de Open Graph valores por defecto
    //////////////////////////////////////////////
    set_page_og_meta_tags();

    //////////////////////////////////////////////
    // Globales personales
    //////////////////////////////////////////////

    // jstodo: Generar la funcionalidad para hacer queu y registro de variables globales y cargarlas al inicializar el framework.
    // quetzal_load_custom_globals();
  }

  /**
   * Inicia la validación de sesión en caso de existir 
   * sesiones persistentes de Quetzal
   *
   * @return mixed
   */
  private function init_authentication()
  {
    global $Quetzal_User;

    // Para mantener abierta una sesión de usuario al ser persistente
    try {
      // Autenticamos al usuario en caso de existir los cookies y de que sean válidos
      $Quetzal_User = QuetzalSession::authenticate();

      if ($Quetzal_User === false) {
        $Quetzal_User = [];
      }

      return true;

    } catch (Exception $e) {
      quetzal_die($e->getMessage());
    }
  }

  /**
   * Set up inicial de todas las variables globales requeridas
   * en el sistema
   *
   * @return void
   */
  private function init_set_globals()
  {
    global $Quetzal_Cookies, $Quetzal_Messages, $Quetzal_Object;

    // Inicializa y carga todas las cookies existentes del sitio
    $Quetzal_Cookies   = get_all_cookies();

    // Inicializa el objeto javascript para el pie de página
    $Quetzal_Object    = quetzal_obj_default_config();

    // Inicializa y carga todos los mensajes por defecto de Quetzal
    $Quetzal_Messages  = get_quetzal_default_messages();
  }

  /**
   * Usado para carga de procesos personalizados del sistema
   * funciones, variables, set up
   *
   * @return void
   */
  private function init_custom()
  {
    /**
     * No son necesarios pero es recomendados tenerlos de forma
     * global registrados aquí, para poder acceder desde todo el sistema
     * dentro de Javascript
     * @since 1.1.4
     */
    register_to_quetzal_obj('current_controller', $this->current_controller);
    register_to_quetzal_obj('current_method'    , $this->current_method);
    register_to_quetzal_obj('current_params'    , $this->params);


    // Inicializar procesos personalizados del sistema o aplicación
    // ........
  }

  /**
   * Método para filtrar y descomponer los elementos de nuestra url y uri
   *
   * @return void
   */
  private function init_filter_url()
  {
    if (isset($_GET['uri'])) {
      $this->uri = $_GET['uri'];
      $this->uri = rtrim($this->uri, '/');
      $this->uri = filter_var($this->uri, FILTER_SANITIZE_URL);
      $this->uri = explode('/', $this->uri);
    }

    return $this->uri;
  }

  /**
   * Iteramos sobre los elementos de la uri
   * para descomponer los elementos que necesitamos
   * controller
   * method
   * params
   * 
   * Definimos las diferentes constantes que ayudan al sistema Quetzal
   * a funcionar de forma correcta
   *
   * @return void
   */
  private function init_set_defaults()
  {
    /////////////////////////////////////////////////////////////////////////////////
    // Necesitamos saber si se está pasando el nombre de un controlador en nuestro URI
    // $this->uri[0] es el controlador en cuestión
    if (isset($this->uri[0])) {
      $this->current_controller  = strtolower($this->uri[0]); // users Controller.php
      unset($this->uri[0]);
    } else {
      $this->current_controller = DEFAULT_CONTROLLER; // establecido en settings.php
    }

    // Definir el controlador solicitado (este valor no cambiará en ningún punto)
    $this->requestedController = $this->current_controller;
    
    // Validando si la petición entrante original es ajax, ajaxController es el único controlador aceptado para AJAX
    if (in_array($this->current_controller, $this->ajaxes)) {
      $this->is_ajax            = true; // Lo usaremos para filtrar más adelante nuestro tipo de respuesta al usuario
    }

    // Validando si la petición entrante original es un endpoint de la API
    if (in_array($this->current_controller, $this->endpoints)) {
      $this->is_endpoint        = true; // Lo usaremos para filtrar más adelante nuestro tipo de respuesta al usuario
    }

    // Definiendo el nombre del archivo del controlador
    $this->controller           = $this->current_controller . 'Controller'; // xyzController

    // Verificamos si no existe la clase buscada, se asigna la por defecto si no existe
    if (!class_exists($this->controller)) {
      $this->current_controller = DEFAULT_ERROR_CONTROLLER; // Para que el CONTROLLER sea error
      $this->controller         = DEFAULT_ERROR_CONTROLLER . 'Controller'; // errorController
      $this->cont_not_found     = true; // No se ha encontrado la clase o controlador en el sistema
    }

    /////////////////////////////////////////////////////////////////////////////////
    // Validación del método solicitado
    if (isset($this->uri[1])) {
      $this->method = str_replace('-', '_', strtolower($this->uri[1]));

      // Existe o no el método dentro de la clase a ejecutar (controllador)
      if (!method_exists($this->controller, $this->method)) {
        $this->current_controller = DEFAULT_ERROR_CONTROLLER; // controlador de errores por defecto
        $this->controller         = DEFAULT_ERROR_CONTROLLER . 'Controller'; // errorController
        $this->current_method     = DEFAULT_METHOD; // método index por defecto
        $this->method_not_found   = true; // el método de la clase no existe
      } else {
        $this->current_method     = $this->method;
      }

      unset($this->uri[1]);
    } else {
      $this->current_method       = DEFAULT_METHOD; // index
    }

    // Verificar que el método solicitado sea público de lo contrario no se da acceso
    $reflection = new ReflectionMethod($this->controller, $this->current_method);
    if (!$reflection->isPublic()) {
      // Si el método solicitado no es público, se manda a ruta de error
      $this->current_controller = DEFAULT_ERROR_CONTROLLER; // controlador de errores por defecto
      $this->controller         = DEFAULT_ERROR_CONTROLLER . 'Controller'; // errorController
      $this->current_method     = DEFAULT_METHOD; // método index por defecto
    }

    // Obteniendo los parámetros de la URI
    $this->params               = array_values(empty($this->uri) ? [] : $this->uri);

    /**
     * Verifica el tipo de petición que se está solicitando
     * @since 1.1.4
     */
    $this->init_check_request_type();

    /////////////////////////////////////////////////////////////////////////////////
    // Creando constantes para utilizar más adelante
    define('CONTROLLER', $this->current_controller);
    define('METHOD'    , $this->current_method);
  }

  /**
   * Verifica el tipo de petición que está recibiendo nuestro
   * sistema, para setear una constante que nos ayudará a filtrar
   * ciertas acciones a realizar al inicio
   *
   * @return void
   */
  private function init_check_request_type()
  {
    /**
     * Recontruye los valores por defecto si es una petición AJAX o a Endpoint de API
     * @since 1.1.4
     */
    if ($this->is_ajax === true) {
      define('DOING_AJAX', true);
    } elseif ($this->is_endpoint === true) {
      define('DOING_API', true);
    } elseif ($this->current_controller === 'cronjob') {
      define('DOING_CRON', true);
    } elseif ($this->current_controller === 'xml') {
      define('DOING_XML', true);
    }

    // En caso de que no exista el controlador solicitado pero es AJAX o Endpoint
    if ($this->cont_not_found === true) {
      if ($this->is_ajax === true) {
        $this->current_controller = 'ajax';
        $this->controller         = $this->current_controller . 'Controller';
        $this->current_method     = DEFAULT_METHOD;
      }

      if ($this->is_endpoint === true) {
        $this->current_controller = 'api';
        $this->controller         = $this->current_controller . 'Controller';
        $this->current_method     = DEFAULT_METHOD;
      }
    }

    // En caso de que no exista la ruta solicitada
    if ($this->method_not_found === true && ($this->is_ajax || $this->is_endpoint)) {
      $this->current_controller = $this->requestedController;
      $this->controller         = $this->current_controller . 'Controller';
      $this->current_method     = DEFAULT_METHOD;
    }
  }

  /**
   * Método para ejecutar y cargar de forma automática el controlador solicitado por el usuario
   * su método y pasar parámetros a él.
   *
   * @return bool
   */
  private function init_dispatch()
  {
    // Ejecutando controlador y método según se haga la petición
    $this->controller = new $this->controller;
    $controllerType   = 'regular';

    // Verificar el tipo de controlador
    if (method_exists($this->controller, 'getControllerType')) {
      $controllerType = $this->controller->getControllerType();
    }

    // Llamada al método que solicita el usuario en curso
    if (empty($this->params)) {
      call_user_func([$this->controller, $this->current_method]);
    } else {
      call_user_func_array([$this->controller, $this->current_method], $this->params);
    }

    return true; // Línea final, todo sucede entre esta línea y el comienzo
  }

  /**
   * Correr nuestro framework
   *
   * @return void
   */
  public static function fly()
  {
    $quetzal = new self();
    $quetzal->init();
    return;
  }
}

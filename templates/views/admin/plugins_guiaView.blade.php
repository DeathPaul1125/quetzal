@extends('includes.admin.layout')

@section('title', 'Guía de plugins')
@section('page_title', 'Guía para desarrolladores de plugins')

@php
  // Helper de sintaxis — produce un bloque de código con resaltado suave
  $code = function(string $lang, string $content) {
    return '<pre class="bg-slate-900 text-slate-100 rounded-lg p-4 text-xs leading-relaxed overflow-x-auto"><code class="language-' . $lang . '">'
      . htmlspecialchars($content, ENT_QUOTES) . '</code></pre>';
  };
@endphp

@section('content')
<div class="space-y-6 max-w-5xl">

  {{-- Intro --}}
  <div>
    <a href="admin/plugins" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver a Plugins
    </a>
  </div>

  <div class="bg-gradient-to-br from-primary/5 to-white rounded-xl border border-slate-200 p-6 sm:p-8">
    <div class="flex items-start gap-4">
      <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
        <i class="ri-plug-line text-primary text-2xl"></i>
      </div>
      <div>
        <h1 class="text-2xl font-bold text-slate-800">Crear y extender con plugins</h1>
        <p class="text-sm text-slate-600 leading-relaxed mt-1.5">
          Los plugins viven en <code class="bg-slate-100 px-1 rounded text-xs">/plugins/&lt;Nombre&gt;/</code> y pueden
          <strong>extender todo el sistema</strong>: agregar rutas, sobrescribir vistas y controladores existentes,
          registrar hooks, directivas Blade, y traer sus propias tablas de base de datos.
        </p>
      </div>
    </div>

    {{-- Índice rápido --}}
    <div class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
      <a href="#estructura" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-folder-line"></i> Estructura
      </a>
      <a href="#manifest" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-file-code-line"></i> Manifest
      </a>
      <a href="#extender" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-puzzle-line"></i> Extender
      </a>
      <a href="#hooks" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-hook-line"></i> Hooks
      </a>
      <a href="#vistas" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-window-line"></i> Vistas Blade
      </a>
      <a href="#migraciones" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-database-line"></i> Migraciones
      </a>
      <a href="#assets" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-image-line"></i> Assets
      </a>
      <a href="#ejemplos" class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white border border-slate-200 hover:border-primary text-slate-700">
        <i class="ri-magic-line"></i> Ejemplos
      </a>
    </div>
  </div>

  {{-- ======= ESTRUCTURA ======= --}}
  <section id="estructura" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
      <i class="ri-folder-line text-primary"></i> Estructura de un plugin
    </h2>
    <p class="text-sm text-slate-600 mb-4">Convención canónica. Todas las carpetas son opcionales excepto <code class="bg-slate-100 px-1 rounded text-xs">plugin.json</code>.</p>

    {!! $code('plain',
'plugins/
└── MiPlugin/
    ├── plugin.json              ← Manifest (REQUERIDO)
    ├── Init.php                 ← Bootstrap del plugin (opcional)
    ├── classes/                 ← Clases PHP (autoload)
    ├── controllers/             ← Controladores (autoload + override de core)
    ├── models/                  ← Modelos (autoload)
    ├── views/
    │   └── <controller>/
    │       └── indexView.blade.php
    ├── migrations/              ← Migraciones PDO (estilo Laravel)
    ├── functions/               ← .php auto-incluidos (funciones globales)
    └── assets/                  ← Recursos públicos vía HTTP
        ├── css/
        ├── js/
        └── images/') !!}

    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 text-amber-900 p-3 text-sm">
      <i class="ri-shield-check-line"></i> Solo <code class="bg-amber-100 px-1 rounded">assets/</code> es accesible vía HTTP. El <code class="bg-amber-100 px-1 rounded">.htaccess</code> de <code>/plugins/</code> bloquea todo lo demás.
    </div>
  </section>

  {{-- ======= MANIFEST ======= --}}
  <section id="manifest" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
      <i class="ri-file-code-line text-primary"></i> Manifest <code class="bg-slate-100 px-2 py-0.5 rounded text-sm ml-1">plugin.json</code>
    </h2>

    {!! $code('json',
'{
  "name": "MiPlugin",
  "version": "1.0.0",
  "description": "Descripción corta del plugin.",
  "author": "Tu Nombre",
  "min_quetzal_version": "1.6.0",
  "min_php": "8.3",
  "requires": ["OtroPlugin"]
}') !!}

    <div class="mt-5 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="text-left px-4 py-2 font-semibold">Campo</th>
            <th class="text-center px-4 py-2 font-semibold">Requerido</th>
            <th class="text-left px-4 py-2 font-semibold">Descripción</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr><td class="px-4 py-2 font-mono text-primary">name</td><td class="text-center">✅</td><td class="text-slate-600">Debe coincidir con el nombre de la carpeta. Alfanumérico, guiones.</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">version</td><td class="text-center">✅</td><td class="text-slate-600">Semver (ej. 1.0.0).</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">description</td><td class="text-center">✅</td><td class="text-slate-600">Resumen corto.</td></tr>
          <tr><td class="px-4 py-2 font-mono text-slate-500">author</td><td class="text-center">⚪</td><td class="text-slate-600">Autor o equipo.</td></tr>
          <tr><td class="px-4 py-2 font-mono text-slate-500">min_quetzal_version</td><td class="text-center">⚪</td><td class="text-slate-600">Versión mínima de Quetzal requerida.</td></tr>
          <tr><td class="px-4 py-2 font-mono text-slate-500">min_php</td><td class="text-center">⚪</td><td class="text-slate-600">Versión mínima de PHP.</td></tr>
          <tr><td class="px-4 py-2 font-mono text-slate-500">requires</td><td class="text-center">⚪</td><td class="text-slate-600">Array con nombres de otros plugins que deben estar habilitados primero.</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  {{-- ======= CÓMO EXTENDER ======= --}}
  <section id="extender" class="bg-white rounded-xl border-2 border-primary/40 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-2">
      <i class="ri-puzzle-line text-primary"></i> Cómo extender lo que ya existe
    </h2>
    <p class="text-sm text-slate-600 mb-6">
      Quetzal resuelve controladores y vistas buscando <strong>primero en plugins habilitados</strong> (por orden) y
      luego en el core. <strong>Último plugin habilitado gana</strong>. Esto te permite sobrescribir cualquier
      comportamiento con solo crear un archivo con el mismo nombre.
    </p>

    {{-- Override de controlador --}}
    <div class="mb-6">
      <h3 class="font-semibold text-slate-800 mb-2 flex items-center gap-2">
        <i class="ri-code-s-slash-line text-primary"></i> 1. Sobrescribir un controlador del core
      </h3>
      <p class="text-sm text-slate-600 mb-2">
        Ejemplo: reemplazar <code class="bg-slate-100 px-1 rounded text-xs">adminController</code> para agregar una ruta personalizada <code class="bg-slate-100 px-1 rounded text-xs">/admin/reportes</code>.
      </p>

      {!! $code('plain',
'plugins/MiPlugin/controllers/adminController.php') !!}

      {!! $code('php',
'<?php
// Extiende el controller del core manteniendo sus métodos.
// Para acceder al core original, usa require_once y class_alias antes de declarar.

class adminController extends Controller implements ControllerInterface
{
  function __construct()
  {
    if (!Auth::validate()) {
      Redirect::to("login");
    }
    parent::__construct();
  }

  // Nuevo método: /admin/reportes
  function reportes()
  {
    $this->setTitle("Reportes");
    $this->setView("reportes");
    $this->render();
  }

  // Sobrescribir index para cambiar el dashboard
  function index()
  {
    $this->setTitle("Mi dashboard personalizado");
    $this->setView("index");
    $this->render();
  }
}') !!}

      <div class="mt-3 rounded-lg border border-sky-200 bg-sky-50 text-sky-900 p-3 text-xs">
        <i class="ri-information-line"></i> <strong>Advertencia:</strong> al sobrescribir un controller pierdes los métodos del core que no redefiniste. La alternativa no-invasiva son los hooks (siguiente sección).
      </div>
    </div>

    {{-- Override de vista --}}
    <div class="mb-6">
      <h3 class="font-semibold text-slate-800 mb-2 flex items-center gap-2">
        <i class="ri-window-line text-primary"></i> 2. Sobrescribir una vista
      </h3>
      <p class="text-sm text-slate-600 mb-2">
        Crea la vista con el mismo nombre en tu plugin. Se renderizará esa en lugar de la del core.
      </p>

      {!! $code('plain',
'plugins/MiPlugin/views/admin/indexView.blade.php') !!}

      {!! $code('blade',
'@extends("includes.admin.layout")

@section("content")
  <h1>Dashboard de MiPlugin</h1>
  <p>Esta vista reemplaza la del core.</p>
@endsection') !!}
    </div>

    {{-- Hooks (preferido) --}}
    <div>
      <h3 class="font-semibold text-slate-800 mb-2 flex items-center gap-2">
        <i class="ri-hook-line text-primary"></i> 3. Hooks (recomendado — no invasivo)
      </h3>
      <p class="text-sm text-slate-600 mb-2">
        Los hooks te permiten enganchar lógica sin tocar el código del core. Registra hooks desde <code class="bg-slate-100 px-1 rounded text-xs">Init.php</code>:
      </p>

      {!! $code('plain',
'plugins/MiPlugin/Init.php') !!}

      {!! $code('php',
'<?php

// Ejecuta código antes de cada dispatch (middleware global)
QuetzalHookManager::registerHook("before_init_dispatch", function ($controller, $method, $params) {
  if ($controller === "admin" && !user_can("admin-access")) {
    Flasher::error("Necesitas permisos de administrador");
    Redirect::to("login");
  }
});

// Agrega una directiva Blade personalizada disponible en TODAS las vistas
QuetzalHookManager::registerHook("on_blade_setup", function ($blade, $compiler) {
  $compiler->directive("money", function ($expression) {
    return "<?php echo money($expression); ?>";
  });
});

// Responde a eventos del ciclo de vida de plugins
QuetzalHookManager::registerHook("plugins_rebuilt", function ($steps, $counters) {
  if ($counters["migration_err"] === 0) {
    // copiar tus propios assets, limpiar tu cache, etc.
  }
});') !!}
    </div>
  </section>

  {{-- ======= HOOKS DISPONIBLES ======= --}}
  <section id="hooks" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
      <i class="ri-hook-line text-primary"></i> Hooks disponibles
    </h2>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="text-left px-4 py-2 font-semibold">Hook</th>
            <th class="text-left px-4 py-2 font-semibold">Momento</th>
            <th class="text-left px-4 py-2 font-semibold">Argumentos</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
          <tr><td class="px-4 py-2 font-mono text-primary">init_set_up</td><td class="text-slate-600">Framework inicializado</td><td class="text-slate-500 font-mono text-xs">$quetzal</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">after_functions_loaded</td><td class="text-slate-600">Funciones core cargadas</td><td class="text-slate-500 text-xs">—</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">settings_loaded</td><td class="text-slate-600">Config del .env disponible</td><td class="text-slate-500 font-mono text-xs">$settings</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">plugins_loaded</td><td class="text-slate-600">Todos los plugins cargados</td><td class="text-slate-500 text-xs">—</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">plugin_loaded</td><td class="text-slate-600">Cada plugin terminó de cargar</td><td class="text-slate-500 font-mono text-xs">$plugin</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">after_init_filter_url</td><td class="text-slate-600">URI parseada</td><td class="text-slate-500 font-mono text-xs">$uri</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">after_init_globals</td><td class="text-slate-600">Globals del framework listas</td><td class="text-slate-500 text-xs">—</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">after_set_globals</td><td class="text-slate-600">Quetzal_Object poblado</td><td class="text-slate-500 text-xs">—</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">after_init_custom</td><td class="text-slate-600">Setup completo, listo para dispatch</td><td class="text-slate-500 text-xs">—</td></tr>
          <tr class="bg-amber-50/30"><td class="px-4 py-2 font-mono text-primary">before_init_dispatch</td><td class="text-slate-600">Justo antes de invocar el controller</td><td class="text-slate-500 font-mono text-xs">$controller, $method, $params</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">is_regular_controller</td><td class="text-slate-600">Construyendo controller regular</td><td class="text-slate-500 font-mono text-xs">$controller</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">is_ajax_controller</td><td class="text-slate-600">Construyendo controller AJAX</td><td class="text-slate-500 font-mono text-xs">$controller</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">is_endpoint_controller</td><td class="text-slate-600">Construyendo endpoint API</td><td class="text-slate-500 font-mono text-xs">$controller</td></tr>
          <tr class="bg-emerald-50/30"><td class="px-4 py-2 font-mono text-primary">on_blade_setup</td><td class="text-slate-600">Blade inicializado, registra directivas</td><td class="text-slate-500 font-mono text-xs">$blade, $compiler</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">resolve_view_path</td><td class="text-slate-600">Resolviendo path de vista</td><td class="text-slate-500 font-mono text-xs">$relative, $engine</td></tr>
          <tr><td class="px-4 py-2 font-mono text-primary">plugins_rebuilt</td><td class="text-slate-600">Rebuild terminado</td><td class="text-slate-500 font-mono text-xs">$steps, $counters</td></tr>
        </tbody>
      </table>
    </div>

    <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
        <div class="font-semibold text-slate-800 mb-1">Registrar</div>
        {!! $code('php', 'QuetzalHookManager::registerHook("nombre", $callable);') !!}
      </div>
      <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
        <div class="font-semibold text-slate-800 mb-1">Recolectar respuestas</div>
        {!! $code('php', '$results = QuetzalHookManager::getHookData("filtro", $valor);') !!}
      </div>
    </div>
  </section>

  {{-- ======= VISTAS BLADE ======= --}}
  <section id="vistas" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
      <i class="ri-window-line text-primary"></i> Vistas Blade
    </h2>
    <p class="text-sm text-slate-600 mb-4">
      Blade es obligatorio desde Quetzal 1.6. Los plugins pueden aportar vistas nuevas o sobrescribir las del core.
    </p>

    {!! $code('blade',
'@extends("includes.admin.layout")

@section("title", "Mi sección")
@section("page_title", "Título visible")

@section("content")
  <div class="bg-white rounded-xl border border-slate-200 p-6">
    <h1 class="text-xl font-bold">Hola {{ $usuario->nombre }}</h1>

    @auth
      <p>Usuario logueado.</p>
    @endauth

    @can("admin-access")
      <button class="btn-primary">Acción solo admin</button>
    @endcan
  </div>
@endsection') !!}

    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
      <div class="p-3 rounded-lg border border-slate-200">
        <code class="text-primary">@@csrf</code>
        <p class="text-xs text-slate-500 mt-1">Emite los campos hidden (csrf, redirect_to, timecheck) compatibles con <code>Csrf::validate</code>.</p>
      </div>
      <div class="p-3 rounded-lg border border-slate-200">
        <code class="text-primary">@@auth / @@guest</code>
        <p class="text-xs text-slate-500 mt-1">Condicional por estado de login (<code>is_logged()</code>).</p>
      </div>
      <div class="p-3 rounded-lg border border-slate-200">
        <code class="text-primary">@@can("slug")</code>
        <p class="text-xs text-slate-500 mt-1">Condicional por permiso del usuario (<code>user_can()</code>).</p>
      </div>
      <div class="p-3 rounded-lg border border-slate-200">
        <code class="text-primary">@@stack("scripts") / @@push</code>
        <p class="text-xs text-slate-500 mt-1">Inyecta JS específico de la vista al footer del layout.</p>
      </div>
    </div>
  </section>

  {{-- ======= MIGRACIONES ======= --}}
  <section id="migraciones" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
      <i class="ri-database-line text-primary"></i> Migraciones por plugin
    </h2>
    <p class="text-sm text-slate-600 mb-4">
      Cada migración es un archivo PHP que retorna una clase anónima con <code>up(PDO)</code> y <code>down(PDO)</code>. El
      tracking es aislado: tu plugin usa su propia tabla <code class="bg-slate-100 px-1 rounded text-xs">plugin_&lt;nombre&gt;_migrations</code>.
    </p>

    {!! $code('plain',
'plugins/MiPlugin/migrations/2026_05_01_100000_create_reports_table.php') !!}

    {!! $code('php',
'<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `reports` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `created_at` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `reports`");
    }
};') !!}

    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 p-3 text-sm">
      <i class="ri-lightbulb-line"></i> El botón <strong>Reconstruir plugins</strong> en la vista de Plugins corre automáticamente las migraciones pendientes de todos los plugins habilitados.
    </div>
  </section>

  {{-- ======= ASSETS ======= --}}
  <section id="assets" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
      <i class="ri-image-line text-primary"></i> Assets públicos
    </h2>
    <p class="text-sm text-slate-600 mb-4">
      Archivos en <code class="bg-slate-100 px-1 rounded text-xs">plugins/MiPlugin/assets/</code> son accesibles vía HTTP. Usa el helper <code class="bg-slate-100 px-1 rounded text-xs">plugin_asset()</code>:
    </p>

    {!! $code('blade',
'<link rel="stylesheet" href="{{ plugin_asset("MiPlugin", "css/style.css") }}">
<script src="{{ plugin_asset("MiPlugin", "js/app.js") }}"></script>
<img src="{{ plugin_asset("MiPlugin", "images/logo.png") }}">') !!}
  </section>

  {{-- ======= EJEMPLOS ======= --}}
  <section id="ejemplos" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 scroll-mt-4">
    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
      <i class="ri-magic-line text-primary"></i> Ejemplos prácticos de extensión
    </h2>

    {{-- Ejemplo 1 --}}
    <div class="mb-6">
      <h3 class="font-semibold text-slate-800 mb-2">Agregar un item al sidebar admin</h3>
      <p class="text-sm text-slate-600 mb-2">
        Desde tu <code class="bg-slate-100 px-1 rounded text-xs">Init.php</code>, hookéate al momento de render del sidebar (próximamente) — o sobrescribe la vista:
      </p>
      {!! $code('blade',
'{{-- plugins/MiPlugin/views/includes/admin/sidebar.blade.php --}}
{{-- Esta vista sobrescribe la del core --}}
@include("includes.admin.sidebar")  {{-- puedes seguir usando el base --}}') !!}
      <p class="text-xs text-slate-500 mt-2">
        Alternativa preferida: el core puede exponer un hook <code class="bg-slate-100 px-1 rounded text-xs">admin_sidebar_items</code> en el futuro. Hoy, override es la vía.
      </p>
    </div>

    {{-- Ejemplo 2 --}}
    <div class="mb-6">
      <h3 class="font-semibold text-slate-800 mb-2">Agregar una nueva ruta pública</h3>
      <p class="text-sm text-slate-600 mb-2">Crea un controller — Quetzal enruta por convención:</p>
      {!! $code('php',
'<?php
// plugins/MiPlugin/controllers/reportesController.php
// URL: /reportes       → reportesController::index()
// URL: /reportes/mensual → reportesController::mensual()

class reportesController extends Controller implements ControllerInterface
{
  function index() {
    $this->setTitle("Reportes");
    $this->setView("index");
    $this->render();
  }

  function mensual() {
    $this->setTitle("Reporte mensual");
    $this->setView("mensual");
    $this->render();
  }
}') !!}
    </div>

    {{-- Ejemplo 3 --}}
    <div class="mb-6">
      <h3 class="font-semibold text-slate-800 mb-2">Registrar un endpoint API</h3>
      {!! $code('php',
'<?php
// plugins/MiPlugin/Init.php
QuetzalHookManager::registerHook("init_set_up", function ($quetzal) {
  $quetzal->addEndpoint("mi_api");  // URL: /mi_api/...
  $quetzal->addAjax("mi_ajax");     // URL: /mi_ajax/... (solo AJAX)
});') !!}
    </div>

    {{-- Ejemplo 4 --}}
    <div>
      <h3 class="font-semibold text-slate-800 mb-2">Directiva Blade para tu plugin</h3>
      {!! $code('php',
'<?php
// plugins/MiPlugin/Init.php
QuetzalHookManager::registerHook("on_blade_setup", function ($blade, $compiler) {
  // @currency(valor, "USD") → $125.50 USD
  $compiler->directive("currency", function ($expression) {
    return "<?php echo format_currency($expression); ?>";
  });
});') !!}

      {!! $code('blade',
'{{-- Ahora en cualquier vista: --}}
@currency($producto["precio"], "USD")') !!}
    </div>
  </section>

  {{-- ======= CICLO DE VIDA ======= --}}
  <section class="bg-slate-900 text-white rounded-xl p-6 sm:p-8">
    <h2 class="text-lg font-bold flex items-center gap-2 mb-4">
      <i class="ri-loop-right-line text-amber-300"></i> Ciclo de vida del plugin
    </h2>
    <ol class="space-y-3 text-sm">
      <li class="flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-amber-400/20 text-amber-300 flex items-center justify-center flex-shrink-0 text-xs font-bold">1</span>
        <div>
          <div class="font-semibold">Descubierto</div>
          <div class="text-slate-400 text-xs">Existe en <code>/plugins/</code> con <code>plugin.json</code> válido.</div>
        </div>
      </li>
      <li class="flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-amber-400/20 text-amber-300 flex items-center justify-center flex-shrink-0 text-xs font-bold">2</span>
        <div>
          <div class="font-semibold">Instalado</div>
          <div class="text-slate-400 text-xs">Registrado en <code>app/config/plugins.json</code>. El usuario clickea "Instalar".</div>
        </div>
      </li>
      <li class="flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-amber-400/20 text-amber-300 flex items-center justify-center flex-shrink-0 text-xs font-bold">3</span>
        <div>
          <div class="font-semibold">Habilitado</div>
          <div class="text-slate-400 text-xs">En cada request el core: registra autoload, paths de vistas, corre <code>Init.php</code>, dispara <code>plugin_loaded</code>.</div>
        </div>
      </li>
      <li class="flex items-start gap-3">
        <span class="w-7 h-7 rounded-full bg-amber-400/20 text-amber-300 flex items-center justify-center flex-shrink-0 text-xs font-bold">4</span>
        <div>
          <div class="font-semibold">Reconstruido</div>
          <div class="text-slate-400 text-xs">Botón "Reconstruir": limpia cache Blade, valida compat, corre migraciones pendientes.</div>
        </div>
      </li>
    </ol>
  </section>

  {{-- Footer CTA --}}
  <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
    <p class="text-sm text-slate-600 mb-3">
      ¿Listo para crear tu primer plugin? Mira <a href="plugins/HelloQuetzal" onclick="return false" class="text-primary hover:underline">plugins/HelloQuetzal</a> como ejemplo funcional completo.
    </p>
    <a href="admin/plugins" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
      <i class="ri-arrow-left-line"></i> Volver a Plugins
    </a>
  </div>
</div>
@endsection

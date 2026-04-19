# Sistema de Plugins de Quetzal

Guía para crear, instalar y extender Quetzal mediante plugins.

Quetzal adopta un sistema de plugins inspirado en FacturaScripts: el core se mantiene independiente, y cada plugin vive en su propia carpeta aportando controladores, modelos, vistas, migraciones, assets, clases y hooks. Los plugins se cargan en orden y **el último plugin habilitado gana** cuando hay colisiones de nombres.

---

## 📁 Estructura de un plugin

Todo plugin vive en `/plugins/<NombrePlugin>/`. La estructura canónica es:

```
plugins/
└── MiPlugin/
    ├── plugin.json          ← Manifest (REQUERIDO)
    ├── Init.php             ← Hook de arranque (opcional)
    ├── classes/             ← Clases PHP (autoload)
    ├── controllers/         ← Controladores (autoload)
    ├── models/              ← Modelos (autoload)
    ├── views/               ← Vistas (soporta .blade.php y .php)
    │   └── <controlador>/
    │       └── indexView.php
    ├── migrations/          ← Migraciones PDO (estilo Laravel)
    ├── functions/           ← Archivos .php con funciones globales
    └── assets/              ← Recursos públicos (accesibles vía HTTP)
        ├── css/
        ├── js/
        └── images/
```

> ⚠️ Solo la carpeta `assets/` es accesible vía HTTP. Todo lo demás (controllers/, models/, etc.) está bloqueado por el `.htaccess` de `/plugins/`.

---

## 📜 Manifest (`plugin.json`)

Archivo JSON **obligatorio** en la raíz del plugin. Define la metadata y compatibilidad.

```json
{
  "name": "MiPlugin",
  "version": "1.0.0",
  "description": "Breve descripción del plugin.",
  "author": "Tu Nombre",
  "min_quetzal_version": "1.6.0",
  "min_php": "8.1",
  "requires": ["OtroPlugin"]
}
```

| Campo | Requerido | Descripción |
|---|---|---|
| `name` | ✅ | Debe coincidir exactamente con el nombre de la carpeta. |
| `version` | ✅ | Versión semántica (ej. `1.0.0`). |
| `description` | ✅ | Resumen corto. |
| `author` | ⚪ | Autor o equipo. |
| `min_quetzal_version` | ⚪ | Versión mínima de Quetzal requerida. |
| `min_php` | ⚪ | Versión mínima de PHP. |
| `requires` | ⚪ | Array con nombres de otros plugins que deben estar habilitados primero. |

---

## 🔄 Ciclo de vida

Los plugins pasan por estos estados, controlados por `QuetzalPluginManager`:

| Estado | Descripción | Método |
|---|---|---|
| **Descubierto** | Existe en `/plugins/` con manifest válido. | `discover()` |
| **Instalado** | Registrado en `app/config/plugins.json`. | `install($name)` |
| **Habilitado** | Instalado + `enabled: true` → se carga en cada request. | `enable($name)` |
| **Deshabilitado** | Instalado pero `enabled: false` → no se carga. | `disable($name)` |
| **Desinstalado** | Quitado del registro (archivos permanecen). | `uninstall($name)` |

Toda la información persiste en `app/config/plugins.json`. Ejemplo:

```json
{
  "plugins": [
    {
      "name": "HelloQuetzal",
      "version": "1.0.0",
      "enabled": true,
      "order": 0,
      "installed_at": "2026-04-19 01:24:17"
    }
  ]
}
```

El campo `order` define la prioridad de carga (menor primero). El último plugin cargado tiene mayor prioridad al resolver clases y vistas.

### Gestión programática

```php
$mgr = QuetzalPluginManager::getInstance();

$mgr->discover();              // array de plugins encontrados en disco
$mgr->listAll();               // array con estado (installed/enabled/order)
$mgr->install('MiPlugin');     // agrega al registro
$mgr->enable('MiPlugin');      // marca enabled=true (valida deps)
$mgr->disable('MiPlugin');     // marca enabled=false
$mgr->uninstall('MiPlugin');   // remueve del registro
$mgr->getEnabled();            // plugins activos en el orden de carga

// Migraciones del plugin (tabla de tracking aislada)
$pdo = new PDO(/* ... */);
$mgr->migrate($pdo, 'MiPlugin');
$mgr->rollbackMigrations($pdo, 'MiPlugin');
```

---

## 🚀 Qué hace el core al cargar un plugin

En cada request, `Quetzal::init()` llama a `QuetzalPluginManager::load()`. Para cada plugin habilitado, en orden:

1. **Autoload**: registra `controllers/`, `models/`, `classes/` en `Autoloader::addPath()`. Los plugins tienen prioridad sobre el core.
2. **Views**: registra `views/` en `View::addBladeViewPath()` y `View::addQuetzalViewPath()`.
3. **Funciones**: auto-incluye cualquier `.php` dentro de `functions/`.
4. **Bootstrap**: ejecuta `Init.php` (si existe).
5. Dispara el hook `plugin_loaded` con la metadata del plugin.

---

## 🎣 Extender el sistema con hooks

Quetzal expone hooks a lo largo del ciclo de vida de la request. Los plugins los usan desde `Init.php`:

```php
// plugins/MiPlugin/Init.php

QuetzalHookManager::registerHook('before_init_dispatch', function ($controller, $method, $params) {
  // Interceptar cualquier ruta antes del dispatch
  if ($controller === 'checkout' && !is_logged()) {
    Redirect::to('login');
  }
});
```

### Hooks disponibles (principales)

| Hook | Cuándo se dispara | Argumentos |
|---|---|---|
| `init_set_up` | Tras cargar composer/config/functions | `$quetzal` |
| `after_functions_loaded` | Funciones core disponibles | — |
| `settings_loaded` | Config cargada | `$settings` |
| `plugin_loaded` | Un plugin terminó de cargar | `$plugin` (manifest+record) |
| `plugins_loaded` | Todos los plugins cargados | — |
| `after_init_filter_url` | URI parseada, controller/method conocidos | `$uri` |
| `after_init_globals` | Globals del framework inicializadas | — |
| `after_set_globals` | Datos de Quetzal_Object listos | — |
| `after_init_custom` | Setup terminado, listo para dispatch | — |
| `before_init_dispatch` | Justo antes de invocar el controlador | `$controller`, `$method`, `$params` |
| `is_regular_controller` | Instanciando un controlador regular | `$controller` |
| `is_ajax_controller` | Instanciando controlador AJAX | `$controller` |
| `is_endpoint_controller` | Instanciando endpoint API | `$controller` |
| `on_blade_setup` | Setup del motor Blade (registra directivas) | `$blade`, `$compiler` |
| `resolve_view_path` | Resolviendo path de vista | `$relative`, `$engine` |

### Registrar un hook que retorna datos

```php
$results = QuetzalHookManager::getHookData('my_custom_filter', $value);
// $results es un array con el retorno de cada callback registrado
```

---

## 🧩 Sobrescribir controladores y vistas del core

Como el Autoloader busca primero en plugins (y las vistas igual), puedes **sobrescribir** clases y vistas del core con solo crear un archivo con el mismo nombre.

**Ejemplo — sobrescribir `homeController`**:

```
plugins/MiPlugin/controllers/homeController.php
```

Desde ese archivo puedes extender el controlador original o reemplazarlo por completo. Cuando haya varios plugins definiendo `homeController`, **gana el último habilitado** (mayor `order`).

**Ejemplo — sobrescribir vista `home/indexView.php`**:

```
plugins/MiPlugin/views/home/indexView.php
```

Lo mismo para `.blade.php`.

### Cuándo usar hooks vs. override

- **Override** → quieres reemplazar comportamiento completamente.
- **Hooks** → quieres agregar lógica sin tocar el original (varios plugins pueden apilarse).

---

## 🎨 Vistas: motor Blade vs. motor Quetzal

### Blade (recomendado)

Activar Blade globalmente en `.env`:

```
USE_BLADE=true
```

Crear vistas con extensión `.blade.php`:

```blade
@extends('includes.template')

@section('title', $title)

@section('content')
  <h1>¡Hola {{ $name }}!</h1>
  @auth
    <p>Bienvenido de vuelta.</p>
  @endauth
@endsection
```

Directivas personalizadas incluidas: `@csrf`, `@auth`, `@guest`.

**Registrar tus propias directivas** desde `Init.php`:

```php
QuetzalHookManager::registerHook('on_blade_setup', function ($blade, $compiler) {
  $compiler->directive('money', function ($expression) {
    return "<?php echo money($expression); ?>";
  });
});
```

**Uso en un controlador**:

```php
$this->setView('index');
$this->setEngine('blade');
$this->render();
```

### Motor Quetzal (PHP plano)

Vistas con extensión `.php`, acceso a datos via `$d` (objeto) o variables extraídas por nombre:

```php
<h1>¡Hola <?php echo htmlspecialchars($d->name); ?>!</h1>
```

---

## 🗄️ Migraciones por plugin

Cada plugin puede traer sus migraciones en `migrations/`. Cada archivo debe retornar una clase anónima con métodos `up(PDO)` y `down(PDO)`:

```php
<?php
// plugins/MiPlugin/migrations/2026_04_20_100000_create_tasks.php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `tasks` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
            )
        ");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS `tasks`");
    }
};
```

Ejecutar migraciones del plugin:

```php
$mgr = QuetzalPluginManager::getInstance();
$mgr->migrate($pdo, 'MiPlugin');
```

El historial se guarda en la tabla `plugin_<miplugin>_migrations`, **aislada del core**. Esto permite que dos plugins tengan un archivo llamado `001_create_table.php` sin chocar.

Para revertir todo:

```php
$mgr->rollbackMigrations($pdo, 'MiPlugin');
```

---

## 🖼️ Assets del plugin

Los archivos en `plugins/<Plugin>/assets/` se sirven directamente vía HTTP. Usa el helper `plugin_asset()` para generar URLs:

```php
<link rel="stylesheet" href="<?php echo plugin_asset('MiPlugin', 'css/style.css'); ?>">
<script src="<?php echo plugin_asset('MiPlugin', 'js/app.js'); ?>"></script>
<img src="<?php echo plugin_asset('MiPlugin', 'images/logo.png'); ?>">
```

En Blade:

```blade
<link rel="stylesheet" href="{{ plugin_asset('MiPlugin', 'css/style.css') }}">
```

El `.htaccess` de `/plugins/` garantiza que solo `/plugins/*/assets/**` sea público. El resto de archivos del plugin permanece seguro.

---

## 📡 Rutas en plugins

Quetzal usa routing por convención: URL `/foo/bar` → clase `fooController`, método `bar()`.

Para exponer una ruta desde un plugin, crea un controlador con el nombre de la ruta:

```php
// plugins/MiPlugin/controllers/reportesController.php
class reportesController extends Controller implements ControllerInterface
{
  function index() { /* GET /reportes */ }
  function mensual() { /* GET /reportes/mensual */ }
}
```

Si ya existe un `reportesController` en el core, tu plugin **lo sobrescribe** automáticamente. Si quieres conservar el original, crea un controlador con nombre nuevo.

Para registrar endpoints API o rutas AJAX adicionales desde un plugin, usa `Init.php`:

```php
QuetzalHookManager::registerHook('init_set_up', function ($quetzal) {
  $quetzal->addEndpoint('mi_api');
  $quetzal->addAjax('mi_ajax');
});
```

---

## 🛠️ Ejemplo completo — plugin `HelloQuetzal`

Hay un plugin de ejemplo funcional en [`plugins/HelloQuetzal/`](../plugins/HelloQuetzal/) que ejercita todas las capacidades:

- Manifest
- Controlador con dos métodos (motores Blade y Quetzal)
- Vistas en ambos motores
- Migración (crea tabla `hello_messages`)
- Asset CSS servido vía `plugin_asset()`
- Hook `plugin_loaded` (agrega header HTTP)
- Directiva Blade personalizada `@hello(nombre)`

Para activarlo:

```php
$mgr = QuetzalPluginManager::getInstance();
$mgr->install('HelloQuetzal');
$mgr->enable('HelloQuetzal');
$mgr->migrate($pdo, 'HelloQuetzal');
```

Luego visita:
- `/hello` — vista con motor Quetzal
- `/hello/blade` — vista con motor Blade

---

## 🔐 Orden de resolución y prioridad

Cuando Quetzal necesita cargar una clase o una vista, busca en este orden:

1. **Paths de plugins habilitados** (último habilitado → primero en consultarse)
2. **Paths del core** (`app/classes`, `app/controllers`, `app/models`, `templates/views`)

Esto significa que:

- Si dos plugins definen `CartController`, gana el de mayor `order`.
- Si un plugin define `homeController`, sustituye al del core sin necesidad de tocar código base.
- Para invertir la prioridad, ajusta `order` en `plugins.json` (menor = se carga antes = menor prioridad final).

---

## ✅ Buenas prácticas

- **Nombra tu plugin con PascalCase** y que el folder coincida con el campo `name` del manifest.
- **Evita colisiones de migraciones**: el sistema las aísla con tabla separada, pero usa timestamps en el nombre por convención (`YYYY_MM_DD_HHMMSS_descripcion.php`).
- **No modifiques el core**: si necesitas cambiar comportamiento, haz override o usa hooks.
- **Declara `requires`** cuando dependas de otro plugin; el manager valida que esté habilitado.
- **Mantén los assets en `assets/`**; ningún otro path es accesible vía HTTP.
- **Documenta los hooks que expone tu plugin** si otros plugins pueden extenderlo.

---

## 🧭 Comandos rápidos

```php
use QuetzalPluginManager as PM;

$mgr = PM::getInstance();

// Descubrimiento
print_r($mgr->listAll());

// Lifecycle
$mgr->install('MiPlugin');
$mgr->enable('MiPlugin');
$mgr->migrate($pdo, 'MiPlugin');

$mgr->disable('MiPlugin');
$mgr->rollbackMigrations($pdo, 'MiPlugin');
$mgr->uninstall('MiPlugin');

// Info
$plugin = get_plugin('MiPlugin');              // helper global
$assetUrl = plugin_asset('MiPlugin', 'css/a.css');
```

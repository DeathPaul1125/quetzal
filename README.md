# Quetzal

<img src="assets/images/quetzal.svg" alt="Quetzal" width="120">

**Quetzal** es un mini-framework PHP ligero, flexible y fácil de implementar, pensado tanto para proyectos pequeños como para aplicaciones que requieren escalabilidad.

Viene con un panel de administración pre-construido en **Tailwind CSS**, sistema de autenticación, ORM sencillo, generador de formularios, tablas dinámicas, carrito de compras, roles/permisos, API REST, envío de correos (PHPMailer), generación de PDFs (Dompdf), códigos QR y 2FA — todo listo para usar.

---

## ✨ Características

- 🎨 **Panel admin en Tailwind CSS** — moderno, responsive y personalizable.
- 🔐 **Autenticación y sesiones persistentes** con cookies y BD.
- 👥 **Roles y permisos** (clase `QuetzalRoleManager`).
- 🗄 **ORM** ligero con métodos CRUD sobre PDO.
- 🎣 **Sistema de hooks** (`QuetzalHookManager`) para extender el flujo sin tocar el core.
- 📧 **Correos SMTP** vía PHPMailer.
- 📄 **PDFs** con Dompdf, **QR** con endroid/qr-code.
- 🔑 **2FA** con `robthree/twofactorauth`.
- 🛒 **Carrito de compras** persistente.
- 🔧 **Creator** — generador de controladores, modelos y vistas.
- 🌐 **API REST** con autenticación por token.
- 🔄 **Blade** opcional como motor de plantillas (jenssegers/blade).

---

## 🚀 Instalación (wizard automático)

Quetzal incluye un asistente web que configura todo por ti: instala dependencias, crea la base de datos, genera el `.env` y configura el usuario admin.

### Requisitos

- PHP **≥ 8.3**
- MySQL / MariaDB
- Apache con `mod_rewrite`
- Extensiones: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `curl`, `json`

### Pasos

1. **Coloca el proyecto** en tu servidor local (por ejemplo `c:\laragon\www\Quetzal` o `/var/www/html/quetzal`).
2. **Abre tu navegador** en la raíz del proyecto:
   ```
   http://localhost/Quetzal/
   ```
   Serás redirigido automáticamente al wizard (`/install`).
3. **Sigue los 5 pasos** del wizard:

   | # | Paso | Qué hace |
   |---|------|----------|
   | 1 | Requisitos | Verifica PHP, extensiones y permisos de escritura |
   | 2 | Dependencias | Detecta/descarga Composer y ejecuta `composer install` |
   | 3 | Configuración | Nombre del proyecto, BD, timezone, idioma, admin |
   | 4 | Instalar | Crea la BD, corre las migraciones, genera `.env` con claves únicas |
   | 5 | Listo | Credenciales + botón para eliminar el instalador |

4. **Entra al sitio** — serás redirigido al login → dashboard de administración.

### Credenciales por defecto

- **Usuario:** `admin`
- **Contraseña:** `123456` (o la que hayas definido en el paso 3)

> 🔒 Cambia la contraseña inmediatamente en producción.

---

## 📂 Estructura del proyecto

```
Quetzal/
├── app/
│   ├── classes/         # Clases del core (Auth, QuetzalModel, Controller, etc.)
│   ├── config/          # .env, quetzal_config.php
│   ├── controllers/     # Controladores (admin, api, login, etc.)
│   ├── functions/       # Helpers y funciones globales
│   ├── logs/            # Logs de aplicación
│   ├── models/          # Modelos de datos
│   ├── vendor/          # Dependencias Composer
│   └── composer.json
├── assets/
│   ├── css/  js/  images/  plugins/  uploads/
├── templates/
│   ├── includes/        # Layout parts (header, footer, sidebar, topbar)
│   ├── modules/         # Bloques reutilizables
│   └── views/           # Vistas por controlador (admin, login, ...)
├── index.php            # Punto de entrada
├── install.php          # Wizard (auto-eliminable)
└── migrate.php          # CLI de migraciones
```

---

## 🗄 Migraciones (estilo Laravel)

Quetzal incluye un sistema de migraciones minimalista. Los archivos viven en [app/migrations/](app/migrations/) y siguen el patrón `YYYY_MM_DD_HHMMSS_descripcion.php`.

### Estructura de una migración

```php
<?php

return new class {
    public function up(PDO $pdo): void {
        $pdo->exec("CREATE TABLE ...");
    }

    public function down(PDO $pdo): void {
        $pdo->exec("DROP TABLE IF EXISTS ...");
    }
};
```

### CLI

```bash
php migrate.php              # corre migraciones pendientes
php migrate.php status       # lista migraciones y su estado
php migrate.php rollback     # revierte el último batch
php migrate.php rollback 3   # revierte los últimos 3 batches
php migrate.php fresh        # drop + migrate (reset total)
php migrate.php make nombre_descriptivo  # genera un archivo de migración vacío
```

### Tabla de control

La tabla `quetzal_migrations` se auto-crea y lleva el registro de qué migraciones se ejecutaron y en qué `batch`, para soportar rollback por lote.

### Desde el wizard

El paso 4 del instalador ejecuta automáticamente todas las migraciones de `app/migrations/`. No necesitas correr el CLI en la instalación inicial.

---

## 🧪 Modo pruebas

En el último paso del wizard hay un bloque colapsable **🧪 Modo pruebas · Reiniciar instalación** que te permite:

- Eliminar la base de datos
- Borrar `app/vendor/` y `composer.lock`
- Eliminar `.env`

Útil para probar el wizard desde cero sin tener que borrar archivos manualmente.

---

## ✅ Suite de tests

Quetzal trae una suite de tests propia (sin phpunit) en `tests/`:

```bash
php tests/run.php                       # toda la suite
php tests/run.php WooCommerce           # sólo una carpeta
php tests/run.php --filter=variantes    # filtro por nombre del caso
```

Cada plugin presente en `/plugins/` queda cubierto **automáticamente** con cuatro chequeos: lint PHP, compilación de todas las vistas Blade, validez del `plugin.json` y forma correcta de las migraciones (`up()`/`down()`). Más detalles y cómo agregar tests para tu plugin en [docs/PLUGINS.md → Tests](docs/PLUGINS.md#-tests-del-sistema-de-plugins).

---

## 🎨 Personalización del dashboard

El panel admin está en:

- [templates/includes/admin/dashboardTop.php](templates/includes/admin/dashboardTop.php) — sidebar + topbar
- [templates/includes/admin/dashboardBottom.php](templates/includes/admin/dashboardBottom.php) — footer
- [templates/views/admin/indexView.php](templates/views/admin/indexView.php) — contenido del home

Todos usan **Tailwind CSS** (via CDN). Los colores de marca están definidos como la paleta `honey` en el bloque `tailwind.config` del `header.php`.

---

## 🔐 Seguridad

- El archivo `install.php` se auto-elimina al finalizar el wizard (botón del paso 5).
- `.htaccess` bloquea el acceso directo a `.env`, `composer.json`, etc.
- Todas las peticiones POST/PUT/DELETE al controlador `ajax` requieren token CSRF.
- Sesiones persistentes opcionales con cookie firmada.

---

## 📄 Licencia

Ver [LICENSE](LICENSE).

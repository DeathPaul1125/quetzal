# Sistema de actualizaciones de Quetzal

Quetzal puede actualizarse a sí mismo descargando un ZIP del core desde un
servidor remoto, validando integridad, aplicando los archivos sin tocar
plugins, corriendo migraciones nuevas, y manteniendo un backup automático
para rollback. El sistema tiene **dos lados** que pueden vivir en distintas
instalaciones de Quetzal o convivir en la misma:

```
                  ┌──────────────────────────────────┐
                  │  SERVIDOR DE UPDATES             │
                  │  (system.facturagt.com)          │
                  │                                  │
                  │  Plugin: QuetzalUpdates          │
                  │  - Sube ZIPs                     │
                  │  - Genera tokens por cliente     │
                  │  - Sirve /quetzalupdates/check   │
                  │  - Sirve /quetzalupdates/download│
                  └──────────────┬───────────────────┘
                                 │
                                 │  HTTPS + X-Update-Token
                                 │
       ┌─────────────────────────┼─────────────────────────┐
       │                         │                         │
       ▼                         ▼                         ▼
┌──────────────┐         ┌──────────────┐         ┌──────────────┐
│  CLIENTE 1   │         │  CLIENTE 2   │         │  CLIENTE N   │
│              │         │              │         │              │
│ Core Quetzal │         │ Core Quetzal │         │ Core Quetzal │
│ + Updater    │         │ + Updater    │         │ + Updater    │
│              │         │              │         │              │
│ token: qu_a..│         │ token: qu_b..│         │ token: qu_n..│
└──────────────┘         └──────────────┘         └──────────────┘
```

---

## ¿Cómo distingo "lado cliente" y "lado servidor"?

| | **Lado cliente** | **Lado servidor** |
|---|---|---|
| **¿Qué hace?** | Recibe updates | Sirve updates |
| **¿Está en el core?** | Sí (lo trae todo Quetzal) | No, es un plugin opcional (`QuetzalUpdates`) |
| **¿Se activa?** | Siempre disponible | Solo si instalás y habilitás el plugin |
| **URL de la UI** | `tu-dominio.com/actualizaciones` | `tu-dominio.com/quetzalupdates` |
| **Permiso requerido** | `admin-access` | `qupdates-admin` |
| **Sidebar** | "Actualizaciones" (Sistema) | "Releases" + "Clientes" (Sistema) |
| **Tablas en BD** | Ninguna (usa `quetzal_options`) | `qu_releases`, `qu_clients`, `qu_downloads` |
| **Datos sensibles** | Token del cliente (en `quetzal_options.update_token`) | Hashes de tokens, ZIPs subidos |

**Regla rápida:** si en el sidebar ves *"Actualizaciones"* → estás en una
instalación cliente. Si ves *"Releases"* y *"Clientes"* → estás en el servidor.
Pueden coexistir en la misma instalación (el servidor también puede
actualizarse a sí mismo).

---

## Flujo completo end-to-end

```
1. Vos (admin del servidor):
   sistema.facturagt.com/quetzalupdates/nuevo
   ├─ Subís quetzal-1.6.1.zip
   ├─ El servidor calcula SHA-256
   └─ Queda guardado en assets/uploads/qupdates/

2. Vos (admin del servidor):
   sistema.facturagt.com/quetzalupdates/clientes/nuevo_cliente
   ├─ Generás un token único: qu_8f2a4c…
   ├─ Le asignás un canal (stable/beta/alpha)
   └─ Le entregás el token al cliente (UNA vez)

3. Cliente (en SU Quetzal):
   tu-cliente.com/actualizaciones
   ├─ Pega endpoint: https://system.facturagt.com/quetzalupdates
   ├─ Pega token: qu_8f2a4c…
   └─ Guarda configuración

4. Cliente carga la página (automático):
   GET /quetzalupdates/check?current=1.6.0
   X-Update-Token: qu_8f2a4c…
   → Si hay update: muestra banner verde con changelog
   → Si está al día: muestra banner gris

5. Cliente clickea "Actualizar ahora":
   ├─ Backup automático (ZIP de su core actual)
   ├─ GET /quetzalupdates/download/1.6.1
   ├─ Verifica SHA-256
   ├─ Extrae a tmp/, mueve a la raíz (sin tocar plugins/)
   ├─ Corre migraciones core nuevas
   └─ Reescribe quetzal.json con la versión nueva
```

---

# Lado servidor — `system.facturagt.com`

## Setup inicial (una vez)

1. **Instalar el plugin `QuetzalUpdates`:**
   ```
   /plugins/QuetzalUpdates/
   ├── plugin.json
   ├── Init.php
   ├── controllers/quetzalupdatesController.php
   ├── migrations/2026_04_30_120000_create_qupdates_tables.php
   └── views/quetzalupdates/
       ├── indexView.blade.php       (listado releases)
       ├── formView.blade.php        (subir/editar release)
       ├── clientesView.blade.php    (listado clientes)
       └── cliente_formView.blade.php (crear/editar cliente)
   ```

2. **Habilitar el plugin** en `Admin → Plugins` (o que esté `enabled` en `plugins.json`).

3. **Correr migraciones:** `Admin → Migraciones → Aplicar pendientes`. Esto crea:

   | Tabla | Para qué |
   |---|---|
   | `qu_releases` | Cada versión publicada (versión, canal, ZIP, SHA-256, changelog) |
   | `qu_clients` | Tokens autorizados (hash SHA-256 — el token plain se muestra UNA vez) |
   | `qu_downloads` | Log de cada `/check` y `/download` (telemetría) |

4. **Sincronizar permisos:** `Admin → Permisos → Sincronizar desde plugins`.
   Esto registra `qupdates-admin` y lo asigna automáticamente al rol `developer`
   y `admin`. Para otros roles, asignalo desde `Admin → Roles → editar`.

## Publicar una versión nueva

1. **Generar el ZIP del core** localmente. Estructura esperada:
   ```
   quetzal-1.6.1.zip
   ├── quetzal.json              ← {"version":"1.6.1","released_at":"...","min_php":"8.1"}
   ├── app/                      ← reemplaza completo en el cliente
   ├── templates/                ← reemplaza completo
   ├── assets/css/               ← reemplaza
   ├── assets/js/                ← reemplaza
   ├── docs/                     ← reemplaza
   └── public/                   ← reemplaza si existe
   ```

   **Importante:** NO incluir `plugins/`, `.env`, `assets/uploads/`,
   `app/cache/`, `app/logs/`, `tmp/`, `backups/`. El cliente protege estos
   paths automáticamente, pero si están en el ZIP solo agrega peso.

   El ZIP puede venir con un subdirectorio raíz único (ej. `quetzal-1.6.1/`)
   o con los archivos al ras — el `QuetzalUpdater` detecta ambos casos.

2. **Sidebar → Sistema → Releases → "Publicar release":**
   - Versión: `1.6.1` (formato semver: `x.y.z` o `x.y.z-beta.N`)
   - Canal: `stable` (visible a todos) / `beta` (a clientes en beta) / `alpha` (interno)
   - PHP mínimo (opcional): `8.1` — se valida en el cliente antes de aplicar
   - Quetzal mínimo (opcional): si está, debe ser `≤` versión actual del cliente
   - Changelog: markdown plano que se muestra al cliente
   - Archivo ZIP: el ZIP del paso 1
   - "Publicar" marcado: visible a clientes / desmarcado: borrador interno

   Al guardar, el servidor calcula el SHA-256 y lo persiste. El ZIP se mueve
   a `assets/uploads/qupdates/quetzal-{version}-{channel}.zip`.

## Autorizar un cliente

1. **Sidebar → Sistema → Clientes → "Autorizar cliente":**
   - **Nombre:** etiqueta interna ("Empresa ACME — producción")
   - **Dominio (opcional):** si lo declarás, el servidor rechaza requests
     cuyo `Origin/Referer` no contenga este dominio (defensa en profundidad)
   - **Canal asignado:** controla qué releases ve este cliente
   - **Activo:** si está apagado, su token deja de funcionar (sin borrarlo)

2. Al guardar, el servidor genera un token (`qu_` + 48 chars hex) y lo
   muestra **una sola vez** en un banner amarillo. Copialo y entregalo al
   cliente — solo guardamos el hash SHA-256, así que no podés recuperarlo
   después.

3. **Si el cliente pierde su token:** "Editar cliente" → marcar "Regenerar
   token" → guardar. El token anterior queda inválido inmediatamente.

4. **Para revocar acceso:** botón rojo "Revocar". Marca `activo=0` sin borrar
   el cliente (preserva el historial de descargas).

## Endpoints públicos del servidor

Estos son los endpoints que consumen las instancias cliente. **No requieren
sesión** — autentican por header `X-Update-Token`.

### `GET /quetzalupdates/check?current={version}`

Headers:
```
X-Update-Token: qu_8f2a4c…
```

Respuestas:
- `200` + JSON manifest → hay update disponible
- `204` → cliente al día
- `401` → token inválido o cliente revocado

Cuerpo del manifest (HTTP 200):
```json
{
  "version":     "1.6.1",
  "channel":     "stable",
  "sha256":      "abc123...",
  "url":         "https://system.facturagt.com/quetzalupdates/download/1.6.1",
  "changelog":   "- Fix N+1 permisos\n- Modo fullscreen APK",
  "min_php":     "8.1",
  "min_quetzal": null,
  "released_at": "2026-04-30 14:30:00",
  "size":        4823456
}
```

Side-effect: actualiza `qu_clients.last_check_at`, `last_known_version`,
`last_ip` y registra una fila en `qu_downloads` con `accion='check'`.

### `GET /quetzalupdates/download/{version}`

Headers:
```
X-Update-Token: qu_8f2a4c…
```

Respuestas:
- `200` + stream binario `application/zip` con header `X-Update-SHA256`
- `400` → versión inválida (formato)
- `401` → token inválido
- `404` → release no existe en el canal del cliente, o no está publicado

Side-effect: registra en `qu_downloads` con `accion='download'` y el HTTP
status real.

## Telemetría

La tabla `qu_downloads` es la fuente de verdad de adopción. Queries útiles:

```sql
-- ¿Qué versión corre cada cliente?
SELECT c.nombre, c.last_known_version, c.last_check_at, c.dominio
FROM qu_clients c
WHERE c.activo = 1
ORDER BY c.last_check_at DESC;

-- Cuántas descargas exitosas tuvo cada release
SELECT r.version, r.channel, COUNT(*) AS descargas
FROM qu_downloads d
JOIN qu_releases r ON r.id = d.release_id
WHERE d.accion = 'download' AND d.http_status BETWEEN 200 AND 299
GROUP BY r.id;

-- Errores 4xx/5xx en las últimas 24h (ver intentos con token inválido)
SELECT * FROM qu_downloads
WHERE http_status >= 400 AND at >= NOW() - INTERVAL 1 DAY
ORDER BY at DESC;
```

---

# Lado cliente — cualquier instalación que recibe updates

## Setup inicial

1. Click en **Sidebar → Sistema → Actualizaciones**.
2. En la card *"Servidor de actualizaciones"*:
   - **URL del servidor:** `https://system.facturagt.com/quetzalupdates`
   - **Token de cliente:** el token `qu_…` que te dio el admin del servidor
   - Guardar.

Eso es todo. La página recarga y consulta automáticamente al servidor.

## ¿Qué pasa cuando hago click en "Actualizar ahora"?

El método `QuetzalUpdater::apply()` orquesta este flujo:

```
1. check()
   GET /quetzalupdates/check?current={version_actual}
   → Si 204 (al día): aborta, sin tocar nada.
   → Si 200: recibe el manifest del release.

2. backup()
   Crea backups/core-{version}-{timestamp}.zip
   Incluye: app/, templates/, assets/css/, assets/js/, docs/, quetzal.json
   Excluye: plugins/, .env, uploads/, cache/, logs/, tmp/, backups/
   ⚠ Si esto falla, el flujo se aborta — todavía no se tocó nada.

3. download(manifest)
   GET /quetzalupdates/download/{version}
   Stream a tmp/updater/release-{version}.zip
   ⚠ Si falla la descarga, queda el backup pero el sistema no se modificó.

4. verify(zip, manifest.sha256)
   hash_file('sha256', ...) == manifest.sha256
   ⚠ Si falla → "archivo corrupto o manipulado", aborta.

5. extract(zip)
   Descomprime a tmp/updater/staging-{timestamp}/
   Auto-detecta si el ZIP venía con subdir raíz o al ras.

6. swap(staging)
   Recorre el staging recursivamente.
   Para cada archivo:
     - Si está en PROTECTED_PATHS → skip (preserva)
     - Si no → @copy() sobre el destino en la raíz del proyecto
   ⚠ A partir de acá los cambios SÍ son visibles. Si algo explota,
     hay que rollback() desde el ZIP de backup.

7. migrate(pdo)  [solo si se pasó PDO al apply()]
   Corre Migrator::run() sobre app/migrations/
   Solo aplica las migraciones nuevas (las que no estén en
   quetzal_migrations).

8. updateVersion(manifest.version)
   Reescribe /quetzal.json con la versión, fecha, min_php, channel nuevos.
```

## Paths protegidos (NUNCA se sobreescriben durante swap)

Definidos en `QuetzalUpdater::PROTECTED_PATHS` (ver `app/classes/QuetzalUpdater.php`):

| Path | Por qué se protege |
|---|---|
| `plugins/` | Tus plugins instalados (Facturador, MobileApp, etc.) |
| `app/config/.env` | Credenciales de BD, claves de API, salts |
| `app/config/plugins.json` | Estado de plugins habilitados/deshabilitados |
| `app/config/sidebar.json` | Sidebar custom del usuario |
| `app/cache/` | Caché de Blade y otros |
| `app/logs/` | Logs del sistema |
| `assets/uploads/` | Archivos subidos por usuarios |
| `tmp/` | Staging del propio updater |
| `backups/` | Backups previos (no nos los vamos a sobreescribir solos) |

Si un release intenta tocar uno de estos paths, el archivo del release se
**ignora** (no se considera error). Esto significa que si querés agregar un
archivo nuevo en una de estas carpetas, tenés que hacerlo por otra vía
(ej. una migración que lo cree).

## Backups y rollback

Antes de cada `apply()` el cliente genera un ZIP en `backups/`:

```
backups/
├── core-1.5.0-20260430-141232.zip   (antes del 1.5.0 → 1.5.1)
├── core-1.5.1-20260501-093011.zip   (antes del 1.5.1 → 1.6.0)
└── core-1.6.0-20260520-180044.zip   (antes del 1.6.0 → 1.6.1)
```

La página de actualizaciones lista los **últimos 10**. Cada uno tiene un
botón "Restaurar" que llama a `QuetzalUpdater::rollback($path)` — extrae el
ZIP y hace `swap()` con los archivos del backup.

**Importante sobre rollback:**
- Las migraciones de BD **NO se revierten** automáticamente. Si la versión
  nueva agregó columnas o tablas, esas siguen ahí después de restaurar.
  Si necesitás revertir una migración específica, usá `Admin → Migraciones →
  Rollback`.
- Los plugins instalados quedan intactos (estaban en `PROTECTED_PATHS` tanto
  en backup como en restore).

## Configuración del lado cliente

Se guarda en la tabla `quetzal_options`:

| Key | Valor | Notas |
|---|---|---|
| `update_endpoint` | URL base | `https://system.facturagt.com/quetzalupdates` (default) |
| `update_token` | Token plain | Lo recibe del admin del servidor |

El token se envía como `X-Update-Token` en cada request. Solo viaja por
HTTPS si configurás HTTPS en `update_endpoint`.

---

# FAQ rápido

**¿Qué pasa si la actualización falla a mitad?**
Mensaje de error con el path del backup. Para revertir: ir a la lista de
backups y click en "Restaurar". El sistema sobreescribe los archivos
modificados con los del backup.

**¿Puedo subir releases manualmente al cliente sin pasar por el servidor?**
Sí. Lo que hace el flujo es: descargar ZIP → verificar SHA → extraer → swap.
Si tenés el ZIP en mano, podés extraerlo manualmente sobre la raíz del
proyecto respetando `PROTECTED_PATHS`. Pero la migración hay que correrla
aparte (`php migrate.php up`).

**¿Cómo cambio la versión que muestra el cliente sin actualizar?**
Editá `quetzal.json` en la raíz: `{"version": "X.Y.Z", ...}`.

**¿Y si quiero que un cliente se quede en una versión vieja?**
Asignale un canal `alpha` o `beta` y no publiques nada nuevo en ese canal.
O directamente revocá su token (`activo=0`) y queda sin acceso a updates.

**¿Cómo gestiono updates de plugins?**
Los plugins NO se actualizan por este sistema (`PROTECTED_PATHS` los
preserva). Cada plugin se instala/actualiza por separado en
`Admin → Plugins → Subir ZIP`.

**¿El servidor también puede actualizarse a sí mismo?**
Sí. El plugin `QuetzalUpdates` queda en `plugins/` (protegido), entonces
una actualización del core no lo toca. Sólo asegurate de tener su token
configurado en sus propias opciones (`update_endpoint`+`update_token`)
apuntando a otro servidor — o a sí mismo, lo cual es válido pero ridículo.

**¿Cómo verifico que el SHA-256 de un release es correcto?**
En el servidor, abrir la BD y consultar `qu_releases`. En el cliente,
mirá el header `X-Update-SHA256` que devuelve `/download/{version}`.
Localmente: `sha256sum quetzal-1.6.1.zip`.

---

# Archivos relevantes (referencia)

## Lado cliente (en core)
- `quetzal.json` — versión actual instalada
- `app/classes/QuetzalUpdater.php` — clase orquestadora
- `app/controllers/actualizacionesController.php` — controller admin
- `templates/views/actualizaciones/indexView.blade.php` — vista admin
- `tests/Core/QuetzalUpdaterTest.php` — 19 tests (semver, paths, swap, backup)

## Lado servidor (plugin QuetzalUpdates)
- `plugins/QuetzalUpdates/plugin.json` — manifiesto
- `plugins/QuetzalUpdates/Init.php` — registra sidebar
- `plugins/QuetzalUpdates/controllers/quetzalupdatesController.php` — admin + API pública
- `plugins/QuetzalUpdates/migrations/...create_qupdates_tables.php` — schema
- `plugins/QuetzalUpdates/views/quetzalupdates/{index,form,clientes,cliente_form}View.blade.php`

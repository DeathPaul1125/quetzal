@extends('includes.admin.layout')

@section('title', 'Generador CRUD')
@section('page_title', 'Generador CRUD (consola)')

@push('head')
<style>
  .q-term {
    background: #0b1120;
    color: #e2e8f0;
    font-family: 'Fira Code', 'Consolas', 'Monaco', monospace;
  }
  .q-term-header {
    background: #1e293b;
    border-bottom: 1px solid #334155;
  }
  .q-term-dot { width: .625rem; height: .625rem; border-radius: 50%; display: inline-block; }
  .q-term-output { min-height: 320px; max-height: 480px; overflow-y: auto; scroll-behavior: smooth; }
  .q-term-line { padding: 2px 0; line-height: 1.5; white-space: pre-wrap; word-break: break-all; }
  .q-term-line.cmd   { color: #93c5fd; }
  .q-term-line.ok    { color: #86efac; }
  .q-term-line.error { color: #fca5a5; }
  .q-term-line.info  { color: #fcd34d; }
  .q-term-line.muted { color: #64748b; }
  .q-term-prompt { color: #4ade80; }
  .q-cursor { display:inline-block; width:8px; height:1em; background:#e2e8f0; animation: blink 1s infinite; vertical-align: text-bottom; }
  @keyframes blink { 0%, 50% { opacity: 1; } 50.1%, 100% { opacity: 0; } }

  /* Inputs estilo terminal */
  .q-term-input {
    background: #0b1120 !important;
    border: 1px solid #334155 !important;
    color: #e2e8f0 !important;
    font-family: 'Fira Code', 'Consolas', monospace !important;
  }
  .q-term-input::placeholder { color: #64748b; }
  .q-term-input:focus { border-color: #4ade80 !important; outline: none !important; box-shadow: 0 0 0 1px #4ade80 !important; }
</style>
@endpush

@section('content')
<div class="space-y-4">

  {{-- ========== GUÍA DE USO ========== --}}
  <details class="bg-white rounded-xl border border-slate-200 overflow-hidden group" open>
    <summary class="cursor-pointer px-5 py-4 flex items-center justify-between hover:bg-slate-50 list-none">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
          <i class="ri-book-open-line text-primary text-xl"></i>
        </div>
        <div>
          <div class="font-semibold text-slate-800">Cómo usar el generador</div>
          <div class="text-xs text-slate-500">Guía rápida, comandos, tipos de campo y ejemplos</div>
        </div>
      </div>
      <i class="ri-arrow-down-s-line text-slate-400 text-xl group-open:rotate-180 transition"></i>
    </summary>

    <div class="px-5 pb-5 pt-1 space-y-5 text-sm text-slate-700">

      {{-- Pasos rápidos --}}
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Pasos rápidos</h4>
        <ol class="space-y-2">
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">1</span>
            <div><strong>Elige el comando</strong> — normalmente <code class="bg-slate-100 px-1 rounded text-xs">make:crud</code> para crear todo de una.</div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">2</span>
            <div><strong>Elige el destino</strong> — <code class="bg-slate-100 px-1 rounded text-xs">core</code> para la app principal, o un plugin habilitado para aislarlo ahí.</div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">3</span>
            <div><strong>Define el nombre del recurso</strong> (ej. <code class="bg-slate-100 px-1 rounded text-xs">tareas</code>) — será la URL, nombre del modelo y controlador. La tabla BD usa el mismo nombre salvo que la cambies.</div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">4</span>
            <div><strong>Agrega los campos</strong> que NO sean <code>id</code>, <code>created_at</code> ni <code>updated_at</code> (esos se generan automáticos). Marca "Req" para NOT NULL y "Uniq" para UNIQUE KEY.</div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">5</span>
            <div><strong>Click "Ejecutar"</strong> — la terminal muestra cada archivo creado. Si hubo error, lo verás en rojo.</div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center">6</span>
            <div><strong>Corre la migración</strong>: ve a <a href="admin/migraciones" class="text-primary hover:underline">Migraciones</a> → "Ejecutar pendientes" para crear la tabla físicamente.</div>
          </li>
          <li class="flex gap-3">
            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center">7</span>
            <div><strong>Visita <code class="bg-slate-100 px-1 rounded text-xs">/{nombre}</code></strong> — el CRUD ya funciona: listar, crear, ver detalle, editar y borrar.</div>
          </li>
        </ol>
      </div>

      {{-- Comandos --}}
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Comandos disponibles</h4>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
              <tr>
                <th class="text-left px-3 py-2 font-semibold">Comando</th>
                <th class="text-left px-3 py-2 font-semibold">Genera</th>
                <th class="text-left px-3 py-2 font-semibold">Úsalo cuando</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr class="bg-emerald-50/40">
                <td class="px-3 py-2 font-mono text-primary">make:crud</td>
                <td class="px-3 py-2">modelo + migración + controlador + 4 vistas</td>
                <td class="px-3 py-2 text-slate-600">Quieres un CRUD completo en un solo paso (recomendado)</td>
              </tr>
              <tr>
                <td class="px-3 py-2 font-mono text-primary">make:model</td>
                <td class="px-3 py-2">solo el modelo</td>
                <td class="px-3 py-2 text-slate-600">Ya tienes la tabla y solo necesitas la clase</td>
              </tr>
              <tr>
                <td class="px-3 py-2 font-mono text-primary">make:migration</td>
                <td class="px-3 py-2">solo el archivo de migración</td>
                <td class="px-3 py-2 text-slate-600">Solo quieres crear/modificar la tabla</td>
              </tr>
              <tr>
                <td class="px-3 py-2 font-mono text-primary">make:controller</td>
                <td class="px-3 py-2">solo el controlador</td>
                <td class="px-3 py-2 text-slate-600">Ya tienes modelo y vistas, falta el controller</td>
              </tr>
              <tr>
                <td class="px-3 py-2 font-mono text-primary">make:views</td>
                <td class="px-3 py-2">las 4 vistas (index, crear, editar, ver)</td>
                <td class="px-3 py-2 text-slate-600">Quieres regenerar solo las vistas</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- Tipos de campos --}}
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Tipos de campos</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">string</code>
            <span class="text-slate-500 ml-2">VARCHAR con longitud configurable (default 255)</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">text</code>
            <span class="text-slate-500 ml-2">TEXT (texto largo, descripciones)</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">int</code>
            <span class="text-slate-500 ml-2">INT(11) — enteros regulares</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">bigint</code>
            <span class="text-slate-500 ml-2">BIGINT — enteros grandes</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">decimal</code>
            <span class="text-slate-500 ml-2">DECIMAL(10,2) — precios, montos</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">boolean</code>
            <span class="text-slate-500 ml-2">TINYINT(1) — 0/1, checkbox en vistas</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">date</code>
            <span class="text-slate-500 ml-2">DATE — solo fecha</span>
          </div>
          <div class="p-2.5 rounded-lg border border-slate-200">
            <code class="text-primary font-semibold">datetime</code>
            <span class="text-slate-500 ml-2">DATETIME — fecha + hora</span>
          </div>
        </div>
      </div>

      {{-- Ejemplos prácticos --}}
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Ejemplos prácticos</h4>

        <div class="space-y-3">
          <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
            <div class="text-sm font-semibold text-slate-800 mb-2">📝 CRUD de tareas</div>
            <div class="text-xs text-slate-600 space-y-1 font-mono">
              <div><span class="text-slate-400">Comando:</span> <span class="text-primary">make:crud</span></div>
              <div><span class="text-slate-400">Nombre:</span> tareas</div>
              <div><span class="text-slate-400">Campos:</span></div>
              <div class="pl-4">• titulo <span class="text-slate-400">(string, 150, required)</span></div>
              <div class="pl-4">• descripcion <span class="text-slate-400">(text)</span></div>
              <div class="pl-4">• completa <span class="text-slate-400">(boolean)</span></div>
              <div class="pl-4">• vencimiento <span class="text-slate-400">(date)</span></div>
            </div>
          </div>

          <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
            <div class="text-sm font-semibold text-slate-800 mb-2">📦 CRUD de productos dentro de un plugin</div>
            <div class="text-xs text-slate-600 space-y-1 font-mono">
              <div><span class="text-slate-400">Comando:</span> <span class="text-primary">make:crud</span></div>
              <div><span class="text-slate-400">Destino:</span> plugin: <em>MyStorePlugin</em></div>
              <div><span class="text-slate-400">Nombre:</span> articulos</div>
              <div><span class="text-slate-400">Tabla:</span> store_articulos</div>
              <div><span class="text-slate-400">Campos:</span></div>
              <div class="pl-4">• nombre <span class="text-slate-400">(string, 200, required)</span></div>
              <div class="pl-4">• sku <span class="text-slate-400">(string, 50, required, unique)</span></div>
              <div class="pl-4">• precio <span class="text-slate-400">(decimal, required)</span></div>
              <div class="pl-4">• stock <span class="text-slate-400">(int)</span></div>
            </div>
          </div>

          <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
            <div class="text-sm font-semibold text-slate-800 mb-2">👥 Solo el modelo (tabla ya existe)</div>
            <div class="text-xs text-slate-600 space-y-1 font-mono">
              <div><span class="text-slate-400">Comando:</span> <span class="text-primary">make:model</span></div>
              <div><span class="text-slate-400">Nombre:</span> clientes</div>
              <div><span class="text-slate-400">Tabla:</span> clientes_mensuales</div>
              <div class="text-slate-500 mt-2">Sin campos — solo crea la clase Model con métodos all, by_id, insertOne, etc.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Qué código se genera --}}
      <div>
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Qué código se genera</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
          <div class="p-3 rounded-lg border border-slate-200">
            <div class="font-semibold text-slate-800 mb-1 flex items-center gap-2">
              <i class="ri-database-line text-primary"></i> Modelo
            </div>
            <ul class="text-xs text-slate-600 space-y-0.5 pl-4 list-disc">
              <li><code>all()</code> / <code>all_paginated()</code></li>
              <li><code>by_id($id)</code></li>
              <li><code>insertOne($data)</code></li>
              <li><code>updateById($id, $data)</code></li>
              <li><code>deleteById($id)</code></li>
            </ul>
          </div>
          <div class="p-3 rounded-lg border border-slate-200">
            <div class="font-semibold text-slate-800 mb-1 flex items-center gap-2">
              <i class="ri-code-s-slash-line text-primary"></i> Controlador
            </div>
            <ul class="text-xs text-slate-600 space-y-0.5 pl-4 list-disc">
              <li><code>index()</code> con búsqueda + paginación</li>
              <li><code>crear()</code> y <code>post_crear()</code></li>
              <li><code>ver($id)</code></li>
              <li><code>editar($id)</code> y <code>post_editar()</code></li>
              <li><code>borrar($id)</code></li>
              <li>Auth guard + CSRF en todos los POST</li>
            </ul>
          </div>
          <div class="p-3 rounded-lg border border-slate-200">
            <div class="font-semibold text-slate-800 mb-1 flex items-center gap-2">
              <i class="ri-window-line text-primary"></i> 4 Vistas Blade
            </div>
            <ul class="text-xs text-slate-600 space-y-0.5 pl-4 list-disc">
              <li><code>indexView</code> — tabla + acciones dropdown</li>
              <li><code>crearView</code> — formulario</li>
              <li><code>editarView</code> — formulario + ID</li>
              <li><code>verView</code> — detalle read-only</li>
              <li>Usan el layout admin Preline/Tailwind</li>
            </ul>
          </div>
          <div class="p-3 rounded-lg border border-slate-200">
            <div class="font-semibold text-slate-800 mb-1 flex items-center gap-2">
              <i class="ri-stack-line text-primary"></i> Migración
            </div>
            <ul class="text-xs text-slate-600 space-y-0.5 pl-4 list-disc">
              <li>Timestamp automático (YYYY_MM_DD_HHMMSS)</li>
              <li><code>id</code>, <code>created_at</code>, <code>updated_at</code> auto</li>
              <li>Tipos MySQL correctos por campo</li>
              <li>UNIQUE KEY si marcaste "Uniq"</li>
              <li>Método <code>down()</code> con DROP TABLE</li>
            </ul>
          </div>
        </div>
      </div>

      {{-- Gotchas --}}
      <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-700 mb-2 flex items-center gap-2">
          <i class="ri-alert-line"></i> Protecciones y límites
        </h4>
        <ul class="text-sm text-amber-900 space-y-1 pl-5 list-disc">
          <li><strong>Nunca sobrescribe:</strong> si el archivo ya existe, falla y te lo dice en rojo. Borra el archivo antes de regenerar.</li>
          <li><strong>Nombres reservados:</strong> no puedes usar <code class="bg-amber-100 px-1 rounded text-xs">admin</code>, <code class="bg-amber-100 px-1 rounded text-xs">api</code>, <code class="bg-amber-100 px-1 rounded text-xs">login</code>, <code class="bg-amber-100 px-1 rounded text-xs">logout</code>, <code class="bg-amber-100 px-1 rounded text-xs">quetzal</code>, <code class="bg-amber-100 px-1 rounded text-xs">error</code>, <code class="bg-amber-100 px-1 rounded text-xs">home</code>, <code class="bg-amber-100 px-1 rounded text-xs">tienda</code>, <code class="bg-amber-100 px-1 rounded text-xs">carrito</code>.</li>
          <li><strong>El generador NO corre migraciones automáticamente.</strong> Después de generar, ve a <a href="admin/migraciones" class="underline font-medium">Migraciones</a> y ejecútalas manualmente.</li>
          <li><strong>El controller generado es base:</strong> tiene validaciones genéricas (<code>sanitize_input</code>, CSRF). Revisa el código para agregar validaciones específicas de tu negocio.</li>
          <li><strong>Si generaste en un plugin:</strong> el plugin debe estar <strong>habilitado</strong> para que las rutas funcionen, y corre <a href="admin/plugins" class="underline font-medium">Reconstruir plugins</a> después.</li>
        </ul>
      </div>
    </div>
  </details>

  <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

    {{-- ============ FORMULARIO (izquierda) ============ --}}
    <div class="lg:col-span-2 space-y-4">
      <div class="bg-white rounded-xl border border-slate-200 p-5 sticky top-4">
        <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
          <i class="ri-terminal-box-line text-primary"></i> Argumentos
        </h3>

        <form id="q-gen-form" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Comando</label>
            <select name="command" id="q-command" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
              <option value="make:crud" selected>make:crud — CRUD completo</option>
              <option value="make:model">make:model — solo modelo</option>
              <option value="make:migration">make:migration — solo migración</option>
              <option value="make:controller">make:controller — solo controlador</option>
              <option value="make:views">make:views — solo vistas (4)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Destino</label>
            <select name="target" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
              <option value="core">core — aplicación principal</option>
              @foreach($enabledPlugins as $p)
                <option value="{{ $p['name'] }}">plugin: {{ $p['name'] }} v{{ $p['version'] }}</option>
              @endforeach
            </select>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Nombre del recurso</label>
              <input type="text" name="name" id="q-name" required pattern="^[a-z][a-z0-9_]{1,49}$"
                     placeholder="tareas"
                     class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
              <p class="text-xs text-slate-500 mt-1">Minúsculas, singular o plural. Será la URL.</p>
            </div>
            <div>
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Tabla BD</label>
              <input type="text" name="table" id="q-table" pattern="^[a-z][a-z0-9_]{1,59}$"
                     placeholder="(mismo que nombre)"
                     class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
              <p class="text-xs text-slate-500 mt-1">Déjalo vacío para usar el mismo.</p>
            </div>
          </div>

          {{-- Sidebar (solo para make:crud) --}}
          <div id="q-sidebar-section" class="pt-3 border-t border-slate-100">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1">
              <i class="ri-sidebar-fold-line"></i> Sidebar
              <span class="text-slate-400 font-normal normal-case">(opcional)</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-slate-600 mb-1">Título visible</label>
                <input type="text" name="sidebar_title" maxlength="50"
                       placeholder="(no aparece en sidebar si vacío)"
                       class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
              </div>
              <div>
                <label class="block text-xs text-slate-600 mb-1">Icono Remix</label>
                <input type="text" name="sidebar_icon" placeholder="ri-folder-line"
                       class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
                <p class="text-xs text-slate-400 mt-0.5">
                  <a href="https://remixicon.com/" target="_blank" class="hover:underline">buscar iconos →</a>
                </p>
              </div>
              <div class="sm:col-span-2">
                <label class="block text-xs text-slate-600 mb-1">Grupo del sidebar</label>
                <input type="text" name="sidebar_group" list="q-groups" placeholder="Gestión" value="Gestión"
                       class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
                <datalist id="q-groups">
                  <option value="Panel">
                  <option value="Gestión">
                  <option value="Sistema">
                  <option value="Desarrollo">
                  <option value="Reportes">
                </datalist>
                <p class="text-xs text-slate-400 mt-0.5">Si el grupo existe se agrega ahí; si no, se crea uno nuevo.</p>
              </div>
            </div>
          </div>

          {{-- Campos dinámicos --}}
          <div id="q-fields-section">
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">
                Campos de la tabla
              </label>
              <button type="button" id="q-add-field" class="text-primary text-xs font-medium hover:underline inline-flex items-center gap-1">
                <i class="ri-add-line"></i> Agregar
              </button>
            </div>

            <div id="q-fields" class="space-y-2"></div>

            <p class="text-xs text-slate-500 mt-2">
              <i class="ri-information-line"></i> <code class="bg-slate-100 px-1 rounded">id</code>, <code class="bg-slate-100 px-1 rounded">created_at</code> y <code class="bg-slate-100 px-1 rounded">updated_at</code> se generan automáticamente.
            </p>
          </div>

          <button type="submit" id="q-run" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg btn-primary font-semibold text-sm">
            <i class="ri-play-fill"></i> Ejecutar
          </button>
        </form>

        <div class="mt-4 pt-4 border-t border-slate-100 text-xs text-slate-500 space-y-1">
          <p><i class="ri-shield-line"></i> El generador nunca sobrescribe archivos existentes.</p>
          <p><i class="ri-shield-line"></i> Nombres reservados (admin, api, login...) son rechazados.</p>
        </div>
      </div>
    </div>

    {{-- ============ TERMINAL (derecha) ============ --}}
    <div class="lg:col-span-3">
      <div class="q-term rounded-xl border border-slate-700 overflow-hidden shadow-xl">
        <div class="q-term-header px-4 py-2.5 flex items-center gap-2">
          <span class="q-term-dot" style="background:#ef4444"></span>
          <span class="q-term-dot" style="background:#eab308"></span>
          <span class="q-term-dot" style="background:#22c55e"></span>
          <div class="flex-1 text-center text-xs text-slate-400 font-mono">quetzal:generator — /admin/generador</div>
          <button type="button" id="q-clear" class="text-xs text-slate-400 hover:text-white font-mono" title="Limpiar terminal">
            <i class="ri-delete-bin-line"></i>
          </button>
        </div>
        <div id="q-output" class="q-term-output px-4 py-3 text-sm">
          <div class="q-term-line muted"># Bienvenido al generador de CRUD de Quetzal</div>
          <div class="q-term-line muted"># Define los argumentos a la izquierda y presiona Ejecutar</div>
          <div class="q-term-line muted"># Los archivos generados aparecerán aquí</div>
          <div class="q-term-line"><span class="q-term-prompt">quetzal:admin&gt;</span> <span class="q-cursor"></span></div>
        </div>
      </div>

      {{-- Preview de archivos que se crearán --}}
      <div class="bg-white rounded-xl border border-slate-200 mt-4 p-4">
        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">
          <i class="ri-eye-line"></i> Archivos que se crearán
        </h4>
        <div id="q-preview" class="text-xs text-slate-600 font-mono space-y-0.5">
          <div class="text-slate-400">Completa los argumentos para ver el preview...</div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
  // ============================================================
  //  Gestión de campos dinámicos
  // ============================================================
  const fieldsBox = document.getElementById('q-fields');
  const addBtn    = document.getElementById('q-add-field');

  function makeFieldRow(data = {}) {
    const row = document.createElement('div');
    row.className = 'grid grid-cols-12 gap-1 items-center';
    row.innerHTML = `
      <input type="text" name="fields[][name]" placeholder="campo" value="${data.name || ''}"
             pattern="^[a-z][a-z0-9_]{0,49}$" required
             class="col-span-4 rounded-md border-slate-300 focus:border-primary focus:ring-primary text-xs font-mono">
      <select name="fields[][type]" class="col-span-3 rounded-md border-slate-300 focus:border-primary focus:ring-primary text-xs font-mono">
        <option value="string"  ${data.type === 'string'  ? 'selected' : ''}>string</option>
        <option value="text"    ${data.type === 'text'    ? 'selected' : ''}>text</option>
        <option value="int"     ${data.type === 'int'     ? 'selected' : ''}>int</option>
        <option value="bigint"  ${data.type === 'bigint'  ? 'selected' : ''}>bigint</option>
        <option value="decimal" ${data.type === 'decimal' ? 'selected' : ''}>decimal</option>
        <option value="boolean" ${data.type === 'boolean' ? 'selected' : ''}>boolean</option>
        <option value="date"    ${data.type === 'date'    ? 'selected' : ''}>date</option>
        <option value="datetime" ${data.type === 'datetime' ? 'selected' : ''}>datetime</option>
      </select>
      <input type="number" name="fields[][length]" value="${data.length || 255}" min="1" max="65535"
             class="col-span-2 rounded-md border-slate-300 focus:border-primary focus:ring-primary text-xs font-mono" title="Longitud (solo string)">
      <label class="col-span-1 flex items-center justify-center" title="Requerido">
        <input type="checkbox" name="fields[][required]" value="1" ${data.required ? 'checked' : ''}
               class="rounded border-slate-300 text-primary focus:ring-primary">
      </label>
      <label class="col-span-1 flex items-center justify-center" title="Único">
        <input type="checkbox" name="fields[][unique]" value="1" ${data.unique ? 'checked' : ''}
               class="rounded border-slate-300 text-primary focus:ring-primary">
      </label>
      <button type="button" class="col-span-1 text-red-500 hover:text-red-700 text-sm" data-q-remove title="Eliminar">
        <i class="ri-close-line"></i>
      </button>
    `;
    row.querySelector('[data-q-remove]').addEventListener('click', () => {
      row.remove();
      updatePreview();
    });
    row.querySelectorAll('input, select').forEach(el => el.addEventListener('input', updatePreview));
    return row;
  }

  function addInitialHeader() {
    const h = document.createElement('div');
    h.className = 'grid grid-cols-12 gap-1 text-[10px] uppercase tracking-wider text-slate-400 font-semibold mb-1';
    h.innerHTML = `
      <div class="col-span-4">Nombre</div>
      <div class="col-span-3">Tipo</div>
      <div class="col-span-2">Len</div>
      <div class="col-span-1 text-center">Req</div>
      <div class="col-span-1 text-center">Uniq</div>
      <div class="col-span-1"></div>
    `;
    fieldsBox.appendChild(h);
  }

  addInitialHeader();

  addBtn.addEventListener('click', () => {
    fieldsBox.appendChild(makeFieldRow());
    updatePreview();
  });

  // Sembrar con 2 campos de ejemplo
  fieldsBox.appendChild(makeFieldRow({ name: 'titulo',      type: 'string',  length: 150, required: true }));
  fieldsBox.appendChild(makeFieldRow({ name: 'descripcion', type: 'text',    length: 255 }));

  // ============================================================
  //  Preview
  // ============================================================
  const preview = document.getElementById('q-preview');
  const nameInput  = document.getElementById('q-name');
  const tableInput = document.getElementById('q-table');
  const cmdSelect  = document.getElementById('q-command');

  function updatePreview() {
    const name  = (nameInput.value || '').trim();
    const table = (tableInput.value || name).trim();
    const cmd   = cmdSelect.value;

    if (!name) {
      preview.innerHTML = '<div class="text-slate-400">Completa los argumentos para ver el preview...</div>';
      return;
    }

    const timestamp = new Date().toISOString().slice(0, 19).replace(/[-T:]/g, '_');
    const items = [];

    if (cmd === 'make:crud' || cmd === 'make:migration') {
      items.push(`<i class="ri-file-add-line text-primary"></i> app/migrations/${timestamp}_create_${table}_table.php`);
    }
    if (cmd === 'make:crud' || cmd === 'make:model') {
      items.push(`<i class="ri-file-add-line text-primary"></i> app/models/${name}Model.php`);
    }
    if (cmd === 'make:crud' || cmd === 'make:controller') {
      items.push(`<i class="ri-file-add-line text-primary"></i> app/controllers/${name}Controller.php`);
    }
    if (cmd === 'make:crud' || cmd === 'make:views') {
      ['index', 'crear', 'editar', 'ver'].forEach(v => {
        items.push(`<i class="ri-file-add-line text-primary"></i> templates/views/${name}/${v}View.blade.php`);
      });
    }

    preview.innerHTML = items.map(it => `<div class="flex items-center gap-1.5">${it}</div>`).join('');
  }

  nameInput.addEventListener('input', updatePreview);
  tableInput.addEventListener('input', updatePreview);
  cmdSelect.addEventListener('change', updatePreview);

  // ============================================================
  //  Terminal output
  // ============================================================
  const output = document.getElementById('q-output');
  const form   = document.getElementById('q-gen-form');
  const runBtn = document.getElementById('q-run');

  function appendLine(text, level = 'muted') {
    const line = document.createElement('div');
    line.className = 'q-term-line ' + level;
    line.textContent = text;
    // Remove last cursor line
    const cursorLine = output.querySelector('.q-cursor');
    if (cursorLine) cursorLine.parentElement.remove();
    output.appendChild(line);
    addCursor();
    output.scrollTop = output.scrollHeight;
  }

  function addCursor() {
    const p = document.createElement('div');
    p.className = 'q-term-line';
    p.innerHTML = '<span class="q-term-prompt">quetzal:admin&gt;</span> <span class="q-cursor"></span>';
    output.appendChild(p);
  }

  document.getElementById('q-clear').addEventListener('click', () => {
    output.innerHTML = '';
    appendLine('# Terminal limpiada', 'muted');
  });

  // ============================================================
  //  Submit AJAX
  // ============================================================
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    runBtn.disabled = true;
    runBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Ejecutando...';

    const fd = new FormData(form);
    // Asegura que hay csrf — lo tomamos del DOM (cualquier @csrf de la página lo tiene)
    // Pero este form no tiene @csrf; agreguémoslo dinámicamente con un input hidden preexistente
    // o tomémoslo del CSRF_TOKEN global si expusimos uno en el Quetzal JS object.

    try {
      const res = await fetch('admin/post_generador_run', {
        method: 'POST',
        body: fd,
        credentials: 'same-origin',
      });
      const data = await res.json();

      (data.output || []).forEach(line => appendLine(line.text, line.level));

      if (data.ok) {
        appendLine('', 'muted');
        appendLine('[DONE] Proceso completado.', 'info');
      }
    } catch (err) {
      appendLine('[ERROR] ' + err.message, 'error');
    } finally {
      runBtn.disabled = false;
      runBtn.innerHTML = '<i class="ri-play-fill"></i> Ejecutar';
    }
  });

  // Inyectar CSRF dinámicamente al form: Quetzal expone CSRF_TOKEN vía var JS Quetzal
  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = 'csrf';
  csrfInput.value = (typeof Quetzal !== 'undefined' && Quetzal.csrf) ? Quetzal.csrf : '';
  form.appendChild(csrfInput);

  updatePreview();
})();
</script>
@endpush
@endsection

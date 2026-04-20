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

  {{-- Info banner --}}
  <div class="rounded-xl border border-sky-200 bg-sky-50 text-sky-900 p-4 text-sm">
    <div class="flex items-start gap-2">
      <i class="ri-information-line text-lg mt-0.5"></i>
      <div>
        <p><strong>Generador asistido de CRUD.</strong> Define el nombre, los campos y el destino (core o plugin habilitado). Genera modelo + migración + controlador + 4 vistas Blade en un paso.</p>
        <p class="mt-1 text-xs text-sky-700">Después de generar, ejecuta <a href="admin/migraciones" class="underline">Migraciones</a> para crear la tabla, y visita <code class="bg-sky-100 px-1 rounded">/{nombre}</code> para probar.</p>
      </div>
    </div>
  </div>

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

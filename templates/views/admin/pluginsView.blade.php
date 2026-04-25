@extends('includes.admin.layout')

@section('title', 'Plugins')
@section('page_title', 'Gestión de plugins')

@section('content')
<div class="space-y-6">

  {{-- Toolbar de acciones globales --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
        <i class="ri-plug-line text-primary text-xl"></i>
      </div>
      <div>
        <h2 class="font-semibold text-slate-800">Plugins</h2>
        <p class="text-xs text-slate-500">Gestiona, habilita y reconstruye los plugins del sistema.</p>
      </div>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="admin/plugins_guia"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold">
        <i class="ri-book-open-line"></i> Guía
      </a>
      <button type="button" data-hs-overlay="#q-upload-plugin"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold">
        <i class="ri-upload-2-line"></i> Subir ZIP
      </button>
      <form method="post" action="admin/post_plugin_rebuild" class="inline"
            onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'ri-loader-4-line animate-spin\'></i> Reconstruyendo...';">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-primary text-sm font-semibold"
                title="Limpia cache, valida manifiestos y ejecuta migraciones pendientes de todos los plugins habilitados">
          <i class="ri-refresh-line"></i> Reconstruir plugins
        </button>
      </form>
    </div>
  </div>

  {{-- Modal de upload (vanilla — no depende de Preline) --}}
  <div id="q-upload-plugin"
       class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-900/60 p-4"
       role="dialog" aria-modal="true">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full" onclick="event.stopPropagation()">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
            <i class="ri-upload-2-line text-primary"></i>
          </div>
          <div>
            <h3 class="font-semibold text-slate-800">Subir plugin (ZIP)</h3>
            <p class="text-xs text-slate-500">Se extrae a <code class="bg-slate-100 px-1 rounded">/plugins/</code></p>
          </div>
        </div>
        <button type="button" data-q-modal-close class="text-slate-400 hover:text-slate-600 p-1">
          <i class="ri-close-line text-xl"></i>
        </button>
      </div>

      <form method="post" action="admin/post_plugin_upload" enctype="multipart/form-data" class="p-5 space-y-4"
            onsubmit="this.querySelector('button[type=submit]').disabled=true; this.querySelector('button[type=submit]').innerHTML='<i class=\'ri-loader-4-line animate-spin\'></i> Subiendo...';">
        @csrf

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Archivo ZIP del plugin</label>
          <label for="q-plugin-zip" id="q-dropzone"
                 class="flex flex-col items-center justify-center gap-2 border-2 border-dashed border-slate-300 rounded-xl p-6 cursor-pointer hover:border-primary hover:bg-slate-50 transition">
            <i class="ri-file-zip-line text-3xl text-slate-400" id="q-dropzone-icon"></i>
            <div class="text-center">
              <div class="text-sm font-medium text-slate-700" id="q-dropzone-title">Haz click o arrastra un ZIP aquí</div>
              <div class="text-xs text-slate-500 mt-0.5" id="q-zip-name">Máximo 20 MB</div>
            </div>
            <input id="q-plugin-zip" type="file" name="plugin_zip" accept=".zip,application/zip,application/x-zip-compressed" required class="hidden">
          </label>
        </div>

        <div class="rounded-lg border border-sky-200 bg-sky-50 text-sky-900 p-3 text-xs space-y-1">
          <div class="flex items-start gap-2">
            <i class="ri-information-line mt-0.5"></i>
            <div>
              <p>El ZIP debe contener un <code class="bg-sky-100 px-1 rounded">plugin.json</code> en la raíz o dentro de una sola carpeta.</p>
              <p class="mt-1">Si el plugin ya existe, se <strong>actualizará</strong> automáticamente (con backup y rollback si falla).</p>
              <p class="mt-1">Tras subirlo aparecerá como <strong>activo</strong> automáticamente (plugins zero-config).</p>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" data-q-modal-close class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</button>
          <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
            <i class="ri-upload-2-line"></i> Subir
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    (function() {
      // Toggle del modal — independiente de Preline
      const modal = document.getElementById('q-upload-plugin');
      if (!modal) return;

      function open()  { modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; }
      function close() { modal.classList.add('hidden');    modal.classList.remove('flex'); document.body.style.overflow = ''; }

      // Botones que abren (cualquier [data-hs-overlay="#q-upload-plugin"])
      document.querySelectorAll('[data-hs-overlay="#q-upload-plugin"]').forEach(btn => {
        btn.addEventListener('click', (e) => { e.preventDefault(); open(); });
      });
      // Botones que cierran
      modal.querySelectorAll('[data-q-modal-close]').forEach(btn => {
        btn.addEventListener('click', close);
      });
      // Click en el backdrop cierra
      modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
      // ESC cierra
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
      });

      // Mostrar nombre de archivo al seleccionar + drag-and-drop
      const input    = document.getElementById('q-plugin-zip');
      const dropzone = document.getElementById('q-dropzone');
      const label    = document.getElementById('q-zip-name');
      const title    = document.getElementById('q-dropzone-title');
      const icon     = document.getElementById('q-dropzone-icon');

      function showFile(file) {
        if (!file) return;
        // Asignar al input para que forme parte del submit
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;

        label.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        label.classList.remove('text-slate-500');
        label.classList.add('text-primary', 'font-mono');
        if (title) title.textContent = 'Archivo listo';
        if (icon) {
          icon.classList.remove('text-slate-400');
          icon.classList.add('text-primary');
        }
      }

      function isZip(file) {
        return file && (
          file.type === 'application/zip' ||
          file.type === 'application/x-zip-compressed' ||
          file.name.toLowerCase().endsWith('.zip')
        );
      }

      if (input) {
        input.addEventListener('change', function() {
          if (this.files[0]) showFile(this.files[0]);
        });
      }

      if (dropzone) {
        // Prevenir que el navegador abra el archivo si sueltan fuera del dropzone
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(evt => {
          dropzone.addEventListener(evt, (e) => { e.preventDefault(); e.stopPropagation(); });
        });

        dropzone.addEventListener('dragenter', () => {
          dropzone.classList.add('border-primary', 'bg-primary/5');
        });
        dropzone.addEventListener('dragover', () => {
          dropzone.classList.add('border-primary', 'bg-primary/5');
        });
        dropzone.addEventListener('dragleave', (e) => {
          // Solo quitar highlight si salimos del dropzone (no al entrar a un child)
          if (e.target === dropzone) {
            dropzone.classList.remove('border-primary', 'bg-primary/5');
          }
        });
        dropzone.addEventListener('drop', (e) => {
          dropzone.classList.remove('border-primary', 'bg-primary/5');
          const file = e.dataTransfer?.files?.[0];
          if (!file) return;
          if (!isZip(file)) {
            alert('Solo se aceptan archivos .zip');
            return;
          }
          showFile(file);
        });

        // Prevenir que el navegador abra el archivo si se suelta FUERA del dropzone
        window.addEventListener('dragover', (e) => e.preventDefault());
        window.addEventListener('drop', (e) => {
          if (!dropzone.contains(e.target)) e.preventDefault();
        });
      }
    })();
  </script>

  {{-- Log del último rebuild (si existe en sesión) --}}
  @if(!empty($_SESSION['plugin_rebuild_log']))
    @php
      $log = $_SESSION['plugin_rebuild_log'];
      unset($_SESSION['plugin_rebuild_log']);
      $valErr = (int)($log['summary']['validation_err'] ?? 0);
      $migErr = (int)($log['summary']['migration_err'] ?? 0);
      $hasErr = ($valErr + $migErr) > 0;
    @endphp

    @if($hasErr)
      {{-- Sólo se abre automáticamente si hay errores --}}
      <details class="bg-white rounded-xl border border-red-200 overflow-hidden" open>
        <summary class="cursor-pointer px-4 py-2.5 bg-red-50 flex items-center justify-between gap-2 list-none hover:bg-red-100">
          <div class="flex items-center gap-2 text-sm font-medium text-red-800">
            <i class="ri-error-warning-fill"></i>
            Se detectaron {{ $valErr + $migErr }} error(es) en la última reconstrucción
            <span class="text-xs font-normal text-red-600">— click para ver detalle</span>
          </div>
          <i class="ri-arrow-down-s-line text-red-500"></i>
        </summary>
        <ul class="divide-y divide-slate-100 text-sm">
          @foreach($log['steps'] as $step)
            @php
              $icon = match($step['status']) {
                'ok'      => 'ri-check-line text-emerald-600',
                'error'   => 'ri-close-line text-red-600',
                'partial' => 'ri-alert-line text-amber-600',
                default   => 'ri-subtract-line text-slate-400',
              };
              $label = match($step['step']) {
                'cache'    => 'Cache',
                'validate' => 'Validación',
                'migrate'  => 'Migración',
                default    => ucfirst($step['step']),
              };
            @endphp
            <li class="px-5 py-2.5 flex items-start gap-3">
              <i class="{{ $icon }} mt-0.5"></i>
              <div class="flex-1 min-w-0">
                <div class="text-xs uppercase tracking-wider text-slate-400">
                  {{ $label }} @isset($step['plugin']) · <span class="font-mono">{{ $step['plugin'] }}</span>@endisset
                </div>
                <div class="text-slate-700 text-sm">{{ $step['message'] }}</div>
              </div>
            </li>
          @endforeach
        </ul>
      </details>
    @else
      {{-- Banner compacto de éxito, sin acordeón desplegado --}}
      <div class="bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-2 flex items-center gap-2 text-sm text-emerald-800">
        <i class="ri-checkbox-circle-fill text-emerald-600"></i>
        <span>Reconstrucción completada sin errores ({{ count($log['steps']) }} pasos).</span>
      </div>
    @endif
  @endif

  {{-- Resumen --}}
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500">Descubiertos</div>
        <div class="text-2xl font-bold mt-1">{{ $summary['total'] }}</div>
      </div>
      <div class="w-12 h-12 rounded-lg bg-purple-50 flex items-center justify-center">
        <i class="ri-plug-line text-purple-600 text-xl"></i>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500">Habilitados</div>
        <div class="text-2xl font-bold mt-1 text-emerald-600">{{ $summary['enabled'] }}</div>
      </div>
      <div class="w-12 h-12 rounded-lg bg-emerald-50 flex items-center justify-center">
        <i class="ri-toggle-line text-emerald-600 text-xl"></i>
      </div>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between">
      <div>
        <div class="text-xs uppercase tracking-wider text-slate-500">Deshabilitados</div>
        <div class="text-2xl font-bold mt-1 text-slate-500">{{ $summary['disabled'] }}</div>
      </div>
      <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center">
        <i class="ri-pause-line text-slate-500 text-xl"></i>
      </div>
    </div>
  </div>

  {{-- ============ BUSCADOR + FILTROS ============ --}}
  @if(!empty($plugins))
    <div class="bg-white rounded-xl border border-slate-200 p-3 flex items-center gap-2 flex-wrap" id="plugin-search-bar">
      <div class="relative flex-1 min-w-[220px]">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="search" id="plugin-search-input" placeholder="Buscar por nombre, autor o descripción..."
               class="w-full pl-10 pr-3 py-2 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
      </div>
      <select id="plugin-filter-status" class="rounded-lg border-slate-300 text-sm py-2">
        <option value="">Todos</option>
        <option value="enabled">Activos</option>
        <option value="disabled">Deshabilitados</option>
      </select>
      <span id="plugin-count-info" class="text-xs text-slate-500 font-mono"></span>
    </div>
  @endif

  {{-- ============ LISTA DE PLUGINS ============ --}}
  @if(empty($plugins))
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center">
      <i class="ri-plug-line text-5xl text-slate-300 mb-3 block"></i>
      <p class="text-slate-500 text-sm">
        No hay plugins en <code>/plugins/</code>. Crea uno siguiendo la <a href="https://" onclick="return false" class="text-primary">documentación</a>.
      </p>
    </div>
  @else
    @php
      // Mapa name → estado enabled para todos los plugins
      $enabledMap = [];
      foreach ($plugins as $pp) { $enabledMap[$pp['name']] = !empty($pp['enabled']); }
      $existsMap = [];
      foreach ($plugins as $pp) { $existsMap[$pp['name']] = true; }
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3" id="plugin-grid">
      @foreach($plugins as $p)
        @php
          $isInstalled = !empty($p['installed']);
          $isEnabled   = !empty($p['enabled']);
          $requires    = $p['requires'] ?? [];
          $searchBlob  = strtolower(trim(($p['name'] ?? '') . ' ' . ($p['author'] ?? '') . ' ' . ($p['description'] ?? '')));

          // Detectar dependencias faltantes (no existen o no están habilitadas)
          $faltantes = [];
          foreach ($requires as $dep) {
            if (empty($existsMap[$dep])) {
              $faltantes[$dep] = 'missing';     // no existe en disco
            } elseif (empty($enabledMap[$dep])) {
              $faltantes[$dep] = 'disabled';    // existe pero no habilitado
            }
          }
          $tieneFaltantes = !empty($faltantes);
        @endphp
        <div class="plugin-card bg-white rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow-sm transition p-3 flex flex-col"
             data-search="{{ $searchBlob }}"
             data-status="{{ $isEnabled ? 'enabled' : 'disabled' }}">
          {{-- Header: icon + name + status --}}
          <div class="flex items-start gap-2 mb-2">
            <div class="w-8 h-8 rounded-md flex-shrink-0 flex items-center justify-center
                        {{ $isEnabled ? 'bg-emerald-50 text-emerald-600' : ($isInstalled ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-400') }}">
              <i class="ri-plug-line text-sm"></i>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-1">
                <h3 class="font-semibold text-slate-800 text-sm truncate">{{ $p['name'] }}</h3>
                @if($isEnabled)
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 flex-shrink-0" title="Activo"></span>
                @else
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-300 flex-shrink-0" title="Deshabilitado"></span>
                @endif
              </div>
              <div class="text-[10px] text-slate-400">v{{ $p['version'] ?? '?' }}@if(!empty($p['author'])) · {{ $p['author'] }}@endif</div>
            </div>
          </div>

          {{-- Description --}}
          @if(!empty($p['description']))
            <p class="text-xs text-slate-600 line-clamp-2 mb-2" title="{{ $p['description'] }}">{{ $p['description'] }}</p>
          @endif

          {{-- Dependencies (solo si hay) --}}
          @if(!empty($requires))
            <div class="flex flex-wrap gap-1 mb-2">
              @foreach($requires as $dep)
                @php
                  $depState = $faltantes[$dep] ?? 'ok';
                  $cls = match($depState) {
                    'missing'  => 'bg-red-50 text-red-700 border border-red-200',
                    'disabled' => 'bg-amber-50 text-amber-700 border border-amber-200',
                    default    => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                  };
                  $ico = match($depState) {
                    'missing'  => 'ri-error-warning-line',
                    'disabled' => 'ri-pause-circle-line',
                    default    => 'ri-check-line',
                  };
                  $ttl = match($depState) {
                    'missing'  => 'Este plugin no está instalado',
                    'disabled' => 'Este plugin existe pero está deshabilitado',
                    default    => 'Habilitado',
                  };
                @endphp
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] {{ $cls }}" title="Requiere {{ $dep }} — {{ $ttl }}">
                  <i class="{{ $ico }} text-[9px]"></i>{{ $dep }}
                </span>
              @endforeach
            </div>
          @endif

          {{-- Banner de faltantes con botón "Activar en cascada" --}}
          @if(!$isEnabled && $tieneFaltantes)
            <div class="mb-2 p-2 rounded bg-amber-50 border border-amber-200 text-[11px] text-amber-800">
              <div class="flex items-start gap-1 mb-1">
                <i class="ri-information-line mt-0.5"></i>
                <span>Activá primero: <strong>{{ implode(', ', array_keys($faltantes)) }}</strong></span>
              </div>
              @php
                $puedeCascada = !in_array('missing', $faltantes, true);
              @endphp
              @if($puedeCascada)
                <form method="post" action="admin/post_plugin_enable" class="inline"
                      onsubmit="return confirm('¿Activar en cascada?\n\nSe habilitarán primero: {{ implode(', ', array_keys($faltantes)) }}\n\nLuego se habilitará {{ $p['name'] }}.');">
                  @csrf
                  <input type="hidden" name="name" value="{{ $p['name'] }}">
                  <input type="hidden" name="cascade" value="1">
                  <button type="submit" class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-600 hover:bg-amber-700 text-white text-[10px] font-semibold">
                    <i class="ri-play-circle-line"></i> Activar en cascada
                  </button>
                </form>
              @endif
            </div>
          @endif

          {{-- Actions (compactas, al final, con icon-only) --}}
          <div class="flex items-center gap-1 mt-auto pt-2 border-t border-slate-100">
            @if($isEnabled)
              <form method="post" action="admin/post_plugin_disable" class="inline">
                @csrf
                <input type="hidden" name="name" value="{{ $p['name'] }}">
                <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 rounded border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs" title="Deshabilitar">
                  <i class="ri-pause-circle-line"></i> Deshabilitar
                </button>
              </form>
            @else
              <form method="post" action="admin/post_plugin_enable" class="inline">
                @csrf
                <input type="hidden" name="name" value="{{ $p['name'] }}">
                @if($tieneFaltantes)
                  <button type="button" disabled
                          class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 text-slate-400 text-xs font-semibold cursor-not-allowed"
                          title="Activá primero los plugins requeridos">
                    <i class="ri-lock-line"></i> Habilitar
                  </button>
                @else
                  <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 rounded btn-primary text-xs font-semibold" title="Habilitar">
                    <i class="ri-play-circle-line"></i> Habilitar
                  </button>
                @endif
              </form>
            @endif

            <a href="admin/migraciones" class="inline-flex items-center justify-center w-7 h-7 rounded border border-slate-200 text-slate-500 hover:bg-slate-50" title="Migraciones">
              <i class="ri-database-2-line text-xs"></i>
            </a>

            <form method="post" action="admin/post_plugin_delete" class="inline ml-auto"
                  onsubmit="return confirm('¿ELIMINAR el plugin &quot;{{ $p['name'] }}&quot; del disco?\n\nEsta acción borra todos los archivos de plugins/{{ $p['name'] }}/ y es IRREVERSIBLE.\n\nLas tablas de BD creadas por sus migraciones NO se eliminan automáticamente — hazlo desde Migraciones si lo deseas.');">
              @csrf
              <input type="hidden" name="name" value="{{ $p['name'] }}">
              <button type="submit" class="inline-flex items-center justify-center w-7 h-7 rounded border border-red-200 text-red-500 hover:bg-red-50" title="Eliminar del disco">
                <i class="ri-delete-bin-line text-xs"></i>
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Empty state del buscador (oculto por default) --}}
  <div id="plugin-empty-results" class="hidden bg-white rounded-xl border border-slate-200 p-8 text-center">
    <i class="ri-search-eye-line text-4xl text-slate-300 mb-2 block"></i>
    <p class="text-sm text-slate-500">Sin plugins que coincidan con tu búsqueda.</p>
    <button type="button" id="plugin-search-clear" class="mt-3 text-xs text-primary hover:underline">
      <i class="ri-close-line"></i> Limpiar filtros
    </button>
  </div>
</div>

@push('scripts')
<script>
(function () {
  const $search = document.getElementById('plugin-search-input');
  const $filter = document.getElementById('plugin-filter-status');
  const $grid   = document.getElementById('plugin-grid');
  const $empty  = document.getElementById('plugin-empty-results');
  const $info   = document.getElementById('plugin-count-info');
  const $clear  = document.getElementById('plugin-search-clear');
  if (!$grid) return;

  const cards = Array.from($grid.querySelectorAll('.plugin-card'));
  const total = cards.length;

  function norm(s) {
    return (s || '').toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '');
  }

  function apply() {
    const q = norm($search?.value || '');
    const st = $filter?.value || '';
    let visible = 0;
    cards.forEach(c => {
      const hay  = norm(c.dataset.search || '');
      const okQ  = !q || hay.includes(q);
      const okS  = !st || c.dataset.status === st;
      const show = okQ && okS;
      c.style.display = show ? '' : 'none';
      if (show) visible++;
    });
    if ($empty) $empty.classList.toggle('hidden', visible !== 0);
    if ($grid)  $grid.style.display = visible === 0 ? 'none' : '';
    if ($info)  $info.textContent = (q || st) ? (visible + ' / ' + total) : (total + ' plugin' + (total === 1 ? '' : 's'));
  }

  $search?.addEventListener('input', apply);
  $filter?.addEventListener('change', apply);
  $clear?.addEventListener('click', () => {
    if ($search) $search.value = '';
    if ($filter) $filter.value = '';
    apply();
    $search?.focus();
  });

  // ESC en el buscador = limpiar
  $search?.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && $search.value) { $search.value = ''; apply(); }
  });

  apply();
})();
</script>
@endpush
@endsection

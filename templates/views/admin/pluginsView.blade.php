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
      $hasErr = ($log['summary']['validation_err'] ?? 0) + ($log['summary']['migration_err'] ?? 0) > 0;
    @endphp
    <details class="bg-white rounded-xl border {{ $hasErr ? 'border-red-200' : 'border-emerald-200' }} overflow-hidden" open>
      <summary class="cursor-pointer px-5 py-3 {{ $hasErr ? 'bg-red-50' : 'bg-emerald-50' }} flex items-center justify-between gap-2 list-none">
        <div class="flex items-center gap-2 text-sm font-medium {{ $hasErr ? 'text-red-800' : 'text-emerald-800' }}">
          <i class="{{ $hasErr ? 'ri-error-warning-line' : 'ri-checkbox-circle-line' }}"></i>
          Log de la última reconstrucción ({{ count($log['steps']) }} pasos)
        </div>
        <i class="ri-arrow-down-s-line text-slate-500"></i>
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

  <div class="rounded-xl border border-sky-200 bg-sky-50 text-sky-900 p-4 text-sm">
    <div class="flex items-start gap-2">
      <i class="ri-information-line text-lg mt-0.5"></i>
      <div>
        <p>
          Los plugins viven en <code class="bg-sky-100 px-1 rounded">/plugins/&lt;Nombre&gt;/</code> y se descubren automáticamente.
          <strong>Todo plugin nuevo queda habilitado por default</strong> — no necesitas editar <code class="bg-sky-100 px-1 rounded">plugins.json</code>.
          El archivo solo guarda los plugins que hayas deshabilitado explícitamente.
        </p>
        <p class="mt-1 text-xs">
          Tras agregar un plugin nuevo, corre <strong>Reconstruir plugins</strong> para limpiar cache y ejecutar sus migraciones.
          Gestiona migraciones desde <a href="admin/migraciones" class="text-sky-700 font-medium hover:underline">Migraciones</a>.
        </p>
      </div>
    </div>
  </div>

  {{-- ============ LISTA DE PLUGINS ============ --}}
  @if(empty($plugins))
    <div class="bg-white rounded-xl border border-slate-200 p-8 text-center">
      <i class="ri-plug-line text-5xl text-slate-300 mb-3 block"></i>
      <p class="text-slate-500 text-sm">
        No hay plugins en <code>/plugins/</code>. Crea uno siguiendo la <a href="https://" onclick="return false" class="text-primary">documentación</a>.
      </p>
    </div>
  @else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      @foreach($plugins as $p)
        @php
          $isInstalled = !empty($p['installed']);
          $isEnabled   = !empty($p['enabled']);
          $requires    = $p['requires'] ?? [];
        @endphp
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <div class="p-5">
            <div class="flex items-start justify-between gap-3 mb-3">
              <div class="flex items-start gap-3 min-w-0">
                <div class="w-11 h-11 rounded-lg flex-shrink-0 flex items-center justify-center
                            {{ $isEnabled ? 'bg-emerald-50 text-emerald-600' : ($isInstalled ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-400') }}">
                  <i class="ri-plug-line text-xl"></i>
                </div>
                <div class="min-w-0">
                  <h3 class="font-semibold text-slate-800 truncate">
                    {{ $p['name'] }}
                    <span class="text-xs font-normal text-slate-400 ml-1">v{{ $p['version'] ?? '?' }}</span>
                  </h3>
                  @if(!empty($p['author']))
                    <div class="text-xs text-slate-500">por {{ $p['author'] }}</div>
                  @endif
                </div>
              </div>

              {{-- Status badge --}}
              <div class="flex-shrink-0">
                @if($isEnabled)
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-medium">
                    <i class="ri-check-line"></i> activo
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-medium">
                    <i class="ri-pause-line"></i> deshabilitado
                  </span>
                @endif
              </div>
            </div>

            {{-- Description --}}
            @if(!empty($p['description']))
              <p class="text-sm text-slate-600 mb-3 leading-relaxed">{{ $p['description'] }}</p>
            @endif

            {{-- Metadata --}}
            <div class="flex items-center gap-3 flex-wrap text-xs text-slate-500 mb-4">
              @if(!empty($p['min_php']))
                <span class="inline-flex items-center gap-1" title="PHP mínimo requerido">
                  <i class="ri-code-s-slash-line"></i> PHP ≥ {{ $p['min_php'] }}
                </span>
              @endif
              @if(!empty($p['min_quetzal_version']))
                <span class="inline-flex items-center gap-1" title="Quetzal mínimo requerido">
                  <i class="ri-quill-pen-line"></i> Quetzal ≥ {{ $p['min_quetzal_version'] }}
                </span>
              @endif
              <span class="inline-flex items-center gap-1" title="Orden de carga">
                <i class="ri-sort-asc"></i> orden #{{ $p['order'] ?? 0 }}
              </span>
            </div>

            {{-- Dependencies --}}
            @if(!empty($requires))
              <div class="mb-4 pb-4 border-b border-slate-100">
                <div class="text-xs uppercase tracking-wider text-slate-400 mb-1.5 font-semibold">Requiere</div>
                <div class="flex flex-wrap gap-1">
                  @foreach($requires as $dep)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-xs text-slate-600">
                      <i class="ri-link"></i> {{ $dep }}
                    </span>
                  @endforeach
                </div>
              </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center justify-between flex-wrap gap-2">
              <div class="flex items-center gap-2 flex-wrap">
                @if($isEnabled)
                  <form method="post" action="admin/post_plugin_disable" class="inline">
                    @csrf
                    <input type="hidden" name="name" value="{{ $p['name'] }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
                      <i class="ri-pause-circle-line"></i> Deshabilitar
                    </button>
                  </form>
                @else
                  <form method="post" action="admin/post_plugin_enable" class="inline">
                    @csrf
                    <input type="hidden" name="name" value="{{ $p['name'] }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg btn-primary text-sm font-semibold">
                      <i class="ri-play-circle-line"></i> Habilitar
                    </button>
                  </form>
                @endif

                <form method="post" action="admin/post_plugin_delete" class="inline"
                      onsubmit="return confirm('¿ELIMINAR el plugin &quot;{{ $p['name'] }}&quot; del disco?\n\nEsta acción borra todos los archivos de plugins/{{ $p['name'] }}/ y es IRREVERSIBLE.\n\nLas tablas de BD creadas por sus migraciones NO se eliminan automáticamente — hazlo desde Migraciones si lo deseas.');">
                  @csrf
                  <input type="hidden" name="name" value="{{ $p['name'] }}">
                  <button type="submit"
                          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium"
                          title="Eliminar el plugin del disco (irreversible)">
                    <i class="ri-delete-bin-line"></i> Eliminar
                  </button>
                </form>
              </div>

              <a href="admin/migraciones" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-primary">
                <i class="ri-database-2-line"></i> Migraciones
              </a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection

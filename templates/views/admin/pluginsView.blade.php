@extends('includes.admin.layout')

@section('title', 'Plugins')
@section('page_title', 'Gestión de plugins')

@section('content')
<div class="space-y-6">

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
        <div class="text-xs uppercase tracking-wider text-slate-500">Instalados</div>
        <div class="text-2xl font-bold mt-1">{{ $summary['installed'] }}</div>
      </div>
      <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center">
        <i class="ri-install-line text-blue-600 text-xl"></i>
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
  </div>

  <div class="rounded-xl border border-sky-200 bg-sky-50 text-sky-900 p-4 text-sm">
    <div class="flex items-start gap-2">
      <i class="ri-information-line text-lg mt-0.5"></i>
      <div>
        <p>
          Los plugins viven en <code class="bg-sky-100 px-1 rounded">/plugins/&lt;Nombre&gt;/</code>. Se descubren automáticamente
          en cada request, pero hay que <strong>instalarlos</strong> (añadir al registro <code class="bg-sky-100 px-1 rounded">plugins.json</code>)
          y <strong>habilitarlos</strong> (marcarlos como activos) para que aporten controladores, vistas y hooks al framework.
        </p>
        <p class="mt-1 text-xs">
          Las migraciones de cada plugin se ejecutan desde
          <a href="admin/migraciones" class="text-sky-700 font-medium hover:underline">Migraciones</a>.
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
                @elseif($isInstalled)
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
                    <i class="ri-pause-line"></i> instalado
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-medium">
                    <i class="ri-download-line"></i> disponible
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
              @if($isInstalled)
                <span class="inline-flex items-center gap-1" title="Orden de carga">
                  <i class="ri-sort-asc"></i> orden #{{ $p['order'] ?? 0 }}
                </span>
              @endif
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
                @if(!$isInstalled)
                  <form method="post" action="admin/post_plugin_install" class="inline">
                    @csrf
                    <input type="hidden" name="name" value="{{ $p['name'] }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg btn-primary text-sm font-semibold">
                      <i class="ri-download-line"></i> Instalar
                    </button>
                  </form>
                @else
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

                  <form method="post" action="admin/post_plugin_uninstall" class="inline"
                        onsubmit="return confirm('¿Desinstalar {{ $p['name'] }}? Se removerá del registro pero los archivos permanecen en disco.');">
                    @csrf
                    <input type="hidden" name="name" value="{{ $p['name'] }}">
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
                      <i class="ri-delete-bin-line"></i>
                    </button>
                  </form>
                @endif
              </div>

              @if($isInstalled)
                <a href="admin/migraciones" class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-primary">
                  <i class="ri-database-2-line"></i> Migraciones
                </a>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection

@extends('includes.admin.layout')

@section('title', 'Migraciones')
@section('page_title', 'Migraciones de base de datos')

@php
  $renderStatusTable = function ($items) {
    // Closure reutilizable para renderizar la tabla de estado
  };
@endphp

@section('content')
<div class="space-y-6">

  <div class="rounded-xl border border-sky-200 bg-sky-50 text-sky-900 p-4 text-sm">
    <div class="flex items-start gap-2">
      <i class="ri-information-line text-lg mt-0.5"></i>
      <div>
        <div class="font-medium">Panel de migraciones</div>
        <p class="text-sky-800/80 mt-1">
          Quetzal lleva control de qué migraciones se ejecutaron usando una tabla dedicada
          (<code class="bg-sky-100 px-1 rounded">quetzal_migrations</code> para el core,
          <code class="bg-sky-100 px-1 rounded">plugin_&lt;nombre&gt;_migrations</code> para cada plugin).
          Solo se ejecutan las migraciones que aún no están registradas en esa tabla, igual que Laravel.
        </p>
      </div>
    </div>
  </div>

  {{-- ============ DUPLICADOS ============ --}}
  @if(!empty($duplicados))
    <div class="bg-white rounded-xl border-2 border-red-300 overflow-hidden">
      <div class="px-5 py-3 bg-red-50 border-b border-red-200 flex items-center gap-2">
        <i class="ri-error-warning-fill text-red-600 text-xl"></i>
        <div>
          <h3 class="font-semibold text-red-900">Migraciones duplicadas detectadas</h3>
          <p class="text-xs text-red-700">
            {{ count($duplicados) }} tabla(s) tienen más de una migración que las crea.
            Esto puede causar conflictos al correr migraciones. Elimina las versiones más viejas o redundantes.
          </p>
        </div>
      </div>
      <div class="divide-y divide-red-100">
        @foreach($duplicados as $tabla => $archivos)
          <div class="p-4">
            <div class="flex items-center gap-2 mb-3">
              <i class="ri-table-line text-slate-600"></i>
              <code class="font-mono font-semibold text-slate-800">{{ $tabla }}</code>
              <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ count($archivos) }} archivos</span>
            </div>
            <div class="space-y-2">
              @foreach($archivos as $i => $a)
                <div class="flex items-center gap-3 p-2 rounded-lg border border-slate-200 {{ $i === 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50' }}">
                  <div class="flex-shrink-0">
                    @if($i === 0)
                      <span title="Más antigua — probablemente la canónica" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                        <i class="ri-check-line"></i> Canónica
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                        <i class="ri-copy-2-line"></i> Duplicada
                      </span>
                    @endif
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="font-mono text-xs truncate" title="{{ $a['path'] }}">{{ $a['file'] }}</div>
                    <div class="text-xs text-slate-500 flex items-center gap-2 flex-wrap mt-0.5">
                      <span class="inline-flex items-center gap-1">
                        <i class="ri-folder-line"></i>
                        {{ $a['source'] === 'core' ? 'Core' : 'Plugin: ' . $a['source'] }}
                      </span>
                      <span>{{ date('Y-m-d H:i', $a['mtime']) }}</span>
                      <span>{{ number_format($a['size'] / 1024, 1) }} KB</span>
                      @if($a['ran'])
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-sky-100 text-sky-700">
                          <i class="ri-check-line"></i> Ya ejecutada
                        </span>
                      @else
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600">pendiente</span>
                      @endif
                    </div>
                  </div>
                  <form method="post" action="admin/post_borrar_migracion" class="inline"
                        onsubmit="return confirm('¿Eliminar la migración &quot;{{ $a['file'] }}&quot;?{{ $a['ran'] ? ' (También se quitará del tracking)' : '' }}');">
                    @csrf
                    <input type="hidden" name="path"   value="{{ $a['path'] }}">
                    <input type="hidden" name="target" value="{{ $a['target'] }}">
                    @if($a['ran'])<input type="hidden" name="force" value="1">@endif
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-100 text-xs font-medium"
                            title="Eliminar archivo{{ $a['ran'] ? ' + tracking' : '' }}">
                      <i class="ri-delete-bin-line"></i> Eliminar
                    </button>
                  </form>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- ============ CORE ============ --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
          <i class="ri-database-2-line text-primary text-xl"></i>
        </div>
        <div>
          <h2 class="font-semibold text-slate-800">Core</h2>
          <div class="text-xs text-slate-500 flex items-center gap-2 flex-wrap">
            <span>{{ $coreSummary['total'] }} totales</span>
            <span class="text-slate-300">·</span>
            <span class="text-emerald-600">{{ $coreSummary['ran'] }} ejecutadas</span>
            <span class="text-slate-300">·</span>
            <span class="text-amber-600">{{ $coreSummary['pending'] }} pendientes</span>
            @if(!empty($coreSummary['missing']))
              <span class="text-slate-300">·</span>
              <span class="text-red-600">{{ $coreSummary['missing'] }} huérfanas</span>
            @endif
            @if($coreSummary['last_batch'])
              <span class="text-slate-300">·</span>
              <span>Último batch: #{{ $coreSummary['last_batch'] }}</span>
            @endif
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2">
        @if($coreSummary['pending'] > 0)
          <form method="post" action="admin/post_migrate" class="inline">
            @csrf
            <input type="hidden" name="target" value="core">
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg btn-primary text-sm font-semibold">
              <i class="ri-play-line"></i> Ejecutar {{ $coreSummary['pending'] }} pendiente(s)
            </button>
          </form>
        @endif
        @if($coreSummary['ran'] > 0)
          <form method="post" action="admin/post_rollback" class="inline"
                onsubmit="return confirm('¿Revertir el último batch de migraciones del core? Esto ejecuta el método down() y puede destruir datos.');">
            @csrf
            <input type="hidden" name="target" value="core">
            <input type="hidden" name="steps" value="1">
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
              <i class="ri-arrow-go-back-line"></i> Rollback último batch
            </button>
          </form>
        @endif
      </div>
    </div>

    @include('includes.admin.migration_table', ['items' => $coreStatus, 'target' => 'core'])
  </div>

  {{-- ============ PLUGINS ============ --}}
  @foreach($plugins as $p)
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
            <i class="ri-plug-line text-purple-600 text-xl"></i>
          </div>
          <div>
            <h2 class="font-semibold text-slate-800">
              {{ $p['name'] }}
              <span class="text-xs font-normal text-slate-400 ml-1">v{{ $p['version'] }}</span>
            </h2>
            <div class="text-xs text-slate-500 flex items-center gap-2 flex-wrap">
              <span>{{ $p['summary']['total'] }} totales</span>
              <span class="text-slate-300">·</span>
              <span class="text-emerald-600">{{ $p['summary']['ran'] }} ejecutadas</span>
              <span class="text-slate-300">·</span>
              <span class="text-amber-600">{{ $p['summary']['pending'] }} pendientes</span>
              @if(!empty($p['summary']['missing']))
                <span class="text-slate-300">·</span>
                <span class="text-red-600">{{ $p['summary']['missing'] }} huérfanas</span>
              @endif
              @if($p['summary']['last_batch'])
                <span class="text-slate-300">·</span>
                <span>Último batch: #{{ $p['summary']['last_batch'] }}</span>
              @endif
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2">
          @if($p['summary']['pending'] > 0)
            <form method="post" action="admin/post_migrate" class="inline">
              @csrf
              <input type="hidden" name="target" value="{{ $p['name'] }}">
              <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg btn-primary text-sm font-semibold">
                <i class="ri-play-line"></i> Ejecutar {{ $p['summary']['pending'] }} pendiente(s)
              </button>
            </form>
          @endif
          @if($p['summary']['ran'] > 0)
            <form method="post" action="admin/post_rollback" class="inline"
                  onsubmit="return confirm('¿Revertir el último batch de migraciones de {{ $p['name'] }}?');">
              @csrf
              <input type="hidden" name="target" value="{{ $p['name'] }}">
              <input type="hidden" name="steps" value="1">
              <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
                <i class="ri-arrow-go-back-line"></i> Rollback
              </button>
            </form>
          @endif
        </div>
      </div>

      @include('includes.admin.migration_table', ['items' => $p['status'], 'target' => $p['name']])
    </div>
  @endforeach

  @if(empty($plugins))
    <div class="bg-white rounded-xl border border-slate-200 p-6 text-center text-sm text-slate-500">
      <i class="ri-plug-line text-2xl mb-2 block"></i>
      No hay plugins habilitados con migraciones. Los plugins habilitados aparecen aquí automáticamente.
    </div>
  @endif
</div>
@endsection

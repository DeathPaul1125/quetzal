@extends('includes.admin.layout')

@section('title', 'Migraciones')
@section('page_title', 'Migraciones de base de datos')

@php
  $renderStatusTable = function ($items) {
    // Closure reutilizable para renderizar la tabla de estado
  };
@endphp

@section('content')
@php
  // Resumen global de pendientes / huérfanas a través de core + plugins
  $gPending  = (int) ($coreSummary['pending'] ?? 0);
  $gMissing  = (int) ($coreSummary['missing'] ?? 0);
  $gTotal    = (int) ($coreSummary['total'] ?? 0);
  foreach (($plugins ?? []) as $p) {
    $gPending += (int) ($p['summary']['pending'] ?? 0);
    $gMissing += (int) ($p['summary']['missing'] ?? 0);
    $gTotal   += (int) ($p['summary']['total'] ?? 0);
  }
@endphp
<div class="space-y-4" id="mig-root">

  {{-- Filtros rápidos --}}
  <div class="bg-white rounded-xl border border-slate-200 p-3 flex items-center gap-2 flex-wrap">
    <span class="text-xs font-semibold uppercase text-slate-500 mr-1">Filtro:</span>
    <button type="button" data-mig-filter="all" class="mig-chip is-active inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-medium">
      <i class="ri-list-check"></i> Todas <span class="text-slate-400 font-mono ml-1">{{ $gTotal }}</span>
    </button>
    <button type="button" data-mig-filter="pending" class="mig-chip inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 text-xs font-medium">
      <i class="ri-time-line"></i> Solo pendientes <span class="font-mono ml-1">{{ $gPending }}</span>
    </button>
    <button type="button" data-mig-filter="missing" class="mig-chip inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50 text-xs font-medium {{ $gMissing === 0 ? 'opacity-40 cursor-not-allowed' : '' }}" @if($gMissing===0) disabled @endif>
      <i class="ri-error-warning-line"></i> Solo huérfanas <span class="font-mono ml-1">{{ $gMissing }}</span>
    </button>
    <span class="ml-auto text-xs text-slate-400">
      @if($gPending > 0)<i class="ri-alert-line text-amber-500"></i> Hay migraciones pendientes por ejecutar.@endif
    </span>
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

  {{-- ============ TABLAS DE TRACKING HUÉRFANAS (plugins borrados) ============ --}}
  @if(!empty($trackingHuerfano))
    <div class="bg-white rounded-xl border-2 border-amber-300 overflow-hidden">
      <div class="px-5 py-3 bg-amber-50 border-b border-amber-200 flex items-center gap-2">
        <i class="ri-delete-bin-2-fill text-amber-600 text-xl"></i>
        <div class="flex-1">
          <h3 class="font-semibold text-amber-900">Tablas de tracking de plugins borrados</h3>
          <p class="text-xs text-amber-700">
            {{ count($trackingHuerfano) }} plugin(s) dejaron su tabla <code class="bg-amber-100 px-1 rounded">plugin_&lt;nombre&gt;_migrations</code> en la BD aunque ya no existen en <code class="bg-amber-100 px-1 rounded">/plugins/</code>.
            Podés dropearlas. Esto NO elimina las tablas de datos del plugin (ej: <code>proyectos_X</code>), solo el tracking.
          </p>
        </div>
      </div>
      <div class="divide-y divide-amber-100">
        @foreach($trackingHuerfano as $th)
          <div class="px-5 py-3 flex items-center gap-3 flex-wrap">
            <div class="flex-1 min-w-0">
              <div class="font-mono text-sm text-slate-800">{{ $th['table'] }}</div>
              <div class="text-xs text-slate-500">
                Plugin: <strong>{{ $th['plugin'] }}</strong> · {{ $th['count'] }} registro(s) de tracking
              </div>
            </div>
            <form method="post" action="admin/post_dropear_tracking_huerfano"
                  onsubmit="return confirm('¿DROPEAR la tabla {{ $th['table'] }}?\n\nEsto elimina el tracking del plugin &quot;{{ $th['plugin'] }}&quot; pero NO borra sus tablas de datos.\n\nIrreversible.');">
              @csrf
              <input type="hidden" name="table" value="{{ $th['table'] }}">
              <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium">
                <i class="ri-delete-bin-line"></i> DROP {{ $th['table'] }}
              </button>
            </form>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- ============ CORE ============ --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mig-target-card"
       data-mig-target="core"
       data-has-pending="{{ (int)$coreSummary['pending'] > 0 ? 1 : 0 }}"
       data-has-missing="{{ (int)($coreSummary['missing'] ?? 0) > 0 ? 1 : 0 }}">
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

      <div class="flex items-center gap-2 flex-wrap">
        @if($coreSummary['pending'] > 0)
          <form method="post" action="admin/post_migrate" class="inline">
            @csrf
            <input type="hidden" name="target" value="core">
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg btn-primary text-sm font-semibold">
              <i class="ri-play-line"></i> Ejecutar {{ $coreSummary['pending'] }} pendiente(s)
            </button>
          </form>
        @endif
        @if(!empty($coreSummary['missing']) && $coreSummary['missing'] > 0)
          <form method="post" action="admin/post_limpiar_huerfanas" class="inline"
                onsubmit="return confirm('¿Quitar del tracking las {{ $coreSummary['missing'] }} migración(es) huérfana(s) del core?\n\nEsto NO revierte los cambios de esquema ya aplicados; solo limpia el registro en quetzal_migrations.');">
            @csrf
            <input type="hidden" name="target" value="core">
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
              <i class="ri-eraser-line"></i> Limpiar {{ $coreSummary['missing'] }} huérfana(s)
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
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mig-target-card"
         data-mig-target="{{ $p['name'] }}"
         data-has-pending="{{ (int)$p['summary']['pending'] > 0 ? 1 : 0 }}"
         data-has-missing="{{ (int)($p['summary']['missing'] ?? 0) > 0 ? 1 : 0 }}">
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

        <div class="flex items-center gap-2 flex-wrap">
          @if($p['summary']['pending'] > 0)
            <form method="post" action="admin/post_migrate" class="inline">
              @csrf
              <input type="hidden" name="target" value="{{ $p['name'] }}">
              <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg btn-primary text-sm font-semibold">
                <i class="ri-play-line"></i> Ejecutar {{ $p['summary']['pending'] }} pendiente(s)
              </button>
            </form>
          @endif
          @if(!empty($p['summary']['missing']) && $p['summary']['missing'] > 0)
            <form method="post" action="admin/post_limpiar_huerfanas" class="inline"
                  onsubmit="return confirm('¿Quitar del tracking las {{ $p['summary']['missing'] }} migración(es) huérfana(s) de {{ $p['name'] }}?\n\nEsto NO revierte los cambios de esquema ya aplicados; solo limpia el registro.');">
              @csrf
              <input type="hidden" name="target" value="{{ $p['name'] }}">
              <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
                <i class="ri-eraser-line"></i> Limpiar {{ $p['summary']['missing'] }} huérfana(s)
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

@push('head')
<style>
  .mig-chip.is-active {
    background: var(--q-primary, #0078d4);
    color: #fff;
    border-color: var(--q-primary, #0078d4);
  }
  .mig-chip.is-active .font-mono,
  .mig-chip.is-active .text-slate-400 { color: rgba(255,255,255,.85) !important; }
</style>
@endpush

@push('scripts')
<script>
(function () {
  const chips = document.querySelectorAll('[data-mig-filter]');
  const cards = document.querySelectorAll('.mig-target-card');
  if (!chips.length || !cards.length) return;

  let mode = 'all';

  function apply() {
    cards.forEach(card => {
      const hasPending = card.dataset.hasPending === '1';
      const hasMissing = card.dataset.hasMissing === '1';

      // Mostrar/ocultar card completa según si aplica al filtro
      let showCard = true;
      if (mode === 'pending') showCard = hasPending;
      else if (mode === 'missing') showCard = hasMissing;
      card.style.display = showCard ? '' : 'none';
      if (!showCard) return;

      // Filtrar filas dentro de la tabla
      card.querySelectorAll('tbody tr[data-row-status]').forEach(tr => {
        const st = tr.dataset.rowStatus;
        let showRow = true;
        if (mode === 'pending') showRow = (st === 'pending');
        else if (mode === 'missing') showRow = (st === 'missing');
        tr.style.display = showRow ? '' : 'none';
      });
    });
  }

  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      if (chip.disabled) return;
      mode = chip.dataset.migFilter;
      chips.forEach(c => c.classList.toggle('is-active', c === chip));
      apply();
    });
  });
})();
</script>
@endpush
@endsection

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

    @include('includes.admin.migration_table', ['items' => $coreStatus])
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

      @include('includes.admin.migration_table', ['items' => $p['status']])
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

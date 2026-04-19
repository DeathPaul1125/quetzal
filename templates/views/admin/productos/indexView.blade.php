@extends('includes.admin.layout')

@section('title', 'Productos')
@section('page_title', 'Productos')

@php
  $rows = $productos['rows'] ?? [];
  $f    = $filters ?? ['q' => '', 'sort' => 'id', 'dir' => 'desc'];

  $sortUrl = function(string $col) use ($f) {
    $dir = ($f['sort'] === $col && $f['dir'] === 'asc') ? 'desc' : 'asc';
    return 'admin/productos?' . http_build_query(['q' => $f['q'], 'sort' => $col, 'dir' => $dir]);
  };
  $sortIcon = function(string $col) use ($f) {
    if ($f['sort'] !== $col) return 'ri-expand-up-down-line text-slate-300';
    return $f['dir'] === 'asc' ? 'ri-arrow-up-line text-primary' : 'ri-arrow-down-line text-primary';
  };
@endphp

@section('content')
<div class="space-y-4">

  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <h2 class="font-semibold text-slate-800">Listado</h2>
        <span class="inline-flex items-center justify-center min-w-[1.75rem] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
          {{ $productos['total'] ?? 0 }}
        </span>
      </div>
      <a href="admin/crear_producto" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
        <i class="ri-add-line"></i> Nuevo producto
      </a>
    </div>

    <form method="get" action="admin/productos" class="mt-4 grid grid-cols-1 sm:grid-cols-12 gap-3">
      <div class="sm:col-span-9">
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="ri-search-line"></i></span>
          <input type="text" name="q" value="{{ $f['q'] }}" placeholder="Buscar por nombre, SKU o descripción..."
                 class="w-full pl-10 pr-3 py-2 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
        </div>
      </div>
      <div class="sm:col-span-3 flex gap-2">
        <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 rounded-lg btn-primary text-sm font-medium">
          <i class="ri-filter-2-line"></i> Buscar
        </button>
        @if($f['q'] !== '')
          <a href="admin/productos" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm">
            <i class="ri-close-line"></i>
          </a>
        @endif
        <input type="hidden" name="sort" value="{{ $f['sort'] }}">
        <input type="hidden" name="dir"  value="{{ $f['dir'] }}">
      </div>
    </form>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if(empty($rows))
      <div class="p-12 text-center">
        <i class="ri-archive-line text-5xl text-slate-300 mb-2 block"></i>
        <p class="text-sm text-slate-500">
          @if($f['q'] !== '')
            No hay productos que coincidan.
          @else
            No hay productos registrados. <a href="admin/crear_producto" class="text-primary hover:underline">Crea el primero</a>.
          @endif
        </p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-xs uppercase tracking-wider text-slate-500">
              <th class="text-left px-5 py-3 font-semibold w-16">Imagen</th>
              <th class="text-left px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('nombre') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  Producto <i class="{{ $sortIcon('nombre') }}"></i>
                </a>
              </th>
              <th class="text-left px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('sku') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  SKU <i class="{{ $sortIcon('sku') }}"></i>
                </a>
              </th>
              <th class="text-right px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('precio') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  Precio <i class="{{ $sortIcon('precio') }}"></i>
                </a>
              </th>
              <th class="text-center px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('stock') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  Stock <i class="{{ $sortIcon('stock') }}"></i>
                </a>
              </th>
              <th class="px-5 py-3 font-semibold w-12"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($rows as $p)
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-5 py-2.5">
                  @if(!empty($p['imagen']))
                    <img src="{{ get_uploaded_image($p['imagen']) }}" alt="{{ $p['nombre'] }}"
                         class="w-10 h-10 rounded-lg object-cover bg-slate-100">
                  @else
                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
                      <i class="ri-image-line"></i>
                    </div>
                  @endif
                </td>
                <td class="px-5 py-3">
                  <a href="admin/ver_producto/{{ $p['id'] }}" class="font-medium text-slate-800 hover:text-primary">
                    {{ $p['nombre'] }}
                  </a>
                  <div class="text-xs text-slate-400 font-mono">#{{ $p['id'] }}</div>
                </td>
                <td class="px-5 py-3"><code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $p['sku'] ?? '—' }}</code></td>
                <td class="px-5 py-3 text-right">
                  <div class="font-medium text-slate-800">{{ money($p['precio'] ?? 0) }}</div>
                  @if(!empty($p['precio_comparacion']) && $p['precio_comparacion'] > ($p['precio'] ?? 0))
                    <div class="text-xs text-slate-400 line-through">{{ money($p['precio_comparacion']) }}</div>
                  @endif
                </td>
                <td class="px-5 py-3 text-center">
                  @if(!empty($p['rastrear_stock']))
                    @php $stockLow = ($p['stock'] ?? 0) <= 5; @endphp
                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full text-xs font-medium {{ $stockLow ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                      {{ $p['stock'] ?? 0 }}
                    </span>
                  @else
                    <span class="text-xs text-slate-400">∞</span>
                  @endif
                </td>
                <td class="px-3 py-3 text-right">
                  <div class="hs-dropdown relative inline-flex">
                    <button type="button" class="hs-dropdown-toggle inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                      <i class="ri-more-2-fill"></i>
                    </button>
                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[10rem] bg-white shadow-lg rounded-xl p-1 mt-2 border border-slate-200 z-20">
                      <a href="admin/ver_producto/{{ $p['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-eye-line text-slate-400"></i> Ver detalle
                      </a>
                      <a href="admin/editar_producto/{{ $p['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-edit-line text-slate-400"></i> Editar
                      </a>
                      <div class="border-t border-slate-100 my-1"></div>
                      <a href="{{ build_url('admin/borrar_producto/' . $p['id']) }}"
                         onclick="return confirm('¿Eliminar el producto &quot;{{ $p['nombre'] }}&quot;?')"
                         class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-red-50 text-red-600">
                        <i class="ri-delete-bin-line"></i> Eliminar
                      </a>
                    </div>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
        <div class="text-xs text-slate-500">
          Mostrando
          <span class="font-medium text-slate-700">{{ ($productos['offset'] ?? 0) + 1 }}</span>
          —
          <span class="font-medium text-slate-700">{{ min(($productos['offset'] ?? 0) + count($rows), $productos['total'] ?? 0) }}</span>
          de
          <span class="font-medium text-slate-700">{{ $productos['total'] ?? 0 }}</span>
        </div>
        @if(!empty($productos['pagination']))
          <div>{!! $productos['pagination'] !!}</div>
        @endif
      </div>
    @endif
  </div>
</div>
@endsection

@extends('includes.admin.layout')

@section('title', $producto['nombre'])
@section('page_title', 'Detalle del producto')

@php $stockLow = !empty($producto['rastrear_stock']) && ($producto['stock'] ?? 0) <= 5; @endphp

@section('content')
<div class="space-y-4">

  <div class="flex items-center justify-between flex-wrap gap-3">
    <a href="admin/productos" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
    <div class="flex items-center gap-2">
      <a href="admin/editar_producto/{{ $producto['id'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
        <i class="ri-edit-line"></i> Editar
      </a>
      <a href="{{ build_url('admin/borrar_producto/' . $producto['id']) }}"
         onclick="return confirm('¿Eliminar el producto &quot;{{ $producto['nombre'] }}&quot;?')"
         class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
        <i class="ri-delete-bin-line"></i> Eliminar
      </a>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Imagen --}}
    <div class="lg:col-span-1">
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        @if(!empty($producto['imagen']))
          <img src="{{ get_uploaded_image($producto['imagen']) }}" alt="{{ $producto['nombre'] }}" class="w-full h-64 object-cover">
        @else
          <div class="w-full h-64 bg-slate-50 flex items-center justify-center text-slate-300">
            <i class="ri-image-line text-6xl"></i>
          </div>
        @endif
      </div>
    </div>

    {{-- Info --}}
    <div class="lg:col-span-2 space-y-4">
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-start justify-between gap-3 mb-3 flex-wrap">
          <div>
            <h2 class="text-xl font-bold text-slate-800">{{ $producto['nombre'] }}</h2>
            <code class="text-xs text-slate-500 font-mono">SKU: {{ $producto['sku'] ?? '—' }}</code>
          </div>
          <div class="text-right">
            <div class="text-2xl font-bold text-primary">{{ money($producto['precio'] ?? 0) }}</div>
            @if(!empty($producto['precio_comparacion']) && $producto['precio_comparacion'] > ($producto['precio'] ?? 0))
              <div class="text-sm text-slate-400 line-through">{{ money($producto['precio_comparacion']) }}</div>
            @endif
          </div>
        </div>

        @if(!empty($producto['descripcion']))
          <p class="text-sm text-slate-600 leading-relaxed mt-3">{{ $producto['descripcion'] }}</p>
        @endif
      </div>

      {{-- Stock info --}}
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Inventario</h3>
        <dl class="divide-y divide-slate-100">
          <div class="flex justify-between py-2.5 text-sm">
            <dt class="text-slate-500">Rastrea stock</dt>
            <dd class="font-medium">
              @if(!empty($producto['rastrear_stock']))
                <span class="text-emerald-600">Sí</span>
              @else
                <span class="text-slate-400">No (stock ilimitado)</span>
              @endif
            </dd>
          </div>
          <div class="flex justify-between py-2.5 text-sm">
            <dt class="text-slate-500">Unidades disponibles</dt>
            <dd class="font-medium">
              @if(!empty($producto['rastrear_stock']))
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium {{ $stockLow ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' }}">
                  {{ $producto['stock'] ?? 0 }}
                  @if($stockLow)<i class="ri-alert-line"></i>@endif
                </span>
              @else
                <span class="text-slate-400">∞</span>
              @endif
            </dd>
          </div>
          <div class="flex justify-between py-2.5 text-sm">
            <dt class="text-slate-500">Slug</dt>
            <dd><code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $producto['slug'] }}</code></dd>
          </div>
          <div class="flex justify-between py-2.5 text-sm">
            <dt class="text-slate-500">Creado</dt>
            <dd class="font-medium">{{ !empty($producto['creado']) ? date('d/m/Y H:i', strtotime($producto['creado'])) : '—' }}</dd>
          </div>
          @if(!empty($producto['actualizado']))
            <div class="flex justify-between py-2.5 text-sm">
              <dt class="text-slate-500">Última actualización</dt>
              <dd class="font-medium">{{ date('d/m/Y H:i', strtotime($producto['actualizado'])) }}</dd>
            </div>
          @endif
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection

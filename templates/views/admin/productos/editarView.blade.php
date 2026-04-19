@extends('includes.admin.layout')

@section('title', 'Editar: ' . ($producto['nombre'] ?? ''))
@section('page_title', 'Editar producto')

@section('content')
<div class="max-w-3xl space-y-4">

  <div class="flex items-center justify-between">
    <a href="admin/ver_producto/{{ $producto['id'] }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al detalle
    </a>
    <a href="{{ build_url('admin/borrar_producto/' . $producto['id']) }}"
       onclick="return confirm('¿Eliminar el producto &quot;{{ $producto['nombre'] }}&quot;?')"
       class="text-sm text-red-600 hover:text-red-700 inline-flex items-center gap-1">
      <i class="ri-delete-bin-line"></i> Eliminar
    </a>
  </div>

  <form method="post" action="admin/post_editar_producto" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-6">
    @csrf
    <input type="hidden" name="id" value="{{ $producto['id'] }}">

    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
      @if(!empty($producto['imagen']))
        <img src="{{ get_uploaded_image($producto['imagen']) }}" alt="{{ $producto['nombre'] }}" class="w-16 h-16 rounded-lg object-cover">
      @else
        <div class="w-16 h-16 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">
          <i class="ri-image-line text-2xl"></i>
        </div>
      @endif
      <div>
        <h3 class="text-lg font-semibold text-slate-800">{{ $producto['nombre'] }}</h3>
        <p class="text-sm text-slate-500 mt-0.5">
          ID #{{ $producto['id'] }} · SKU <code>{{ $producto['sku'] }}</code>
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre</label>
        <input type="text" name="nombre" value="{{ $producto['nombre'] }}" required minlength="3" maxlength="150"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">SKU</label>
        <input type="text" name="sku" value="{{ $producto['sku'] }}" maxlength="100"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Cambiar imagen</label>
        <input type="file" name="imagen" accept="image/*"
               class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
        <p class="text-xs text-slate-500 mt-1">Déjalo vacío para mantener la imagen actual.</p>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción</label>
        <textarea name="descripcion" rows="3" maxlength="255"
                  class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">{{ $producto['descripcion'] ?? '' }}</textarea>
      </div>
    </div>

    <div class="border-t border-slate-100 pt-5">
      <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <i class="ri-price-tag-3-line"></i> Precios
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Precio</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">$</span>
            <input type="number" name="precio" value="{{ $producto['precio'] }}" required min="0.01" step="0.01"
                   class="w-full pl-7 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Precio de comparación</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">$</span>
            <input type="number" name="precio_comparacion" value="{{ $producto['precio_comparacion'] ?? 0 }}" min="0" step="0.01"
                   class="w-full pl-7 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          </div>
        </div>
      </div>
    </div>

    <div class="border-t border-slate-100 pt-5">
      <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <i class="ri-stack-line"></i> Inventario
      </h4>
      <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer mb-3">
        <input type="checkbox" name="rastrear_stock" value="1" @if(!empty($producto['rastrear_stock'])) checked @endif
               class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary">
        <div>
          <div class="text-sm font-medium text-slate-800">Rastrear stock</div>
        </div>
      </label>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Unidades disponibles</label>
        <input type="number" name="stock" value="{{ $producto['stock'] ?? 0 }}" min="0"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="admin/ver_producto/{{ $producto['id'] }}" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Guardar cambios
      </button>
    </div>
  </form>
</div>
@endsection

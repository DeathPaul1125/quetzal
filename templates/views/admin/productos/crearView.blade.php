@extends('includes.admin.layout')

@section('title', 'Nuevo producto')
@section('page_title', 'Nuevo producto')

@section('content')
<div class="max-w-3xl space-y-4">

  <div>
    <a href="admin/productos" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
  </div>

  <form method="post" action="admin/post_crear_producto" enctype="multipart/form-data" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-6">
    @csrf

    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
      <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
        <i class="ri-archive-line text-primary text-xl"></i>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-slate-800">Información del producto</h3>
        <p class="text-sm text-slate-500 mt-0.5">Define los datos básicos, precio y stock.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" required minlength="3" maxlength="150"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
               placeholder="Ej. Camisa polo azul">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">SKU</label>
        <input type="text" name="sku" maxlength="100"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
               placeholder="Opcional, se generará si vacío">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Imagen</label>
        <input type="file" name="imagen" accept="image/*"
               class="w-full text-sm text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción</label>
        <textarea name="descripcion" rows="3" maxlength="255"
                  class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                  placeholder="Descripción corta del producto..."></textarea>
      </div>
    </div>

    {{-- Precios --}}
    <div class="border-t border-slate-100 pt-5">
      <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <i class="ri-price-tag-3-line"></i> Precios
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Precio <span class="text-red-500">*</span></label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">$</span>
            <input type="number" name="precio" required min="0.01" step="0.01"
                   class="w-full pl-7 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Precio de comparación</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">$</span>
            <input type="number" name="precio_comparacion" min="0" step="0.01"
                   class="w-full pl-7 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                   placeholder="Opcional, tachado en la ficha">
          </div>
        </div>
      </div>
    </div>

    {{-- Stock --}}
    <div class="border-t border-slate-100 pt-5">
      <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
        <i class="ri-stack-line"></i> Inventario
      </h4>
      <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer mb-3">
        <input type="checkbox" name="rastrear_stock" value="1" id="track-stock" class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary">
        <div>
          <div class="text-sm font-medium text-slate-800">Rastrear stock</div>
          <div class="text-xs text-slate-500">El stock se descontará automáticamente con cada venta.</div>
        </div>
      </label>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Unidades disponibles</label>
        <input type="number" name="stock" min="0" value="0"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="admin/productos" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Crear producto
      </button>
    </div>
  </form>
</div>
@endsection

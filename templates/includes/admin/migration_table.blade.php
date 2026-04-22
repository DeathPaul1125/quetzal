@if(empty($items))
  <div class="p-8 text-center text-sm text-slate-500">
    No hay migraciones en este target.
  </div>
@else
  @php $tgt = $target ?? 'core'; @endphp
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="text-left px-5 py-2.5 font-semibold">Migración</th>
          <th class="text-center px-5 py-2.5 font-semibold">Estado</th>
          <th class="text-center px-5 py-2.5 font-semibold">Batch</th>
          <th class="text-right px-5 py-2.5 font-semibold">Ejecutada</th>
          <th class="text-right px-5 py-2.5 font-semibold">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @foreach($items as $m)
          <tr class="hover:bg-slate-50/60">
            <td class="px-5 py-2.5 font-mono text-xs text-slate-700 break-all">
              {{ $m['name'] }}
            </td>
            <td class="px-5 py-2.5 text-center">
              @if($m['status'] === 'ran')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium">
                  <i class="ri-check-line"></i> Ejecutada
                </span>
              @elseif($m['status'] === 'pending')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
                  <i class="ri-time-line"></i> Pendiente
                </span>
              @elseif($m['status'] === 'missing')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-medium" title="Registrada como ejecutada pero el archivo fue borrado del disco">
                  <i class="ri-error-warning-line"></i> Huérfana
                </span>
              @endif
            </td>
            <td class="px-5 py-2.5 text-center text-xs text-slate-500">
              {{ $m['batch'] !== null ? '#' . $m['batch'] : '—' }}
            </td>
            <td class="px-5 py-2.5 text-right text-xs text-slate-500">
              {{ $m['executed_at'] ? date('Y-m-d H:i', strtotime($m['executed_at'])) : '—' }}
            </td>
            <td class="px-5 py-2.5 text-right">
              @if($m['status'] === 'missing')
                {{-- Huérfana: solo quitar del tracking (no hay archivo que borrar) --}}
                <form method="post" action="admin/post_borrar_migracion" class="inline"
                      onsubmit="return confirm('¿Quitar la migración huérfana &quot;{{ $m['name'] }}&quot; del tracking?\n\nEsto NO revierte los cambios de esquema que pudo haber hecho; solo limpia el registro.');">
                  @csrf
                  <input type="hidden" name="path" value="{{ $m['path'] ?? '' }}">
                  <input type="hidden" name="target" value="{{ $tgt }}">
                  <input type="hidden" name="force" value="1">
                  <input type="hidden" name="missing" value="1">
                  <button type="submit"
                          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium"
                          title="Quitar del tracking">
                    <i class="ri-eraser-line"></i> Quitar tracking
                  </button>
                </form>
              @elseif(!empty($m['path']))
                @php $ran = $m['status'] === 'ran'; @endphp
                <form method="post" action="admin/post_borrar_migracion" class="inline"
                      onsubmit="return confirm('¿Eliminar la migración &quot;{{ $m['name'] }}&quot;?{{ $ran ? '\n\nYa fue ejecutada — también se quitará del tracking. Los cambios de esquema que hizo NO se revierten.' : '' }}');">
                  @csrf
                  <input type="hidden" name="path"   value="{{ $m['path'] }}">
                  <input type="hidden" name="target" value="{{ $tgt }}">
                  @if($ran)<input type="hidden" name="force" value="1">@endif
                  <button type="submit"
                          class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium"
                          title="Eliminar archivo{{ $ran ? ' + tracking' : '' }}">
                    <i class="ri-delete-bin-line"></i> Eliminar
                  </button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

@if(empty($items))
  <div class="p-8 text-center text-sm text-slate-500">
    No hay migraciones en este target.
  </div>
@else
  @php $tgt = $target ?? 'core'; $tid = 'dt-' . md5($tgt); @endphp
  <div class="overflow-x-auto">
    <div class="q-dt-wrap" data-title="Migraciones · {{ $tgt }}" data-icon="ri-database-2-line" data-unit="migraciones" data-per-page="25">
      <table class="q-data-table">
        <thead>
          <tr>
            <th>Migración</th>
            <th data-filter="Estado">Estado</th>
            <th data-type="number">Batch</th>
            <th data-type="date">Ejecutada</th>
            <th data-sortable="false" style="width:1%;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $m)
            <tr>
              <td><span class="font-mono text-xs text-slate-700 break-all">{{ $m['name'] }}</span></td>
              <td>
                @if($m['status'] === 'ran')
                  <span class="q-chip q-chip-green"><i class="ri-check-line"></i> Ejecutada</span>
                @elseif($m['status'] === 'pending')
                  <span class="q-chip q-chip-amber"><i class="ri-time-line"></i> Pendiente</span>
                @elseif($m['status'] === 'missing')
                  <span class="q-chip q-chip-red" title="Registrada pero el archivo fue borrado"><i class="ri-error-warning-line"></i> Huérfana</span>
                @endif
              </td>
              <td class="text-center"><span class="text-xs text-slate-500 font-mono">{{ $m['batch'] !== null ? '#' . $m['batch'] : '—' }}</span></td>
              <td><span class="text-xs text-slate-500">{{ $m['executed_at'] ? date('Y-m-d H:i', strtotime($m['executed_at'])) : '—' }}</span></td>
              <td>
                @if($m['status'] === 'missing')
                  <form method="post" action="admin/post_borrar_migracion" class="inline"
                        onsubmit="return confirm('¿Quitar la migración huérfana &quot;{{ $m['name'] }}&quot; del tracking?\n\nEsto NO revierte los cambios de esquema que pudo haber hecho; solo limpia el registro.');">
                    @csrf
                    <input type="hidden" name="path" value="{{ $m['path'] ?? '' }}">
                    <input type="hidden" name="target" value="{{ $tgt }}">
                    <input type="hidden" name="force" value="1">
                    <input type="hidden" name="missing" value="1">
                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium" title="Quitar del tracking">
                      <i class="ri-eraser-line"></i> Quitar
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
                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium" title="Eliminar archivo{{ $ran ? ' + tracking' : '' }}">
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
  </div>
@endif

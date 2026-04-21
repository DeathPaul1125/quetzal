<?php
/**
 * Vista de error de base de datos (reusa el módulo general con contexto DB).
 *
 * Enriquece $d con información de conexión para ayudar a diagnosticar:
 *   - host, nombre de BD, usuario (no password)
 *   - SQLSTATE parseado si está en el mensaje
 *   - sugerencia de causa probable
 */
$sqlstate = '';
$hint     = '';

$errorMsg = (string)($d->error ?? '');
if (preg_match('/SQLSTATE\[([A-Z0-9]+)\]/', $errorMsg, $m)) {
  $sqlstate = $m[1];
  // Hints típicos según SQLSTATE
  $hints = [
    '42S02' => 'La tabla referenciada no existe. Revisa si corriste las migraciones pendientes en admin/migraciones.',
    '42S22' => 'Una columna referenciada no existe. Puede ser un alias mal escrito o una migración faltante.',
    '23000' => 'Violación de constraint (FK, UNIQUE o NOT NULL). Revisa el valor que intentas insertar/actualizar.',
    '42000' => 'Error de sintaxis SQL o tabla/columna reservada.',
    'HY000' => 'Error genérico. Revisa el mensaje completo abajo.',
    '08004' => 'El servidor MySQL rechazó la conexión. Verifica host, puerto y credenciales.',
    '28000' => 'Credenciales inválidas. Revisa DB_USER y DB_PASS en .env.',
    '2002'  => 'No se puede conectar al servidor MySQL. ¿Está corriendo?',
  ];
  $hint = $hints[$sqlstate] ?? '';
}

// Inyectar la info DB como "error" extendido en $d y delegar al módulo general
$d->title = $d->title ?? 'Error en la base de datos';
$d->kind  = $d->kind  ?? 'PDOException';

// Delegar al módulo general, que ya hace todo el render con debug info.
// Primero agregamos un banner contextual antes del render.
$debug    = defined('APP_DEBUG') && APP_DEBUG;
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Quetzal';
$baseUrl  = function_exists('get_base_url') ? get_base_url() : '/';
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Error BD — <?= $h($siteName) ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css">
<style>
  body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
  .mono { font-family: 'Fira Code', ui-monospace, 'SF Mono', Consolas, monospace; }
</style>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

  <!-- Banner DB -->
  <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
    <div class="flex items-start gap-4">
      <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
        <i class="ri-database-2-line text-3xl"></i>
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded-full font-semibold">
            Base de datos
          </span>
          <?php if ($sqlstate): ?>
            <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full mono">SQLSTATE <?= $h($sqlstate) ?></span>
          <?php endif; ?>
          <span class="text-xs opacity-80">· <?= $h(date('Y-m-d H:i:s')) ?></span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold mt-2 break-words">Error en la base de datos</h1>
        <p class="mt-2 text-white/90 text-sm sm:text-base break-words"><?= $h($errorMsg) ?></p>
      </div>
    </div>
    <div class="mt-5 flex items-center gap-2 flex-wrap">
      <a href="<?= $h($baseUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition">
        <i class="ri-home-line"></i> Inicio
      </a>
      <a href="<?= $h($baseUrl) ?>admin/migraciones" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition">
        <i class="ri-database-2-line"></i> Migraciones
      </a>
      <button onclick="history.back()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition">
        <i class="ri-arrow-left-line"></i> Volver
      </button>
      <button onclick="location.reload()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition">
        <i class="ri-refresh-line"></i> Reintentar
      </button>
    </div>
  </div>

  <!-- Hint / causa probable -->
  <?php if ($hint): ?>
    <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 mb-4 flex items-start gap-3">
      <i class="ri-lightbulb-line text-sky-600 text-xl flex-shrink-0 mt-0.5"></i>
      <div class="min-w-0">
        <div class="text-xs uppercase tracking-wider text-sky-700 font-semibold mb-1">
          Causa probable (SQLSTATE <?= $h($sqlstate) ?>)
        </div>
        <p class="text-sm text-sky-900"><?= $h($hint) ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Info de conexión DB (solo debug) -->
  <?php if ($debug): ?>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
      <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2 text-sm font-medium text-slate-700">
        <i class="ri-plug-line text-slate-500"></i>
        <span>Conexión actual</span>
      </div>
      <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
        <?php if (defined('DB_HOST')): ?>
          <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Host</div><div class="mono text-slate-700"><?= $h(DB_HOST) ?></div></div>
        <?php endif; ?>
        <?php if (defined('DB_NAME')): ?>
          <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Base de datos</div><div class="mono text-slate-700"><?= $h(DB_NAME) ?></div></div>
        <?php endif; ?>
        <?php if (defined('DB_USER')): ?>
          <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Usuario</div><div class="mono text-slate-700"><?= $h(DB_USER) ?></div></div>
        <?php endif; ?>
        <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Password</div><div class="mono text-slate-400">[REDACTED]</div></div>
      </div>
    </div>

    <!-- Ubicación del error + snippet (si tenemos info de excepción) -->
    <?php
      $file    = (string)($d->file ?? '');
      $line    = (int)   ($d->line ?? 0);
      $trace   = is_array($d->trace ?? null) ? $d->trace : [];
      $snippet = [];
      if ($file !== '' && is_file($file) && is_readable($file)) {
        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if ($lines !== false) {
          $start = max(0, $line - 6);
          $end   = min(count($lines) - 1, $line + 4);
          for ($i = $start; $i <= $end; $i++) $snippet[$i + 1] = $lines[$i];
        }
      }
    ?>
    <?php if ($file): ?>
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
          <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <i class="ri-map-pin-line text-amber-500"></i>
            <span>Ubicación del error</span>
          </div>
          <span class="mono text-xs text-slate-500 break-all">
            <?= $h($file) ?><span class="text-red-600 font-semibold">:<?= $h($line) ?></span>
          </span>
        </div>
        <?php if (!empty($snippet)): ?>
          <pre style="background:#0f172a;color:#e2e8f0;margin:0;overflow-x:auto;" class="text-xs mono"><?php foreach ($snippet as $n => $src): $isHi = ($n === $line); ?><span style="display:block;padding:2px 0;<?= $isHi ? 'background:rgba(239,68,68,.15);border-left:3px solid #ef4444;' : '' ?>"><span style="display:inline-block;width:3.25rem;text-align:right;padding:0 .75rem;color:<?= $isHi ? '#fca5a5;font-weight:600' : '#64748b' ?>;border-right:1px solid #1e293b;"><?= $n ?></span> <?= $h($src) ?></span><?php endforeach; ?></pre>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($trace)): ?>
      <details class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
        <summary class="px-5 py-3 border-b border-slate-100 flex items-center justify-between cursor-pointer" style="list-style:none;">
          <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <i class="ri-stack-line text-slate-500"></i>
            <span>Stack trace</span>
            <span class="text-xs text-slate-400">(<?= count($trace) ?> frames)</span>
          </div>
          <i class="ri-arrow-down-s-line text-slate-400"></i>
        </summary>
        <div class="divide-y divide-slate-100 text-sm">
          <?php foreach ($trace as $i => $f): ?>
            <?php
              $tFile  = $f['file'] ?? '[internal]';
              $tLine  = $f['line'] ?? 0;
              $tClass = $f['class'] ?? '';
              $tType  = $f['type']  ?? '';
              $tFunc  = $f['function'] ?? '?';
            ?>
            <div class="px-5 py-3">
              <div class="flex items-start gap-3">
                <span class="mono text-xs text-slate-400 mt-0.5 flex-shrink-0 w-8">#<?= $i ?></span>
                <div class="min-w-0 flex-1">
                  <div class="mono text-xs font-semibold text-slate-800 break-all">
                    <?= $h($tClass . $tType . $tFunc) ?>()
                  </div>
                  <div class="mono text-xs text-slate-500 mt-0.5 break-all">
                    <?= $h($tFile) ?><?php if ($tLine): ?><span class="text-slate-400">:<?= $h($tLine) ?></span><?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </details>
    <?php endif; ?>

  <?php else: ?>

    <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 text-center">
      <i class="ri-customer-service-2-line text-5xl text-slate-300 block mb-3"></i>
      <p class="text-slate-600 max-w-md mx-auto">
        No se pudo conectar a la base de datos. El equipo técnico fue notificado.
      </p>
      <p class="text-xs text-slate-400 mt-3">
        Para ver detalles, activa <code class="bg-slate-100 px-1 rounded mono">APP_DEBUG=true</code> en <code class="mono">.env</code>.
      </p>
    </div>

  <?php endif; ?>

  <div class="text-center mt-6 text-xs text-slate-400">
    <?= $h($siteName) ?> · <?= $h(date('Y-m-d H:i:s')) ?>
  </div>
</div>

</body>
</html>

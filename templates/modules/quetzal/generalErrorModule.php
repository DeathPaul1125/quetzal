<?php
/**
 * Vista de error general (autocontenida, sin dependencias de layout).
 *
 * $d lleva:
 *   - title:   'Hubo un error'
 *   - kind:    clase de la excepción (Exception, PDOException, etc.)
 *   - error:   mensaje
 *   - file, line, code
 *   - trace:   array de frames (getTrace)
 *   - traceAs: string legible
 *   - previous: null | { kind, message, file, line }
 *
 * En modo APP_DEBUG muestra stack trace + contexto. En producción solo el mensaje.
 */
$debug    = defined('APP_DEBUG') && APP_DEBUG;
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Quetzal';
$baseUrl  = function_exists('get_base_url') ? get_base_url() : '/';
$kind     = $d->kind ?? 'Error';
$msg      = (string)($d->error ?? 'Error desconocido');
$file     = (string)($d->file  ?? '');
$line     = (int)   ($d->line  ?? 0);
$code     = $d->code ?? 0;
$trace    = is_array($d->trace ?? null) ? $d->trace : [];
$previous = $d->previous ?? null;

$uri     = $_SERVER['REQUEST_URI']     ?? '';
$method  = $_SERVER['REQUEST_METHOD']  ?? 'GET';
$referer = $_SERVER['HTTP_REFERER']    ?? '';
$ua      = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Snippet del archivo con la línea del error resaltada (solo en debug)
$snippet = [];
if ($debug && $file !== '' && is_file($file) && is_readable($file)) {
  $lines = @file($file, FILE_IGNORE_NEW_LINES);
  if ($lines !== false) {
    $start = max(0, $line - 6);
    $end   = min(count($lines) - 1, $line + 4);
    for ($i = $start; $i <= $end; $i++) {
      $snippet[$i + 1] = $lines[$i];
    }
  }
}

$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $h($d->title ?? 'Error') ?> — <?= $h($siteName) ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css">
<style>
  body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
  .mono { font-family: 'Fira Code', ui-monospace, 'SF Mono', Consolas, monospace; }
  pre.snippet { background: #0f172a; color: #e2e8f0; border-radius: .5rem; padding: 0; overflow-x: auto; margin: 0; }
  pre.snippet .ln { display: inline-block; width: 3.25rem; text-align: right; padding: 0 .75rem; color: #64748b; user-select: none; border-right: 1px solid #1e293b; }
  pre.snippet .row { display: block; padding: 2px 0; }
  pre.snippet .row.hi { background: rgba(239,68,68,.15); border-left: 3px solid #ef4444; }
  pre.snippet .row.hi .ln { color: #fca5a5; font-weight: 600; }
  pre.snippet code { white-space: pre; font-family: inherit; }
  details > summary { list-style: none; cursor: pointer; }
  details > summary::-webkit-details-marker { display: none; }
</style>
</head>
<body class="bg-slate-50 min-h-screen">

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">

  <!-- Banner principal -->
  <div class="bg-gradient-to-r from-red-500 to-rose-600 rounded-2xl shadow-lg p-6 sm:p-8 text-white mb-6">
    <div class="flex items-start gap-4">
      <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
        <i class="ri-error-warning-line text-3xl"></i>
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs uppercase tracking-wider bg-white/20 px-2 py-0.5 rounded-full font-semibold">
            <?= $h($kind) ?>
          </span>
          <?php if ($code): ?>
            <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full mono">código <?= $h($code) ?></span>
          <?php endif; ?>
          <span class="text-xs opacity-80">· <?= $h(date('Y-m-d H:i:s')) ?></span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold mt-2 break-words"><?= $h($d->title ?? 'Hubo un error') ?></h1>
        <?php if ($debug): ?>
          <p class="mt-2 text-white/90 text-sm sm:text-base break-words"><?= $h($msg) ?></p>
        <?php else: ?>
          <p class="mt-2 text-white/90 text-sm sm:text-base">Ocurrió un problema inesperado. Intenta de nuevo en unos momentos.</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="mt-5 flex items-center gap-2 flex-wrap">
      <a href="<?= $h($baseUrl) ?>" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition">
        <i class="ri-home-line"></i> Ir al inicio
      </a>
      <button onclick="history.back()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition">
        <i class="ri-arrow-left-line"></i> Volver
      </button>
      <button onclick="location.reload()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition">
        <i class="ri-refresh-line"></i> Reintentar
      </button>
      <?php if ($debug): ?>
        <button onclick="copyReport()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white/15 hover:bg-white/25 text-sm font-semibold transition ml-auto">
          <i class="ri-clipboard-line"></i> Copiar reporte
        </button>
      <?php endif; ?>
    </div>
  </div>

<?php if ($debug): ?>

  <!-- Ubicación del error con snippet -->
  <?php if ($file): ?>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
      <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
          <i class="ri-map-pin-line text-red-500"></i>
          <span>Ubicación del error</span>
        </div>
        <span class="mono text-xs text-slate-500 break-all">
          <?= $h($file) ?><span class="text-red-600 font-semibold">:<?= $h($line) ?></span>
        </span>
      </div>
      <?php if (!empty($snippet)): ?>
        <pre class="snippet text-xs"><code><?php foreach ($snippet as $n => $src): $isHi = ($n === $line); ?><span class="row<?= $isHi ? ' hi' : '' ?>"><span class="ln"><?= $n ?></span> <?= $h($src) ?></span><?php endforeach; ?></code></pre>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Excepción anterior -->
  <?php if ($previous): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
      <div class="flex items-start gap-2">
        <i class="ri-arrow-go-back-line text-amber-600 text-lg"></i>
        <div class="min-w-0 flex-1">
          <div class="text-xs uppercase tracking-wider text-amber-800 font-semibold">
            Excepción anterior (causa raíz)
          </div>
          <div class="text-sm text-amber-900 mt-1 break-words"><?= $h($previous['kind'] ?? 'Error') ?>: <?= $h($previous['message'] ?? '') ?></div>
          <div class="mono text-xs text-amber-700 mt-1 break-all"><?= $h($previous['file'] ?? '') ?>:<?= $h($previous['line'] ?? 0) ?></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Stack trace -->
  <?php if (!empty($trace)): ?>
    <details class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4" open>
      <summary class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
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
            $args   = [];
            foreach (($f['args'] ?? []) as $a) {
              if (is_string($a))      $args[] = '"' . (mb_strlen($a) > 40 ? mb_substr($a, 0, 40) . '…' : $a) . '"';
              elseif (is_numeric($a)) $args[] = (string)$a;
              elseif (is_bool($a))    $args[] = $a ? 'true' : 'false';
              elseif (is_null($a))    $args[] = 'null';
              elseif (is_array($a))   $args[] = 'array(' . count($a) . ')';
              elseif (is_object($a))  $args[] = get_class($a);
              else                    $args[] = gettype($a);
            }
          ?>
          <div class="px-5 py-3">
            <div class="flex items-start gap-3">
              <span class="mono text-xs text-slate-400 mt-0.5 flex-shrink-0 w-8">#<?= $i ?></span>
              <div class="min-w-0 flex-1">
                <div class="mono text-xs font-semibold text-slate-800 break-all">
                  <?= $h($tClass . $tType . $tFunc) ?>(<span class="text-slate-500 font-normal"><?= $h(implode(', ', $args)) ?></span>)
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

  <!-- Contexto del request -->
  <details class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-4">
    <summary class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
      <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
        <i class="ri-global-line text-slate-500"></i>
        <span>Contexto del request</span>
      </div>
      <i class="ri-arrow-down-s-line text-slate-400"></i>
    </summary>
    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
      <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Método</div><div class="mono text-slate-700"><?= $h($method) ?></div></div>
      <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">URI</div><div class="mono text-slate-700 break-all"><?= $h($uri) ?></div></div>
      <?php if ($referer): ?>
        <div class="sm:col-span-2"><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Referer</div><div class="mono text-slate-700 break-all"><?= $h($referer) ?></div></div>
      <?php endif; ?>
      <div class="sm:col-span-2"><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">User Agent</div><div class="mono text-slate-500 break-all"><?= $h($ua) ?></div></div>
      <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">PHP</div><div class="mono text-slate-700"><?= $h(PHP_VERSION) ?></div></div>
      <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Memoria</div><div class="mono text-slate-700"><?= number_format(memory_get_peak_usage(true) / 1024 / 1024, 2) ?> MB</div></div>
      <?php if (defined('QUETZAL_VERSION')): ?>
        <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Quetzal</div><div class="mono text-slate-700"><?= $h(QUETZAL_VERSION) ?></div></div>
      <?php endif; ?>
      <?php if (defined('CONTROLLER')): ?>
        <div><div class="uppercase tracking-wider text-slate-400 font-semibold mb-1">Controller / método</div><div class="mono text-slate-700"><?= $h(CONTROLLER) ?> / <?= $h(defined('METHOD') ? METHOD : '?') ?></div></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($_GET) || !empty($_POST)): ?>
      <div class="border-t border-slate-100 p-5">
        <?php if (!empty($_GET)): ?>
          <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1.5">$_GET</div>
          <pre class="mono text-xs bg-slate-50 p-3 rounded-lg overflow-x-auto mb-3"><?= $h(print_r($_GET, true)) ?></pre>
        <?php endif; ?>
        <?php if (!empty($_POST)): ?>
          <?php
            $safePost = $_POST;
            foreach (['password','passwd','pwd','secret','token','csrf','_t'] as $sens) {
              if (isset($safePost[$sens])) $safePost[$sens] = '[REDACTED]';
            }
          ?>
          <div class="text-xs uppercase tracking-wider text-slate-400 font-semibold mb-1.5">$_POST</div>
          <pre class="mono text-xs bg-slate-50 p-3 rounded-lg overflow-x-auto"><?= $h(print_r($safePost, true)) ?></pre>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </details>

<?php else: ?>

  <!-- Modo producción: mensaje genérico -->
  <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 text-center">
    <i class="ri-customer-service-2-line text-5xl text-slate-300 block mb-3"></i>
    <p class="text-slate-600 max-w-md mx-auto">
      Ha ocurrido un error inesperado. El equipo técnico fue notificado.
      Si el problema persiste, por favor contacta a soporte.
    </p>
    <p class="text-xs text-slate-400 mt-3">
      Para ver detalles técnicos, activa <code class="bg-slate-100 px-1 rounded mono">APP_DEBUG=true</code> en <code class="mono">.env</code>.
    </p>
  </div>

<?php endif; ?>

  <?php if (class_exists('Flasher') && method_exists('Flasher', 'flash')): ?>
    <div class="mt-4"><?= Flasher::flash() ?></div>
  <?php endif; ?>

  <div class="text-center mt-6 text-xs text-slate-400">
    <?= $h($siteName) ?> · <?= $h(date('Y-m-d H:i:s')) ?>
  </div>
</div>

<?php if ($debug): ?>
<script>
function copyReport() {
  const report = <?= json_encode([
    'kind'    => $kind,
    'message' => $msg,
    'file'    => $file,
    'line'    => $line,
    'code'    => $code,
    'uri'     => $uri,
    'method'  => $method,
    'time'    => date('c'),
    'php'     => PHP_VERSION,
    'trace'   => $d->traceAs ?? '',
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  const text =
    '[' + report.kind + '] ' + report.message + '\n' +
    'at ' + report.file + ':' + report.line + '\n' +
    report.method + ' ' + report.uri + '\n' +
    'time: ' + report.time + '\n' +
    'php: ' + report.php + '\n\n' +
    report.trace;
  navigator.clipboard.writeText(text).then(
    () => alert('Reporte copiado al portapapeles.'),
    () => { const ta = document.createElement('textarea'); ta.value = text; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); alert('Reporte copiado.'); }
  );
}
</script>
<?php endif; ?>

</body>
</html>

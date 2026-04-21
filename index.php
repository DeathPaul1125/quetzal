<?php

/**
 * Quetzal — punto de entrada
 *
 * Framework PHP ligero y flexible.
 */

$envMissing    = !is_file(__DIR__ . '/app/config/.env');
$vendorMissing = !is_file(__DIR__ . '/app/vendor/autoload.php');
$installerOk   = is_file(__DIR__ . '/install.php');

// Instalación incompleta (.env o vendor faltan)
if ($envMissing || $vendorMissing) {

    // El wizard está disponible → redirigir
    if ($installerOk) {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        header('Location: ' . ($scriptDir === '' ? '' : $scriptDir) . '/install');
        exit;
    }

    // Sin wizard: mostrar página estática con instrucciones claras
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Quetzal — Instalación requerida</title>
      <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-slate-50 to-white text-slate-800 antialiased">
      <div class="max-w-2xl mx-auto px-4 py-16">
        <div class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-200 p-8 sm:p-10">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0 text-2xl">⚙️</div>
            <div class="flex-1">
              <h1 class="text-2xl font-bold text-slate-800">Instalación requerida</h1>
              <p class="text-sm text-slate-500 mt-1">Quetzal no puede arrancar porque faltan archivos clave.</p>
            </div>
          </div>

          <div class="mt-6 space-y-3 text-sm">
            <?php if ($vendorMissing): ?>
              <div class="flex items-start gap-2 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800">
                <span class="font-bold">✗</span>
                <div>Falta <code class="bg-red-100 px-1 rounded">app/vendor/autoload.php</code> — no se han instalado las dependencias de Composer.</div>
              </div>
            <?php endif; ?>
            <?php if ($envMissing): ?>
              <div class="flex items-start gap-2 p-3 rounded-lg bg-red-50 border border-red-200 text-red-800">
                <span class="font-bold">✗</span>
                <div>Falta <code class="bg-red-100 px-1 rounded">app/config/.env</code> — no se ha configurado el proyecto.</div>
              </div>
            <?php endif; ?>
            <div class="flex items-start gap-2 p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900">
              <span class="font-bold">⚠</span>
              <div>Falta <code class="bg-amber-100 px-1 rounded">install.php</code> — por eso no hay wizard automático.</div>
            </div>
          </div>

          <div class="mt-6 pt-6 border-t border-slate-100">
            <h2 class="font-semibold text-slate-800 mb-3">Cómo arreglar (3 pasos)</h2>
            <ol class="space-y-4 text-sm">
              <li class="flex gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                <div class="flex-1">
                  <div class="font-medium text-slate-800 mb-1">Restaura <code>install.php</code> desde git</div>
                  <pre class="bg-slate-900 text-slate-100 rounded-md p-3 text-xs overflow-x-auto"><code>git checkout HEAD -- install.php</code></pre>
                </div>
              </li>
              <li class="flex gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                <div class="flex-1">
                  <div class="font-medium text-slate-800 mb-1">Refresca esta página — el wizard te redirigirá automáticamente</div>
                  <p class="text-xs text-slate-500">O ve directo a <code class="bg-slate-100 px-1 rounded">/install</code></p>
                </div>
              </li>
              <li class="flex gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-200 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                <div class="flex-1">
                  <div class="font-medium text-slate-800 mb-1">Sigue los 5 pasos del wizard</div>
                  <p class="text-xs text-slate-500">Verificar requisitos → instalar dependencias → configurar BD → crear admin → listo.</p>
                </div>
              </li>
            </ol>
          </div>

          <details class="mt-6">
            <summary class="cursor-pointer text-sm text-slate-500 hover:text-slate-700">Alternativa: instalación manual desde terminal</summary>
            <pre class="mt-3 bg-slate-900 text-slate-100 rounded-md p-3 text-xs overflow-x-auto"><code>cd app
composer install
cp config/.env.example config/.env
# Luego edita config/.env con tus credenciales MySQL</code></pre>
          </details>
        </div>
        <p class="text-center text-xs text-slate-400 mt-6">Quetzal Framework</p>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Requerir la clase principal del framework
require_once 'app/classes/Quetzal.php';

// Ejecutar Quetzal
Quetzal::fly();

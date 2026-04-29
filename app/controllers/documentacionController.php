<?php

/**
 * Hub central de documentación. Lista todos los plugins habilitados con
 * tabs y muestra la documentación de cada uno. Los plugins pueden contribuir
 * documentación de dos formas:
 *
 *   1. Archivo en la carpeta del plugin (auto-discovery):
 *      - plugins/<Plugin>/docs/manual.md
 *      - plugins/<Plugin>/docs/README.md
 *      - plugins/<Plugin>/docs/manual.html
 *      - plugins/<Plugin>/README.md
 *
 *   2. Hook 'plugin_documentation' que retorna un array de items:
 *      [
 *        ['plugin' => 'NombrePlugin', 'title' => 'Manual', 'content_html' => '<p>...</p>'],
 *        ['plugin' => 'NombrePlugin', 'title' => 'API ref', 'url' => 'admin/x'],
 *      ]
 *
 * Plugins NO habilitados no aparecen.
 */
class documentacionController extends Controller implements ControllerInterface
{
  function __construct()
  {
    if (!Auth::validate()) { Flasher::new('Iniciá sesión.', 'danger'); Redirect::to('login'); }
    parent::__construct();
  }

  function index()
  {
    $plugins = $this->_collectDocs();

    $active = sanitize_input((string)($_GET['p'] ?? ''));
    if ($active === '' || !isset($plugins[$active])) {
      $active = array_key_first($plugins) ?: '';
    }

    $this->setTitle('Documentación');
    $this->addToData('plugins', $plugins);
    $this->addToData('active',  $active);
    $this->setView('index');
    $this->render();
  }

  /** @return array<string, array> */
  private function _collectDocs(): array
  {
    if (!class_exists('QuetzalPluginManager')) return [];
    $enabled = QuetzalPluginManager::getInstance()->getEnabled();

    // Items aportados por hook
    $byPlugin = [];
    if (class_exists('QuetzalHookManager')) {
      foreach (QuetzalHookManager::getHookData('plugin_documentation') as $list) {
        if (!is_array($list)) continue;
        foreach ($list as $item) {
          if (!is_array($item) || empty($item['plugin'])) continue;
          $byPlugin[$item['plugin']][] = $item;
        }
      }
    }

    $out = [];
    foreach ($enabled as $p) {
      $name    = $p['name'];
      $sources = $byPlugin[$name] ?? [];

      // Auto-discovery de archivos en la carpeta del plugin
      $base = $p['path'] ?? (defined('PLUGINS_PATH') ? PLUGINS_PATH . $name . DIRECTORY_SEPARATOR : '');
      foreach ([
        'docs/manual.md', 'docs/README.md', 'docs/manual.html', 'docs/index.html',
        'README.md', 'MANUAL.md',
      ] as $rel) {
        $abs = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (is_file($abs)) {
          $raw = (string) @file_get_contents($abs);
          if ($raw === '') continue;
          $sources[] = [
            'plugin'       => $name,
            'title'        => basename($rel),
            'content_html' => $this->_renderDoc($abs, $raw),
            'source_path'  => 'plugins/' . $name . '/' . $rel,
          ];
        }
      }

      // Manual interno custom (controller dedicado en el plugin)
      $internalUrl = $this->_internalManualUrl($name);
      if ($internalUrl !== null) {
        array_unshift($sources, [
          'plugin' => $name,
          'title'  => 'Manual interno (página completa)',
          'url'    => $internalUrl,
        ]);
      }

      $out[$name] = [
        'name'        => $name,
        'version'     => $p['version'] ?? '?',
        'description' => $p['description'] ?? '',
        'author'      => $p['author'] ?? null,
        'sources'     => $sources,
      ];
    }

    ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
    return $out;
  }

  private function _renderDoc(string $abs, string $raw): string
  {
    $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
    if ($ext === 'html' || $ext === 'htm') return $raw;
    return $this->_markdownToHtml($raw);
  }

  /**
   * Markdown → HTML mínimo. Cubre encabezados, listas, código, links, énfasis.
   */
  private function _markdownToHtml(string $md): string
  {
    $md = str_replace(["\r\n", "\r"], "\n", $md);

    $md = preg_replace_callback('/```([a-z0-9_+-]*)\n(.*?)```/s', function ($m) {
      return '<pre class="bg-slate-900 text-slate-100 rounded p-3 overflow-auto text-xs font-mono my-3"><code>' . htmlspecialchars($m[2], ENT_QUOTES) . '</code></pre>';
    }, $md);

    $md = preg_replace_callback('/`([^`]+)`/', function ($m) {
      return '<code class="bg-slate-100 px-1 py-0.5 rounded text-xs font-mono text-slate-700">' . htmlspecialchars($m[1], ENT_QUOTES) . '</code>';
    }, $md);

    $md = preg_replace('/^######\s+(.+)$/m', '<h6 class="font-semibold text-sm mt-3 mb-1">$1</h6>', $md);
    $md = preg_replace('/^#####\s+(.+)$/m',  '<h5 class="font-semibold text-base mt-3 mb-1">$1</h5>', $md);
    $md = preg_replace('/^####\s+(.+)$/m',   '<h4 class="font-bold text-base mt-4 mb-2">$1</h4>', $md);
    $md = preg_replace('/^###\s+(.+)$/m',    '<h3 class="font-bold text-lg mt-5 mb-2 text-slate-800">$1</h3>', $md);
    $md = preg_replace('/^##\s+(.+)$/m',     '<h2 class="font-bold text-xl mt-6 mb-3 text-slate-800 border-b border-slate-100 pb-1">$1</h2>', $md);
    $md = preg_replace('/^#\s+(.+)$/m',      '<h1 class="font-extrabold text-2xl mt-2 mb-4 text-slate-900">$1</h1>', $md);

    $md = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $md);
    $md = preg_replace('/(?<!\w)_([^_]+)_(?!\w)/', '<em>$1</em>', $md);

    $md = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function ($m) {
      $url = htmlspecialchars($m[2], ENT_QUOTES);
      $txt = htmlspecialchars($m[1], ENT_QUOTES);
      return '<a href="' . $url . '" class="text-primary hover:underline">' . $txt . '</a>';
    }, $md);

    $lines = explode("\n", $md);
    $out   = [];
    $inUl  = false;
    $inOl  = false;
    foreach ($lines as $ln) {
      if (preg_match('/^[-*]\s+(.+)$/', $ln, $m)) {
        if (!$inUl) { if ($inOl) { $out[] = '</ol>'; $inOl = false; } $out[] = '<ul class="list-disc list-inside my-2 space-y-1 text-sm text-slate-700">'; $inUl = true; }
        $out[] = '<li>' . $m[1] . '</li>'; continue;
      }
      if (preg_match('/^\d+\.\s+(.+)$/', $ln, $m)) {
        if (!$inOl) { if ($inUl) { $out[] = '</ul>'; $inUl = false; } $out[] = '<ol class="list-decimal list-inside my-2 space-y-1 text-sm text-slate-700">'; $inOl = true; }
        $out[] = '<li>' . $m[1] . '</li>'; continue;
      }
      if ($inUl) { $out[] = '</ul>'; $inUl = false; }
      if ($inOl) { $out[] = '</ol>'; $inOl = false; }
      $out[] = $ln;
    }
    if ($inUl) $out[] = '</ul>';
    if ($inOl) $out[] = '</ol>';
    $md = implode("\n", $out);

    $blocks = preg_split('/\n{2,}/', $md);
    foreach ($blocks as &$b) {
      $b = trim($b);
      if ($b === '') continue;
      if (preg_match('/^\s*</', $b)) continue;
      $b = '<p class="text-sm text-slate-700 my-2 leading-relaxed">' . $b . '</p>';
    }
    return implode("\n\n", array_filter($blocks));
  }

  private function _internalManualUrl(string $plugin): ?string
  {
    $known = ['Caex' => 'caexmanual'];
    return $known[$plugin] ?? null;
  }
}

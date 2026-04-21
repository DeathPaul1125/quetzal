<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Illuminate\View\ViewServiceProvider;

/**
 * Container mínimo extendiendo el base. Illuminate\View 11+ llama
 * $app->terminating() en ViewServiceProvider::register(), pero ese método
 * solo existe en la Application real (no en Container base). Como no
 * necesitamos ejecutar callbacks al final del request, lo exponemos como
 * no-op para evitar el fatal.
 */
final class _QuetzalBladeContainer extends Container
{
    public function terminating(callable $callback): static
    {
        // no-op; no hay ciclo de vida "terminate" en Quetzal
        return $this;
    }
}

/**
 * Wrapper propio alrededor de illuminate/view para Blade.
 *
 * Reemplaza a jenssegers/blade que tenía incompatibilidades de sintaxis
 * con PHP 8.4 (parámetros implícitamente nullable). Esta clase:
 *
 *   - Configura un contenedor Illuminate aislado
 *   - Registra files/events/config con los paths de vistas y cache
 *   - Arranca ViewServiceProvider para tener Factory y BladeCompiler
 *   - Registra el contenedor como Container::getInstance() global para que
 *     las directivas @echo y componentes puedan resolver app('blade.compiler')
 *
 * API pública expuesta:
 *   - render(view, data)   → string HTML renderizado
 *   - exists(view)         → bool
 *   - compiler()           → BladeCompiler (para registrar directivas)
 *   - factory()            → Factory (acceso avanzado)
 *   - container()          → Container (acceso al DI)
 */
class QuetzalBladeEngine
{
    private Container $container;
    private Factory $factory;
    private BladeCompiler $compiler;

    public function __construct(array $viewPaths, string $cachePath, ?Container $container = null)
    {
        $this->container = $container ?? new _QuetzalBladeContainer();

        $this->setupContainer($viewPaths, $cachePath);

        (new ViewServiceProvider($this->container))->register();

        $this->factory  = $this->container->get('view');
        $this->compiler = $this->container->get('blade.compiler');

        // Illuminate\View compila echos con código que llama app('blade.compiler').
        // Sin un Container::getInstance() global, esas resoluciones fallan en runtime.
        Container::setInstance($this->container);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->factory->make($view, $data)->render();
    }

    public function exists(string $view): bool
    {
        return $this->factory->exists($view);
    }

    public function compiler(): BladeCompiler
    {
        return $this->compiler;
    }

    public function factory(): Factory
    {
        return $this->factory;
    }

    public function container(): Container
    {
        return $this->container;
    }

    private function setupContainer(array $viewPaths, string $cachePath): void
    {
        $this->container->bindIf('files', fn () => new Filesystem());
        $this->container->bindIf('events', fn () => new Dispatcher());
        $this->container->bindIf('config', fn () => new Repository([
            'view.paths'    => $viewPaths,
            'view.compiled' => $cachePath,
        ]));

        Facade::setFacadeApplication($this->container);
    }
}

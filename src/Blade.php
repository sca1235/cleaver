<?php

namespace Aschmelyun\Cleaver;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Factory;

class Blade
{

    /**
     * @var Container
     */
    private $container;
    /**
     * @var array
     */
    private $viewPaths;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var Factory|void
     */
    private $instance;

    /**
     * @param $viewPaths
     * @param $cachePath
     * @param Dispatcher|null $events
     */
    function __construct($viewPaths, $cachePath, Dispatcher $events = null) {

        $this->container = new Container;

        $this->viewPaths = (array) $viewPaths;

        $this->cachePath = $cachePath;

        $this->registerFilesystem();

        $this->registerEvents($events ?: new Dispatcher);

        $this->registerEngineResolver();

        $this->registerViewFinder();

        $this->instance = $this->registerFactory();
    }

    /**
     * @return Factory|void
     */
    public function view()
    {
        return $this->instance;
    }

    /**
     * @return void
     */
    public function registerFilesystem()
    {
        $this->container->singleton('files', function(){
            return new Filesystem;
        });
    }

    /**
     * @param Dispatcher $events
     * @return void
     */
    public function registerEvents(Dispatcher $events)
    {
        $this->container->singleton('events', function() use ($events)
        {
            return $events;
        });
    }
    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $me = $this;

        $this->container->singleton('view.engine.resolver', function($app) use ($me)
        {
            $resolver = new EngineResolver;

            // Next we will register the various engines with the resolver so that the
            // environment can resolve the engines it needs for various views based
            // on the extension of view files. We call a method for each engines.
            foreach (array('php', 'blade') as $engine)
            {
                $me->{'register'.ucfirst($engine).'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    public function registerPhpEngine(EngineResolver $resolver)
    {
        $resolver->register('php', function() {
            return new PhpEngine($this->container['files']);
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param EngineResolver $resolver
     * @return void
     */
    public function registerBladeEngine(EngineResolver $resolver)
    {
        $me = $this;
        $app = $this->container;

        // The Compiler engine requires an instance of the CompilerInterface, which in
        // this case will be the Blade compiler, so we'll first create the compiler
        // instance to pass into the engine so it can compile the views properly.
        $this->container->singleton('blade.compiler', function($app) use ($me)
        {
            $cache = $me->cachePath;

            return new BladeCompiler($app['files'], $cache);
        });

        $resolver->register('blade', function() use ($app)
        {
            return new CompilerEngine($app['blade.compiler'], $app['files']);
        });
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $me = $this;
        $this->container->singleton('view.finder', function($app) use ($me)
        {
            $paths = $me->viewPaths;

            return new FileViewFinder($app['files'], $paths);
        });
    }

    /**
     * Register the view environment.
     *
     * @return Factory
     */
    public function registerFactory(): Factory
    {
        // Next we need to grab the engine resolver instance that will be used by the
        // environment. The resolver will be used by an environment to get each of
        // the various engine implementations such as plain PHP or Blade engine.
        $resolver = $this->container['view.engine.resolver'];

        $finder = $this->container['view.finder'];

        $env = new Factory($resolver, $finder, $this->container['events']);

        // We will also set the container instance on this view environment since the
        // view composers may be classes registered in the container, which allows
        // for great testable, flexible composers for the application developer.
        $env->setContainer($this->container);

        return $env;
    }

    /**
     * @return mixed|object
     */
    public function getCompiler()
    {
        return $this->container['blade.compiler'];
    }

}
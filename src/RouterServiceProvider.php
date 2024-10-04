<?php

namespace Illuminate\Router;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
class RouterServiceProvider extends ServiceProvider
{


    public function register()
    {
       
    }
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(\Illuminate\Routing\Router $router)
    {
        
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');        
        

    }
}

<?php

namespace jyj1993126;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    	$this->commands( Commands\SwooleStart::class );
    }
}

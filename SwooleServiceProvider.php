<?php

namespace jyj1993126\lumenswoole;

use Illuminate\Support\ServiceProvider;

class SwooleServiceProvider extends ServiceProvider
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

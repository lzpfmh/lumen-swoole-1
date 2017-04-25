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
		$this->app->configure( 'swoole' );
		$this->mergeConfigFrom(
			__DIR__ . '/swoole.php', 'swoole'
		);
		$this->commands( Commands\SwooleStart::class );
	}
	
	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes(
			[
				__DIR__ . '/swoole.php' => base_path( 'config/swoole.php' ) ,
			]
		);
	}
}

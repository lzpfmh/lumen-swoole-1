<?php
namespace jyj1993126\lumenswoole\Commands;

use Illuminate\Console\Command;

/**
 * @author Leon J
 * @since 2017/4/25
 */
class SwooleStart extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'swoole:start';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'start a swoole server';
	
	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$host = config( 'swoole.host' , '127.0.0.1' );
		$port = config( 'swoole.port' , 9050 );
		$configuration = config( 'swoole.configuration' , [] );
		$this->info( "swoole is running at {$host}:{$port} " );
		( new \jyj1993126\lumenswoole\Wrappers\SwooleHttpWrapper( $host , $port , $configuration ) )->run();
	}
}
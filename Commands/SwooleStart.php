<?php
namespace jyj1993126\Commands;

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
		( new \jyj1993126\Wrappers\SwooleHttpWrapper )->run();
	}
}
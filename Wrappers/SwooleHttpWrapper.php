<?php

namespace jyj1993126\lumenswoole\Wrappers;

use Illuminate\Http\Response as IlluminateResponse;
use jyj1993126\lumenswoole\Utils;
use Monolog\Handler\Curl\Util;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @author Leon J
 * @since 2017/4/24
 */
class SwooleHttpWrapper
{
	protected $server;
	
	/**
	 * SwooleApplication constructor.
	 * @param $host
	 * @param $port
	 * @param $configuration
	 */
	public function __construct( $host, $port, $configuration )
	{
		$this->server = new \swoole_http_server( $host, $port );
		$this->server->on( 'request' , [ $this , 'onRequest' ] );
		$this->server->set( $configuration );
	}
	
	public function run()
	{
		$this->server->start();
	}
	
	public function onRequest( \swoole_http_request $request , \swoole_http_response $response )
	{
		Utils::ucHeaders( $request );
		$illuminateRequest = Utils::convertRequest( $request );
		$illuminateResponse = app()->handle( $illuminateRequest );
		$this->handleResponse(
			$response ,
			$illuminateResponse ,
			isset( $request->header['Accept-Encoding'] ) ? $request->header['Accept-Encoding'] : ''
		);
	}
	
	protected function handleResponse(
		\swoole_http_response $response ,
		IlluminateResponse $illuminateResponse ,
		$accept_encoding = ''
	)
	{
		$accept_gzip = config( 'swoole.accept_gzip' , false ) && stripos( $accept_encoding , 'gzip' ) !== false;
		
		// status
		$response->status( $illuminateResponse->getStatusCode() );
		// headers
		$response->header( 'Server' , config( 'laravoole.base_config.server' ) );
		foreach( $illuminateResponse->headers->allPreserveCase() as $name => $values )
		{
			foreach( $values as $value )
			{
				$response->header( $name , $value );
			}
		}
		// cookies
		foreach( $illuminateResponse->headers->getCookies() as $cookie )
		{
			$response->rawcookie(
				$cookie->getName() ,
				urlencode( $cookie->getValue() ) ,
				$cookie->getExpiresTime() ,
				$cookie->getPath() ,
				$cookie->getDomain() ,
				$cookie->isSecure() ,
				$cookie->isHttpOnly()
			);
		}
		// content
		if( $illuminateResponse instanceof BinaryFileResponse )
		{
			$content = function () use ( $illuminateResponse )
			{
				return $illuminateResponse->getFile()->getPathname();
			};
			if( $accept_gzip && isset( $response->header['Content-Type'] ) )
			{
				$size = $illuminateResponse->getFile()->getSize();
			}
		}
		else
		{
			$content = $illuminateResponse->getContent();
			// check gzip
			if( $accept_gzip && isset( $response->header['Content-Type'] ) )
			{
				$mime = $response->header['Content-Type'];
				
				if( strlen( $content ) > config( 'laravoole.base_config.gzip_min_length' ) && is_mime_gzip( $mime ) )
				{
					$response->gzip( config( 'laravoole.base_config.gzip' ) );
				}
			}
		}
		$this->endResponse( $response , $content );
	}
	
	protected function endResponse( \swoole_http_response $response , $content )
	{
		if( !is_string( $content ) )
		{
			$response->sendfile( realpath( $content() ) );
		}
		else
		{
			// send content & close
			$response->end( $content );
		}
	}
}
<?php

namespace jyj1993126\Wrappers;

use Illuminate\Http\Response as IlluminateResponse;
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
	 */
	public function __construct()
	{
		$this->server = new \swoole_http_server(
			config( 'swoole.host' , '127.0.0.1' ) , config( 'swoole.port' , 9050 )
		);
		$this->server->on( 'request' , [ $this , 'onRequest' ] );
	}
	
	public function run()
	{
		$this->server->start();
	}
	
	public function onRequest( \swoole_http_request $request , \swoole_http_response $response )
	{
		$this->ucHeaders( $request );
		$illuminateRequest = $this->convertRequest( $request );
		$illuminateResponse = app()->dispatch( $illuminateRequest );
		$this->handleResponse(
			$response ,
			$illuminateResponse ,
			isset( $request->header['Accept-Encoding'] ) ? $request->header['Accept-Encoding'] : ''
		);
	}
	
	protected function ucHeaders( $request )
	{
		// merge headers into server which ar filted by swoole
		// make a new array when php 7 has different behavior on foreach
		$new_header = [];
		$uc_header = [];
		foreach( $request->header as $key => $value )
		{
			$new_header['http_' . $key] = $value;
			$uc_header[ucwords( $key , '-' )] = $value;
		}
		$server = array_merge( $request->server , $new_header );
		
		// swoole has changed all keys to lower case
		$server = array_change_key_case( $server , CASE_UPPER );
		$request->server = $server;
		$request->header = $uc_header;
		return $request;
	}
	
	protected function convertRequest( \swoole_http_request $request )
	{
		$get = isset( $request->get ) ? $request->get : [];
		$post = isset( $request->post ) ? $request->post : [];
		$cookie = isset( $request->cookie ) ? $request->cookie : [];
		$server = isset( $request->server ) ? $request->server : [];
		$header = isset( $request->header ) ? $request->header : [];
		$files = isset( $request->files ) ? $request->files : [];
		// $attr = isset($request->files) ? $request->files : [];
		
		$content = $request->rawContent() ?: null;
		
		return new \Illuminate\Http\Request(
			$get , $post , []/* attributes */ , $cookie , $files , $server , $content
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
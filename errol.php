<?php
/****
Errol PHP framework
by Colin Dean

"I guess she thought he was Errol Flynn"
****/
//////////////////////// FUNCTIONS /////////////////////////////
/**
 * Add a route to the routing table.
 *
 * Supply a method to use only a certain HTTP Method to react to the call.
 * Valid methods: GET, POST, PUT, DELETE, HEAD, OPTIONS, TRACK, TRACE
 *
 * @throws RouteExistsException
 * @param regex $path a regex string which will match the path
 * @param function $handler the handler to be called when the path is activated
 * @param string $method optional, set to handle certain HTTP methods (CRUD)
 **/
function route(/* regex */ $path, 
							 /*function or method*/ $handler, 
							 /*string*/ $method=null){
	ErrolRouter::defineRoute($path, $handler, $method);
}
/**
 * Add an error handler in userspace. The error handler function must accept
 * a single parameter, an instance of Exception.
 *
 * @throws RouteExistsException
 * @param int $code The HTTP Status code associated with the handler
 * @param function $handler The function to be called when the code is tripped
**/
function errorHandler(/* int matching http error code */ $code,
											/*function or method*/ $handler){
	ErrolRouter::addErrorHandler($code, $handler);
}
/**
 * Return the relative URL for a certain handler. Use this to set routes and
 * reference the routes by handler function instead of the path itself.
 *
 * @param function $handler the function the path of which is desired
 * @return string the path which should be used
 **/
function url_for(/*function*/$handler){
  return ErrorRouter::getPathForHandler($handler);
}
////////////////////////// CLASSES //////////////////////////////
class ErrolRouter {

	static private handlerList = array();
	static private errorHandlers = array();

	static public function getPathForHandler(/*function*/$handler){
		throw new Exception("Not yet implemented.");
	}

	static public function defineRoute(/* regex */ $path, 
												 /*function or method*/ $handler, 
												 /*string*/ $method=null){
		if( array_key_exists ($code, self::$handlerList) ){
			throw new ErrolRouteDuplicationException($path, $handler);
		} else {
			self::$handlerList[$path] = array('handler'=>$handler, 
																				'method'=>$method);
		}
  }

	static public function addErrorHandler(/*int matching http error code*/ $code,
											/*function or method*/ $handler){
		if( array_key_exists ($code, self::$errorHandlers) ){
			throw new ErrolRouteDuplicationException($code, $handler);
		} else {
			self::$errorHandlers[$code] = $handler;
		}
	}

  static public function getHandler(/*regex*/ $path){
		if( array_key_exists($path, self::$handlerList) ){
			return self::$handlerList[$path];
		} else {
			throw new ErrolRouteNotFoundException(404, $path);
		}
  }
	static public function getErrorHandler(/*int*/$code){
		if( array_key_exists($path, self::$errorHandlers) ){
			return self::$errorHandlers[$code];
		} else {
			return "ErrolRouter::defaultErrorHandler";
		}
	}

	static public function run(){
		//get the requested path
    $path = $_SERVER['PATH'];
		try {
			$handler = self::getHandler($path);
			$handler();
		} catch (ErrolRouteNotFoundException $routeException) {
			$errorHandler = self::$getErrorHandler($routerException->code);
			$errorHandler($routeException);
		}
  }

}

class ErrolRoute {
	private $path;
	private $handler;
	private $method;

	function __construct($path, $handler, $method){
		$this->path = $path;
		$this->handler = $handler;
		$this->method = $method;
	}

}

function ErrolRoutingTable implements Iterator, Countable {
	private $list;

	public getRoute($location, $method=null){
		if(!array_key_exists($location, $this->list)){
			throw new ErrolRouteNotFoundException($location, $method);
		}
		$handlerSet = $this->list[$location];
		if(is_string($handlerSet){
			return $handlerSet;
		}
		if(is_array($handlerSet){
			return $handlerSet[$method];
		}
	}

	public addRoute($location, $handler, $method=null){
		if(!$method){
			if(array_key_exists($location, $this->list){
				throw new ErrorRouteDuplicationException($location, $handler, $method);
			}
			$this->list[$location] = $handler;
		} else {
			if(array_key_exists($method, $this->list[$location])){
				throw new ErrolRouteDuplicationException($location, $handler, $method);
			}
			$this->list[$location] = array($method => $handler);
		}
	}
	public function count(){return count($this->list);}
	public function key(){return key($this->list);}
	public function current(){return current($this->list);}
	public function rewind(){reset($this->list);}
	public function next(){return next($this->list);}
	public function valid(){return $this->current() !== false;}

}


class ErrolRouteNotFoundException extends Exception {

	public $code, $path;
  
	function __construct($code, $path){
		$this->message = "Route not found: [{$path}]";
		$this->code = $code;
		$this->path = $path;
	}

}

class ErrolRouteDuplicationException extends Exception {

	public $location, $handler, $method;

	function __construct($location, $handler, $method){
		$this->location = $location;
		$this->handler = $handler;
		$this->method
		$this->message = "Duplicate route detected: [{$method}]->[{$location}]->[{$handler}]";
	}
}

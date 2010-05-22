<?php
/****
Deano PHP framework
by Colin Dean

****/
//////////////////////// FUNCTIONS /////////////////////////////
/**
 * Add a route to the routing table.
 *
 * Supply a method to use only a certain HTTP Method to react to the call.
 * Valid methods: GET, POST, PUT, DELETE, HEAD, OPTIONS, TRACK, TRACE
 *
 * @throws DeanoRouteExistsException
 * @param regex $path a regex string which will match the path
 * @param function $handler the handler to be called when the path is activated
 * @param string $method optional, set to handle certain HTTP methods (CRUD)
 **/
function route(/* regex */ $path, 
							 /*function or method*/ $handler, 
							 /*string*/ $method=null){
	DeanoRouter::defineRoute($path, $handler, $method);
}
/**
 * Add an error handler in userspace. The error handler function must accept
 * a single parameter, an instance of Exception.
 *
 * @throws DeanoRouteExistsException
 * @param int $code The HTTP Status code associated with the handler
 * @param function $handler The function to be called when the code is tripped
**/
function errorHandler(/* int matching http error code */ $code,
											/*function or method*/ $handler){
	DeanoRouter::addErrorHandler($code, $handler);
}
/**
 * Return the relative URL for a certain handler. Use this to set routes and
 * reference the routes by handler function instead of the path itself.
 *
 * @param function $handler the function the path of which is desired
 * @return string the path which should be used
 **/
function url_for(/*function*/$handler){
  return DeanoRouter::getPathForHandler($handler);
}

function run(){
	DeanoRouter::run();
}

function dlog($message, $level=DeanoLog::INFO){
	DeanoLog::addLog($message, $level);
}
////////////////////////// CLASSES //////////////////////////////
class DeanoRouter {

	private static $handlerList;// = array();
  private static $errorHandlers;// = array();

	static public function getPathForHandler(/*function*/$handler){
		throw new Exception("Not yet implemented.");
	}

	static public function defineRoute(/* regex */ $path, 
												 /*function or method*/ $handler, 
												 /*string*/ $method=null){
		dlog("Defining route {$method} {$path} with handler {$handler}");
		if( array_key_exists ($path, self::$handlerList) ){
			throw new DeanoRouteDuplicationException($path, $handler, $method);
		} else {
			self::$handlerList[$path] = array('handler'=>$handler, 
																				'method'=>$method);
		}
  }

	static public function addErrorHandler(/*int matching http error code*/ $code,
											/*function or method*/ $handler){
		dlog("Defining error handler {$handler} for code {$code}");
		if( array_key_exists ($code, self::$errorHandlers) ){
			throw new DeanoRouteDuplicationException($code, $handler);
		} else {
			self::$errorHandlers[$code] = $handler;
		}
	}

  static public function getHandler(/*regex*/ $path){
		if( array_key_exists($path, self::$handlerList) ){
			return self::$handlerList[$path];
		} else {
			throw new DeanoRouteNotFoundException(404, $path);
		}
  }
	static public function getErrorHandler(/*int*/$code){
		if( array_key_exists($code, self::$errorHandlers) ){
			return self::$errorHandlers[$code];
		} else {
			return "DeanoRouter::defaultErrorHandler";
		}
	}

	static public function init(){
		self::$handlerList = array();
		self::$errorHandlers = array();
	}

	static public function run(){
		//get the requested path
    $path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];
		$phpNeedsAGoramFinally = false;
		dlog("Getting handler for path {$path} method {$method}");
		try {
			$handler = self::getHandler($path);
			dlog("Handler for {$method} {$path} is {$handler}, calling");
			$handler['handler']();
		} catch (DeanoRouteNotFoundException $routeException) {
			dlog("Handler for {$method} {$path} not found", DeanoLog::WARN);
			$errorHandler = self::getErrorHandler($routeException->code);
			dlog("Error handler for {$routeException->code} is {$errorHandler}, calling");
			$errorHandler($routeException);
		} catch (Exception $e) {
			echo '<div class="deano-exception" style="border:1px solid red;background-color:#fdd;padding:1em">'.
						'<div><strong>Uncaught general exception</strong></div>'.
						"<div>{$e}</div>".
						"</div>";
			if(DEANO_LOG && $phpNeedsAGoramFinally='true'){
				DeanoLog::prettyPrint();
			}
		}
		
		if(DEANO_LOG && !$phpNeedsAGoramFinally){
			DeanoLog::prettyPrint();
		}
		
  }

}

class DeanoRoute {
	private $path;
	private $handler;
	private $method;

	function __construct($path, $handler, $method=null){
		$this->path = $path;
		$this->handler = $handler;
		$this->method = $method;
	}

}

class DeanoRoutingTable implements Iterator, Countable {
	private $list;

	public function __construct(){
		$this->list = array();
	}

	public function addRoute(/*DeanoRoute*/ $route){
		$this->list[] = $route;
	}

	public function getRoute($location, $method=null){
		//this needs to be made more efficient. a lot more efficient.
		foreach($this->list as $route){
			if($route->location == $location && $route->method = $method){
				return $route;
			}
		}
	}
  /*
	public getRoute($location, $method=null){
		if(!array_key_exists($location, $this->list)){
			throw new DeanoRouteNotFoundException($location, $method);
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
				throw new DeanoRouteDuplicationException($location, $handler, $method);
			}
			$this->list[$location] = array($method => $handler);
		}
	}
	*/
	public function count(){return count($this->list);}
	public function key(){return key($this->list);}
	public function current(){return current($this->list);}
	public function rewind(){reset($this->list);}
	public function next(){return next($this->list);}
	public function valid(){return $this->current() !== false;}

}


class DeanoRouteNotFoundException extends Exception {

	public $code, $path;
  
	function __construct($code, $path){
		$this->message = "Route not found: [{$path}]";
		$this->code = $code;
		$this->path = $path;
	}

}

class DeanoRouteDuplicationException extends Exception {

	public $location, $handler, $method;

	function __construct($location, $handler, $method){
		$this->location = $location;
		$this->handler = $handler;
		$this->method = $method;
		$this->message = "Duplicate route detected: [{$method}]->[{$location}]->[{$handler}]";
	}
}

class DeanoLog {
	private static $log; //array();

	private static $start_time; //microtime(true);
	const INFO = "info";
	const WARN = "warn";
	const ERROR = "error";

	static public function init(){
		self::$start_time = microtime();
		self::$log = array();
		if(!defined('DEANO_LOG')){define('DEANO_LOG', false);}
		self::addLog("DeanoLog started");
	}
	static public function addLog(/*string*/$message, $level=DeanoLog::INFO){
		$time = microtime() - self::$start_time;
		if(DEANO_LOG === false) return;
		self::$log[] = array(
											"time" => $time,
											"level" => $level,
											"message" => $message,
											"location" => "",//this needs to be the class::method [filename:line#] of the caller
											"memory" => self::_formatUsage(memory_get_peak_usage())
									);
	}

	static public function getLog(){
		return self::$log;
	}

	static public function prettyPrint(){
		self::prettyPrintTable();
	}

	static public function prettyPrintText(){
		echo "<pre>";
		var_dump(self::getLog());
		echo "</pre>";
	}

	static public function prettyPrintTable(){
		echo "<table><thead><tr>";
		foreach (array("Time","Level","Message","Location","Memory") as $h){
			echo "<th>{$h}</th>";
		}
		echo "</tr></thead><tbody>";
		foreach(self::getLog() as $ln){
			echo sprintf(
				'<tr><td>%f</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				$ln['time'], $ln['level'], $ln['message'], 
				$ln['location'], $ln['memory']
			);
		}
		echo "</tbody></table>";
	}
	
	private function _formatUsage( $bytes ) {
  	$symbols = array('B', 'KiB', 'MiB', 'GiB', 'TiB' );
    $exp = floor( log( $bytes ) / log( 1024 ) );
    $formatted = ( $bytes / pow( 1024, floor( $exp ) ) );
    return sprintf( '%.2f ' . $symbols[ $exp ], $formatted );
  }
}
DeanoLog::init();
DeanoRouter::init();
?>

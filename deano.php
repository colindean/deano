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
	DeanoRouter::addRoute($path, $handler, $method);
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
function url_for(/*function*/$handler, $method=null){
  return DeanoRouter::getPathForHandler($handler, $method);
}

function run(){
	DeanoRouter::run();
}

function dlog($message, $level=DeanoLog::INFO){
	DeanoLog::addLog($message, $level);
}
////////////////////////// CLASSES //////////////////////////////
class DeanoRouter {

	private static $handlerTable;// = array();
  private static $errorTable;// = array();
	private static $errorException;//this belongs elsewhere, but dunno where

	static public function getPathForHandler(/*function*/$handler, $method=null){
		return self::$handlerTable->getRouteByHandler($handler, $method);
	}

	static public function addRoute(/* regex */ $path, 
												 /*function or method*/ $handler, 
												 /*string*/ $method=null){
		dlog("Defining route [{$method} {$path}] with handler {$handler}");
		//addRoute will throw an exception if a route already exists
		//don't catch it here, as it's a development problem, not a user error
		self::$handlerTable->addRoute(new DeanoRoute($path, $handler, $method));
	}
		
	static public function addErrorHandler(/*int matching http error code*/ $code,
											/*function or method*/ $handler){
		dlog("Defining error handler {$handler} for code {$code}");
		//addRoute will throw an exception if a route already exists
		//don't catch it here, as it's a development problem, not a user error
		self::$errorTable->addRoute(new DeanoRoute($code, $handler));
	}

	static public function getRoute(/*regex*/$path, /*method*/$method=null){
		//getRoute will return null if there isn't a route which matches
		return self::$handlerTable->getRoute($path, $method);
	}

	static public function getErrorHandler(/*int*/$code){
		//this must always return something, so put a trycatch here
		try {
			return self::$errorTable->getRoute($code);
		} catch (DeanoRouteErrorException $e) {
			self::$errorException = $e;
			return "DeanoRouter::defaultErrorHandler";
		}
	}

	static public function defaultErrorHandler($e){
		header("HTTP/1.1 {$e->code} {$e->status}");
		header("Content-Type: text/html");
		echo("<html><head><title>{$e->code} {$e->status}</title></head><body>".
					"<h1>{$e->code} {$e->status}</h1>");
	}

	static public function init(){
		self::$handlerTable = new DeanoRoutingTable();
		self::$errorTable = new DeanoRoutingTable();
	}

	static public function run(){
		//get the requested path
    $path = array_key_exists('PATH_INFO', $_SERVER) ? $_SERVER['PATH_INFO'] : $_SERVER['REQUEST_URI'];
		$method = $_SERVER['REQUEST_METHOD'];
		$phpNeedsAGoramFinally = false;
				dlog("Getting handler for path {$path} method {$method}");
		try {
			$route = self::getRouteByLocation($path, $method);
					dlog("Handler for {$method} {$path} is {$handler}, calling");
			($route->handler)();
		} catch (DeanoRouteErrorException $routeException) {
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
	public $path;
	public $handler;
	public $method;

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
		//check if a route with the same params already exists in the list
		if(!in_array($route, $this->list)){
			$this->list[] = $route;
		} else {
			throw new DeanoRouteDuplicationException($route);
		}
	}

	public function getRouteByHandler($handler, $method=null){
		$h = array_filter($this->list,
												create_function('$r',
																				'return $r->handler == $handler;');
		//if there's nothing there, throw a 404
		if( count($h) == 0 ){
			throw new DeanoRouteNotFoundException($handler, $method);
		}
		
		//if there's only one and its method is null, return it
		if( (count($h) == 1) && ($h[0]->method == null)){
			return $h[0];
		}

		//now we have all routes which match the location, just need to match method
		$byMethod = array_filter($h,
															create_function('$r',
																							'return $r->method == $method;');
		if(count($byMethod) == 0){
			throw new DeanoRouteNoMethodException($location, $method);
		}
		
		return $byMethod[0];
	}

	public function getRoute($location, $method=null){
		return $this->getRouteByLocation($location, $method);
	}

	public function getRouteByLocation($location, $method=null){
		//rather than loop, filter by location and then by method
		//does 5.3 support first class functions?
		$byLoc = array_filter($this->list, 
													create_function('$r',
																					'return $r->location == $location;');
		//if there's nothing there, throw a 404
		if( (count($byLoc) == 0) ){
			throw new DeanoRouteNotFoundException($location, $method);
		}
		//if there's only one and its method is null, return it
		if( (count($byLoc) == 1) && ($byLoc[0]->method == null)){
			return $byLoc[0];
		}

		//now we have all routes which match the location, just need to match method
		$byMethod = array_filter($byLoc,
															create_function('$r',
																							'return $r->method == $method;');
		if(count($byMethod) == 0){
			throw new DeanoRouteNoMethodException($location, $method);
		}
		
		return $byMethod[0];
	}

	//I don't think there's a distinct need for delete route

	public function count(){return count($this->list);}
	public function key(){return key($this->list);}
	public function current(){return current($this->list);}
	public function rewind(){reset($this->list);}
	public function next(){return next($this->list);}
	public function valid(){return $this->current() !== false;}

}

class DeanoRouteErrorException extends Exception {
	public $code, $path, $method, $status;
	function __construct($code, $path, $method=null){
		$this->message = "General error at [{$method} {$path}]";
		$this->code = $code;
		$this->path = $path;
		$this->method = $method;
		$this->status = '';
	}
}

class DeanoRouteNotFoundException extends DeanoRouteErrorException {
	function __construct($path, $method=null){
		parent::__construct(404, $path, $method);
		$this->message = "Route not found: [{$method} {$path}]";
		$this->status = "Not Found";
	}
}

class DeanoRouteNoMethodException extends DeanRouteErrorException {
	function __construct($path, $method=null){
		parent::__construct(405, $path, $method);
		$this->message = "Route found, but not for given method [{$method} {$path}]";
		$this->status = "Method Not Allowed";
	}
}

class DeanoRouteDuplicationException extends Exception {

	public $route;

	function __construct($route){
	$this->route = $route;
	$this->message = "Duplicate route detected: [{$route->method}]->[{$route->location}]->[{$route->handler}]";
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

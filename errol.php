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
 * @throws RouteExistsException
 * @param regex $path a regex string which will match the path
 * @param function $handler the handler to be called when the path is activated
 * @param string $nickname optional nickname for internal use
 **/
function route(/* regex */ $path, 
							 /*function or method*/ $handler, 
							 /*string*/ $nickname=null){
	ErrolRouter::defineRoute($path, $handler, $nickname);
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
////////////////////////// CLASSES //////////////////////////////
class ErrolRouter {

	static private handlerList = array();
	static private errorHandlers = array();

	static public function defineRoute(/* regex */ $path, 
												 /*function or method*/ $handler, 
												 /*string*/ $nickname=null){
		if( array_key_exists ($code, self::$handlerList) ){
			throw new ErrolRouteDuplicationException($path, $handler);
		} else {
			self::$handlerList[$path] = array('handler'=>$handler, 
																				'nickname'=>$nickname);
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
			throw new ErrolRouteNotFoundException(404, "Route does not exist.");
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

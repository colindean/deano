<?php
/****
Errol PHP framework
by Colin Dean

"I guess she thought he was Errol Flynn"
****/

function route(/* regex */ $path, /*function or method*/ $handler, /*string*/ $nickname=null){
	ErrolRouter::addRoute($path, $handler, $nickname);
}

class ErrolRouter {

	static private handlerList = array();

	static public addRoute(/* regex */ $path, /*function or method*/ $handler, /*string*/ $nickname=null){
		self::$handlerList[$path] = array('handler'=>$handler, 'nickname'=>$nickname);
  }

  static public getHandler(/*regex*/ $path){
		return self::$handlerList[$path];
  }

	static public run(){
		//get the requested path
    $path = $_SERVER['PATH'];
		$handler = self::getHandler($path);
		$handler();
  }

}

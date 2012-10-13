<?php
//this is an example of what a controller file should look like
define('DEANO_LOG', true);
require 'deano.php';

route('/','root');
function root(){
  render("root");
}

route('/about','about');
function about(){
  render("about");
}

route('/errt','errt');
function errt(){

}

errorHandler(404, 'notFound');
function notFound($e){
  //you must call $e->header yourself in order to output the appropriate header
	$e->header();
  render("errors/404");
}

run();

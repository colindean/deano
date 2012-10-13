<?php
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
	$e->header();
	print_head();
	echo "Not found!";
}


function print_head(){
	echo "<h1>Deano</h1>";
}

run();
?>

<p>end of target</p>

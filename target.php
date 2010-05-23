<?php
define('DEANO_LOG', true);
require 'deano.php';

route('/','root');
function root(){
	print_head();
	echo '<p><a href="'.url_for('about').'">About?</a></p>';
}

route('/about','about');
function about(){
	print_head();
	echo "This is the about page. ";
  echo sprintf('<a href="%s">This should link to root</a>', url_for('root'));
	echo ' <a href="error">This should cause a 404</a>.';
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

<?php
define('DEANO_LOG', true);
require 'deano.php';

route('/','root');
function root(){
	echo '<p><a href="about">About?</a></p>';
}

route('/about','about');
function about(){
	echo "This is the about page.";
  echo sprintf('<a href="%s">This should link to root</a>', url_for('root'));
} 

errorHandler(404, 'notFound');
function notFound(){
	echo "Not found!";
}

run();
?>

<p>end of target</p>

<?php
define('DEANO_LOG', true);
require 'deano.php';

route('/','root');
function root(){
	echo "I guess she thought he was Errol Flynn";
}

errorHandler(404, 'notFound');
function notFound(){
	echo "Not found!";
}

run();
?>
<p>end of target</p>

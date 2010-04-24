<?php
require 'errol.php'

route('/','root');
function root(){
	echo "I guess she thought he was Errol Flynn";
}

errorHandler(404, 'notFound');
function notFound(){
	echo "Not found!";
}

?>

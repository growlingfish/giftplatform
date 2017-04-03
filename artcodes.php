<?php

require_once('lib/rest.php');
require_once('lib/simple_html_dom.php');

//$artcodes = curl_get('http://www.artcodes.co.uk/');

$artcodes = str_get_html(curl_get('http://www.artcodes.co.uk/'));

//link rel='https://api.w.org/'

foreach ($artcodes->find('link[rel=https://api.w.org/]') as $element) {
	var_dump($element->href); 
}
 
?>
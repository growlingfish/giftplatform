<?php

function get_api_base () {
	require_once('rest.php');
	require_once('simple_html_dom.php');

	$artcodes = str_get_html(curl_get('http://www.artcodes.co.uk/'));

	foreach ($artcodes->find('link[rel=https://api.w.org/]') as $element) {
		return $element->href; 
	}
}
 
?>
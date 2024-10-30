<?php

	if (!$_GET['url'])
		die;
		
	if (!$_GET['number'])
		$_GET['number']=0;
	
	function blugz_load_url($blugz_url) {
		if(function_exists('curl_exec')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $blugz_url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$xml = curl_exec($ch);
			curl_close($ch);
		} else {
			$xml = file_get_contents($blugz_url);
		}
		return $xml;
	}
	
	function str_img_src($html) {
        if (stripos($html, '<img') !== false) {
            $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
            preg_match($imgsrc_regex, $html, $matches);
            unset($imgsrc_regex);
            unset($html);
            if (is_array($matches) && !empty($matches)) {
                return $matches[2];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
	$html = blugz_load_url($_GET['orig']);
	if (preg_match('/The document has moved[^H]*HREF="([^"]*)/',$html,$m)) {
		$html = blugz_load_url('http://www.google.com'.$m[1]);
	}
		
	//echo str_replace('','',$html);
	//echo htmlentities($html);
	if (preg_match_all('/thumbnails:\[([^\]]*)/',$html,$m))
		if (preg_match('/url:\'([^\']*)/',$m[1][$_GET['number']],$n))
			echo blugz_load_url($n[1]);

	
	
	//var_dump( str_img_src($html) );

?>

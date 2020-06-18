<?php
function pbrf_showToastFunctions( $atts ) {
	add_action( 'wp_enqueue_scripts', 'pbk_scripts' );
	$a = shortcode_atts( array(
        'function' => '',
    ), $atts );
    $directory = dirname(__DIR__) . '/modules';
    if($a['function']=='' || !isset($a['function']) || !file_exists($directory."/".$a['function'].".php")) {
    	$ret="<div class=''>Invalid File</div>";
    }else {
  		include $directory . "/" . $a['function'].".php";
    }
    return $ret;
}

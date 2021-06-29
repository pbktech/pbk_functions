<?php
add_action( 'wp_ajax_getSupportMod', 'getSupportMod' );

function getSupportMod(){
    if(!empty($_REQUEST['f']) && file_exists(dirname(__DIR__) . "/classes/support_mods/" . $_REQUEST['f'] . ".php")){
        include dirname(__DIR__) . "/classes/support_mods/" . $_REQUEST['f'] . ".php";
    }else{
        echo "<div class='alert alert-warning'>I can't seem to find what you're looking for.</div>";
    }
}
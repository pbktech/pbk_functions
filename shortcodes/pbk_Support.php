<?php
add_shortcode('show_pbk_restaurant_support', 'show_pbk_restaurant_support');

function show_pbk_restaurant_support(){
    $support = new PBKSupport();
    echo $support->supportRouter();
}
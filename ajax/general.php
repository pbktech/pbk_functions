<?php
add_action( 'wp_ajax_get_availableRestaurants', 'availableRestaurants' );

function availableRestaurants(){
    $restaurants = array();
    global $wp;
    global $wpdb;
    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        $result = $wpdb->get_results("SELECT restaurantName, restaurantID FROM pbc_pbrestaurants where isOpen = 1 AND restaurantCode!='SSC'");
    }else{
        $result = $wpdb->get_results("");
    }
    foreach($result as $r){
        $restaurants[] = (object)["id" => $r->restaurantID, "text" => $r->restaurantName];
    }
    echo json_encode((object)["results" => $restaurants, "pagination" => (object)["more" => true]]);
    wp_die();
}
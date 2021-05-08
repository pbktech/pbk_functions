<?php
add_action( 'wp_ajax_get_availableRestaurants', 'availableRestaurants' );
add_action( 'wp_ajax_get_restaurantOptions', 'restaurantOptions' );

function availableRestaurants(){
    $restaurants = array();
    global $wpdb;
    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        $result = $wpdb->get_results("SELECT restaurantName, restaurantID FROM pbc_pbrestaurants where isOpen = 1 AND restaurantCode!='SSC'");
    }else{
        $result = $wpdb->get_results("SELECT restaurantID,restaurantName FROM  pbc_pbrestaurants WHERE restaurantID IN (SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."')");
    }
    foreach($result as $r){
        $restaurants[] = (object)["id" => $r->restaurantID, "text" => $r->restaurantName];
    }
    header('Content-Type: application/json');
    echo json_encode((object)["results" => $restaurants, "pagination" => (object)["more" => true]]);
    wp_die();
}

function restaurantOptions(){
    $restaurants = array();
    $cu = wp_get_current_user();
    global $wpdb;
    $results = $wpdb->get_results("SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."'");
    foreach ($results as $r){
        $restaurants[] = $r->restaurantID;
    }
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles) || in_array($_REQUEST['restaurantID'], $restaurants, true)) {
        $options = $wpdb->get_row("SELECT options FROM pbc_pbrestaurants WHERE restaurantID = '" . $_REQUEST['restaurantID'] . "'");
    }else{
        wp_die();
    }
    header('Content-Type: application/json');
    echo json_encode($options);
    wp_die();
}
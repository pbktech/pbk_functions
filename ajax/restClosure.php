<?php
add_action( 'wp_ajax_get_closure_list', 'closureList' );
add_action( 'wp_ajax_add_new_closure', 'addClosure');

function closureList(){
    global $wpdb;
    $restClose=array();
    $closures = $wpdb->get_results("SELECT closureTime, reopenTime,restaurantName, ppc.restaurantID as 'restaurantID'  FROM pbc_pbr_closures ppc, pbc_pbrestaurants ppr WHERE ppc.restaurantID = ppr.restaurantID AND isDeleted = 0 AND closureTime >= CURRENT_DATE() ");
    if($closures) {
        foreach ($closures as $c) {

            $restClose[$c->closureTime]['closureTime'] = $c->closureTime;
            $restClose[$c->closureTime]['reopenTime'] = $c->reopenTime;
            $restClose[$c->closureTime]['RestaurantIDs'][] = array_keys(json_decode($c->restaurantID,true));
            $restClose[$c->closureTime]['RestaurantNames'][] = array_values(json_decode($c->restaurantID,true));
        }
    }
    return $restClose;
}

function addClosure($r){
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $tasks=new task_engine($mysqli);
    $startDate=date("Y-m-d",strtotime($_POST["startDate"]));
    $startTime=date("H:i:s",strtotime($_POST["startTime"]));
    $tasks->add_task(['what'=>'execBackground',
        'target'=>"/home/jewmanfoo/levelup-website-bot/change.sh ",
        'files' => json_encode($_POST['change']),
        'dueDate' => $startDate . " " . $startTime]);

}
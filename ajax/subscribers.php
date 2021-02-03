<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
add_action("wp_ajax_subscribers_get_trans", "subscribers_get_trans");
add_action("wp_ajax_subscribers_get_users", "subscribers_get_users");
//add_action("wp_ajax_nopriv_subscribers_get_trans", "subscribers_get_trans");

function subscribers_get_trans() {
    $data = array("data" => array());
    if(wp_verify_nonce( $_REQUEST['nonce'], '_get_trans_'.$_REQUEST['uis'] )) {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT concat('$', format(amount, 2)) as 'price',transactionType, transactionStatus, DATE_FORMAT(transactionTime, '%m/%d/%Y %r') as 'datetime' FROM pbc_subscriptions_transactions WHERE subscriptionID  = '" . $_REQUEST['uis'] . "'", ARRAY_A
        );
        if($results){
          foreach($results as $r) {
              $data['data'][] = $r;
          }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}
function subscribers_get_users() {
    global $wpdb;
    $data = array();
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM pbc_subscriptions_transactions WHERE subscriptionID  = %d",
            $_REQUEST['uis']
        )
    );
    if($results){
        $data = [];
    }
    echo json_encode($data);
    wp_die();
}

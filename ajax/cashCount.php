<?php
add_action( 'wp_ajax_addCashLog', 'addCashLog' );
add_action( 'wp_ajax_getCashLogs', 'getCashLogs' );
add_action( 'wp_ajax_getCashLog', 'getCashLog' );

function addCashLog(){
    global $wpdb;
    $response = ["status" => 200];
    $cu = wp_get_current_user();
    $wpdb->query(
        $wpdb->prepare(
            "
   INSERT INTO pbc_cash_log
   ( employeeName, restaurantID, userID, countType, cashCount )
   VALUES ( %s, %d, %d, %s, %s )
   ",
            array(
                $_REQUEST['firstName'] . " " . $_REQUEST['lastName'],
                $_REQUEST['restaurant'],
                $cu->ID,
                $_REQUEST['countType'],
                json_encode($_REQUEST['cash'])
            )
        )
    );
    if(empty($wpdb->insert_id)){
        $response = ["status" => 400, "msg" => "Save Failed: " . $wpdb->last_error];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    wp_die();
}

function getCashLogs(){
    global $wpdb;
    $data = array("data" => array());
    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        $result = $wpdb->get_results("SELECT restaurantName,countType,timeStamp,logID  FROM pbc_pbrestaurants pp, pbc_cash_log pcl where isOpen = 1 AND restaurantCode!='SSC' AND  pp.restaurantID = pcl.restaurantID AND DATE(timeStamp) =  CURDATE() ");
    }else{
        $result = $wpdb->get_results("SELECT restaurantName,countType,timeStamp,logID FROM  pbc_pbrestaurants pp, pbc_cash_log pcl WHERE pp.restaurantID = pcl.restaurantID AND pcl.restaurantID IN (SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."') AND DATE(timeStamp) = CURDATE()");
    }
    if ($result) {
        foreach ($result as $r) {
            $data['data'][] = [
                "restaurant" => $r->restaurantName,
                "countType" => ucwords(str_replace("_", " ", $r->countType)),
                "dateTime" => date("h:i a",strtotime($r->timeStamp)),
                "view" => '
                <div class="btn-group">
                    <a href="#" title="View" data-toggle="tooltip" class="text-success viewEntry" data-log="' . $r->logID . '" >
                    <i class="fas fa-eye" data-log="' . $r->logID . '"  ></i> View</a>
                </div>'
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}

function getCashLog(){
    global $wpdb;
    $data = $wpdb->get_row("SELECT * FROM pbc_cash_log WHERE logID='" . $_REQUEST['logID'] . "'");
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}


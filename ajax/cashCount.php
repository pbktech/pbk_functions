<?php
add_action( 'wp_ajax_addCashLog', 'addCashLog' );
add_action( 'wp_ajax_getCashLogs', 'getCashLogs' );
add_action( 'wp_ajax_getCashLog', 'getCashLog' );
add_action( 'wp_ajax_adminGetCashLogs', 'adminGetCashLogs' );

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
                "dateTime" => date("g:i a",strtotime($r->timeStamp)),
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

function adminGetCashLogs(){
    $fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
    global $wpdb;
    $data = array("data" => array());
    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        $result = $wpdb->get_results("SELECT restaurantName,countType,timeStamp,logID,cashCount  FROM pbc_pbrestaurants pp, 
         pbc_cash_log pcl where isOpen = 1 AND restaurantCode!='SSC' AND  pp.restaurantID = pcl.restaurantID AND 
         timeStamp BETWEEN '" . date("Y-m-d", strtotime($_REQUEST['startDate'])) . " 00:00:00' AND '" . date("Y-m-d", strtotime($_REQUEST['endDate'])) . " 23:59:59'");
        if ($result) {
            foreach ($result as $r) {
                $total = 0;
                $cash = json_decode($r->cashCount);
                foreach($cash as $c){
                    $total += $c->calc;
                }
                $data['data'][] = [
                    "restaurant" => $r->restaurantName,
                    "countType" => ucwords(str_replace("_", " ", $r->countType)),
                    "dateTime" => date("m/d/Y g:i a", strtotime($r->timeStamp)),
                    "total" => $fmt->formatCurrency($total, 'USD'),
                    "view" => '
                <div class="btn-group">
                    <a href="#" title="View" data-toggle="tooltip" class="text-success viewEntry" data-log="' . $r->logID . '" >
                    <i class="fas fa-eye" data-log="' . $r->logID . '"  ></i> View</a>
                </div>'
                ];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}

function ccModal(){
    ?>
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="receiptHeader"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container" id="receiptBody"></div>
                </div>
            </div>
        </div>
    </div>
<?php
}


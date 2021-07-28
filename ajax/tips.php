<?php
add_action( 'wp_ajax_tips_get_list', 'tips_get_list' );

function tips_get_list(){
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    $answer = [];
    $o = [];
    $authorized = 0;
    global $wpdb;
    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)  || in_array("author", $cu->roles)) {
        $authorized = 1;
    }else{
        $result = $wpdb->get_results("SELECT restaurantID FROM  pbc_pbrestaurants WHERE restaurantID IN (SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."')", ARRAY_N);
        if(count($result)!=0 && in_array($_REQUEST['restaurantID'],$result)){
            $authorized = 1;
        }
    }
    if($authorized === 0){
        $answer = ["message" => "You are not authorized to access this location.", "status" => 401];
    }else{
        $bot = "2020-05-01 00:00:00";
        $latest = date("Y-m-d", time() - 60 * 60 * 24) . " 23:59:59";
        $toast = new ToastReport($_REQUEST['restaurantID']);
        $toast->setStartTime(date("Y-m-d G:i:s", strtotime($bot)));
        $toast->setEndTime(date("Y-m-d G:i:s", strtotime($latest)));
        $orders = $toast->getTippedOrders();
        if(count($orders) !== 0){
            foreach ($orders as $order){
                $p = $toast->getPaymentInfo($order->ToastCheckID);
                $o[] = ["order" => [
                    "checkNumber" => $p->checkNumber,
                    "tabName" => $p->tabName,
                    "checkOpen" => date("m/d/Y g:i a", strtotime($p->openedDate)),
                    "checkClose" => date("m/d/Y g:i a", strtotime($p->closedDate)),
                    "checkPaid" => date("m/d/Y g:i a", strtotime($p->paidDate)),
                    "checkPayment" => $p->paymentType,
                    "checkTip" => $fmt->formatCurrency($p->tipAmount, "USD"),
                    "checkTotal" => $fmt->formatCurrency($p->totalAmount, "USD")

                ], "employees" => $toast->getClockedInEmployees("Team Member")];
            }
        }
        $count = (count($orders) === 0) ? " no orders " : count($orders) ." order(s)";
        $answer = ["message" => "There are " . $count . "  requiring assignment.", "status" => 200, "orders" => $o];
    }
    returnAJAXData($answer);
}

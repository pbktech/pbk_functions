<?php
add_action( 'wp_ajax_tips_get_list', 'tips_get_list' );
add_action( 'wp_ajax_assignTips', 'assignTips' );

function assignTips(){
    global $wpdb;
    $answer = ["status" => 200, "message" => []];
    $data = json_decode(stripslashes($_REQUEST['data']));
    $exists = $wpdb->get_var("SELECT count(*) as 'total' FROM pbc_TipDistribution WHERE orderGUID = " . $data->checkID);
    if(!empty($exists) && $exists->total !== 0 ){
        $answer = ["status" => 400, "message" => ["This check has already been distributed."]];
    }
    if($answer['status'] = 200){
        $people = array_unique(array_merge($data->worked, $data->driver));
        $tipShare = [];
        foreach ($people as $p){
            $tipShare[$p] = 0;
        }
        $cu = wp_get_current_user();
        $toast = new ToastReport();
        $toast->setRestaurantID($data->restaurantID);
        $workerPercent = 1 / count($data->worked);
        $driverPercent = 1 / count($data->driver);
        $order = $toast->getPaymentInfo($data->checkID);
        $dateOfBusiness = date("Y-m-d", strtotime($order->closedDate));
        $share = round(($order->tipAmount / 2), 2);
        foreach ($data->worked as $e) {
            $tipShare[$e] += round($share * $workerPercent, 3);
        }
        foreach ($data->driver as $e) {
            $tipShare[$e] += round($share * $driverPercent, 3);
            if ($e == "a0") {
                $tipShare[$e] += round($share * $driverPercent, 3);
            }
        }
        foreach ($tipShare as $e => $t) {
            $userID = json_encode(array("Initial" => array("Date" => date("Y-m-d G:i:s"), "User" => $cu->user_firstname . " " . $cu->user_lastname)));
            $wpdb->query($wpdb->prepare("INSERT INTO pbc_TipDistribution(employeeGUID,orderGUID,dateOfBusiness,tipAmount,userID)values(%s,%s,%s,%s,%s)", array($e, $data->checkID, $dateOfBusiness, $t, $userID)));
        }
        $wpdb->update(
            'pbc_ToastOrderPayment',
            array(
                'tipsAssigned' => '1'
            ),
            array('ToastCheckID' => $data->checkID)
        );
    }
    returnAJAXData($answer);
}

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
        $restaurants = [];
        $result = $wpdb->get_results("SELECT restaurantID FROM  pbc_pbrestaurants WHERE restaurantID IN (SELECT restaurantID  FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='".$cu->ID."')");
        if($result){
            foreach ($result as $r){
                $restaurants[] = $r->restaurantID;
            }
        }
        if(count($result)!=0 && in_array($_REQUEST['restaurantID'],$restaurants)){
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
                    "checkID" => $order->ToastCheckID,
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

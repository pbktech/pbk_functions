<?php
add_action("wp_ajax_om_get_orders", "om_get_orders");
add_action("wp_ajax_om_get_payments", "om_get_payments");
add_action("wp_ajax_om_duplicate", "om_duplicate");

function om_get_orders(){
    global $wpdb;
    $conditions = array();
    $data = array("data" => array());
    if(!empty($_REQUEST['name'])){
        $conditions[] = "(real_name1 LIKE '%" . $_REQUEST['name'] . "%' OR companyName LIKE '%" . $_REQUEST['name'] . "%')";
    }
    if(!empty($_REQUEST['startDate'])){
        $conditions[] = "dateDue >= '" . date("Y-m-d 00:00:00", strtotime($_REQUEST['startDate'])) . "'";
    }
    if(!empty($_REQUEST['endDate'])){
        $conditions[] = "dateDue <= '" . date("Y-m-d 23:59:59", strtotime($_REQUEST['endDate'])) . "'";
    }
    if(!empty($conditions)) {
        $conditions = " AND " . implode(" AND ", $conditions);
    }else{
        $conditions = "";
    }

   $results = $wpdb->get_results("SELECT real_name1, companyName, dateOrdered, dateDue, headerID, UuidFromBin(pmoh.publicUnique) as 'guid', orderType FROM pbc_minibar_user pmu, pbc_minibar_order_header pmoh WHERE pmu.id = pmoh.mbUserID" . $conditions);
    if ($results) {
        foreach ($results as $r) {
            switch ($r->orderType){
                case "minibar":
                    $url = "https://www.pbkminibar.com";
                    break;
                default:
                    $url = "https://www.pbkgrouporder.com";
            }
            $data['data'][] = [
                "name" => $r->real_name1,
                "company" => $r->companyName,
                "ordered" => date("m/d/y g:i A", strtotime($r->dateOrdered)),
                "due" => date("m/d/y g:i A", strtotime($r->dateDue)),
                "orderType" => $r->orderType,
                "actions" => '
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Actions
                    </button>
                     <div class="dropdown-menu">
                        <div style="text-align: left;padding-left: 5px;">
                            <a href="#" data-receiptLink="' . $url . '/receipt/' . $r->guid . '" title="Receipt" class="text-info showReceipt" data-toggle="tooltip" data-placement="bottom" ><i data-receiptLink="' . $url . '/receipt/' . $r->guid . '" class="fas fa-receipt"></i> Receipt</a>
                        </div>
                        <div style="text-align: left;padding-left: 5px;">
                            <a href="#" title="Refund" data-toggle="tooltip" class="text-danger refundOrder" data-orderid="' . $r->headerID .'"><i data-orderid="' . $r->headerID .'" class="fas fa-money-bill"></i> Refund</a>
                        </div>
                        <div style="text-align: left;padding-left: 5px;">
                            <a href="#" title="Reorder" data-toggle="tooltip" class="text-success duplicateOrder" data-orderID="' . $r->headerID .'" ><i class="fas fa-clone"></i> Duplicate</a>
                        </div>
                     </div>
                 </div>'
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}

function om_duplicate(){
    $now = date("Y-m-d H:i:s");
    $data = [];
    $mysqli = new mysqli(DB_HOST,DB_USER, DB_PASSWORD, DB_NAME);
    $mysqli->set_charset('utf8mb4');
    global $wpdb;
    $oldOrder = new PBKOrder($mysqli);
    $oldOrder->setOrderID($_REQUEST['headerID']);
    $orderHeader = $oldOrder->returnHeaderInfo();
    $toastOrder = new ToastOrder($orderHeader->GUID);
    $toastOrder->setOrderID($orderHeader->headerID);
    $toastOrder->setUserID($orderHeader->mbUserID);
    $checks = $toastOrder->returnOrderChecks();
    $q = $wpdb->get_row( "SELECT mbUserID, minibarID, orderType, isGroup, payerType, maximumCheck, defaultPayment, messageToUser, deliveryInstructions, fulfillment, company FROM pbc_minibar_order_header pmoh, pbc_minibar pm WHERE pm.idpbc_minibar = pmoh.minibarID AND headerID = '" . $_REQUEST['headerID'] . "'");
    $delDay = strtotime($_REQUEST['newDate'] . " " .$_REQUEST['newTime']);
    $fulfillment = $q->fulfillment === "pickup" ? "pickup" : "delivery";
    if($fulfillment === "delivery"){
        $cutoff = $delDay - 3600;
    }else{
        $cutoff = $delDay - 600;
    }
    $order = new PBKOrder($mysqli);
    $headerInfo['mbUserID'] = $q->mbUserID;
    $headerInfo['minibarID'] = $q->minibarID;
    $headerInfo['deliveryDate'] = date("Y-m-d G:i:s", $delDay);
    $headerInfo['orderType'] = $q->orderType;
    $headerInfo['isGroup'] = $q->isGroup;
    $headerInfo['payerType'] = $q->payerType;
    $headerInfo['defaultPayment'] = $q->defaultPayment;
    $headerInfo['maximumCheck'] = empty($q->maximumCheck) ? null : $q->maximumCheck;
    if ($orderID = $order->createOrderHeader($headerInfo)) {
        $order->setOrderID($orderID);
        $headerGUID = $order->getGUID();
        if(!empty($q->delInstructions)){
            $order->updateOrderField("deliveryInstructions",$q->delInstructions );
        }
        if(!empty($q->delInstructions)){
            $order->updateOrderField("messageToUser",$q->messageToUser );
        }
    } else {
        $data = ["status" => 400, "msg" => "Failed to create order", "header" => $headerInfo];
        returnAJAXData($data);
    }
    $check = new PBKCheck($mysqli);
    $checks = $wpdb->get_results("SELECT mbUserID, tabName, subtotal, tax, smsConsent, checkID FROM pbc_minibar_order_check WHERE mbOrderID = '" . $_REQUEST['headerID'] . "'");
    if($checks){
        foreach($checks as $c){
            $h = array(
                'orderHeaderID' => $orderID,
                'mbUserID' => $c->mbUserID,
                'name' => $c->tabName,
                'subtotal' => round($c->subtotal, 2),
                'tax' => round($c->tax, 2),
                'smsConsent' => $c->smsConsent
            );
            if ($orderCheckID = $check->createCheckHeader($h)) {
                $items = $wpdb->get_results("SELECT itemID,itemName,itemPrice,quantity,itemGUID FROM pbc_minibar_order_items WHERE checkID = '" . $c->checkID . "'");
                if($items){
                    $insertItem = $mysqli->prepare("INSERT INTO pbc2 . pbc_minibar_order_items(checkID, itemName, itemPrice, itemGUID, quantity) VALUES (?,?,?,?,?)");
                    foreach($items as $i){
                        $insertItem->bind_param('sssss',
                            $orderCheckID,
                            $i->itemName,
                            $i->itemPrice,
                            $i->itemGUID,
                            $i->quantity
                        );
                        $insertItem->execute();
                        $mods = $wpdb->get_results("SELECT * FROM pbc_minibar_order_mods WHERE itemID = '" . $i->itemID . "'");
                        if($mods){
                            $insertMod = $mysqli->prepare("INSERT INTO pbc2 . pbc_minibar_order_mods(itemID, modName, modPrice, modGUID) VALUES (?,?,?,?)");
                            foreach($mods as $m){
                                $insertMod->bind_param('ssss',
                                    $insertItem->insert_id,
                                    $m->modName,
                                    $m->modPrice,
                                    $m->modGUID
                                );
                                $insertMod->execute();
                            }
                        }
                        $discounts = $wpdb->get_results("SELECT * FROM pbc_minibar_order_discount WHERE checkID = '" . $c->checkID . "'");
                        if($discounts) {
                            $insertDiscount = $mysqli->prepare("INSERT INTO pbc2 . pbc_minibar_order_discount (checkID, discountName, discountGUID, discountAmount, promoCode, discountType) VALUES (?,?,?,?,?,?)");
                            foreach ($discounts as $d) {
                                $insertDiscount->bind_param('ssssss',
                                    $orderCheckID,
                                    $d->discountName,
                                    $d->discountGUID,
                                    $d->discountAmount,
                                    $d->promoCode,
                                    $d->discountType
                                );
                                $insertDiscount->execute();
                            }
                        }
                        $payments = $wpdb->get_results("SELECT * FROM pbc_minibar_order_payment WHERE mbCheckID = '" . $c->checkID . "' AND appliesTo !='Header'");
                        if($payments){
                            $insertPayment = $mysqli->prepare("INSERT INTO pbc_minibar_order_payment (mbCheckID, mbUserID, paymentType, paymentDate, paymentAmount, paymentStatus, cardNum, appliesTo) VALUES (?,?,?,?,?,?,?,?)");
                            foreach($payments as $p){
                                $insertPayment->bind_param('sssssss',
                                    $orderCheckID,
                                    $p->mbUserID,
                                    $p->paymentType,
                                    $now,
                                    $p->paymentAmount,
                                    $p->paymentStatus,
                                    $p->cardNum,
                                    $p->appliesTo
                                );
                                $insertPayment->execute();
                            }
                        }
                    }
                }
            }
        }
    }else{
        $data = ["status" => 200, "msg" => "The order for " . $q->company . " has been duplicated."];
        returnAJAXData($data);
    }
    $tasks = new task_engine($mysqli);
    $tasks->add_task(['what'=>'execBackground',
        'target'=>"/home/jewmanfoo/toast-api/postMinibar.sh ",
        'files' => $orderID,
        'dueDate' => date('Y-m-d H:i:s',$cutoff)]);
    
    returnAJAXData($data);
}

function om_get_payments(){
    global $wpdb;

}
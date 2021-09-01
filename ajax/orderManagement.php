<?php
add_action("wp_ajax_om_get_orders", "om_get_orders");
add_action("wp_ajax_om_get_payments", "om_get_payments");
add_action("wp_ajax_om_duplicate", "om_duplicate");
add_action("wp_ajax_om_cancel", "om_cancel");

function om_get_orders() {
    global $wpdb;
    $conditions = array();
    $data = array("data" => array());
    if (!empty($_REQUEST['name'])) {
        $conditions[] = "(real_name1 LIKE '%" . $_REQUEST['name'] . "%' OR companyName LIKE '%" . $_REQUEST['name'] . "%')";
    }
    if (!empty($_REQUEST['startDate'])) {
        $conditions[] = "dateDue >= '" . date("Y-m-d 00:00:00", strtotime($_REQUEST['startDate'])) . "'";
    }
    if (!empty($_REQUEST['endDate'])) {
        $conditions[] = "dateDue <= '" . date("Y-m-d 23:59:59", strtotime($_REQUEST['endDate'])) . "'";
    }
    if (!empty($conditions)) {
        $conditions = " AND " . implode(" AND ", $conditions);
    } else {
        $conditions = "";
    }

    $results = $wpdb->get_results("SELECT real_name1, companyName, dateOrdered, dateDue, headerID, UuidFromBin(pmoh.publicUnique) as 'guid', orderType, isDeleted FROM pbc_minibar_user pmu, pbc_minibar_order_header pmoh WHERE pmu.id = pmoh.mbUserID" . $conditions);
    if ($results) {
        foreach ($results as $r) {
            switch ($r->orderType) {
                case "minibar":
                    $url = "https://www.pbkminibar.com";
                    break;
                default:
                    $url = "https://www.pbkgrouporder.com";
            }
            if ($r->isDeleted === '0') {
                if (time() < strtotime($r->dateDue)) {
                    $cancel = '<a href="#" title="Refund" data-toggle="tooltip" class="text-danger cancelOrder" data-orderid="' . $r->headerID . '"><i data-orderid="' . $r->headerID . '" class="far fa-trash-alt"></i> Cancel</a>';
                } else {
                    $cancel = '<a href="#" title="Refund" data-toggle="tooltip" class="text-danger refundOrder" data-orderid="' . $r->headerID . '"><i data-orderid="' . $r->headerID . '" class="fas fa-money-bill"></i> Refund</a>';
                }
            } else {
                $cancel = 'CANCELED';
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
                        <div style="text-align: left;padding-left: 5px;">' . $cancel . '</div>
                        <div style="text-align: left;padding-left: 5px;">
                            <a href="#" title="Reorder" data-toggle="tooltip" class="text-success duplicateOrder" data-orderID="' . $r->headerID . '" ><i class="fas fa-clone"></i> Duplicate</a>
                        </div>
                     </div>
                 </div>',
                "deleted" => $r->isDeleted,
                "microTime" => strtotime($r->dateDue) * 1000
            ];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    wp_die();
}

function om_duplicate() {
    $now = date("Y-m-d H:i:s");
    $data = [];
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $mysqli->set_charset('utf8mb4');
    global $wpdb;
    $q = $wpdb->get_row("SELECT mbUserID, minibarID, orderType, isGroup, payerType, maximumCheck, defaultPayment, messageToUser, deliveryInstructions, fulfillment, company, defaultPromo FROM pbc_minibar_order_header pmoh, pbc_minibar pm WHERE pm.idpbc_minibar = pmoh.minibarID AND headerID = '" . $_REQUEST['headerID'] . "'");
    $oldOrder = new PBKOrder($mysqli);
    $oldOrder->setOrderID($_REQUEST['headerID']);
    $orderHeader = $oldOrder->returnHeaderInfo();
    $toast = new Toast($orderHeader->GUID);
    $toastOrder = new ToastOrder($orderHeader->GUID);
    $toastOrder->setOrderID($orderHeader->headerID);
    $toastOrder->setUserID($orderHeader->mbUserID);
    $diningOption = $toastOrder->returnDiningOption();
    $checks = $toastOrder->returnOrderChecks();
    if (!empty($q->defaultPromo)) {
        $toastOrder->setDefaultPromo($q->defaultPromo);
     //   $disCheck = [ "order" => $checks, "promoCode" => $q->defaultPromo];
     //   $discount = $toast->postOrder("applicableDiscounts", $disCheck);
    }
    $orderObject = (object)["entityType" => "Order", "diningOption" => ["guid" => $diningOption, "entityType" => "DiningOption"], "checks" => $checks->order];
    $response = $toast->postOrder("prices", $orderObject);
 //   $orderInfo = json_decode($response);
    $delDay = strtotime($_REQUEST['newDate'] . " " . $_REQUEST['newTime']);
    $fulfillment = $q->fulfillment === "pickup" ? "pickup" : "delivery";
    if ($fulfillment === "delivery") {
        $cutoff = $delDay - 3600;
    } else {
        $cutoff = $delDay - 600;
    }
    $order = new PBKOrder($mysqli);
    $headerInfo['mbUserID'] = $q->mbUserID;
    $headerInfo['minibarID'] = $q->mbUserID;
    $headerInfo['deliveryDate'] = $delDay;
    $headerInfo['deliveryDate'] = date("Y-m-d G:i:s", $delDay);
    $headerInfo['orderType'] = $q->orderType;
    $headerInfo['isGroup'] = $q->isGroup;
    $headerInfo['payerType'] = $q->payerType;
    $headerInfo['defaultPayment'] = $q->defaultPayment;
    $headerInfo['maximumCheck'] = empty($q->maximumCheck) ? null : $q->maximumCheck;
    if ($orderID = $order->createOrderHeader($headerInfo)) {
        $order->setOrderID($orderID);
        $headerGUID = $order->getGUID();
        if (!empty($q->delInstructions)) {
            $order->updateOrderField("deliveryInstructions", $q->delInstructions);
        }
        if (!empty($q->delInstructions)) {
            $order->updateOrderField("messageToUser", $q->messageToUser);
        }
    } else {
        $data = ["status" => 400, "msg" => "Failed to create order", "header" => $headerInfo];
        returnAJAXData($data);
    }
    $check = new PBKCheck($mysqli);
    foreach ($response->checks as $c){
        $h = array(
            'orderHeaderID' => $orderID,
            'mbUserID' => $q->mbUserID,
            'name' => $c->tabName,
            'subtotal' => round($c->totalAmount, 2),
            'tax' => round($c->taxAmount, 2),
            'smsConsent' => 0
        );
        if ($orderCheckID = $check->createCheckHeader($h)) {
            foreach ($c->selections as $i) {
                if($i->item->guid === "7a6cf320-4afa-4f84-97a9-a37b3a287aca"){continue;}
                $wpdb->query($wpdb->prepare("INSERT INTO pbc2 . pbc_minibar_order_items(checkID, itemName, itemPrice, itemGUID, quantity) VALUES (%d,%s,%s,%s,%d)",
                    $orderCheckID,
                    $i->displayName,
                    $i->receiptLinePrice,
                    $i->itemGroup->guid . "/" . $i->item->guid,
                    $i->quantity
                ));
                $newItemID = $wpdb->insert_id;
                if (!empty($i->modifiers)) {
                    foreach ($i->modifiers as $m) {
                        if($m->selectionType !== "SPECIAL_REQUEST") {
                            $wpdb->query($wpdb->prepare("INSERT INTO pbc2 . pbc_minibar_order_mods(itemID, modName, modPrice, modGUID) VALUES (%d,%s,%s,%s)",
                                $newItemID,
                                $m->displayName,
                                $m->receiptLinePrice,
                                $m->optionGroup->guid . "/" . $m->item->guid
                            ));
                        }
                    }
                }
            }
            if(!empty($c->appliedDiscounts)){
                foreach ($c->appliedDiscounts as $d){
                    $wpdb->query($wpdb->prepare("INSERT INTO pbc2 . pbc_minibar_order_discount (checkID, discountName, discountGUID, discountAmount, promoCode, discountType) VALUES (%d,%s,%s,%s,%s,%s)",
                        $orderCheckID,
                        $d->name,
                        $d->discount->guid,
                        $d->discountAmount,
                        empty($d->appliedPromoCode) ? "Contract" : $d->appliedPromoCode,
                        "system"
                    ));
                }
            }
            $payment = new PBKPayment($mysqli);
            $payment->setPaymentID($q->defaultPayment);
            $paymentInfo = $payment->returnPayementInfo();
            $args = array(
                'mbCheckID' => $orderCheckID,
                'mbUserID' => $q->mbUserID,
                'paymentType' => 'Prepay',
                'paymentDate' => date("Y-m-d G:i:s"),
                'paymentAmount' => $c->totalAmount,
                'paymentStatus' => "approved",
                'authorization' => json_encode(array()),
                'fdsToken' => json_encode(array()),
                'cardNum' => date('Ymd'),
                'transactionID' => json_encode(array()),
                'addressID' => $paymentInfo->addressID,
                'tipAmount' => $paymentInfo->tipAmount
            );
            $info = $payment->addPaymentToTable($args);
        }
    }
    /*
    $orderHeader = $oldOrder->returnHeaderInfo();
    $toastOrder = new ToastOrder($orderHeader->GUID);
    $toastOrder->setOrderID($orderHeader->headerID);
    $toastOrder->setUserID($orderHeader->mbUserID);
    $checks = $toastOrder->returnOrderChecks();
    $headerInfo['mbUserID'] = $q->mbUserID;
    $headerInfo['minibarID'] = $q->minibarID;
    $headerInfo['deliveryDate'] = date("Y-m-d G:i:s", $delDay);
    $headerInfo['orderType'] = $q->orderType;
    $headerInfo['isGroup'] = $q->isGroup;
    $headerInfo['payerType'] = $q->payerType;
    $headerInfo['defaultPayment'] = $q->defaultPayment;
    $headerInfo['maximumCheck'] = empty($q->maximumCheck) ? null : $q->maximumCheck;
    $checks = $wpdb->get_results("SELECT mbUserID, tabName, subtotal, tax, smsConsent, checkID FROM pbc_minibar_order_check WHERE mbOrderID = '" . $_REQUEST['headerID'] . "'");
    if ($checks) {
        foreach ($checks as $c) {
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
                if ($items) {
                    foreach ($items as $i) {
                        $wpdb->query($wpdb->prepare("INSERT INTO pbc2 . pbc_minibar_order_items(checkID, itemName, itemPrice, itemGUID, quantity) VALUES (%d,%s,%s,%s,%d)",
                            $orderCheckID,
                            $i->itemName,
                            $i->itemPrice,
                            $i->itemGUID,
                            $i->quantity
                        ));
                        $mods = $wpdb->get_results("SELECT * FROM pbc_minibar_order_mods WHERE itemID = '" . $i->itemID . "'");
                        $discounts = $wpdb->get_results("SELECT * FROM pbc_minibar_order_discount WHERE checkID = '" . $c->checkID . "'");
                        if ($discounts) {
                            foreach ($discounts as $d) {
                                $wpdb->query($wpdb->prepare("INSERT INTO pbc2 . pbc_minibar_order_discount (checkID, discountName, discountGUID, discountAmount, promoCode, discountType) VALUES (%d,%s,%s,%s,%s,%s)",
                                    $orderCheckID,
                                    $d->discountName,
                                    $d->discountGUID,
                                    $d->discountAmount,
                                    $d->promoCode,
                                    $d->discountType
                                )
                                );
                            }
                        }
                        $payments = $wpdb->get_results("SELECT * FROM pbc_minibar_order_payment WHERE mbCheckID = '" . $c->checkID . "' AND (appliesTo is null OR appliesTo ='Check')");
                        if ($payments) {
                            foreach ($payments as $p) {
                                $wpdb->query($wpdb->prepare("INSERT INTO pbc2 . pbc_minibar_order_payment (mbCheckID, mbUserID, paymentType, paymentDate, paymentAmount, paymentStatus, cardNum, appliesTo) VALUES (%d,%s,%s,%s,%s,%s,%s,%s)",
                                    $orderCheckID,
                                    $p->mbUserID,
                                    $p->paymentType,
                                    $now,
                                    $p->paymentAmount,
                                    $p->paymentStatus,
                                    $p->cardNum,
                                    $p->appliesTo
                                )
                                );
                            }
                        }
                    }
                }
            }
        }
    } else {
        $data = ["status" => 400, "msg" => "There were no checks found"];
        returnAJAXData($data);
    }
*/
    $tasks = new task_engine($mysqli);
    $tasks->add_task(['what' => 'execBackground',
        'target' => "sh /home/jewmanfoo/toast-api/postMinibar.sh ",
        'files' => $orderID,
        'dueDate' => date('Y-m-d H:i:s', $cutoff)]);
   $data = ["status" => 200, "msg" => "The order for " . $q->company . " has been duplicated and scheduled for " . date("m/d/Y h:i a", $delDay) . "."];
    returnAJAXData($data);
}

function om_get_payments() {
    global $wpdb;

}

function om_cancel() {
    global $wpdb;
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $wpdb->update("pbc_minibar_order_header", array("isDeleted" => 1), array("headerID" => $_REQUEST['headerID']));
    if (!empty($wpdb->last_error)) {
        returnAJAXData(['status' => 400, 'msg' => 'Removing order failed: ' . $wpdb->last_error]);
    }
    $taskID = $wpdb->get_var("SELECT id from pbc_tasks WHERE files = '" . $_REQUEST['headerID'] . "' AND target = '/home/jewmanfoo/toast-api/postMinibar.sh '");
    $tasks = new task_engine($mysqli);
    $tasks->delete_task($taskID);
    returnAJAXData(['status' => 200, 'msg' => 'The order has been canceled.']);

}
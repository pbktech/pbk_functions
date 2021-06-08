<?php


final class PBKReceipt {



    public function getReceiptItems(): array{
        $stmt = $mysqli->prepare("SELECT * FROM pbc_minibar_order_header WHERE publicUnique = UuidToBin(?)");
        $stmt->bind_param("s", $request->guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $orderHeaderID = $row;
        $orderCheckID = [];
        $orderPayment = [];

        if (empty($orderHeaderID)) {
            $stmt = $mysqli->prepare("SELECT * FROM pbc_minibar_order_check,pbc_minibar_order_header, pbc_minibar pm WHERE mbOrderID = headerID AND pbc_minibar_order_check.publicUnique = UuidToBin(?) AND miniBarID=pm.idpbc_minibar");
            $stmt->bind_param("s", $request->guid);
        } else {
            if(!empty($row->defaultPayment)){
                $q = $mysqli->prepare("SELECT paymentID, mbCheckID, mbUserID, paymentType, paymentDate, paymentAmount, paymentStatus, authorization, fdsToken, cardNum FROM pbc_minibar_order_payment WHERE paymentID = ?");
                $q->bind_param('s',$row->defaultPayment);
                $q->execute();
                $pResult = $q->get_result();
                while($pr = $pResult->fetch_object()){
                    $orderPayment[] = $pr;
                }
            }
            $stmt = $mysqli->prepare("SELECT * FROM pbc_minibar_order_check,pbc_minibar_order_header, pbc_minibar pm WHERE mbOrderID = headerID AND mbOrderID = ? AND miniBarID=pm.idpbc_minibar ");
            $stmt->bind_param("s", $orderHeaderID->headerID);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_object()) {
            $orderCheckID[] = $row;
        }

        $orderChecks = array();
        foreach ($orderCheckID as $check) {
            $checkItems = array();
            $payments = array();
            $discounts = array();
            $stmt = $mysqli->prepare("SELECT * FROM pbc_minibar_order_items WHERE checkID=?");
            $stmt->bind_param("s", $check->checkID);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_object()) {
                $modItems = array();
                $stmt1 = $mysqli->prepare("SELECT * FROM pbc_minibar_order_mods WHERE itemID=?");
                $stmt1->bind_param("s", $row->itemID);
                $stmt1->execute();
                $result1 = $stmt1->get_result();
                $modPrice = 0;
                while ($row1 = $result1->fetch_object()) {
                    $modPrice+=$row1->modPrice;
                    $modItems[] = array("name" => $row1->modName, "price" => $row1->modPrice, "guid" => $row1->modGUID);
                }

                $checkItems[] = array("name" => $row->itemName, "price" => ($row->itemPrice + $modPrice) * $row->quantity, "quantity" => $row->quantity, "guid" => $row->itemGUID, "mods" => $modItems);
            }

            $stmt = $mysqli->prepare("SELECT * FROM pbc_minibar_order_discount WHERE checkID=?");
            $stmt->bind_param("s", $check->checkID);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_object()) {
                $discounts[] = $row;
            }

            $stmt = $mysqli->prepare("SELECT UuidFromBin(publicUnique) as 'guid', paymentType, paymentDate, paymentAmount, authorization, cardNum, tipAmount FROM pbc_minibar_order_payment WHERE mbCheckID=?");
            $stmt->bind_param("s", $check->checkID);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_object()) {
                $payments[] = $row;
            }

            $orderChecks[] = ["tab" => $check->tabName, "ordered" => date("m/d/y g:i:s a", strtotime($check->checkAdded)), "items" => $checkItems, "discounts" => $discounts, "payments" => $payments, "totals" => array("subtotal" => $check->subtotal, "tax" => $check->tax)];
        }

        return array("checks" => $orderChecks, "payment" => $orderPayment, "minibar" => $orderCheckID[0]->company, "delivery" => date("m/d/y g:i:s a", strtotime($orderCheckID[0]->dateDue)));
    }

}
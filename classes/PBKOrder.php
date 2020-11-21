<?php

final class PBKOrder {

    public $today;
    private $mysqli;
    private $payment;
    private $contact;
    private $orderID;

    public function __construct($mysql) {
        if (!isset($mysql)) {
            $report = new ToastReport;
            $m = "Users class failed to construct. Missing MySQLi object.";
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            exit;
        }
        $this->setMysqli($mysql);
        $this->setToday(date(TODAY_FORMAT));
    }

    public function setMysqli(mysqli $mysql): void {
        $this->mysqli = $mysql;
    }

    private function setToday(string $date): void {
        $this->today = $date;
    }

    public function createOrderHeader(array $headerInfo): ?int {
        $stmt = $this->mysqli->prepare("INSERT INTO pbc_minibar_order_header (mbUserID, minibarID, dateDue, orderType, dateOrdered,isGroup,payerType,defaultPayment,maximumCheck) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?) ");
        $stmt->bind_param("sssssssss",
            $headerInfo['mbUserID'],
            $headerInfo['minibarID'],
            $headerInfo['deliveryDate'],
            $headerInfo['orderType'],
            $this->today,
            $headerInfo['isGroup'],
            $headerInfo['payerType'],
            $headerInfo['defaultPayment'],
            $headerInfo['maximumCheck']
        );
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to create order<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        if (isset($stmt->insert_id) && is_numeric($stmt->insert_id)) {
            $this->setOrderID($stmt->insert_id);
            return $stmt->insert_id;
        }
        return false;
    }

    public function setOrderID(int $orderID): void {
        $this->orderID = $orderID;
    }

    public function getGUID(): string {
        $stmt = $this->mysqli->prepare("SELECT UuidFromBin(publicUnique) as 'orderGUID' FROM pbc_minibar_order_header WHERE headerID = ?");
        $stmt->bind_param("s", $this->orderID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if ($row->orderGUID) {
            return $row->orderGUID;
        }
        return false;
    }

    public function checkGUID(string $guid): int {
        $stmt = $this->mysqli->prepare("SELECT headerID FROM pbc_minibar_order_header WHERE publicUnique = UuidToBin(?)");
        $stmt->bind_param("s", $guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->headerID)) {
            $this->setOrderID($row->headerID);
            return $row->headerID;
        }
        return false;
    }

    public function returnHeaderInfo(): ?object {
        $stmt = $this->mysqli->prepare("SELECT * FROM pbc_minibar_order_header pmoh,pbc_pbrestaurants pbr, pbc_minibar pm WHERE pm.restaurantID = pbr.restaurantID AND pm.idpbc_minibar = pmoh.minibarID AND  headerID=?");
        $stmt->bind_param("s", $this->orderID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->payerType)) {
            return $row;
        }
        return null;
    }

    public function setContact(object $contactInfo): void {
        $this->contact = $contactInfo;
    }

    public function setPayment(object $paymentInfo): void {
        $this->payment = $paymentInfo;
    }
}
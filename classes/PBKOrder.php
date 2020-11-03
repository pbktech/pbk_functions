<?php

final class PBKOrder{

    private $mysqli;
    private $payment;
    private $contact;
    private $orderID;
    public $today;

    public function __construct($mysql){
        if (!isset($mysql)) {
            $report = new ToastReport;
            $m = "Users class failed to construct. Missing MySQLi object.";
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            exit;
        }
        $this->setMysqli($mysql);
        $this->setToday(date(TODAY_FORMAT));
    }

    public function createOrderHeader(array $headerInfo): ?int{
        $report = new ToastReport;
        $m = "HEader Info: " . print_r($headerInfo,true);
        $report->reportEmail("errors@theproteinbar.com", $m, "User error");
        $stmt = $this->mysqli->prepare("INSERT INTO pbc_minibar_order_header (mbUserID, minibarID, dateDue, orderType, dateOrdered,isGroup,payerType,defaultPayment) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ");
        $stmt->bind_param("ssssssss",
            $headerInfo['mbUserID'],
            $headerInfo['minibarID'],
            $headerInfo['deliveryDate'],
            $headerInfo['orderType'],
            $this->today,
            $headerInfo['isGroup'],
            $headerInfo['payerType'],
            $headerInfo['defaultPayment']);
        $stmt->execute();
        if(isset($stmt->insert_id) && is_numeric($stmt->insert_id)){
            $this->setOrderID($stmt->insert_id);
            return $stmt->insert_id;
        }
        $report = new ToastReport;
        $m = "MySQL Error: " . $stmt->error;
        $report->reportEmail("errors@theproteinbar.com", $m, "User error");
        return false;
    }

    public function getGUID(): string{
        $stmt = $this->mysqli->prepare("SELECT UuidFromBin(publicUnique) as 'orderGUID' FROM pbc_minibar_order_header WHERE headerID = ?");
        $stmt->bind_param("s", $this->orderID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if($row->orderGUID){
            return $row->orderGUID;
        }
        return false;
    }

    public function checkGUID(string $guid): int{
        $stmt = $this->mysqli->prepare("SELECT headerID FROM pbc_minibar_order_header WHERE publicUnique = UuidToBin(?)");
        $stmt->bind_param("s", $guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if(isset($row->headerID)){
            $this->setOrderID($row->headerID);
            return $row->headerID;
        }
        return false;
    }

    public function checkOrderLink(string $link): ?object{
        $stmt = $this->mysqli->prepare("SELECT orderHeaderID FROM pbc_minibar_users_links WHERE linkPurpose = 'group_order' AND linkHEX=? AND linkExpires >= NOW()");
        $stmt->bind_param("s", $link);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if(isset($row->linkID)){
            $this->setOrderID($row->orderHeaderID);
            return (object)["headerGUID" => $this->getGUID(), "payer" => $row->payerType];
        }
        return null;
    }

    public function setOrderID(int $orderID): void{
        $this->orderID = $orderID;
    }

    public function setContact(object $contactInfo): void{
        $this->contact = $contactInfo;
    }

    public function setOrder(object $orderInfo): void{
        $this->contact = $orderInfo;
    }

    public function setPayment(object $paymentInfo): void{
        $this->payment = $paymentInfo;
    }

    private function setToday(string $date): void{
        $this->today = $date;
    }

    public function setMysqli(mysqli $mysql): void {
        $this->mysqli = $mysql;
    }
}
<?php


class HouseAccount extends PBKPayment{

    private $guid;

    public function __construct($mysqli){
        parent::__construct($mysqli);

    }

    public function validateHouseAccount(){
        if($mysqli=$this->getMysqli()) {
            $stmt = $mysqli->prepare("SELECT accountID, maxIndividualOrder FROM pbc_minibar_house_accounts WHERE publicUnique=UuidToBin(?) AND onHold=0");
            $stmt->bind_params('s', $this->guid);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_object();
            if (!empty($row)) {
                $args = array();
                $args['mbCheckID'] = null;
                $args['mbUserID'] = $this->userID;
                $args['paymentType'] = "House";
                $args['paymentDate'] = date('Y-m-d H:i:s');
                $args['paymentAmount'] = .01;
                $args['paymentStatus'] = "approved";
                $args['authorization'] = json_encode(array());
                $args['fdsToken'] = json_encode(array());
                $args['cardNum'] = $row->accountID;
                $args['transactionID'] = json_encode(array());
                $args['addressID'] = $this->billingID;
                $info = $this->addPaymentToTable($args);
                return (object)["response" => array("transaction_status" => "approved"), "info" => $info];
            }else{
                return (object)["response" => array("transaction_status" => "failed")];
            }
        }
    }

    public function setGUID(string $guid): void{
        $this->guid = $guid;
    }
}
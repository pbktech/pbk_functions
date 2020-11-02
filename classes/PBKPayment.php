<?php


class PBKPayment
{
    protected $card;
    protected $billing;
    protected $billingID;
    private $paymentType;
    protected $mysqli;
    protected $config;
    protected $billAmount;
    protected $billingName;
    protected  $userID;

    public function __construct($mysql){
        if (!isset($mysql)) {
            $report = new ToastReport;
            $m = "Users class failed to construct. Missing MySQLi object.";
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            exit;
        }
        $this->setMysqli($mysql);
        $this->setToday(date(TODAY_FORMAT));
        $this->setConfig();
    }

    public function setCard(object $card): void {
        $this->card = $card;
    }

    private function setConfig($sandbox=0){
        if(!defined('ABSPATH')){
            if (file_exists('/var/www/html/c2.theproteinbar.com')) {
                define('ABSPATH', '/var/www/html/c2.theproteinbar.com/');
            }else {
                define('ABSPATH', '/var/www/html/c2dev.theproteinbar.com/');
            }
        }
        $default = dirname(ABSPATH) . '/config.json';
        $this->config=json_decode(file_get_contents($default));
        if($sandbox==0){
            $this->ToastClient=$this->config->ToastClient;
            $this->ToastSecret=$this->config->ToastSecret;
            $this->url=$this->config->ToastURL;
        }else {
            $this->ToastClient=$this->config->sbToastClient;
            $this->ToastSecret=$this->config->sbToastSecret;
            $this->url=$this->config->sbToastURL;
        }
        $this->localDB=$this->config->dBase;
    }

    protected function addPaymentToTable(array $args): array{
        $stmt = $this->mysqli->prepare("INSERT INTO pbc_minibar_order_payment (mbCheckID, mbUserID, paymentType, paymentDate, paymentAmount, paymentStatus, authorization, fdsToken, cardNum, transactionID, addressID) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssssss',
            $args['mbCheckID'],
            $args['mbUserID'],
            $args['paymentType'],
            $args['paymentDate'],
            $args['paymentAmount'],
            $args['paymentStatus'],
            $args['authorization'],
            $args['fdsToken'],
            $args['cardNum'],
            $args['transactionID'],
            $args['addressID']
        );
        $stmt->execute();

        if (isset($stmt->error) && $stmt->error !== '') {
            return [$stmt->error];
        }

        if (!empty($stmt->insert_id)) {
            return ['status' => 200, "id" => $stmt->insert_id];
        }
        return ["status" => 400, "msg" => "Insert Failure", "request" => $args];
    }

    public function setBilling(int $billingID): void {
        $this->billingID=$billingID;
        $stmt=$this->mysqli->prepare("SELECT * FROM pbc_minibar_users_address WHERE addressID=? AND addressType='billing'");
        $stmt->bind_param('s',$billingID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $this->billing = $row;
    }

    protected function getCCType($cardNumber) {
        $cardType = 'Unknown';
        // Remove non-digits from the number
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        switch ($cardNumber) {
            case(0 === strpos($cardNumber, "4")):
                $cardType = 'Visa';
                break;
            case(preg_match('/^5[1-5]/', $cardNumber) >= 1):
                $cardType = 'Mastercard';
                break;
            case(preg_match('/^3[47]/', $cardNumber) >= 1):
                $cardType = 'American Express';
                break;
            case(preg_match('/^3(?:0[0-5]|[68])/', $cardNumber) >= 1):
                $cardType = 'Diners Club';
                break;
            case(preg_match('/^6(?:011|5)/', $cardNumber) >= 1):
                $cardType = 'Discover';
                break;
            case(preg_match('/^(?:2131|1800|35\d{3})/', $cardNumber) >= 1):
                $cardType = 'JCB';
                break;
            default:
        }

        return $cardType;
    }

    public function setBillingName(string $type): void {
        $this->billingName = $type;
    }

    public function setPaymentType(string $type): void {
        $this->paymentType = $type;
    }

    private function setToday(string $date): void{
        $this->today = $date;
    }

    public function setUserID($var)
    {
        $this->userID=$var;
    }

    protected function getMysqli(): mysqli{
        return $this->mysqli;
    }
    public function setMysqli(mysqli $mysql): void {
        $this->mysqli = $mysql;
    }


}
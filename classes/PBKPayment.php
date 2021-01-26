<?php


class PBKPayment {
    protected $card;
    protected $billing;
    protected $billingID;
    protected $paymentID;
    protected $checkID;
    protected $mysqli;
    protected $config;
    protected $billAmount;
    protected $billingName;
    protected $userID;
    protected $checkGUID;
    private $paymentType;
    private $guid;
    public const TODAY_FORMAT = "Y-m-d G:i:s";

    public function __construct($mysql) {
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

    private function setToday(string $date): void {
        $this->today = $date;
    }

    final private function setConfig(): void {
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/var/www/html/c2.theproteinbar.com/');
        }
        $default = dirname(ABSPATH) . '/config.json';
        $this->config = json_decode(file_get_contents($default));
        $this->ToastClient = $this->config->sbToastClient;
        $this->ToastSecret = $this->config->sbToastSecret;
        $this->url = $this->config->sbToastURL;
    }

    final public function setCard($card): void {
        $this->card = $card;
    }

    public final function addPaymentToTable(array $args): array {
        $stmt = $this->mysqli->prepare("INSERT INTO pbc_minibar_order_payment (mbCheckID, mbUserID, paymentType, paymentDate, paymentAmount, paymentStatus, authorization, fdsToken, cardNum, transactionID, addressID, expDate) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssssssssssss',
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
            $args['addressID'],
            $args['expDate']
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

    final public function setBilling(int $billingID): void {
        $this->billingID = $billingID;
        $stmt = $this->mysqli->prepare("SELECT * FROM pbc_minibar_users_address WHERE addressID=? AND addressType='billing'");
        $stmt->bind_param('s', $billingID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        $this->billing = $row;
    }

    final public function returnPayementInfo(): ?object {
        $stmt = $this->mysqli->prepare("SELECT * FROM pbc_minibar_order_payment WHERE paymentID=?");
        $stmt->bind_param("s", $this->paymentID);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_object();
    }

    final public function validateGUID(string $guid): int {
        $stmt = $this->mysqli->prepare("SELECT paymentID FROM pbc_minibar_order_payment WHERE publicUnique=UuidToBin(?)");
        $stmt->bind_param("s", $guid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->checkID)) {
            $this->setGUID($guid);
            $this->setPaymentID($row->paymentID);
            return $row->paymentID;
        }
        return false;

    }

    final public function setPaymentID(int $id): void {
        $this->paymentID = $id;
    }

    public function getGUID(): string {
        return $this->guid;
    }

    protected function setGUID(string $guid): void {
        $this->guid = $guid;
    }

    final public function setBillingName(string $type): void {
        $this->billingName = $type;
    }

    final public function setCheckID(int $check): void {
        $this->checkID = $check;
    }

    final public function setCheckGUID(string $guid): void {
        $this->checkGUID = $guid;
    }

    final public function setPaymentType(string $type): void {
        $this->paymentType = $type;
    }

    final public function setUserID(int $var): void {
        $this->userID = $var;
    }

    final public function setBillAmount(float $var): void {
        $this->billAmount = $var;
    }

    final protected function getCCType(string $cardNumber): string {
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

    final protected function getMysqli(): mysqli {
        return $this->mysqli;
    }

    final public function setMysqli(mysqli $mysql): void {
        $this->mysqli = $mysql;
    }


}
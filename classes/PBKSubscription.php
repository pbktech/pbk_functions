<?php


class PBKSubscription {
    private int $uid;
    private $mysqli;
    private object $config;
    private object $sub;
    public const LOCAL_TAX = .1175;
    private const TODAY_FORMAT = "Y-m-d G:i:s";

    public function __construct() {
        $this->setConfig();
        $this->connectDB();
    }

    final public function billSubscriber(array $args): array{
        require_once "PBKPayment.php";
        require_once "payments/Payeezy.php";
        require_once dirname(__DIR__) . '/templates/email/public.php';
        $taxAmount = round($args['cost'] * self::LOCAL_TAX, 2);
        $totalAmount = $taxAmount + $args['cost'];
        $pay = new Payeezy($this->mysqli);
        $pay->setMerchantRef("PBK Subscription for " . date('F Y'));
        $pay->setSubTotal($args['cost']);
        $pay->setTaxAmount($taxAmount);
        $pay->setBillAmount($totalAmount);
        $firstData = json_decode($this->sub->firstData);
        $token = array("token_type" => $firstData->token->token_type, "token_data" => [
            "type" => $firstData->card->type,
            "value" => $firstData->token->token_data->value,
            "cardholder_name" => $firstData->card->cardholder_name,
            "exp_date" => $firstData->card->exp_date,
            "special_payment" => "B"
        ]);
        $pay->setToken($token);
        $auth = $pay->purchaseToken();
        $txn = [
            'transactionType' => "Rebill",
            'transaction_status' => ($auth->transaction_status === "approved" ? "Approved" : "Declined"),
            'transactionTime' => date(self::TODAY_FORMAT),
            'resultAuth' => json_encode($auth),
            'totalAmount' => $totalAmount
        ];
        $this->storeTransaction($txn);
        if ($auth->transaction_status !== "approved" || empty($auth->transaction_status)) {
            return ["status" => 400, "message" => "Credit Card Declined", "class" => "danger"];
        }
        $this->subscriptionReceipt($auth,['taxAmount'=> $taxAmount, 'cost' => $totalAmount],$firstData);
        return ["status" => 200, "message" => $_REQUEST['guest'] . " has been charged " . $totalAmount, "class" => "success"];
    }

    private function subscriptionReceipt(object $capture, array $info, object $firstData): void{
        $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $receipt = getHeader();
        $receipt .= "
    <p style=\"padding-top:.5em;\">
        <img src=\"https://images.getbento.com/accounts/79416dac6744e5896d428cd16e2e574c/media/images/69611Email_ReferAFriend.jpg?w=1800&fit=max&auto=compress,format&h=1800\" style=\"margin: auto;\" />
    </p>
    <table style='border: none;width: 75%; padding-top: 1em;'>
    <tr><td colspan='2' style='font-size: 125%; '><span style='color: #F36C21; font-weight: bold;'>" . $this->sub->planName . " for " . date('F Y') . "</span><br><span style='font-style: italic; color: rgb(172, 174, 176); font-size: 12px;'>" . $this->sub->planDescription . "</span></td></tr>
    </table>
    <table style='border: none;width: 75%; padding-top: 1em;'>
        <tr style='padding-top: 1em;'><td colspan='2' style='text-align: right;'>Subtotal " . $fmt->formatCurrency($info['cost'], "USD") . "</td></tr>
        <tr><td colspan='2' style='text-align: right;'>Tax " . $fmt->formatCurrency($info['taxAmount'], "USD") . "</td></tr>
        <tr><td colspan='2' style='text-align: right;'>Total " . $fmt->formatCurrency($info['taxAmount'] + $info['cost'], "USD") . "</td></tr>
        <tr><td colspan='2' style='text-align: right;'>" . $capture->token->token_data->type . " " . $firstData->card->card_number . "</td></tr>
        <tr><td colspan='2' style='text-align: right;'>Auth " . $capture->transaction_id . "</td></tr>
    </table>
";
        $receipt .= getSubscribeText();
        $receipt .= getFooter();
        $toast = new ToastReport();
        $toast->reportEmail($this->sub->emailAddress, $receipt, "Thank you for your subscription!");

    }

    private function storeTransaction(array $args): int{
        $insert = $this->mysqli->prepare("INSERT INTO pbc_subscriptions_transactions (subscriptionID, transactionType, transactionStatus, transactionTime, fdsResponse, amount) VALUES (?,?,?,?,?,?)");
        $insert->bind_param('ssssss',
            $this->uid,
            $args['transactionType'],
            $args['transaction_status'],
            $args['transactionTime'],
            $args['resultAuth'],
            $args['totalAmount']
        );
        $insert->execute();
        return $insert->insert_id;
    }
    final public function setUID(int $uid, int $plainID): void {
        $this->uid = $uid;
        $q = $this->mysqli->prepare("SELECT * FROM pbc_subscriptions ps, pbc_subscriptions_plans psp WHERE ps.userID = ? AND ps.subPlan = psp.planID AND ps.subPlan = ?");
        $q->bind_param('ss',$this->uid,$plainID);
        $q->execute();
        $r = $q->get_result();
        $this->sub = $r->fetch_object();
    }

    private function setConfig() {
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/var/www/html/c2.theproteinbar.com/');
        }
        $default = dirname(ABSPATH) . '/config.json';
        $this->config = json_decode(file_get_contents($default));
    }

    private function connectDB(): void {
        $this->mysqli = new mysqli($this->config->host, $this->config->username, $this->config->password, $this->config->dBase);
        $this->mysqli->set_charset('utf8mb4');
    }
}
<?php
require dirname(dirname(__FILE__, 2)) . "/vendor/autoload.php";

class Payeezy extends PBKPayment{

    public $client;
    private $transaction_id;
    private $transaction_tag;

    public function __construct($mysqli){
        parent::__construct($mysqli);
        $this->client = new Payeezy_Client();
        $this->client->setApiKey($this->config->Payeezy->Key);
        $this->client->setApiSecret($this->config->Payeezy->Secret);
        $this->client->setMerchantToken($this->config->Payeezy->Merchant);

    }

    public function getAuthToken(): object{
        $this->client->setUrl($this->config->Payeezy->URL . "v1/transactions");
        $authorize_card_transaction = new Payeezy_CreditCard($this->client);
        if(isset($this->checkID)){
            $merchant_ref = "PBKMinibar-" . $this->checkGUID;
        }else{
            $merchant_ref = "Protein Bar Pre-auth";
        }
        $authorize_response = $authorize_card_transaction->authorize(
            [
                "merchant_ref" => $merchant_ref,
                "amount" => round($this->billAmount*100),
                "currency_code" => "USD",
                "credit_card" => array(
                    "type" =>  $this->getCCType($this->card->cardNumber),
                    "cardholder_name" => $this->billingName,
                    "card_number" => $this->card->cardNumber,
                    "exp_date" => preg_replace('/\D/', '', $this->card->expiryDate),
                    "cvv" => $this->card->cvc
                ),
                "auth" => "false"
            ]
        );
        $args = [
            'mbCheckID'=>$this->checkID,
        'mbUserID' => $this->userID,
        'paymentType' => $authorize_response->card->type,
        'paymentDate' => date('Y-m-d H:i:s'),
        'paymentAmount' => $this->billAmount,
        'paymentStatus' => $authorize_response->transaction_status,
        'authorization' => json_encode(array("bank_resp_code" => $authorize_response->bank_resp_code, "bank_message" => $authorize_response->bank_message, "gateway_resp_code"=>$authorize_response->gateway_resp_code, "gateway_message" => $authorize_response->gateway_message)),
        'fdsToken' => json_encode(
            array(
                "token_type" => $authorize_response->token->token_type,
                "token_data" => [
                    "type" => $this->getCCType($this->card->cardNumber),
                    "value" => $authorize_response->token->token_data->value,
                    "cardholder_name" => $this->billingName,
                    "exp_date" => preg_replace('/\D/', '', $this->card->expiryDate)
                ]
            )
        ),
        'cardNum' => $authorize_response->card->card_number,
        'transactionID' => json_encode(array("transaction_id"=>$authorize_response->transaction_id, "transaction_tag"=>$authorize_response->transaction_tag)),
        'addressID' => $this->billingID,
        'expDate' => preg_replace('/\D/', '', $this->card->expiryDate)
        ];
        $info=$this->addPaymentToTable($args);
        return (object)["response" => $authorize_response, "info" => $info];
    }

    public function captureCard(){
        $this->client->setUrl($this->config->Payeezy->URL . "v1/transactions");
        $capture_card_transaction = new Payeezy_CreditCard($this->client);
        $reponse =  $capture_card_transaction->capture(
            $this->transaction_id,
            array(
                "amount"=> $this->billAmount,
                "transaction_tag" => $this->transaction_tag,
                "merchant_ref" => "PBKMinibar-" . $this->checkGUID,
                "currency_code" => "USD",
            )
        );
        $stmt = $this->mysqli->prepare("UPDATE pbc_minibar_order_payment SET capture = ? WHERE paymentID = ?");
        $stmt->bind_param("ss",$reponse, $this->paymentID);
        $stmt->execute();
        return $reponse;
    }

    public function captureTokenSale(){
        $stmt = $this->mysqli->prepare("SELECT fdsToken FROM pbc_minibar_order_payment WHERE paymentID = ?");
        $stmt->bind_param("s", $this->paymentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();

        $this->client->setUrl($this->config->Payeezy->URL . "v1/transactions");

        $authorize_card_transaction = new Payeezy_Token($this->client);

        return $authorize_card_transaction->authorize(
            [
                "merchant_ref" => "PBKMinibar-" . $this->checkGUID,
                "transaction_type" => "purchase",
                "method" => "token",
                "amount" => $this->billAmount,
                "currency_code" => "USD",
                "token" => json_decode($row->fdsToken,true)
            ]
        );
    }

    public function setTransactionID(string $id): void{
        $this->transaction_id = $id;
    }

    public function setTransactionTag(string $tag): void{
        $this->transaction_tag = $tag;
    }
}
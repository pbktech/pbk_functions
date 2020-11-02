<?php
require dirname(dirname(__FILE__, 2)) . "/vendor/autoload.php";

class Payeezy extends PBKPayment{

    public $client;

    public function __construct($mysqli){
        parent::__construct($mysqli);
        $this->client = new Payeezy_Client();
        $this->client->setApiKey($this->config->Payeezy->Key);
        $this->client->setApiSecret($this->config->Payeezy->Secret);
        $this->client->setMerchantToken($this->config->Payeezy->Merchant);
      //  $this->client->setUrl($this->config->Payeezy->URL);

    }

    public function getAuthToken(): object{
        $this->client->setUrl("https://api-cert.payeezy.com/v1/transactions");
        $authorize_card_transaction = new Payeezy_CreditCard($this->client);

        $authorize_response = $authorize_card_transaction->authorize(
            [
                "amount" => "1",
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
        $args=array();
        $args['mbCheckID']=null;
        $args['mbUserID'] = $this->userID;
        $args['paymentType'] = $authorize_response->card->type;
        $args['paymentDate'] = date('Y-m-d H:i:s');
        $args['paymentAmount'] = .01;
        $args['paymentStatus'] = $authorize_response->transaction_status;
        $args['authorization'] = json_encode(array("bank_resp_code" => $authorize_response->bank_resp_code, "bank_message" => $authorize_response->bank_message, "gateway_resp_code"=>$authorize_response->gateway_resp_code, "gateway_message" => $authorize_response->gateway_message));
        $args['fdsToken'] = json_encode(array("token_type" => $authorize_response->token->token_type, "value" =>$authorize_response->token->token_data->value));
        $args['cardNum'] = $authorize_response->card->card_number;
        $args['transactionID'] = json_encode(array());
        $args['addressID'] = $this->billingID;
        $info=$this->addPaymentToTable($args);

        if($authorize_response->transaction_status == 'approved') {
            $tasks = new task_engine($this->mysqli);
            $tasks->add_task(
                ['what' => 'execBackground',
                    'target' => "/home/jewmanfoo/toast-api/ccFunction.php ",
                    'files' => json_encode(array("action" => "void", "transaction_tag" => $authorize_response->transaction_tag, "transaction_id" => $authorize_response->transaction_id)),
                    'dueDate' => date('Y-m-d H:i:s', strtotime('+1 hour'))]
            );
        }
        return (object)["response" => $authorize_response, "info" => $info];
    }

    public function authCard(): object{
        $authorize_card_transaction = new Payeezy_CreditCard($this->client);
        $cardType = $this->getCCType($this->card->number);
        return  $authorize_card_transaction->authorize(
            [
                "merchant_ref" => "PBKMinibar-" . $checkGUID,
                "amount" => $this->billAmount,
                "currency_code" => "USD",
                "billing_address" => $billing_address,
                "credit_card" => array(
                    "type" => $cardType,
                    "cardholder_name" => $this->billingName,
                    "card_number" => $this->card->number,
                    "exp_date" => preg_replace('/\D/', '', $this->card->expiryDate),
                    "cvv" => $this->card->cvc
                )
            ]
        );
    }

    public function captureCard(){

    }

    private function processTransaction(string $endpoint,array $payload): object {
        $apiKey = $this->config->Payeezy->Key;
        $apiSecret = $this->config->Payeezy->Secret;
        $nonce = random_bytes(4);
        $timestamp = microtime();
        $token = $this->config->Payeezy->Merchant;
        $payloadString = print_r($payload,true);
        $data = $apiKey . $nonce . $timestamp . $token . $payloadString;
        $hashAlgorithm = "sha256";
        $hmac = hash_hmac ( $hashAlgorithm , $data , $apiSecret, false );
        $authorization = base64_encode($hmac);

        $json=json_encode($payload, JSON_FORCE_OBJECT);
        $headers=$this->hmacAuthorizationToken($json);
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $this->config->Payeezy->URL . $endpoint);
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt($request, CURLOPT_POSTFIELDS, $json);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_HEADER, false);
        curl_setopt(
            $request,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'apikey:'=> $this->config->Payeezy->Key,
                'token:' => $this->config->Payeezy->Merchant,
                'Authorization:' => $headers['authorization'],
                'nonce:' => $headers['nonce'],
                'timestamp:' => $headers['timestamp'],
            )
        );

        $response = curl_exec($request);

        if (false === $response) {
            echo curl_error($request);
        }
        curl_close($request);

        $answer=json_decode($response);
        if($answer->code == 200 || $answer->code == 201 || $answer->code == 202){
            return $answer;
        }else{
            return (object)[
                "response" => $answer,
                "headers" => array(
                'Content-Type: application/json',
                'apikey:' => $this->config->Payeezy->Key,
                'token:' => $this->config->Payeezy->Merchant,
                    'Authorization:' => $headers['authorization'],
                    'nonce:' => $headers['nonce'],
                    'timestamp:' => $headers['timestamp'],
            ), "payload" => $payload];
        }
    }

}
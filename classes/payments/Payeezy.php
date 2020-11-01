<?php
require dirname(dirname(__FILE__, 2)) . "/vendor/autoload.php";

class Payeezy extends PBKPayment{

    private $client;

    public function __construct($mysqli){
        parent::__construct($mysqli);
        $this->client = new Payeezy_Client();
        $this->client->setApiKey($this->config->Payeezy->Key);
        $this->client->setApiSecret($this->config->Payeezy->Secret);
        $this->client->setMerchantToken($this->config->Payeezy->Merchant);
      //  $this->client->setUrl($this->config->Payeezy->URL);

    }

    private function hmacAuthorizationToken($payload): array
    {
        $nonce = (string)hexdec(bin2hex(random_bytes(8)));
        $timestamp = (string)(time()*1000); //time stamp in milli seconds
        $data = $this->config->Payeezy->Key . $nonce . $timestamp . $this->config->Payeezy->Merchant . $payload;
        $hashAlgorithm = "sha256";
        $hmac = hash_hmac($hashAlgorithm, $data, $this->config->Payeezy->Secret, false);    // HMAC Hash in hex
        $authorization = base64_encode($hmac);
        return array(
            'authorization' => $authorization,
            'nonce' => $nonce,
            'timestamp' => $timestamp,
            'apikey' => $this->config->Payeezy->Key,
            'token' => $this->config->Payeezy->Merchant,
        );
    }

    public function getAuthToken(): object{
        $this->client->setUrl("https://api-cert.payeezy.com/v1/transactions/tokens");
        $authorize_card_transaction = new Payeezy_CreditCard($this->client);

        $authorize_response = $authorize_card_transaction->authorize(
            [
                "merchant_ref" => "Astonishing-Sale",
                "amount" => "1",
                "currency_code" => "USD",
                "credit_card" => array(
                    "type" =>  $this->getCCType($this->card->number),
                    "cardholder_name" => $this->billingName,
                    "card_number" => $this->card->cardNumber,
                    "exp_date" => preg_replace('/\D/', '', $this->card->expiryDate),
                    "cvv" => $this->card->cvc
                )
            ]
        );
        return $authorize_response;
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
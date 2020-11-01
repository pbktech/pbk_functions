<?php
require dirname(dirname(__FILE__, 2)) . "/vendor/autoload.php";

class Payeezy extends PBKPayment{

    private $client;
    private $header;

    public function __construct($mysqli){
        parent::__construct($mysqli);

    }

    private function hmacAuthorizationToken($payload): array
    {
        $nonce = (string)hexdec(bin2hex(openssl_random_pseudo_bytes(4, $cstrong)));
        $timestamp = (string)(time()*1000); //time stamp in milli seconds
        $data = $this->config->Payeezy->Key . $nonce . $timestamp . $this->config->Payeezy->Merchant . serialize($payload);
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
        $payload=[
            "type" => "FDToken",
            "credit_card" => [
                "type" => $this->getCCType($this->card->number),
                "cardholder_name" => $this->billingName,
                "card_number" => $this->card->number,
                "exp_date" => preg_replace('/\D/', '', $this->card->expiryDate),
                "cvv" => $this->card->cvc
            ],
            "auth" => "false",
            "ta_token" => "123"
        ];
        return $this->processTransaction( "v1/transactions/tokens", $payload);
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
        $json=json_encode($payload);

        $headers=$this->hmacAuthorizationToken($payload);
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
                'apikey:'. $this->config->Payeezy->Key,
                'token:'. $this->config->Payeezy->Merchant,
                'Authorization:'.$headers['authorization'],
                'nonce:'.$headers['nonce'],
                'timestamp:'.$headers['timestamp'],
            )
        );

        $response = curl_exec($request);

        if (false === $response) {
            echo curl_error($request);
        }
        curl_close($request);

        return json_decode($response);
    }

}
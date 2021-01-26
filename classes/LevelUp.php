<?php

final class LevelUp {
    private $config;
    private $localDB;
    private $url = "https://api.thelevelup.com/v15/";
    private $token;


    function __construct() {
        $this->setConfig();
        $this->token = $this->getAuthorized();
    }

    public function setConfig() {
        if (!defined('ABSPATH')) {
            if (file_exists('/var/www/html/c2.theproteinbar.com')) {
                define('ABSPATH', '/var/www/html/c2.theproteinbar.com/');
            } else {
                define('ABSPATH', '/var/www/html/c2dev.theproteinbar.com/');
            }
        }
        $default = dirname(ABSPATH) . '/config.json';
        $this->config = json_decode(file_get_contents($default));
        $this->localDB = $this->config->dBase;
    }

    private function getAuthorized(): ?string{
        $json = json_encode(array("api_key" => $this->config->levelUp_key, "client_secret" => $this->config->levelUp_secret));
        $result = $this->postCURL($json, "access_tokens");
        return $result->access_token->token ?? null;
    }

    private function getCURL($url): ?array{
        $url = trim($url);
        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, $url);
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURLConnection, CURLOPT_HEADER, 0);
        $result = curl_exec($cURLConnection);
        curl_close($cURLConnection);
        return json_decode($result, true);
    }

    private function postCURL(string $json, string $page, string $head = ""): object{
        $header = array("Content-Type: application/json", 'Accept: application/json');
        if(!empty($head)){
            $header[] = $head;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_URL, $this->url . $page);
        $result = curl_exec($ch);
        print_r($result);
        return json_decode($result);
    }

    public function getUser(){

    }

    public function addCredit(array $c): object {
        $c['merchant_funded_credit']['duration_in_seconds'] = 31536000;
        $c['merchant_funded_credit']['global'] = "false";
        $json = json_encode($c);
        $head = 'Authorization:token merchant="' . $this->merch_token . '"';
        return $this->postCURL($json, "merchant_funded_credits", $head);
    }

    function checkRegistered(string $c): array{
        return [$this->token];
        $c = http_build_query(array("api_key" =>trim($this->config->levelUp_key), "email" =>trim($c)));
        return $this->getCURL($this->url . "registration?" . $c);
    }

}

<?php

class LevelUp{
	private $config;
	private $localDB;
  private $clientID;
  private $secret;
  private $url="https://api.thelevelup.com/v15/";
  private $luAuth;
  private $lv_request=165859;
  private $merch_token="140248434-jW7PTzJzDdBNu7ciemL4jP45mzQP7VF7f9345LDRAQTrxyLWsuT1Z3vUvdQeRR";

  function __construct() {
		$this->setConfig();
    $token=$this->getAuthorized();
    $this->luAuth=$token->access_token->token;
	}
	public function setConfig(){
		if(!defined('ABSPATH')){
		  if (file_exists('/var/www/html/c2.theproteinbar.com')) {
		    define('ABSPATH', '/var/www/html/c2.theproteinbar.com/');
		  }else {
		    define('ABSPATH', '/var/www/html/c2dev.theproteinbar.com/');
		  }
		}
		$default = dirname(ABSPATH) . '/config.json';
		$this->config=json_decode(file_get_contents($default));
		$this->localDB=$this->config->dBase;
    $this->clientID=$this->config->LevelUp_client;
    $this->secret=$this->config->LevelUp_secret;
	}
  function getAuthorized() {
    $json=json_encode(array("api_key"=>$this->clientID,"client_secret"=>$this->secret));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER,Array("Content-Type: application/json",'Accept: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_URL,$this->url. "access_tokens");
		$result=curl_exec($ch);
		return json_decode($result);
	}
  function addCredit($c) {
		print_r($this->getPersmission());
    $c['merchant_funded_credit']['duration_in_seconds']=31536000;
    $c['merchant_funded_credit']['global']="false";
    $json=json_encode($c);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER,Array(
      "Content-Type: application/json",
      "Accept: application/json",
      'Authorization:token
			merchant="'.$this->merch_token.'"'
    ));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_URL,$this->url. "merchant_funded_credits");
		$result=curl_exec($ch);
		return json_decode($result);
	}
  function perRequest() {
    $c['permissions_request']['email']="jon@theproteinbar.com";
    $c['permissions_request']['permission_keynames'][]="give_merchant_funded_credit";
    $c['permissions_request']['target_app_id']=107;
    $json=json_encode($c);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER,Array(
      "Content-Type: application/json",
      "Accept: application/json",
      "Authorization:token ".$this->luAuth
    ));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_URL,$this->url. "apps/permissions_requests");
		$result=curl_exec($ch);
		return json_decode($result);
	}
  function checkRegistered($c) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt ($ch, CURLOPT_HTTPHEADER,Array());
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_URL,$this->url. "registration?api_key=" . $this->clientID . "&email=" . $c);
    $result=curl_exec($ch);
    return json_decode($result);
  }
  function getPersmission() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt ($ch, CURLOPT_HTTPHEADER,Array("Authorization:token ".$this->luAuth));
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_URL,$this->url. "apps/permissions_requests/" . $this->lv_request);
    $result=curl_exec($ch);
    return json_decode($result);
  }

}

<?php

class PBKUser{
  private $userExists = FALSE;
  private $userID;
  private $guestUser = FALSE;
  private $mysqli;
  var $userDetails = null;
  var $yesvno = array(1=>"Yes",0=>"No");

  function __construct($mysql,$user=null) {
    if(!isset($mysql)){
      $report=new ToastReport;
      $m="Users class failed to construct. Missing MySQLi object.";
      $report->reportEmail("errors@theproteinbar.com",$m,"User error");
      return array("message"=>"There was an error setting up, this has been reported.","Variant"=>"danger");
      exit;
    }
		$this->setmysqli($mysql);
    if(isset($user)){
      $this->userCheck($user);
    }
	}

  function userCheck($user){
    $stmt1=$this->mysqli->prepare("SELECT id,password FROM pbc_minibar_user WHERE  email_address=?");
    $stmt1->bind_param("s", $user);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $row1 = $result1->fetch_object();
    if(isset($row1->id)){
      $this->setUserID($row1->id);
      if(isset($row1->password)){
        $this->setUserExists(TRUE);
      }else {
        $this->setGuestUser(TRUE);
      }
    }
  }

  function doRegister($request){
    if($this->getUserExists()){
      return array("message"=>"Username is already being used.","Variant"=>"danger");
    }
    if($this->getGuestUser()){
      if($newUserID=$this->addNewUser($request)){
        $this->setUserID($newUserID);
      }else{
        return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
      }
    }else{
      if(!$this->updateUser($request)){
        return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
      }
    }
    if($linkHEX=$this->generateHexLink("user_registration")){
      $report=new ToastReport;
      $m="Thank you for signing up for an account with Protein Bar & Kitchen. Please click this link you confrim your email. <a href='https://mb.theproteinbar.com/confirm/".$linkHEX."'>https://mb.theproteinbar.com/confirm/".$linkHEX."</a>";
      $report->reportEmail($request->user,$m,"Minibar Email Verification");
      return array("message"=>"Thank you for signing up. Please check your email for an email confirmation.","Variant"=>"success");
    }else{
      return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
    }
    return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
  }

  function doLogin($request){
    if(!$this->getUserExists()){
      return array("message"=>"Invalid Username","Variant"=>"danger");
    }
    if(password_verify($request->password, $this->userDetails->password)){
      $loginTime=date("Y-m-d G:i:s");
      $loginExpires=date("Y-m-d G:i:s", strtotime('+3 hours'));
      $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_sessions (mbUserId,loginTime,expireTime)VALUES(?,?,?)");
      $stmt->bind_param("sss", $this->userID,$loginTime,$loginExpires);
      $stmt->execute();
      if(isset($stmt->error) && $stmt->error!=''){
        $report=new ToastReport;
        $m="User (".$this->userID.") failed to create hex link.<br><br>LP: ".$lp."<br><br>DB Error: " . $stmt->error;
        $report->reportEmail("errors@theproteinbar.com",$m,"User error");
        return array("message"=>"There was an error logging you in. This error has been reported.","Variant"=>"danger");
      }
      $stmt=$this->mysqli->prepare("SELECT UuidFromBin(SessionGUID) as 'session' FROM pbc_minibar_users_sessions WHERE sessionID = ?");
      $stmt->bind_param("s", $stmt->insert_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_object();
      if(isset($row->session)){
        return array("message"=>"Login Successful","Variant"=>"success","sessionID"=>$row->session,"guestName"=>$this->userDetails->real_name1);
      }
    }
    return array("message"=>"Invalid Username/Password","Variant"=>"danger");
  }
  function checkSession($sessionGUID){
    $stmt=$this->mysqli->prepare("SELECT * FROM pbc_minibar_users_sessions WHERE SessionGUID = UuidToBin(?) AND expireTime >= NOW()");
    $stmt->bind_param("s", $sessionGUID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_object();
    if(isset($row->sessionID)){
      return TRUE;
    }
    return FALSE;
  }
  function generateHexLink($lp){
    $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_links (mbUserID, linkPurpose)VALUES(?,?)");
    $stmt->bind_param("ss", $this->userID,$lp);
    $stmt->execute();
    if(isset($stmt->error) && $stmt->error!=''){
      $report=new ToastReport;
      $m="User (".$this->userID.") failed to create hex link.<br><br>LP: ".$lp."<br><br>DB Error: " . $stmt->error;
      $report->reportEmail("errors@theproteinbar.com",$m,"User error");
      return FALSE;
    }
    $linkId=$stmt->insert_id;
    $stmt=$this->mysqli->prepare("SELECT linkHEX FROM pbc_minibar_users_links WHERE linkID=?");
    $stmt->bind_param("s", $linkId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_object();
    if(isset($row->linkHEX)){
      return $row->linkHEX;
    }
    return FALSE;
  }
  function checkValidLinkHEX($linkHEX,$lp){
    $stmt=$this->mysqli->prepare("SELECT * FROM pbc_minibar_users_links,pbc_minibar_user WHERE linkHEX=? AND linkPurpose=? AND id=mbUserID");
    $stmt->bind_param("ss", $linkHEX,$lp);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_object();
    if(isset($row->id)){
      $this->setUserID($row->id);
      return TRUE;
    }
    return FALSE;
  }
  function doForgotPassword(){
    if(!empty($this->userID)){
      $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET requireNewPassword=1, isLocked=0 WHERE id='".$this->userID."'");
      $stmt->execute();
      if(isset($stmt->error) && $stmt->error!=''){
        $report=new ToastReport;
        $m="User (".$this->userID.") failed to lock password.<br><br>DB Error: " . $stmt->error;
        $report->reportEmail("errors@theproteinbar.com",$m,"User error");
        return FALSE;
      }
      if($linkHEX=$this->generateHexLink("forgot_password")){
        $report=new ToastReport;
        $m="Someone has requested a new password to Protein Bar & Kitchen using this email. Please use this link to reset your password. <a href='https://mb.theproteinbar.com/forgotpass/".$linkHEX."'>https://mb.theproteinbar.com/forgotpass/".$linkHEX."</a>";
        $report->reportEmail($this->userDetails->email_address,$m,"Minibar Email Verification");
      }else{
        return FALSE;
      }
    }
    return array("message"=>"Thank you for your request. If we find an account associated with your email address, we will email you instructions to reset your password.","Variant"=>"success");
  }
  private function addNewUser($request){
    $phone=$this->cleanPhone($request->phone);
    $emailConsent=$this->switchEmailConsent($request->emailConsent);
    $password = password_hash($password, PASSWORD_DEFAULT);
    $stmt=$this->mysqli->prepare("INSERT INTO pbc_minibar_user (login_name, password, email_address, real_name1, phone_number, emailConsent)VALUES(?,?,?,?,?,?)");
    $stmt->bind_param("ssssss", $request->user,$password,$request->user,$request->name,$phone,$emailConsent);
    $stmt->execute();
    if(isset($stmt->error) && $stmt->error!=''){
      $report=new ToastReport;
      $m="User failed to add.<br><br>Request: ".print_r($request,true)."<br><br>DB Error: " . $stmt->error;
      $report->reportEmail("errors@theproteinbar.com",$m,"User error");
      return FALSE;
    }
    return $stmt->insert_id;
  }

  private function loadUserDetails(){
    $stmt1=$this->mysqli->prepare("SELECT email_address,real_name1,phone_number,emailConsent,password FROM pbc_minibar_user WHERE  id=?");
    $stmt1->bind_param("s", $this->userID);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $this->userDetails = $result1->fetch_object();
  }

  private function updateUser($request){
    $phone=$this->cleanPhone($request->phone);
    $emailConsent=$this->switchEmailConsent($request->emailConsent);
    $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET login_name=?, real_name1=?, phone_number=?, emailConsent=? WHERE id='".$this->userID."'");
    $stmt->bind_param("ssss", $request->user,$request->name,$phone,$emailConsent);
    $stmt->execute();
    if(isset($stmt->error) && $stmt->error!=''){
      $report=new ToastReport;
      $m="User (".$this->userID.") failed to update.<br><br>Request: ".print_r($request,true)."<br><br>DB Error: " . $stmt->error;
      $report->reportEmail("errors@theproteinbar.com",$m,"User error");
      return FALSE;
    }
    if(!empty($request->password)){
      $this->updatePassword($request->password);
    }
    return TRUE;
  }
  function updatePassword($password){
    $password = password_hash($password, PASSWORD_DEFAULT);
    $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET password=?,requireNewPassword=0 WHERE id='".$this->userID."'");
    $stmt->bind_param("s", $password);
    $stmt->execute();
    if(isset($stmt->error) && $stmt->error!=''){
      $report=new ToastReport;
      $m="User (".$this->userID.") failed to update password<br><br>DB Error: " . $stmt->error;
      $report->reportEmail("errors@theproteinbar.com",$m,"User error");
      return FALSE;
    }
    return TRUE;
  }
  function cleanPhone($phone){
    return preg_replace('/\D+/', '', $phone);
  }
  function switchEmailConsent($emailConsent){
    if($emailConsent=='on'){
      return 1;
    }
    return 0;
  }
  function getUserExists(){
    if($this->userExists === TRUE){
      return TRUE;
    }
    return FALSE;
  }
  function getGuestUser(){
    if($this->guestUser === TRUE){
      return TRUE;
    }
    return FALSE;
  }
  function setUserExists($var){
    if($var===TRUE){
      $this->loadUserDetails();
    }
    $this->userExists=$var;
  }
  function getUserID(){
    return $this->userExists;
  }
  function setUserID($var){
    $this->userID=$var;
  }
  function setGuestUser($var){
    if($var===TRUE){
      $this->loadUserDetails();
    }
    $this->guestUser=$var;
  }
  function setmysqli($var){
    $this->mysqli=$var;
  }
}

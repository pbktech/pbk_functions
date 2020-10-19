<?php

class PBKUser
{
    private $userExists = false;
    private $userID;
    private $guestUser = false;
    private $mysqli;
    public $userDetails = null;
    public $yesvno = array(1=>"Yes",0=>"No");

    public function __construct($mysql, $user=null)
    {
        if (!isset($mysql)) {
            $report=new ToastReport;
            $m="Users class failed to construct. Missing MySQLi object.";
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return array("message"=>"There was an error setting up, this has been reported.","Variant"=>"danger");
            exit;
        }
        $this->setmysqli($mysql);
        if (isset($user)) {
            $this->userCheck($user);
        }
    }

    public function userCheck($user)
    {
        $stmt1=$this->mysqli->prepare("SELECT id,password FROM pbc_minibar_user WHERE  email_address=?");
        $stmt1->bind_param("s", $user);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $row1 = $result1->fetch_object();
        if (isset($row1->id)) {
            $this->setUserID($row1->id);
            if (isset($row1->password)) {
                $this->setUserExists(true);
            } else {
                $this->setGuestUser(true);
            }
        }
    }

    public function doRegister($request)
    {
        if ($this->getUserExists()) {
            return array("message"=>"Username is already being used.","Variant"=>"danger");
        }
        if ($this->getGuestUser()) {
            if (!$this->updateUser($request)) {
                return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
            }
        } else {
            if ($newUserID=$this->addNewUser($request)) {
                $this->setUserID($newUserID);
            } else {
                return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
            }
        }
        if ($linkHEX=$this->generateHexLink("user_registration")) {
            $report=new ToastReport;
            $m="Thank you for signing up for an account with Protein Bar & Kitchen. Please click this link you confrim your email. <a href='https://mb.theproteinbar.com/confirm/".$linkHEX."'>https://mb.theproteinbar.com/confirm/".$linkHEX."</a>";
            $report->reportEmail($request->user, $m, "Minibar Email Verification");
            return array("message"=>"Thank you for signing up. Please check your email for an email confirmation.","Variant"=>"success");
        } else {
            return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
        }
        return array("message"=>"There was an error signing you up. This error has been reported.","Variant"=>"danger");
    }

    public function doLogin($request)
    {
        if (!$this->getUserExists()) {
            return array("message"=>"Invalid Username/Password","Variant"=>"danger");
        }
        $stmt=$this->mysqli->prepare("SELECT COUNT(*) as 'count' FROM pbc_minibar_users_failed_login WHERE mbUserID=? AND currentTime >= DATE_SUB(NOW(),INTERVAL 1 HOUR)");
        $stmt->bind_param("s", $this->userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->count) && $row->count>5) {
            $this->userDetails->isLocked=1;
            $this->lockUserAccount();
        }
        if ($this->userDetails->isLocked==1) {
            return array("message"=>"Your account is locked. Please reset your password to unlock it.","Variant"=>"danger");
        }
        if ($this->userDetails->isConfirmed==0) {
            return array("message"=>"Your must confirm your email before you can access your account.","Variant"=>"danger");
        }
        if (password_verify($request->password, $this->userDetails->password)==1) {
            $loginTime=date("Y-m-d G:i:s");
            $loginExpires=date("Y-m-d G:i:s", strtotime('+3 hours'));
            $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_sessions (mbUserId,loginTime,expireTime)VALUES(?,?,?)");
            $stmt->bind_param("sss", $this->userID, $loginTime, $loginExpires);
            $stmt->execute();
            if (isset($stmt->error) && $stmt->error!='') {
                $report=new ToastReport;
                $m="User (".$this->userID.") failed to create hex link.<br><br>LP: ".$lp."<br><br>DB Error: " . $stmt->error;
                $report->reportEmail("errors@theproteinbar.com", $m, "User error");
                return array("message"=>"There was an error logging you in. This error has been reported.","Variant"=>"danger");
            }
            $newid=$stmt->insert_id;
            $stmt=$this->mysqli->prepare("SELECT UuidFromBin(SessionGUID) as 'session' FROM pbc_minibar_users_sessions WHERE sessionID = ?");
            $stmt->bind_param("s", $newid);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_object();
            if (isset($row->session)) {
                $addresses=array();
                $stmt=$this->mysqli->prepare("SELECT addressID,street,addStreet,city,state,zip FROM pbc_minibar_users_address WHERE mbUserID=? AND addressType='billing' AND isDeleted=0");
                $stmt->bind_param("s", $this->userID);
                $stmt->execute();
                $result = $stmt->get_result();
                while($row = $result->fetch_object()){
                    $addresses[]=$row;
                }

/*
                $guestCredits=array();
                $toast=new Toast("d76525a6-fa31-4122-b13c-148924d10512");
                $customers=$toast->findCustomerID(preg_replace("/[^0-9]/", "",$this->userDetails->phone_number));
                if(!empty($customers)){
                    $fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
                    foreach($customers as $c) {
                        $credits = $toast->getCustCredits($c->guid);
                        if(isset($credits->amount) && $credits->amount!=0){
                            $guestCredits[]= (object)["guid"=>, "amount"=>$fmt->formatCurrency($credits->amount,"USD")];
                        }
                    }
                }
*/
                return array(
                    "message"=>"Login Successful",
                    "Variant"=>"success",
                    "sessionID"=>$row->session,
                    "guestName"=>$this->userDetails->real_name1,
                    "addresses" => $addresses,
                    "phone" => $this->userDetails->phone_number,
                    "email" => $this->userDetails->email_address
                );
            }
        } else {
            $ip=$this->getClientIP();
            $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_failed_login (mbUserID,ipAddress)VALUES(?,?)");
            $stmt->bind_param("ss", $this->userID, $ip);
            $stmt->execute();
            if (isset($stmt->error) && $stmt->error!='') {
                $report=new ToastReport;
                $m="User (".$this->userID.") failed to insert failed login.<br><br>LP: ".$lp."<br><br>DB Error: " . $stmt->error;
                $report->reportEmail("errors@theproteinbar.com", $m, "User error");
                return array("message"=>"There was an error logging you in. This error has been reported.","Variant"=>"danger");
            }
        }
        return array("message"=>"Invalid Username/Password","Variant"=>"danger");
    }
    private function lockUserAccount()
    {
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET isLocked=1 WHERE id='".$this->userID."'");
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to lock account.<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
    }
    public function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    public function checkSession($sessionGUID)
    {
        $stmt=$this->mysqli->prepare("SELECT * FROM pbc_minibar_users_sessions WHERE SessionGUID = UuidToBin(?) AND expireTime >= NOW()");
        $stmt->bind_param("s", $sessionGUID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->sessionID)) {
            return true;
        }
        $this->doLogout($sessionGUID);
        return false;
    }
    public function generateHexLink($lp)
    {
        $stmt=$this->mysqli->prepare("INSERT INTO pbc2.pbc_minibar_users_links (mbUserID, linkPurpose)VALUES(?,?)");
        $stmt->bind_param("ss", $this->userID, $lp);
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to create hex link.<br><br>LP: ".$lp."<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        $linkId=$stmt->insert_id;
        $stmt=$this->mysqli->prepare("SELECT linkHEX FROM pbc_minibar_users_links WHERE linkID=?");
        $stmt->bind_param("s", $linkId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->linkHEX)) {
            return $row->linkHEX;
        }
        return false;
    }
    public function doLogout($sessionID)
    {
      $logOutTime=date("Y-m-d G:i:s");
      $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_users_sessions SET logoutTime=? WHERE SessionGUID = UuidToBin(?)");
      $stmt->bind_param("ss", $logOutTime,$sessionID);
      $stmt->execute();
      if (isset($stmt->error) && $stmt->error!='') {
          return false;
      }
      return true;
    }
    public function checkValidLinkHEX($linkHEX, $lp)
    {
        $stmt=$this->mysqli->prepare("SELECT * FROM pbc_minibar_users_links,pbc_minibar_user WHERE linkHEX=? AND linkPurpose=? AND id=mbUserID");
        $stmt->bind_param("ss", $linkHEX, $lp);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_object();
        if (isset($row->id)) {
            $this->setUserID($row->id);
            return true;
        }
        return false;
    }
    public function doForgotPassword()
    {
        if (!empty($this->userID)) {
            $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET requireNewPassword=1, isLocked=0 WHERE id='".$this->userID."'");
            $stmt->execute();
            if (isset($stmt->error) && $stmt->error!='') {
                $report=new ToastReport;
                $m="User (".$this->userID.") failed to lock password.<br><br>DB Error: " . $stmt->error;
                $report->reportEmail("errors@theproteinbar.com", $m, "User error");
                return false;
            }
            if ($linkHEX=$this->generateHexLink("forgot_password")) {
                $report=new ToastReport;
                $m="Someone has requested a new password to Protein Bar & Kitchen using this email. Please use this link to reset your password. <a href='https://mb.theproteinbar.com/forgotpass/".$linkHEX."'>https://mb.theproteinbar.com/forgotpass/".$linkHEX."</a>";
                $report->reportEmail($this->userDetails->email_address, $m, "Protein Bar & Kitchen Password Reset");
            } else {
                return false;
            }
        }
        return array("message"=>"Thank you for your request. If we find an account associated with your email address, we will email you instructions to reset your password.","Variant"=>"success");
    }
    private function addNewUser($request)
    {
        $phone=$this->cleanPhone($request->phone);
        $emailConsent=$this->switchEmailConsent($request->emailConsent);
        $password = password_hash($request->password, PASSWORD_DEFAULT);
        $stmt=$this->mysqli->prepare("INSERT INTO pbc_minibar_user (login_name, password, email_address, real_name1, phone_number, emailConsent)VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $request->user, $password, $request->user, $request->name, $phone, $emailConsent);
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User failed to add.<br><br>Request: ".print_r($request, true)."<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        return $stmt->insert_id;
    }

    private function loadUserDetails()
    {
        $stmt1=$this->mysqli->prepare("SELECT email_address,real_name1,phone_number,emailConsent,password,isLocked,requireNewPassword,isConfirmed FROM pbc_minibar_user WHERE  id=?");
        $stmt1->bind_param("s", $this->userID);
        $stmt1->execute();
        $result1 = $stmt1->get_result();
        $this->userDetails = $result1->fetch_object();
    }

    private function updateUser($request)
    {
        $phone=$this->cleanPhone($request->phone);
        $emailConsent=$this->switchEmailConsent($request->emailConsent);
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET login_name=?, real_name1=?, phone_number=?, emailConsent=? WHERE id='".$this->userID."'");
        $stmt->bind_param("ssss", $request->user, $request->name, $phone, $emailConsent);
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to update.<br><br>Request: ".print_r($request, true)."<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        if (!empty($request->password)) {
            $this->updatePassword($request->password);
        }
        return true;
    }
    public function updatePassword($password)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $stmt=$this->mysqli->prepare("UPDATE pbc_minibar_user SET password=?,requireNewPassword=0 WHERE id='".$this->userID."'");
        $stmt->bind_param("s", $password);
        $stmt->execute();
        if (isset($stmt->error) && $stmt->error!='') {
            $report=new ToastReport;
            $m="User (".$this->userID.") failed to update password<br><br>DB Error: " . $stmt->error;
            $report->reportEmail("errors@theproteinbar.com", $m, "User error");
            return false;
        }
        return true;
    }
    public function cleanPhone($phone)
    {
        return preg_replace('/\D+/', '', $phone);
    }
    public function switchEmailConsent($emailConsent)
    {
        if ($emailConsent=='on') {
            return 1;
        }
        return 0;
    }
    public function getUserExists()
    {
        if ($this->userExists === true) {
            return true;
        }
        return false;
    }
    public function getGuestUser()
    {
        if ($this->guestUser === true) {
            return true;
        }
        return false;
    }
    public function setUserExists($var)
    {
        if ($var===true) {
            $this->loadUserDetails();
        }
        $this->userExists=$var;
    }
    public function getUserID()
    {
        return $this->userExists;
    }
    public function setUserID($var)
    {
        $this->userID=$var;
    }
    public function setGuestUser($var)
    {
        if ($var===true) {
            $this->loadUserDetails();
        }
        $this->guestUser=$var;
    }
    public function setmysqli($var)
    {
        $this->mysqli=$var;
    }
}

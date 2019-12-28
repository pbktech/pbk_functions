<?php
global $ret;
global $wp;
global $wpdb;
$current_user = wp_get_current_user();
$userLevel=get_currentuserinfo();
if($userLevel->user_level<=7) {
	$sql="SELECT GUID,restaurantName FROM pbc_pbrestaurants WHERE isOpen=1";
}else {
	$sql="SELECT GUID,restaurantName FROM pbc_pbrestaurants WHERE isOpen=1 AND pbc_pbrestaurants.GUID IN
(SELECT GUID FROM pbc_pbr_managers WHERE pbc_pbr_managers.managerID='" . $current_user->ID . "')";
}
$files=$wpdb->get_results($sql);
foreach($files as $file){
	$rests[$file->restaurantName]=$file->GUID;
}
if(isset($_GET['cguid']) && isset($_GET['sguid'])) {
	$toast = new Toast(trim($_GET['sguid']));
	date_default_timezone_set($toast->getTimeZone());
	$c=$toast->getCustomer($_GET['cguid']);
	$transactions=$toast->getCustomerTransactions($c->guid);
	$ret.="<h3>Customer Credit Transaction History</h3><div>
			<p><strong>".ucfirst($c->firstName)." ".ucfirst($c->lastName)."</strong></p>";
	foreach($transactions as $t){
		$ret.="<p>". date("m/d/Y",strtotime($t->transactionDate)) . " " .$t->transactionType . " " . money_format('%(#4n', $t->amount) . " <i>Expires: " . date("m/d/Y",strtotime($t->expirationDate)) ."</i> ".$t->note . "</p>";
	}
	$ret.="</div>
	";
}
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$toast = new Toast(trim($_POST['GUID']));
	date_default_timezone_set($toast->getTimeZone());
	if($_POST['part']==1) {
		$customers=$toast->findCustomerID(preg_replace("/[^0-9]/", "",$_POST['phone']));
		$ret.="<script>
			function validateFormPart(formName){
				var amount=document.forms[formName]['credit'].value;
				var note=document.forms[formName]['note'].value;
				var firstName=document.forms[formName]['firstName'].value;
				var lastName=document.forms[formName]['lastName'].value;
				if(amount.trim()=='' || isNaN(amount) || parseFloat(amount) > 20 || amount==0) {alert('Please Enter a Valid Credit Amount up to $20'); return false;}
				if(note.trim()== \"\"){alert('Please Enter a Valid Note'); return false;}
				if(firstName.trim()== \"\"){alert('Please Enter a Valid First Name'); return false;}
				if(lastName.trim()== \"\"){alert('Please Enter a Valid Last Name'); return false;}
				return true;
			}
			</script>
		";
		if(count($customers)==0){
			$ret.="<div>Customer Not Found, please create one below:</div><div>
			<form method='POST' name='addCust0' action='".home_url( $wp->request )."' onsubmit='return validateFormPart(\"addCust0\")' >
			<input type='text' class=\"form-control\" name='firstName' id='' placeholder='Customer First Name'/><br />
			<input type='text' class=\"form-control\" name='lastName' id='' placeholder='Customer Last Name'/><br />
			<input type='text' class=\"form-control\" name='email' id='' placeholder='E-mail Address'/><br />
			<input type='text' class=\"form-control\" name='credit' placeholder='Credit Amount'/><br />
			<input type='text' class=\"form-control\" name='note' maxlength='250' placeholder='Credit Reason' /><br />
			<input type='hidden' name='part' value='2' /><input type='hidden' name='phone' value='".preg_replace("/[^0-9]/", "",$_POST['phone'])."' />
			<input type='hidden' name='GUID' value='".$_POST['GUID']."' />
		<br /><input type='submit' value='Add' />
		</form></div>	";
		}else {
			$count=1;
			foreach($customers as $c){
				$credits=$toast->getCustCredits($c->guid);
				$ret.="<div>
							<p><strong>".ucfirst($c->firstName)." ".ucfirst($c->lastName). " ".$c->phone. " " .$c->email."</strong><br />";
						if(!isset($credits->amount) || $credits->amount=='' || $credits->amount==0) {
							$ret.="No Credits Assigned Yet";
						}else {
							$ret.="
							<a href='".home_url( $wp->request )."?cguid=".$c->guid."&amp;sguid=".trim($_POST['GUID'])."' target='_blank' >".money_format('%(#4n', $credits->amount)." Expires: ".date("m/d/Y",strtotime($credits->earliestExpirationDate))."</a>";
						}
				$ret.="	</p>
							<p><form method='POST' name='addCust".$count."' action='".home_url( $wp->request )."' onsubmit='return validateFormPart(\"addCust".$count."\")'>
								<input type='hidden' name='part' value='2' /><input type='hidden' name='guid' value='".$c->guid."' />
								<input type='text' class=\"form-control\" name='credit' placeholder='Credit Amount' /><br /><input type='text' class=\"form-control\" name='note' maxlength='250' placeholder='Credit Reason' />
								<input type='hidden' name='firstName' value='".$c->firstName."' />
								<input type='hidden' name='lastName' value='".$c->lastName."' />
								<input type='hidden' name='phone' value='".$c->phone."' />
								<input type='hidden' name='GUID' value='".$_POST['GUID']."' /><input type='hidden' name='part' value='2' />
								<br /><input type='submit' value='Add' />
								</form>
							</p>
						</div>
				";
				$count++;
			}
		}
	}
	if($_POST['part']==2) {
		if(!isset($_POST['guid'])) {
			$_POST['guid']=$toast->genGUID($_POST['firstName'].$_POST['lastName'].$_POST['phone'].time());
			$ph=array("guid"=>$_POST['guid'],"firstName"=>$_POST['firstName'],"lastName"=>$_POST['lastName'],"phone"=>$_POST['phone']);
			if(isset($_POST['email']) && $_POST['email']!='') {$ph['email']=$_POST['email'];}
			$addCust=$toast->addCustomer($ph);
			if(isset($addCust->status) && $addCust->status==400) {
				echo "<div>".$addCust->message."</div>";die();
			}
		}
		$date=date("Y-m-d")."T".date("G:i:s.000O");
		$ph=array("guid"=>$toast->genGUID($_POST['guid'].$_POST['credit'].$date),"transactionType"=>"ADD_VALUE","amount"=>$_POST['credit'],"localCreatedDate"=>$date,"note"=>$_POST['note']);
		$addCredit=$toast->addCustomerCredit($_POST['guid'],$ph);
		if(isset($addCredit->status) && $addCredit->status==400) {
			$ret.="<div>".$addCredit->message."</div>";die();
		}
		$wpdb->insert(
	'pbc_ToastCreditAdds',
	array('userID' => $current_user->ID, 'creditAmount' => $_POST['credit'], 'restaurantGUID' => $_POST['guid'],'note' => $_POST['note']),
	array('%d','%f','%s','%s') );
		echo "<script>window.location = \"".home_url( $wp->request )."?cguid=".$_POST['guid']."&sguid=".trim($_POST['GUID'])."\"</script>";
	}
		$ret.="<hr>";
}
if(!isset($_POST['phone'])){$_POST['phone']='';}
if(!isset($_POST['GUID'])){$_POST['GUID']='';}
$restaurant=new Restaurant();
$ret.="
<script>
	function validatePhoneNumber(elementValue){
		var phoneNumberPattern = /^\(?(\d{3})\)?[- ]?(\d{3})[- ]?(\d{4})$/;
		return phoneNumberPattern.test(elementValue);
	}
	function validateForm() {
		if(!validatePhoneNumber(document.forms['phoneSearch']['phone'].value)) {
			alert('Please Enter a Valid Phone Number');
			document.getElementById('phone').style.borderColor = '#ff0000';
        return false;
     }
	}
	jQuery(\"#GUID\").select2({
		theme: \"classic\"
	});
</script>
<div>
	<h3>Search for a Customer</h3>
	<form method='POST' name='phoneSearch' action='".home_url( $wp->request )."' onsubmit='return validateForm()'>
		<div class=\"form-group\">
			<input type='text' class=\"form-control\" name='phone' id='phone' value='".$_POST['phone']."' placeholder='Customer Phone Number'/>
		</div>
		<div class=\"form-group\">
		";
$ret.=$restaurant->buildRestaurantSelector(0,'GUID');
$ret.="
</div>
<div class=\"form-group\">
		<input type='hidden' name='part' value='1' />
		<input type='submit' value='Search' />
		</div>
	</form>
</div>";

<?php
/*
*
*
*
*
*
*
*
*/
class Restaurant {

	public $allRestaurants=array();
	public $rinfo=array();
	public $restaurantID=null;
	public $isAboveStore=0;
	public $incidentTypes=array(
		"foodborneIllness"=>array("Name"=>"Foodborne Illness/Foreign Object","sendTo"=>array("lcominsky@theproteinbar.com","vwillis@theproteinbar.com")),
		"injury"=>array("Name"=>"Injury","sendTo"=>array("lcominsky@theproteinbar.com","hr@theproteinbar.com")),
		"lostStolenProperty"=>array("Name"=>"Lost or Stolen Property","sendTo"=>array("lcominsky@theproteinbar.com","hr@theproteinbar.com","jarbitman@theproteinbar.com"))
	);
	public $myRestaurants=array();
	public $nhoSatus=array(
	"Position"=>array(1=>"TM",2=>"TLIT",3=>"MIT"),
	"Uniform"=>array(1=>"In Progress",2=>"Completed"),
	"FHR Onboarding"=>array(1=>"In Progress",2=>"Completed"),
	"Food Handler"=>array(1=>"In Progress",2=>"Submitted"),
	"Schedule"=>array(1=>"No",2=>"Yes"),
	"Section"=>array(1=>"FOH",2=>"BOH"),
	"Attendance"=>array(1=>"On Time",2=>"Late",3=>"No Show"),
	);
	public $Markets=array("Chicago","District of Columbia","Colorado");
	private $daysOfWeek=array("","","","","","","");

   public function __construct($restID=null) {
   	if(isset($restID) && is_numeric($restID)) {
   		$this->restaurantID=$restID;
   		if($this->checkNewRestaurant()) {
   			$this->checkRestaurantAccess();
   			$this->loadRestaurant();
   		}
   	}else {
   		$this->allRestaurants=$this->loadRestaurants();
   	}
		$this->checkAboveStore();
		$this->getMyRestaurants();
   }
	 function getMyRestaurants($field='restaurantID'){
		 if(isset($this->myRestaurants)){unset($this->myRestaurants);}
		 global $wp;
		 global $wpdb;
 		$cu = wp_get_current_user();
		$q="SELECT ".$field.",restaurantName FROM pbc_pbrestaurants WHERE isOpen=1 AND restaurantID!=0";
		if($this->isAboveStore==0){
			$q.=" AND restaurantID IN (SELECT restaurantID FROM pbc2.pbc_pbr_managers WHERE managerID='".$cu->ID."')";
		}
		 $rests=$wpdb->get_results($q,'ARRAY_A');
		 foreach ( $rests as $rest ){
			 $this->myRestaurants[$rest[$field]]=$rest['restaurantName'];
		 }
	 }
	public function checkAboveStore(){
		global $wp;
		$cu = wp_get_current_user();
		if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
			$this->isAboveStore=1;
		}
	}
	public function checkRestaurantAccess() {
		if(strpos(strtolower($_SERVER['REQUEST_URI']), 'directory')==false) {
			global $wpdb;
			global $current_user;
			$restaurants = $wpdb->get_results("SELECT mgrType FROM pbc_pbr_managers WHERE pbc_pbr_managers.restaurantID='".$this->restaurantID."'", OBJECT );
			if($restaurants[0]->mgrType != "AGM" && $restaurants[0]->mgrType != "GM" && $restaurants[0]->mgrType != "AM" && $restaurants[0]->mgrType != "SSC") {
				echo "<div class='error'><p>You do not have access to this restaurant.</p></div>";
				exit;
			}
		}
//		return $restaurants;
	}
	public function loadRestaurants() {
		global $wpdb;
		$restaurants = $wpdb->get_results("SELECT * FROM pbc2.pbc_pbrestaurants WHERE isOpen=1 AND restaurantID!=0");
		return $restaurants;
	}

	public function loadRestaurant() {
		global $wpdb;
		$wpdb->show_errors();
		$this->rinfo= $wpdb->get_row( "SELECT * FROM pbc_pbrestaurants WHERE restaurantID = '".$this->restaurantID."'");
	}

	public function setRestaurantInfo($var) {
		if(isset($this->rinfo)){unset($this->rinfo);}
		if(is_array($var)) {$var= (object) $var;}
		$this->rinfo=$var;
	}

	public function loadOtherRestaurantData() {
		global $wpdb;
	}

	private function checkNewRestaurant() {
		global $wpdb;
		$wpdb->show_errors();
		$restaurant = $wpdb->get_row( "SELECT * FROM pbc_pbrestaurants WHERE restaurantID = '".$this->restaurantID."'");
		if(isset($restaurant->city)) {
			return true;
		}else {
			return false;
		}
	}
	public function getAMRestaurants() {
		global $wpdb;
		$restaurant = $wpdb->get_results( "SELECT restaurantID, restaurantCode FROM pbc_pbrestaurants WHERE  pbc_pbrestaurants.restaurantID IN
(SELECT restaurantID FROM pbc_pbr_managers WHERE pbc_pbr_managers.mgrType='AM' AND pbc_pbr_managers.managerID IN
(SELECT managerID FROM pbc_pbr_managers WHERE pbc_pbr_managers.mgrType='AM' AND pbc_pbr_managers.restaurantID='".$this->restaurantID."'))");
		return $restaurant;
	}

	public function restaurantEditBox(){
		$allUsers=$this->getUserNames();
		$return= "

			<form method=\"post\" action=\"admin-post.php\">
         	<input type=\"hidden\" name=\"action\" value=\"pbr_save_restaurant_option\" />
         	<div style='float:left;'>
				<label for='restaurantID'>Restaurant ID</label><br /><input name='restaurantID' id='restaurantID' type='text' ";
		if(isset($this->rinfo->restaurantID)) { $return.= " value='".$this->rinfo->restaurantID."' ";}

		$return.= "/> <br />\n<label for='toastID'>Toast ID</label><br /><input name='toastID' id='toastID' type='text' ";
		if(isset($this->rinfo->toastID)) { $return.= " value='".$this->rinfo->toastID."' ";}

		$return.= "/> <br />\n<label for='GUID'>Toast GUID</label><br /><input name='GUID' id='GUID' type='text' ";
		if(isset($this->rinfo->GUID)) { $return.= " value='".$this->rinfo->GUID."' ";}

		$return.= "/> <br />\n<label for='levelUpID'>LevelUp ID</label><br /><input name='levelUpID' id='levelUpID' type='text' ";
		if(isset($this->rinfo->levelUpID)) { $return.= " value='".$this->rinfo->levelUpID."' ";}

		$return.= "/> <br />\n<label for='mnkyID'>Monkey ID</label><br /><input name='mnkyID' id='mnkyID' type='text' ";
		if(isset($this->rinfo->mnkyID)) { $return.= " value='".$this->rinfo->mnkyID."' ";}

		$return.= "/> <input name='microsID' id='microsID' type='hidden' value='".$this->rinfo->microsID."' /><br />\n<label for='restaurantName'>Restaurant Name</label><br /><input name='restaurantName' id='restaurantName' type='text' ";
		if(isset($this->rinfo->restaurantName)) { $return.= " value='".$this->rinfo->restaurantName."' ";}

		$return.= "/> <br />\n<label for='restaurantCode'>Restaurant Code</label><br /><input name='restaurantCode' id='restaurantCode' type='text' ";
		if(isset($this->rinfo->restaurantCode)) { $return.= " value='".$this->rinfo->restaurantCode."' ";}

		$return.= "/> <br />\n<label for='openingDate'>Opening Date</label><br /><input name='openingDate' id='openingDate' type='text' ";
		if(isset($this->rinfo->openingDate)) { $return.= " value='".$this->rinfo->openingDate."' ";}

		$return.= "/> <br />\n<label for='address1'>Address 1</label><br /><input name='address1' id='address1' type='text' ";
		if(isset($this->rinfo->address1)) { $return.= " value='".$this->rinfo->address1."' ";}

		$return.= "/> <br />\n<label for='address2'>Address 2</label><br /><input name='address2' id='address2' type='text' ";
		if(isset($this->rinfo->address2)) { $return.= " value='".$this->rinfo->address2."' ";}

		$return.= "/> <br />\n<label for='city'>City</label><br /><input name='city' id='city' type='text' ";
		if(isset($this->rinfo->city)) { $return.= " value='".$this->rinfo->city."' ";}

		$return.= "/> <br />\n<label for='zip'>Zip</label><br /><input name='zip' id='zip' type='text' ";
		if(isset($this->rinfo->zip)) { $return.= " value='".$this->rinfo->zip."' ";}

		$return.= "/> <br />\n<label for='state'>State</label><br /><input name='state' id='state' type='text' ";
		if(isset($this->rinfo->state)) { $return.= " value='".$this->rinfo->state."' ";}

		$return.= "/> <br />\n<label for='latLong'>Latitute & Longitude</label><br /><input name='latLong' id='latLong' type='text' ";
		if(isset($this->rinfo->latLong)) { $return.= " value='".$this->rinfo->latLong."' ";}

		$return.= "/> <br />\n<label for='phone'>Phone</label><br /><input name='phone' id='phone' type='text' ";
		if(isset($this->rinfo->phone)) { $return.= " value='".$this->rinfo->phone."' ";}

		$return.= "/> <br />\n<label for='email'>E-Mail</label><br /><input name='email' id='email' type='text' ";
		if(isset($this->rinfo->email)) { $return.= " value='".$this->rinfo->email."' ";}

		$return.= "/> <br />\n<label for='isOpen'>Is Open</label><br /><select name='isOpen' id='isOpen'><option value='1' ";
		if(isset($this->rinfo->isOpen) && $this->rinfo->isOpen==1) { $return.= " selected='selected' ";}
		$return.= ">Yes</option><option value='0' ";
		if(isset($this->rinfo->isOpen) && $this->rinfo->isOpen==0) { $return.= " selected='selected' ";}
		$return.= ">No</option></select></div>";

		$return.= "\n\n<div style='float:left;margin-left:50px;'>";

		$return.= "<label for='am'>AM</label><br /><select name='am' id='am'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if($this->getManagerID("AM")==$user->ID) {$return.=" selected='selected' ";}
			$return.=">".$user->display_name."</option>";
		}
		$return.= "</select><br />";

		$return.= "<label for='gm'>GM</label><br /><select name='gm' id='gm'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if($this->getManagerID("GM")==$user->ID) {$return.=" selected='selected' ";}
			$return.=">".$user->display_name."</option>";
		}
		$return.= "</select><br />";

		$return.= "<label for='agm'>AGM</label><br /><select name='agm' id='agm'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if($this->getManagerID("AGM")==$user->ID) {$return.=" selected='selected' ";}
			$return.=">".$user->display_name."</option>";
		}

		$return.= "</select><br /><label for='str'>Store Address</label><br /><select name='str' id='str'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if($this->getManagerID("STR")==$user->ID) {$return.=" selected='selected' ";}
			$return.=">".$user->display_name."</option>";
		}

		$return.= "</select><br /><label for='market'>Market</label><br /><select name='market' id='market'><option value=''>----------</option>";
		foreach($this->Markets as $market){
			$return.="<option value='".$market."'";
			if($this->rinfo->market==$market) {$return.=" selected='selected' ";}
			$return.=">".$market."</option>";
		}
		$return.= "</select><br />\n<h3>Restaurant Hours</h3>";
		$return.= " <br />\n<label for='timeZone'>Time Zone</label><br /><input name='timeZone' id='timeZone' type='text' ";
		if(isset($this->rinfo->timeZone)) { $return.= " value='".$this->rinfo->timeZone."' ";}

		$return.="/><table>";
//		echo strtotime("Monday");
		for($ia=1419206400;$ia<=1419724800;$ia+=86400) {

			$return.= "\n
			<script>
	jQuery(document).ready(function() {
		jQuery('#time_picker".$ia."o').timepicker({
			'timeFormat': 'h:mm p',
			interval: 15,
			    minTime: '5:00 am',
			    maxTime: '9:00 pm',
					dynamic: false,
					dropdown: true,
	 	    	scrollbar: true
		});
		jQuery('#time_picker".$ia."c').timepicker({
			'timeFormat': 'h:mm p',
			interval: 15,
			    minTime: '5:00 am',
			    maxTime: '9:00 pm',
					dynamic: false,
					dropdown: true,
	 	    	scrollbar: true
		});
	});
</script><tr><td><label for='".date("l",$ia)."'>".date("l",$ia)." Open</label></td><td><label for='".date("l",$ia)."'>".date("l",$ia)." Close</label></td></tr>
<tr><td>
<input id='time_picker".$ia."o' name='".date("l",$ia)."open' id='".date("l",$ia)."' value='".$this->getHours(date("l",$ia)."open")."' style='width: 100px;'/></td><td>
			<input id='time_picker".$ia."c' name='".date("l",$ia)."close' id='".date("l",$ia)."' value='".$this->getHours(date("l",$ia)."close")."' style='width: 100px;'/></td></tr>
			<br />";
	}
      $return.= "</table></div><div style='clear:both;'></div><br /><br /><input type=\"submit\" value=\"Submit\" class=\"button-primary\"/></form> <button type=\"button\" onclick=\"javascript:window.location='admin.php?page=pbr-edit-restaurant';\">Cancel</button>";

      return $return;
	}
	public function insertUpdateRestaurantInfo() {
		global $wpdb;
		if($wpdb->replace(
	'pbc_pbrestaurants',
	array(
    'restaurantID' => $this->rinfo->restaurantID,
		'toastID' => $this->rinfo->toastID,
		'GUID' => $this->rinfo->GUID,
		'levelUpID' => $this->rinfo->levelUpID,
		'microsID' => $this->rinfo->microsID,
		'restaurantName' => $this->rinfo->restaurantName,
		'restaurantCode' => $this->rinfo->restaurantCode,
		'openingDate' => $this->rinfo->openingDate,
		'address1' => $this->rinfo->address1,
		'address2' => $this->rinfo->address2,
		'city' => $this->rinfo->city,
		'state' => $this->rinfo->state,
		'zip' => $this->rinfo->zip,
		'phone' => $this->rinfo->phone,
		'email' => $this->rinfo->email,
		'timeZone' => $this->rinfo->timeZone,
		'isOpen' => $this->rinfo->isOpen,
		'mnkyID' => $this->rinfo->mnkyID,
		'latLong' => $this->rinfo->latLong,
		'market' => $this->rinfo->market
	),
	array('%d','%d','%s','%s','%d','%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
		)){
			$wpdb->replace('pbc_pbr_managers',array( 'restaurantID'=> $this->rinfo->restaurantID, 'managerID'=> $this->rinfo->am, 'mgrType' => 'AM'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_managers',array( 'restaurantID'=> $this->rinfo->restaurantID, 'managerID'=> $this->rinfo->gm, 'mgrType' => 'GM'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_managers',array( 'restaurantID'=> $this->rinfo->restaurantID, 'managerID'=> $this->rinfo->agm, 'mgrType' => 'AGM'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_managers',array( 'restaurantID'=> $this->rinfo->restaurantID, 'managerID'=> $this->rinfo->str, 'mgrType' => 'STR'), array('%d','%s','%s'));

			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Mondayopen, 'type' => 'Mondayopen'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Mondayclose, 'type' => 'Mondayclose'), array('%d','%s','%s'));

			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Tuesdayopen, 'type' => 'Tuesdayopen'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Tuesdayclose, 'type' => 'Tuesdayclose'), array('%d','%s','%s'));

			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Wednesdayopen, 'type' => 'Wednesdayopen'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Wednesdayclose, 'type' => 'Wednesdayclose'), array('%d','%s','%s'));

			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Thursdayopen, 'type' => 'Thursdayopen'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Thursdayclose, 'type' => 'Thursdayclose'), array('%d','%s','%s'));

			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Fridayopen, 'type' => 'Fridayopen'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Fridayclose, 'type' => 'Fridayclose'), array('%d','%s','%s'));

			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Saturdayopen, 'type' => 'Saturdayopen'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Saturdayclose, 'type' => 'Saturdayclose'), array('%d','%s','%s'));

			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Sundayopen, 'type' => 'Sundayopen'), array('%d','%s','%s'));
			$wpdb->replace('pbc_pbr_hours',array( 'restaurantID'=> $this->rinfo->restaurantID, 'hoursInfo'=> $this->rinfo->Sundayclose, 'type' => 'Sundayclose'), array('%d','%s','%s'));
			return true;
		}else {
			$wpdb->show_errors();
			$wpdb->print_error();
			die("There was an error.");
			return false;
		}
	}
	function switchBackgroundColor($color){
		switch($color){
			case 2:
				return "p-3 mb-2 bg-success text-white";
			case 1:
				return "p-3 mb-2 bg-danger text-white";
		}
		return "p-3 mb-2 bg-danger text-white";
	}
	function nhoHeader($atts){
		$nhoTime=json_decode($atts['nhoTIme']);
		return '
			<div class="form-group">
					<div class="row" style="text-align: center; color: #ffffff; background-color: #f36c21;">
						<div class="col"><h3 style="color: #ffffff;">NHO Roster for '.date("m/d/Y",strtotime($atts['nhoDate'])).' at '.$atts['restaurantName'].'<br>hosted by '.$atts['display_name'].'</h3></div>
				</div>
				<div class="row"  style="background-color: #B2D235; color: #ffffff; text-align: center;">
					<div class="col"><h3 style="color: #ffffff;">'.$nhoTime->Start.' - '.$nhoTime->End.'</h3></div>
				</div>
				<div class="row" style="background-color: #0e2244; color: #ffffff; text-align: center;">
					<div class="col" ><div style="width:180px;"><strong>Name</strong><br><strong>Location</strong></div></div>
					<div class="col"><strong>Position</strong></div>
					<div class="col"><strong>FOH/BOH</strong></div>
					<div class="col"><strong>Uniform</strong></div>
					<div class="col"><strong>FHR Onboarding</strong></div>
					<div class="col"><strong>Food Handler</strong></div>
					<div class="col"><strong>Schedule</strong></div>
					</div>
				</div>
		';
	}
	function nho_attendance($nho){
		$attendees=$this->getNHOAttendees($nho['nhoID']);
		$return="
		<div>
			<form method=\"post\" action=\"admin-post.php\">
			<input type=\"hidden\" name=\"action\" value=\"pbr_nho_attendance_update\" />
			<input type=\"hidden\" name=\"nhoDate\" value=\"".$nho['nhoDate']."\" />
			<input type=\"hidden\" name=\"nhoTime\" value=\"".$nho['nhoTime']."\" />
			<input type=\"hidden\" name=\"nhoLocation\" value=\"".$nho['nhoLocation']."\" />
			<table><tr><td>Employee Name</td><td>On Time?</td><td>Notes</td></tr>
			";
			if($attendees){
				foreach($attendees as $attendee){
					$json=json_decode($attendee->attendance);
					$return.="
					<tr>
					<td>".stripslashes($attendee->employeeName)." <input type='hidden' name='attendeeID[]' value='".$attendee->attendeeID."' /><input type='hidden' name='employeeName[".$attendee->attendeeID."]' value='".$attendee->employeeName."' /></td>
					<td><select name='att[".$attendee->attendeeID."][clock]'>
					";
					foreach ($this->nhoSatus["Attendance"] as $key => $value) {
						if($key==$json->clock){$selected='selected';}else{$selected='';}
						$return.='<option value="'.$key.'" '.$selected.' >'.$value.'</option>';
					}
					$return.="</select></td>
					<td><input type='text'
					name='att[".$attendee->attendeeID."][notes]' placeholder='Notes'
					value='".$json->notes."'/></td>
					</tr>";
				}
			}
			$return.="
			</table>
			<input type='submit' value='Save and Send to HR' />
			</form>
		</div>";
		return $return;
	}
	function updateNHOAttendance($atts){
		global $wpdb;
		global $wp;
		$email="<div>Time: ".$atts['nhoTime']."</div><table><tr><td>Employee Name</td><td>On Time?</td><td>Notes</td></tr>";
		foreach($atts['attendeeID'] as $attendeeID){
			$email.="<tr><td>".$atts['employeeName'][$attendeeID]."</td><td>".$this->nhoSatus["Attendance"][$atts['att'][$attendeeID]['clock']]."</td><td>".$atts['att'][$attendeeID]['notes']."</td></tr>";
			$json=json_encode($atts['att'][$attendeeID]);
			$wpdb->query(
			$wpdb->prepare( "
			UPDATE pbc_nhoAttenndess SET attendance=%s WHERE attendeeID=%s
			",$json,$attendeeID));
		}
		$email.="</table>";
		$cu = wp_get_current_user();
		$report=new ToastReport();
		$report->reportEmail($cu->user_email.",hr@theproteinbar.com",$email,"NHO Attendance Report for ".date("m/d/Y", strtotime($atts['nhoDate'])));
	}
	function nho_build_roster($atts){
		global $wpdb;
		$nho=$wpdb->get_row('SELECT nhoID,restaurantName,display_name,nhoTIme FROM pbc2.pbc_NHOSchedule,pbc_users,pbc_pbrestaurants WHERE nhoLocation=restaurantID AND ID=nhoHost AND nhoDate="'.$atts['nhoDate'].'" AND nhoLocation="'.$atts['nhoLocation'].'"');
		$return="<div style='width:100%;'><table style='width:100%;'>";
		$return.=$this->nhoHeader(array("nhoDate"=>$atts['nhoDate'],"restaurantName"=>$nho->restaurantName,"display_name"=>$nho->display_name,"nhoTIme"=>$nho->nhoTIme));
		$attendees=$this->getNHOAttendees($nho->nhoID);
		if($attendees){
			foreach($attendees as $attendee){
				$json=json_decode($attendee->attData);
				$return.='
			<tr style="background-color: #ffffff; text-align: center;">
				<td>'.stripslashes($attendee->employeeName).'</td>
				<td>'.$this->getRestaurantName($attendee->restaurant).'</td>
				<td>'.$this->nhoSatus["Position"][$json->uni].'</td>
				<td class="'.$this->switchBackgroundColor($json->section).'">'.$this->nhoSatus["Section"][$json->section].'</td>
				<td class="'.$this->switchBackgroundColor($json->uni).'">'.$this->nhoSatus["Uniform"][$json->uni].'</td>
				<td class="'.$this->switchBackgroundColor($json->fhro).'">'.$this->nhoSatus["FHR Onboarding"][$json->fhro].'</td>
				<td class="'.$this->switchBackgroundColor($json->fh).'">'.$this->nhoSatus["Food Handler"][$json->fh].'</td>
				<td class="'.$this->switchBackgroundColor($json->schedule).'">'.$this->nhoSatus["Schedule"][$json->schedule].'</td>
			</tr>
			';
		}
	}else {
		$return.="<tr><td colspan='7'>No one signed up, yet</td></tr>";
	}
		$return.="</tbody></table></div>";
		return $return;
	}
	function nho_post_event(){

	}
	function nho_sign_up_manage($atts=null){
		if(isset($atts['nhoDate']) && isset($atts['nhoLocation']) && $atts['nhoDate']!="_new"){
			global $wpdb;
			$nho=$wpdb->get_row('SELECT * FROM pbc_NHOSchedule WHERE nhoDate="'.$atts['nhoDate'].'" AND nhoLocation="'.$atts['nhoLocation'].'"',ARRAY_A);
			$nhoTime=json_decode($nho['nhoTime'],true);
			if(!$nho['nhoID'] || $nho['nhoID']==''){return "Invalid Location and date, please try again.";}else{$get['nhoID']=$nho['nhoID'];}
		}else{
			$nho['nhoDate']=date("Y-m-d");
			$nho['nhoHost']="";
			$nho['nhoID']="";
			$nho['nhoTime']=array("Start"=>'',"End"=>'');
			$nho['nhoLocation']="";
			$nho['maxParticipants']=7;
		}
		$allUsers=$this->getUserNames();
		$rests=$this->loadRestaurants();
		if($atts['r']==0 || $atts['nhoDate']=="_new"){
		$return= "
		<link rel='stylesheet' id='wp-block-library-css'  href='https://c2.theproteinbar.com/wp-includes/css/dist/block-library/style.min.css?ver=5.2' type='text/css' media='all' />
<link rel='stylesheet' id='jquery-ui-standard-css-css'  href='//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css?ver=5.2' type='text/css' media='all' />
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('#nhoDate').datepicker({
        dateFormat : 'mm/dd/yy'
    });
		jQuery('#time_picker_start').timepicker({
			'timeFormat': 'h:mm p',
			interval: 15,
			    minTime: '5:00 am',
			    maxTime: '9:00 pm',
					dynamic: false,
					dropdown: true,
	 	    	scrollbar: true
		 });
		jQuery('#time_picker_end').timepicker({
			'timeFormat': 'h:mm p',
			interval: 15,
			    minTime: '5:00 am',
			    maxTime: '9:00 pm',
					dynamic: false,
					dropdown: true,
	 	    	scrollbar: true
		 });
});
</script>
		<div>
			<form method=\"post\" action=\"admin-post.php\">
			<input type=\"hidden\" name=\"action\" value=\"pbr_save_nho\" />
			<input type=\"hidden\" name=\"nhoID\" value='".$nho['nhoID']."'\" />
					<label for='nhoDate'>NHO Date</label><br /><input type=\"text\" id=\"nhoDate\" name=\"nhoDate\" value=\"".date("Y-m-d",strtotime($nho['nhoDate']))."\"/><br />
					";
					$return.= "<label for='nhoHost'>NHO Host</label><br /><select name='nhoHost' id='nhoHost'><option value=''>----------</option>";
					foreach($allUsers as $user){
						$return.="<option value='".$user->ID."'";
						if($nho['nhoHost']==$user->ID) {$return.=" selected='selected' ";}
						$return.=">".$user->display_name."</option>";
					}
					$return.= "</select><br />
					<label for='nhoLocation'>NHO Location</label><br /><select name='nhoLocation' id='nhoLocation'><option value=''>----------</option>";
					foreach($rests as $r){
						$return.="<option value='".$r->restaurantID."'";
						if($nho['nhoLocation']==$r->restaurantID) {$return.=" selected='selected' ";}
						$return.=">".$r->restaurantName."</option>";
					}
					$return.= "</select><br />
					<label for='startTime'>Start Time</label><br />
					<input id='time_picker_start' name='nhoTime[Start]' value='".$nhoTime['Start']."'/><br />
					<label for='startTime'>End Time</label><br />
					<input id='time_picker_end' name='nhoTime[End]' value='".$nhoTime['End']."'/><br />
			<label for='maxParticipants'>Max Peeps</label><br /><input type=\"text\" id=\"maxParticipants\" name=\"maxParticipants\" value=\"".$nho['maxParticipants']."\"/><br />
			<input type='submit' value='Save' />
			</form>
		</div>
		<script type='text/javascript' src='https://c2.theproteinbar.com/wp-includes/js/jquery/ui/datepicker.min.js?ver=1.11.4'></script>
		<script type='text/javascript'>";
		$return.= '
jQuery(document).ready(function(jQuery){jQuery.datepicker.setDefaults({"closeText":"Close","currentText":"Today","monthNames":["January","February","March","April","May","June","July","August","September","October","November","December"],"monthNamesShort":["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],"nextText":"Next","prevText":"Previous","dayNames":["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],"dayNamesShort":["Sun","Mon","Tue","Wed","Thu","Fri","Sat"],"dayNamesMin":["S","M","T","W","T","F","S"],"dateFormat":"MM d, yy","firstDay":1,"isRTL":false});});
</script>
';
	if($atts['nhoDate']!="_new"){
		$return.=$this->nho_attendance(array("nhoLocation"=>$nho['nhoLocation'],"nhoDate"=>$nho['nhoDate'],"nhoID"=>$nho['nhoID'],"nhoTime"=>$nhoTime['Start']." - ".$nhoTime['End']));
	}
}elseif($atts['r']==1){
	$return=$this->nhoHistory($get);
	if($file=$this->buildNHORosterFile($return)){
		$return.="<p><a href='$file' target='_blank'>Printable PDF</a></p>";
	}
}
	return $return;
	}

	function buildNHOAttendeeLine($att=null,$disable=0){
		if(isset($att)){
			global $wpdb;
			$attendee=$wpdb->get_row("SELECT * FROM pbc2.pbc_nhoAttenndess WHERE attendeeID='$att'",ARRAY_A);
		}else{
			$attendee['attendeeID']='NEW';
			$attendee['employeeName']='';
			$attendee['restaurant']='';
			$attendee['attData']=json_encode(array("position"=>"","section"=>"","uni"=>"","fhro"=>"","fh"=>"","schedule"=>""));
		}
		if($disable==1){$disabled=' disabled ';}else {
			$disabled='';
		}
		$json=json_decode($attendee['attData']);
		$return='
		<div class="row" style="text-align: center;">
			<div class="col"><input type="text" class="form-control" name="nhoStatus['.$attendee['attendeeID'].'][employeeName]" id="employeeName" value="'.$attendee['employeeName'].'" style="width:180px;"/>
			<input type="hidden" name="nhoStatus['.$attendee['attendeeID'].'][attendeeID]" value="'.$attendee['attendeeID'].'" /><br>
			';
		if(is_array($this->myRestaurants) && count($this->myRestaurants)==1 && $attendee['attendeeID']=='NEW'){
			foreach($this->myRestaurants as $key => $value){
				$return.='<input type="hidden" name="nhoStatus['.$attendee['attendeeID'].'][restaurantID]" value="'.$key.'"/>'.$value;
			}
		}elseif(is_array($this->myRestaurants) && count($this->myRestaurants)==1 && $attendee['attendeeID']!='NEW') {
			$return.='<input type="hidden" name="nhoStatus['.$attendee['attendeeID'].'][restaurantID]" value="'.$attendee['restaurant'].'"/>'.$this->getRestaurantName($attendee['restaurant']);
		}else{
			$return.='
			<select class="custom-select custom-select-sm" name="nhoStatus['.$attendee['attendeeID'].'][restaurantID]" '.$disabled.'>
			<option value="">Choose One</option>';
			foreach ($this->myRestaurants as $key => $value) {
				if($key==$attendee['restaurant']){$selected='selected';}else{$selected='';}
				$return.='<option value="'.$key.'" '.$selected.' >'.$value.'</option>';
			}
			$return.='</select>';
		}
		$return.='';
		$return.='
			</div>
			<div class="col" style="text-align:left;">';
			foreach ($this->nhoSatus["Position"] as $key => $value) {
				if($key==$json->position){$selected=' checked="checked" ';}else{$selected='';}
				$return.='
				<input type="radio" name="nhoStatus['.$attendee['attendeeID'].'][position]" value="'.$key.'"'.$selected.' '.$disabled.'/> '.$value.'<br>';
			}
			$return.='
				</div>
				<div class="col" style="text-align:left;">';
				foreach ($this->nhoSatus["Section"] as $key => $value) {
					if($key==$json->section){$selected=' checked="checked" ';}else{$selected='';}
					$return.='
					<input type="radio" name="nhoStatus['.$attendee['attendeeID'].'][section]" value="'.$key.'"'.$selected.' '.$disabled.'/> '.$value.'<br>';
				}
			$return.='
			</div>
			<div class="col '.$this->switchBackgroundColor($json->uni).'" style="text-align:left;white-space:nowrap;"  id="Uniform_td_'.$attendee['attendeeID'].'">';
			foreach ($this->nhoSatus["Uniform"] as $key => $value) {
				if($key==$json->uni){$selected=' checked="checked" ';}else{$selected='';}
				$return.='
				<input type="radio" name="nhoStatus['.$attendee['attendeeID'].'][uni]" value="'.$key.'" id="Uniform_'.$attendee['nhoID'].'"'.$selected.' '.$disabled.' onclick="changeBackground(\'Uniform\',\''.$attendee['attendeeID'].'\','.$key.');"/> '.$value.'<br>';
			}
			$return.='
			</div>
			<div class="col '.$this->switchBackgroundColor($json->fhro).'" style="text-align:left;white-space:nowrap;" id="FirstHR_td_'.$attendee['attendeeID'].'">';
			foreach ($this->nhoSatus["FHR Onboarding"] as $key => $value) {
				if($key==$json->fhro){$selected=' checked="checked" ';}else{$selected='';}
				$return.='
				<input type="radio" name="nhoStatus['.$attendee['attendeeID'].'][fhro]" value="'.$key.'" id="FirstHR_'.$attendee['nhoID'].'"'.$selected.' '.$disabled.' onclick="changeBackground(\'FirstHR\',\''.$attendee['attendeeID'].'\','.$key.');"/> '.$value.'<br>';
			}
			$return.='
			</div>
			<div class="col '.$this->switchBackgroundColor($json->fh).'" style="text-align:left;white-space:nowrap;" id="FoodHandler_td_'.$attendee['attendeeID'].'">';
			foreach ($this->nhoSatus["Food Handler"] as $key => $value) {
				if($key==$json->fh){$selected=' checked="checked" ';}else{$selected='';}
				$return.='
				<input type="radio" name="nhoStatus['.$attendee['attendeeID'].'][fh]" value="'.$key.'" id="FoodHandler_'.$attendee['nhoID'].'"'.$selected.' '.$disabled.' onclick="changeBackground(\'FoodHandler\',\''.$attendee['attendeeID'].'\','.$key.');"/> '.$value.'<br>';
			}
			$return.='
			</div>
			<div class="col '.$this->switchBackgroundColor($json->schedule).'" style="text-align:left;white-space:nowrap;" id="Schedule_td_'.$attendee['attendeeID'].'">';
						foreach ($this->nhoSatus["Schedule"] as $key => $value) {
							if($key==$json->schedule){$selected=' checked="checked" ';}else{$selected='';}
							$return.='
							<input type="radio" name="nhoStatus['.$attendee['attendeeID'].'][schedule]" value="'.$key.'" id="Schedule_'.$attendee['nhoID'].'"'.$selected.' '.$disabled.' onclick="changeBackground(\'Schedule\',\''.$attendee['attendeeID'].'\','.$key.');"/> '.$value.'<br>';
						}
						$return.='
				</div>
		</div>
		';
		return $return;
	}
	function buildNHORosterFile($item){
	  $report=new ToastReport();
	  $title="NHO Roster for ".date("m-d-Y",strtotime($item['nhoDate']));
	  $mpdf = new \Mpdf\Mpdf([
	  	'mode' => 'c',
	    'format' => 'A4-L',
	  	'margin_left' => 5,
	  	'margin_right' => 5,
	  	'margin_top' => 5,
	  	'margin_bottom' => 5,
	  	'margin_header' => 0,
	  	'margin_footer' => 0
	  ]);
	  $mpdf->SetTitle($title);
	  $mpdf->SetAuthor("Protein Bar & Kitchen");
	  $mpdf->WriteHTML(utf8_encode($item));
	  $mpdf->Output($report->docSaveLocation.str_replace(" ","_",$title).".pdf", 'F');
		if(file_exists($report->docSaveLocation.str_replace(" ","_",$title).".pdf")){return $report->docDownloadLocation.str_replace(" ","_",$title).".pdf";}else {
			return false;
		}
	}
	function buildHTMLPDF($content){
		$content=json_decode($content);
	  $report=new ToastReport();
	  $mpdf = new \Mpdf\Mpdf([
	  	'mode' => 'c',
	    'format' => $content->format,
	  	'margin_left' => 5,
	  	'margin_right' => 5,
	  	'margin_top' => 5,
	  	'margin_bottom' => 5,
	  	'margin_header' => 0,
	  	'margin_footer' => 0,
			'CSSselectMedia' => 'Screen'
	  ]);
		$stylesheet=file_get_contents(dirname(dirname(__FILE__)) . "/assets/css/mpdf-bootstrap.css");
	  $mpdf->SetTitle($content->title);
	  $mpdf->SetAuthor("Protein Bar & Kitchen");
		$mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
	  $mpdf->WriteHTML(utf8_encode($content->html),\Mpdf\HTMLParserMode::HTML_BODY);
	  $mpdf->Output($report->docSaveLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf", 'F');
		if(file_exists($report->docSaveLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf")){
			return array("Link"=>$report->docDownloadLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf","Local"=>$report->docSaveLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf");
		}else {
			return false;
		}
	}
	function getNHOEvents(){
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM pbc2.pbc_NHOSchedule WHERE nhoDate>=CURDATE()-2", ARRAY_A);
	}
	function updateNHO($nho){
		global $wpdb;
		$nho['nhoDate']=date("Y-m-d",strtotime($nho['nhoDate']));
		$nhoTime=json_encode($nho['nhoTime']);
		$wpdb->query(
			$wpdb->prepare( "
				REPLACE INTO pbc_NHOSchedule (nhoDate,nhoHost,nhoLocation,maxParticipants,nhoID,nhoTime)VALUES(%s,%s,%s,%s,%s,%s)",$nho['nhoDate'],$nho['nhoHost'],$nho['nhoLocation'],$nho['maxParticipants'],$nho['nhoID'],$nhoTime));
			wp_redirect(  admin_url( 'admin.php?page=pbr-nho' ) );
	}
	function updateNHOAttendee($nhos){
		global $wpdb;
		foreach($nhos['nhoStatus'] as $nho){
			if($nho['employeeName']!=''){
				$attData=json_encode(array("position"=>$nho["position"],"section"=>$nho["section"],"uni"=>$nho["uni"],"fhro"=>$nho["fhro"],"fh"=>$nho["fh"],"schedule"=>$nho['schedule']));
				if($nho['attendeeID']=='NEW'){
					$wpdb->query(
						$wpdb->prepare( "
						INSERT IGNORE INTO pbc_nhoAttenndess (nhoID,employeeName,attData,restaurant)VALUES(%s,%s,%s,%s)",$nhos['nhoID'],$nho['employeeName'],$attData,$nho['restaurantID']));
				}else{
						$wpdb->query(
							$wpdb->prepare( "
							UPDATE pbc_nhoAttenndess SET employeeName=%s,attData=%s,restaurant=%s WHERE attendeeID=%s
							",$nho['employeeName'],$attData,$nho['restaurantID'],$nho['attendeeID']));
				}
			}
			if($wpdb->last_error !== '') {
				$rpt= new ToastReport();
				$rpt->reportEmail("jon@theproteinbar.com","SQL Error \n".$wpdb->print_error()."\n\nPosted Data \n".print_r($nho,true),"NHO Save Error");
				return "Fail";
			}
		}
		return "Success";
	}
	function nhoHistory($get){
		global $wpdb;
		if (!isset($get['nhoID'])) {
			$nhoEvents=$this->getPreviousNHOEvents(3);
			$return="
			<div>
			<form method=\"get\" action=\"admin.php\">
				<input type=\"hidden\" name=\"page\" value=\"pbr-nho-archive\" />
				<select name='nhoID' onchange='this.form.submit()'>
					<option value=''>Choose One</option>
			";
			foreach($nhoEvents as $event){
				$return.="
					<option value=".$event->nhoID.">".date("m/d/Y",strtotime($event->nhoDate)) . " at ".$event->restaurantName." with ".$event->display_name."</option>";
			}
			$return.="
				</select>
			</form>
			</div>
			";
		}else{
			$event=$this->getNHOAttendees($get['nhoID']);
			$nho=$wpdb->get_row('SELECT nhoID,restaurantName,display_name,nhoTIme,nhoDate FROM pbc2.pbc_NHOSchedule,pbc_users,pbc_pbrestaurants WHERE nhoLocation=restaurantID AND ID=nhoHost AND nhoID="'.$get['nhoID'].'"');
			$nhoTime=json_decode($nho->nhoTIme);
			$return='
			<div style="width:100%;text-align:center;">
				<table style="width:100%;text-align:center;">
				<thead>
					<tr style="text-align: center; color: #ffffff; background-color: #f36c21;">
						<td colspan="10"><h3 style="color: #ffffff;text-align:center;">NHO Roster for '.date("m/d/Y",strtotime($nho->nhoDate)).' at '.$nho->restaurantName.'<br>hosted by '.$nho->display_name.'</h3></td></tr>
				</thead>
				<tbody>
				<tr style="background-color: #B2D235; color: #ffffff; text-align: center;">
					<td colspan="10"><h3 style="color: #ffffff;text-align:center;">'.$nhoTime->Start.' - '.$nhoTime->End.'</h3></td>
				</tr>
				<tr style="background-color: #0e2244; color: #ffffff; text-align: center;">
					<td><strong>Name</h5></td>
					<td><strong>Location</h5></td>
					<td><strong>Position</h5></td>
					<td><strong>FOH/BOH</h5></td>
					<td><strong>Uniform</h5></td>
					<td><strong>FHR Onboarding</h5></td>
					<td><strong>Food Handler</h5></td>
					<td><strong>Schedule</h5></td>
					<td><strong>Attendance</h5></td>
					<td><strong>Notes</h5></td>
				</tr>
				';
			foreach($event as $e){
				$att=json_decode($e->attData);
				$attendance=json_decode($e->attendance);
				$return.='
				<tr style="text-align: center;">
					<td>'.stripslashes($e->employeeName).'</td>
					<td>'.$this->getRestaurantName($e->restaurant).'</td>
					<td>'.$this->nhoSatus['Position'][$att->position].'</td>
					<td>'.$this->nhoSatus['Section'][$att->section].'</td>
					<td>'.$this->nhoSatus['Uniform'][$att->uni].'</td>
					<td>'.$this->nhoSatus['FHR Onboarding'][$att->fhro].'</td>
					<td>'.$this->nhoSatus['Food Handler'][$att->fh].'</td>
					<td>'.$this->nhoSatus['Schedule'][$att->schedule].'</td>
					<td>'.$this->nhoSatus['Attendance'][$attendance->clock].'</td>
					<td>'.$attendance->notes.'</td>
				</tr>
				';
			}
			$return.="
				</table>
			</div>";
		}
		return $return;
	}
	 function get_incident_reports(){
		global $wpdb;
		$results=$wpdb->get_results("SELECT * FROM pbc_incident_reports WHERE dateOfIncident BETWEEN '".date("Y-m-d",strtotime($_GET['startDate']))." 00:00:00' AND '".date("Y-m-d",strtotime($_GET['endDate']))." 23:59:59' OR reportAdded  BETWEEN '".date("Y-m-d",strtotime($_GET['startDate']))." 00:00:00' AND '".date("Y-m-d",strtotime($_GET['endDate']))." 23:59:59'");
		if($results){
			return $results;
		}
		return false;
	}
		private function getPreviousNHOEvents($months) {
			global $wpdb;
			return $wpdb->get_results("SELECT nhoDate,nhoID,restaurantName,display_name FROM pbc2.pbc_NHOSchedule,pbc_users,pbc_pbrestaurants WHERE nhoDate >= DATE_SUB(curdate(),INTERVAL ".$months." MONTH)
AND pbc_users.id=nhoHost AND pbc_pbrestaurants.restaurantID=nhoLocation");
		}
		private function getUserNames() {
			global $wpdb;
			return $wpdb->get_results("SELECT ID, display_name FROM $wpdb->users WHERE user_status = '0' ");
		}
	function getNHOAttendees($nho) {
		global $wpdb;
		$q="SELECT * FROM pbc_nhoAttenndess WHERE nhoID='$nho'";
		if($this->isAboveStore==0){
			foreach($this->myRestaurants as $key => $value){
				$rest[]="restaurant='".$key."'";
			}
			$q.=" AND (".implode(" OR ",$rest).")";
		}
		return $wpdb->get_results($q);
	}
	private function getManagerID($mgr) {
		global $wpdb;
		return $wpdb->get_var( "SELECT managerID FROM pbc_pbr_managers WHERE restaurantID='".$this->rinfo->restaurantID."' AND mgrType='$mgr'");
	}
	function getNHOInfo($nhoID) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare( "SELECT nhoID,restaurantName,display_name,nhoTIme,nhoDate FROM pbc2.pbc_NHOSchedule,pbc_users,pbc_pbrestaurants WHERE nhoLocation=restaurantID AND ID=nhoHost AND pbc_NHOSchedule.nhoID=%s",$nhoID));
	}
	private function convertTime($t) {
		$t=$t/60;
		return $t;
	}
	public function getManagerName($mgr) {
		global $wpdb;
		return $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE ID in (SELECT managerID FROM pbc_pbr_managers WHERE restaurantID='".$this->rinfo->restaurantID."' AND mgrType='$mgr')");
	}
	public function getManagerEmail($mgr) {
		global $wpdb;
		return $wpdb->get_var( "SELECT user_email FROM $wpdb->users WHERE ID in (SELECT managerID FROM pbc_pbr_managers WHERE restaurantID='".$this->rinfo->restaurantID."' AND mgrType='$mgr')");
	}
	public function getHours($type) {
		global $wpdb;
		return $wpdb->get_var( "SELECT hoursInfo FROM pbc_pbr_hours WHERE restaurantID='".$this->rinfo->restaurantID."' AND type='$type'");
	}
	public function getRestaurantName($type) {
		global $wpdb;
		return $wpdb->get_var( "SELECT restaurantName FROM pbc_pbrestaurants WHERE restaurantID='".$type."'");
	}
	public function buildLoggedInName($name="reporterName"){
		global $wpdb;
		global $wp;
		$cu = wp_get_current_user();
		$verify=$wpdb->get_var( "SELECT mgrType FROM pbc_pbr_managers WHERE managerID='".$cu->ID."'");
		if(isset($verify->mgrType) && $verify->mgrType=="STR"){
			$value="";
		}else {
			$value=esc_html( $cu->user_firstname ) . " " . esc_html( $cu->user_lastname );
		}
		return "<input class=\"form-control\" type='text' name='".$name."' value='".$value."' required />";
	}
	public function buildRestaurantSelector($single=0,$field='restaurantID'){
		$this->getMyRestaurants($field);
		if(count($this->myRestaurants)==0){
			return "<div class='alert alert-danger'>No Restaurants Assigned</div>";
		}elseif (count($this->myRestaurants)==1) {
			return "<input type='hidden' value='".$this->myRestaurants[1]."' name='$field' /><div>".$this->myRestaurants[0]."</div>";
		}else {
			if($single==0){
				$return= "
					<select name='".$field."' class=\"custom-select multipleSelect\" required id='".$field."'>
						<option value=''>Choose One</option>
					";
				foreach($this->myRestaurants as $id=>$name){
					$return.="
						<option value='$id'>$name</option>
						";
				}
				$return.="
					</select>";
				}
			return $return;
		}
	}
	public function pbk_form_processing(){
		return "
		<script>
			jQuery(document).ready(function() {
				jQuery(\"#submit\").click(function(){
	    		window.scrollTo(0,0);
	    		jQuery(\"#queryResults\").hide();
	    		jQuery(\"#processingGif\").show();
	  		});
			});
		</script>
		<div id='processingGif' style=\"display: none;text-align:center;\"><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>
		";
	}
	public function buildDateSelector($field='startDate',$label="Starting Date"){
		if(isset($_GET[$field])){$dateValue=$_GET[$field];}else{$dateValue="";}
		return "
		<script>
			jQuery(document).ready(function() {
				jQuery('#".$field."').datepicker({
			      dateFormat : 'mm/dd/yy'
				});
			});
		</script>
		<label for='$field'>$label</label>
		<input class=\"form-control\" type=\"text\" id=\"".$field."\" name=\"".$field."\" value=\"".$dateValue."\"/>
		";
	}

}

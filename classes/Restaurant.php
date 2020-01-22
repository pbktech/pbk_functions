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
	public $timeZones=array("America/Chicago"=>"Central","America/New_York"=>"Eastern","America/Denver"=>"Mountain");
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
	 	$this->checkAboveStore();
   	if(isset($restID) && is_numeric($restID)) {
   		$this->restaurantID=$restID;
   		if($this->checkNewRestaurant()) {
   			$this->checkRestaurantAccess();
   			$this->loadRestaurant();
   		}
   	}else {
   		$this->allRestaurants=$this->loadRestaurants();
   	}
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
			if(array_key_exists($this->restaurantID,$this->myRestaurants) || $this->isAboveStore==1) {
				return;
			}else{
				echo "<div class='alert alert-danger'><p>You do not have access to this restaurant.</p></div>";
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
	private function restuarant_editor_textfield($id,$name,$r_info){
		$value='';
		if(isset($r_info[$id])){$value=$r_info[$id];}
		if($id=='openingDate' && isset($r_info[$id])){$value=date("m/d/Y",strtotime($r_info[$id]));}
		return "
	<div class='col'>
		<label for='".$id."'><strong>".$name."</strong></label><br /><input name='".$id."'  class='form-control' id='".$id."' value='".$value."' type='text' />
	</div>";
	}
	public function restaurantEditBox(){
		$r_info= (array) $this->rinfo;
		$allUsers=$this->getUserNames();
		$colOne=array("restaurantName"=>"Restaurant Name","restaurantID"=>"Restaurant ID","restaurantCode"=>"Restaurant Code","toastID"=>"Toast ID",
	"GUID"=>"Toast GUID","mnkyID"=>"Monkey ID","levelUpID"=>"LevelUp ID","openingDate"=>"Opening Date",""=>"");
	$colTwo=array("address1"=>"Address","address2"=>"Suite","city"=>"City","state"=>"State","zip"=>"Zip","latLong"=>"Latitute & Longitude",
	"phone"=>"Phone","email"=>"E-mail");
		$return= "
		<script>
		jQuery( function() {
			jQuery( \"#tabs\" ).tabs();
		} );
		jQuery(document).ready(function() {
			jQuery('#openingDate').datepicker({
				showButtonPanel: true,";
		if(isset($r_info['openingDate'])){
			$return.= "defaultDate: new Date(".date("Y, m, d",strtotime($r_info['openingDate']))."),";
		}
		$return.=	"
				dateFormat : 'mm/dd/yy'
			});
			jQuery('input.timepicker').timepicker({
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
		<div class='container-fluid;'>
			<form method=\"post\" action=\"admin-post.php\">
         	<input type=\"hidden\" name=\"action\" value=\"pbr_save_restaurant_option\" />
					<div id='tabs'>
					<ul class=\"nav nav-tabs\">
						<li class=\"nav-item\"><a href='#ids'>Base Information</a></li>
						<li class=\"nav-item\"><a href='#demographics'>Location</a></li>
						<li class=\"nav-item\"><a href='#hours'>Hours</a></li>
					</ul>
					<div id=\"ids\">
							<div class='form-group'>
								<div class='row'>
									";
									$count=0;
									foreach($colOne as $id=>$name){
										if(isset($id) && $id!=''){
											$return.=$this->restuarant_editor_textfield($id,$name,$r_info);
										}else{
											$return.="<div class='col'></div>";
										}
										$count++;
										$return.=($count%3 == 0 ? "</div><div class='row'>" : "");
									}
		$return.= "
						</div>
					</div>
				</div>
				<div id='demographics'>
					<div class='form-group'>
					<div class='row'>";
					$count=0;
					foreach($colTwo as $id=>$name){
						if(isset($id) && $id!=''){
							$return.=$this->restuarant_editor_textfield($id,$name,$r_info);
						}else{
							$return.="<div class='col'></div>";
						}
						$count++;
						$return.=($count%3 == 0 ? "</div><div class='row'>" : "");
					}
		$return.= "<div class='col'>
		<label for='isOpen'><strong>Is Open</strong></label><br /><select name='isOpen' class='form-control' id='isOpen'><option value='1' ";
		if(isset($this->rinfo->isOpen) && $this->rinfo->isOpen==1) { $return.= " selected='selected' ";}
		$return.= ">Yes</option><option value='0' ";
		if(isset($this->rinfo->isOpen) && $this->rinfo->isOpen==0) { $return.= " selected='selected' ";}
		$return.= ">No</option></select></div></div><div class='row'><div class='col'>
		";
		$return.= "<label for='am'><strong>AM</strong></label><br /><select name='am' class='form-control' id='am'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if(isset($this->rinfo->restaurantID) && $this->rinfo->restaurantID!=''){
				if($this->getManagerID("AM")==$user->ID) {$return.=" selected='selected' ";}
			}
			$return.=">".$user->display_name."</option>";
		}
		$return.= "</select></div><div class='col'>";

		$return.= "<label for='gm'><strong>GM</strong></label><br /><select name='gm' class='form-control' id='gm'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if(isset($this->rinfo->restaurantID) && $this->rinfo->restaurantID!=''){
				if($this->getManagerID("GM")==$user->ID) {$return.=" selected='selected' ";}
			}
			$return.=">".$user->display_name."</option>";
		}
		$return.= "</select></div><div class='col'>";

		$return.= "<label for='agm'><strong>AGM</strong></label><br /><select name='agm' class='form-control' id='agm'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if(isset($this->rinfo->restaurantID) && $this->rinfo->restaurantID!=''){
				if($this->getManagerID("AGM")==$user->ID) {$return.=" selected='selected' ";}
			}
			$return.=">".$user->display_name."</option>";
		}

		$return.= "</select></div></div><div class='row'><div class='col'><label for='str'><strong>Store Address</strong></label><br /><select name='str' class='form-control' id='str'><option value=''>----------</option>";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'";
			if(isset($this->rinfo->restaurantID) && $this->rinfo->restaurantID!=''){
				if($this->getManagerID("STR")==$user->ID) {$return.=" selected='selected' ";}
			}
			$return.=">".$user->display_name."</option>";
		}

		$return.= "</select></div><div class='col'><label for='market'><strong>Market</strong></label><br /><select name='market' class='form-control' id='market'><option value=''>----------</option>";
		foreach($this->Markets as $market){
			$return.="<option value='".$market."'";
			if(isset($this->rinfo->market) && $this->rinfo->market==$market) {$return.=" selected='selected' ";}
			$return.=">".$market."</option>";
		}
		$return.= "</select></div><div class='col'></div></div>
		</div>
	</div>
	<div id='hours'>
	<div class='form-group'>

		<div class='row'>
	";
	$return.= "<div class='col'>
	<label for='timeZone'><strong>Time Zone</strong></label><br /><select name='timeZone' class='form-control' id='timeZone'><option value=''>----------</option>";
	foreach($this->timeZones as $value=>$name){
		if(isset($r_info['timeZone']) && md5($r_info['timeZone'])==md5($value)) {$selected=" selected='selected' ";}else{$selected="";}
		$return.="<option value='".$value."' $selected >".$name."</option>";
	}
		$return.="
		</select></div></div>
		";
		$ocunt=0;
		for($ia=1419206400;$ia<=1419724800;$ia+=86400) {
			if(isset($this->rinfo->restaurantID)){
				$open=$this->getHours(date("l",$ia)."open");
				$close=$this->getHours(date("l",$ia)."close");
			}else {
				$open='';
				$close='';
			}
			$return.= "
			<div class='row'>
				<div class='col'>
					<h5 style='padding-top:1.5em;'><strong>".date("l",$ia)."</strong></h5>
				</div>
			</div>
			<div class='row'>
				<div class='col'>
					<label for='".date("l",$ia)."'>Open</label><br />
					<input class='timepicker form-control' id='time_picker".$ia."o' name='".date("l",$ia)."open' id='".date("l",$ia)."' value='".$open."' />
				</div>
				<div class='col'>
					<label for='".date("l",$ia)."'>Close</label><br />
					<input class='timepicker form-control' id='time_picker".$ia."c' name='".date("l",$ia)."close' id='".date("l",$ia)."' value='".$close."' />
				</div>
				<div class='col'></div>
			</div>";
		}
      $return.= "
			</div>
			</div>
			<div class='form-group'>
				<div class='row' style='padding-left:15px;'>
					<div class='col'>
						<button type=\"submit\" class=\"btn btn-primary\"/>Submit</button>
						<button type=\"button\" class='btn btn-warning' onclick=\"javascript:window.location='admin.php?page=pbr-edit-restaurant';\">Cancel</button>
					</div>
				</div>
				</div>
			</form>
			</div></div>";

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
		'openingDate' => date("Y-m-d",strtotime($this->rinfo->openingDate)),
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
		<div class='container-fluid' style='padding-top:1em;'>
				<h2>Attendance Report</h2>
				<form method=\"post\" action=\"admin-post.php\">
				<div class='row'>
					<div class='col'>
					<input type=\"hidden\" name=\"action\" value=\"pbr_nho_attendance_update\" />
					<input type=\"hidden\" name=\"nhoDate\" value=\"".$nho['nhoDate']."\" />
					<input type=\"hidden\" name=\"nhoTime\" value=\"".$nho['nhoTime']."\" />
					<input type=\"hidden\" name=\"nhoLocation\" value=\"".$nho['nhoLocation']."\" />
					<table id='myTable' class=\"table table-striped table-hover\" style='width:100%;'>
        		<thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
          		<tr><th>Employee Name</th><th>On Time?</th><th>Notes</th></tr>
						</thead>
			";
			if($attendees){
				foreach($attendees as $attendee){
					$json=json_decode($attendee->attendance);
					$return.="
					<tr>
					<td>".stripslashes($attendee->employeeName)." <input type='hidden' name='attendeeID[]' value='".$attendee->attendeeID."' /><input type='hidden' name='employeeName[".$attendee->attendeeID."]' value='".$attendee->employeeName."' /></td>
					<td><select class='form-control' name='att[".$attendee->attendeeID."][clock]'>
					";
					foreach ($this->nhoSatus["Attendance"] as $key => $value) {
						if($key==$json->clock){$selected='selected';}else{$selected='';}
						$return.='<option value="'.$key.'" '.$selected.' >'.$value.'</option>';
					}
					$return.="</select></td>
					<td><input type='text' class='form-control'
					name='att[".$attendee->attendeeID."][notes]' placeholder='Notes'
					value='".$json->notes."'/></td>
					</tr>";
				}
			}
			$return.="
				</table>
			</div>
		</div>
		<div class='row'>
			<div class='col'>
				<button type=\"submit\" class=\"btn btn-primary\"/>Save and Send to HR</button>
				</div>
			</div>
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
			$nho['nhoDate']=date("m/d/Y");
			$nho['nhoHost']="";
			$nho['nhoID']="";
			$nho['nhoTime']=array("Start"=>'',"End"=>'');
			$nho['nhoLocation']="";
			$nho['maxParticipants']=7;
		}
		$allUsers=$this->getUserNames();
		$rests=$this->loadRestaurants();
		if((!isset($atts['r']) || $atts['r']==0) || $atts['nhoDate']=="_new"){
		$return= "
		<script>
		jQuery(document).ready(function() {
    	jQuery('#nhoDate').datepicker({
				minDate: new Date(),
				showButtonPanel: true,";
		if(isset($nho['nhoDate'])){
			$return.= "
				defaultDate: new Date(".date("Y, m, d",strtotime($nho['nhoDate']))."),";
		}
		$return.=	"
        dateFormat : 'mm/dd/yy'
    });
		jQuery('.timepicker').timepicker({
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
		<div class='container-fluid;'>
			<form method=\"post\" action=\"admin-post.php\">
			<div class='row'>
				<div class='col'>
					<input type=\"hidden\" name=\"action\" value=\"pbr_save_nho\" />
					<input type=\"hidden\" name=\"nhoID\" value='".$nho['nhoID']."' />
					<label for='nhoDate'>NHO Date</label><br /><input class='form-control' type=\"text\" id=\"nhoDate\" name=\"nhoDate\" value=\"".date("Y-m-d",strtotime($nho['nhoDate']))."\"/>
				</div>
				<div class='col'>
					";
					$return.= "
						<label for='nhoHost'>NHO Host</label><br /><select class='form-control' name='nhoHost' id='nhoHost'><option value=''>----------</option>";
					foreach($allUsers as $user){
						$return.="<option value='".$user->ID."'";
						if($nho['nhoHost']==$user->ID) {$return.=" selected='selected' ";}
						$return.=">".$user->display_name."</option>";
					}
					$return.= "</select>
					</div>
					<div class='col'>
						<label for='nhoLocation'>NHO Location</label><br /><select class='form-control' name='nhoLocation' id='nhoLocation'><option value=''>----------</option>";
					foreach($rests as $r){
						$return.="<option value='".$r->restaurantID."'";
						if($nho['nhoLocation']==$r->restaurantID) {$return.=" selected='selected' ";}
						$return.=">".$r->restaurantName."</option>";
					}
					if(!isset($nhoTime)){$nhoTime['Start']='';$nhoTime['End']='';}
					$return.= "</select>
					</div>
				</div>
				<div class='row'>
					<div class='col'>
						<label for='startTime'>Start Time</label><br />
						<input class='timepicker form-control' id='time_picker_start' name='nhoTime[Start]' value='".$nhoTime['Start']."'/><br />
					</div>
					<div class='col'>
						<label for='startTime'>End Time</label><br />
						<input class='timepicker form-control' id='time_picker_end' name='nhoTime[End]' value='".$nhoTime['End']."'/><br />
					</div>
					<div class='col'>
						<label for='maxParticipants'>Max Peeps</label><br /><input class='form-control' type=\"text\" id=\"maxParticipants\" name=\"maxParticipants\" value=\"".$nho['maxParticipants']."\"/><br />
					</div>
				</div>
				<div class='row'>
					<div class='col'>
						<button type=\"submit\" class=\"btn btn-primary\"/>Submit</button>
						<button type=\"button\" class='btn btn-warning' onclick=\"javascript:window.location='admin.php?page=pbr-nho';\">Cancel</button>
					</div>
				</div>
			</form>
		</div>";
if($_GET['nhoDate']!="_new"){
		$return.=$this->nho_attendance(array("nhoLocation"=>$nho['nhoLocation'],"nhoDate"=>$nho['nhoDate'],"nhoID"=>$nho['nhoID'],"nhoTime"=>$nhoTime['Start']." - ".$nhoTime['End']));
	}
}elseif($atts['r']==1){
	$return=$this->nhoHistory($get);
	if($file=$this->buildNHORosterFile($return)){
		$return.="<div><a href='$file' target='_blank'>Printable PDF</a></div>";
	}
}
	return $return;
	}

	function buildNHOAttendeeLine($att=null,$disable=0){
		if(isset($att)){
			global $wpdb;
			$attendee=$wpdb->get_row("SELECT * FROM pbc2.pbc_nhoAttenndess WHERE attendeeID='$att'",ARRAY_A);
		}else{
			$attendee['nhoID']='';
			$attendee['attendeeID']='NEW';
			$attendee['employeeName']='';
			$attendee['restaurant']='';
			$attendee['attData']=json_encode(array("position"=>"","section"=>"","uni"=>"","fhro"=>"","fh"=>"","schedule"=>""));
		}
		if($disable==1){$disabled=' disabled ';}else {$disabled='';}
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
	function buildHTMLPDF($content,$save=1){
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
		if($save==0){
			$mpdf->Output();
		}else {
			$mpdf->Output($report->docSaveLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf", 'F');
		}
		if(file_exists($report->docSaveLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf")){
			return array("Link"=>$report->docDownloadLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf","Local"=>$report->docSaveLocation.str_replace(" ","_",str_replace("/","_",$content->title)).".pdf");
		}else {
			return false;
		}
	}
	function getNHOEvents(){
		global $wpdb;
		global $wp;
		$user = wp_get_current_user();
		if(user_can( $user->ID, 'delete_posts' )){
			return $wpdb->get_results("SELECT * FROM pbc2.pbc_NHOSchedule WHERE nhoDate>=CURDATE()-2", ARRAY_A);
		}else {
			return $wpdb->get_results("SELECT * FROM pbc2.pbc_NHOSchedule WHERE nhoDate>=CURDATE()", ARRAY_A);
		}
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
					<td style="color: #ffffff;"><strong>Name</strong></td>
					<td><strong>Location</strong></td>
					<td><strong>Position</strong></td>
					<td><strong>FOH/BOH</strong></td>
					<td><strong>Uniform</strong></td>
					<td><strong>FHR Onboarding</strong></td>
					<td><strong>Food Handler</strong></td>
					<td><strong>Schedule</strong></td>
					<td><strong>Attendance</strong></td>
					<td><strong>Notes</strong></td>
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
	public function pbk_array_nav($array){
		$return="
		<div class='container-fluid' style='width:100%;'>
			<div class='row'>
				<div class='col'>
					<ul class='nav flex-column'>";
						foreach($array as $a){
							$return.="
						<li class='nav-item'><a class='nav-link' href='" . $a['Link'] . "'>" . $a['Title'] . "</a></li>
						";
						}
						$return.="
						</ul>
					</div>
				</div>
			</div>
		";
		return $return;
	}
	public function pbk_get_children(){
		$return=array();
		global $post;
		global $wpdb;
		$args = array(
    'post_type'      => 'page',
    'posts_per_page' => -1,
    'post_parent'    => $post->ID,
    'order'          => 'ASC',
    'orderby'        => 'menu_order'
 		);
		$parent = new WP_Query( $args );
		if ( $parent->have_posts() ){
			foreach ( $parent->posts as $post ){
				$return[]=array("Link"=>get_permalink($post->ID),"Title"=>$post->post_title);
			}
		}
		return $return;
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

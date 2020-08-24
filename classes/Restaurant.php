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
	public $ownershipType=array('Owned', 'Leased', 'Financed');
	public $violationLevel=array('Verbal', 'Written', 'Final Written', 'Termination');
	public $violationType=array('Safety', 'PBK Look', 'Tardy', 'Accuracy','Cash Mishandling / Theft', 'Absent', 'Speed', 'Gross Misconduct', 'No Call / No Show', 'Connection', 'Leadership', 'Other');
	public $violationSupport=array('Policy', 'The Blend', 'Recipes', 'The 4 Keys', 'Station Aids', 'Other');
	public $deviceType=array();
	public $orderTypes=array("LightBulb"=>"Light Bulbs","KeyRelease"=>"Key Release");
	public $deviceStatus=array('Active', 'B-Stock', 'Retired', 'Returned');
	private $bulbs=array(1=>"Nucleus Large", 2=>"Nucleus Small", 3=>"Overhead Lighting", 4=>"Refrigeration Lighting", 5=>"Other");
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
		if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles) || in_array("author", $cu->roles)) {
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
		global $wpdb;
		$r_info= (array) $this->rinfo;
		$allUsers=$this->getUserNames();
		$colOne=array("restaurantName"=>"Restaurant Name","restaurantID"=>"Restaurant ID","restaurantCode"=>"Restaurant Code","toastID"=>"Toast ID",
	"GUID"=>"Toast GUID","mnkyID"=>"Monkey ID","levelUpID"=>"LevelUp ID","openingDate"=>"Opening Date",""=>"");
	$colTwo=array("address1"=>"Address","address2"=>"Suite","city"=>"City","state"=>"State","zip"=>"Zip","latLong"=>"Latitute & Longitude",
	"phone"=>"Phone","email"=>"E-mail");
	$aa_users = $wpdb->get_results("SELECT managerID FROM pbc2.pbc_pbr_managers where mgrType LIKE '%AA%' AND restaurantID='".$this->restaurantID."'");
	if(isset($aa_users) && count($aa_users)!=0){
		$aausers=array();
		foreach($aa_users as $u){$aausers[]=$u->managerID;}
		$preselect="jQuery('#additionAccess').val(['" . implode("','", $aausers) . "']).trigger('change');";
	}else{
		$preselect="";
	}
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
			jQuery('#additionAccess').select2();
			".$preselect."
		});
		</script>
		<div class='container-fluid;'>
		<div id='pbk_message'></div>
			<form method=\"post\" action=\"\" id='restaurantEditor'>
         	<input type=\"hidden\" name=\"action\" value=\"pbr_save_restaurant_option\" />
					<div id='tabs'>
					<ul class=\"nav nav-tabs\">
						<li class=\"nav-item\"><a href='#ids'>Base Information</a></li>
						<li class=\"nav-item\"><a href='#demographics'>Location</a></li>
						<li class=\"nav-item\"><a href='#hours'>Hours</a></li>
						<li class=\"nav-item\"><a href='#roster'>Roster</a></li>
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
		$return.= "</select></div>
		<div class='col'>
			<label for='additionAccess'><strong>Additional Access</strong></label>
			<br />
			<select name='additionAccess[]' class=\"custom-select multipleSelect\" style='width:100%;' id='additionAccess' multiple >";
		foreach($allUsers as $user){
			$return.="<option value='".$user->ID."'>".$user->display_name."</option>";
		}
			$return.= "
			</select>
		</div></div>
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
			<div id='roster'>
			".$this->restaurantRoster($this->rinfo->restaurantID)."
			</div>
			<div class='form-group'>
				<div class='row' style='padding-left:15px;'>
					<div class='col'>
						<button type=\"submit\" class=\"btn btn-primary\" id='send'/>Submit</button>
						<button type=\"button\" class='btn btn-warning' onclick=\"javascript:window.location='admin.php?page=pbr-edit-restaurant';\">Cancel</button>
					</div>
				</div>
				</div>
			</form>
			</div></div>
			<script>
			jQuery( '#send' ).click(function() {
			    var form_data = jQuery( \"#restaurantEditor\" ).serializeArray();
			    form_data.push( { \"name\" : \"security\", \"value\" : \" " . wp_create_nonce( "secure_nonce_name" ) . "\" } );
			    jQuery.ajax({
			        url : ajaxurl, // Here goes our WordPress AJAX endpoint.
			        type : 'post',
			        data : form_data,
			        success : function( response ) {
			            jQuery( '#pbk_message' ).html( response );
			        },
			        fail : function( err ) {
			            alert( \"There was an error: \" + err );
			        }
			    });
			    return false;
			});
			</script>
			";
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
			$wpdb->query("DELETE FROM pbc_pbr_managers WHERE mgrType LIKE '%AA%' AND restaurantID='". $this->rinfo->restaurantID ."'");

			if(isset($_POST['additionAccess']) && count($_POST['additionAccess'])!=0){
				foreach ($_POST['additionAccess'] as $u) {
					$wpdb->insert('pbc_pbr_managers',array( 'restaurantID'=> $this->rinfo->restaurantID, 'managerID'=> $u, 'mgrType' => 'AA'.$u), array('%d','%s','%s'));
				}
			}

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
						if($key==$json->clock){$selected=' selected';}else{$selected='';}
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
				if($key==$attendee['restaurant']){$selected=' selected';}else{$selected='';}
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
		if(isset($content->Save) && $content->Save!=''){$docSaveLocation=$content->Save;}else{$docSaveLocation=$report->docSaveLocation;}
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
		if(isset($content->watermark)){
			$mpdf->SetWatermarkText($content->watermark);
			$mpdf->showWatermarkText = true;
		}
		$mpdf->WriteHTML($stylesheet,\Mpdf\HTMLParserMode::HEADER_CSS);
	  $mpdf->WriteHTML(utf8_encode($content->html),\Mpdf\HTMLParserMode::HTML_BODY);
		if(isset($content->fileName)){
			$filename=$content->fileName . ".pdf";
		}else{
			$filename=str_replace(" ","_",str_replace("/","_",$content->title)).".pdf";
		}
		if(file_exists($docSaveLocation.$filename)){unlink($docSaveLocation.$filename);}
		if($save==0){
			$mpdf->Output();
		}else {
			$mpdf->Output($docSaveLocation.$filename, 'F');
		}
		if(file_exists($docSaveLocation.$filename)){
			return array("Link"=>$report->docDownloadLocation.$filename,"Local"=>$docSaveLocation.$filename);
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
	function getMiniBarLocations(){
		global $wpdb;
		$results= $wpdb->get_results("SELECT idpbc_minibar,company,restaurantName,imageFile FROM pbc2.pbc_minibar,pbc_pbrestaurants WHERE pbc_minibar.restaurantID=pbc_pbrestaurants.restaurantID");
		if($results){
			$d['Options'][]="\"lengthMenu\": [ [25, 50, -1], [25, 50, \"All\"] ]";
			$d['File']="PBK_Device_List_";
			$d['Headers']=array("MiniBar","Restaurant","Ordering Link");
			foreach($results as $r){
				$json=json_decode($r->imageFile);
				$d['Results'][]=array(
					"<a href='".admin_url( 'admin.php?page=pbr-edit-minibar&id='.$r->idpbc_minibar )."'>" . $r->company . "</a>",
					$r->restaurantName,
					"<a href='".$json->link."' target='_blank'>" . str_replace("https://minibar.theproteinbar.com/","",$json->link) . "</a>"
				);
			}
			return $d;
		}
		return;
	}
	function getMiniBarInformation($id){
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM pbc2.pbc_minibar WHERE idpbc_minibar='".$id."'",ARRAY_A);
	}
	function showMiniBarBuilder($info=array("idpbc_minibar"=>"_NEW","company"=>"","restaurantID"=>"","outpostIdentifier"=>"","imageFile"=>"")){
		if(isset($info['imageFile']) && $info['imageFile']!=""){
			$links=json_decode($info['imageFile'],true);
			$imageAdd="
					<strong>Current Image</strong><br><img src='".$links['image']."' alt='' />
			";
		}else {
			$imageAdd="";
			$links['image']="";
			$links['link']="";
		}
		if($info['idpbc_minibar']!="_NEW" && isset($links['image']) && $links['image']!=""){
			$sendTest="
			<a href='".admin_url( 'admin.php?page=pbr-edit-minibar&id='.$info['idpbc_minibar'] )."&testEmail=1' class=\"btn btn-secondary\">Send Test Email</a>
			";
		}
		if(isset($_GET['testEmail']) && $_GET['testEmail']==1){
			$current_user = wp_get_current_user();
			$report=new ToastReport;
			$content="<div>
	    <a href='".$links['link']."' target='_blank'>
	    <img src='".$links['image']."' alt='Your Protein Bar MiniBar order has been delivered!' />
	    </a>
	    </div>'";
			$handle = @fopen($links['image'], 'r');
			if($handle){
				$report->reportEmail($current_user->user_email,$content,"IT'S ALL GOOD: Your meal has arrived!");
				echo switchpbrMessages(4);
			}else {
				echo switchpbrMessages(5);
			}
		}
		if(isset($links['day']) && count($links['day'])!=0){
			$preselect="jQuery('#deliveryDay').val(['" . implode("','", $links['day']) . "']).trigger('change');";
		}else{
			$preselect="";
		}
		return $this->pbk_addImageSelector()."
		<script>
		jQuery(document).ready(function() {
			jQuery('input.timepicker').timepicker({
				'timeFormat': 'h:mm p',
				interval: 30,
				minTime: '5:00 am',
				maxTime: '9:00 pm',
				dynamic: false,
				dropdown: true,
				scrollbar: true
			});
			jQuery('#deliveryDay').select2();
			".$preselect."
		});
		</script>
		<div class='container-fluid;'>
	<form method=\"post\" action=\"admin-post.php\">
  	<input type=\"hidden\" name=\"action\" value=\"pbk_save_minibar\" />
		<input type=\"hidden\" name=\"idpbc_minibar\" value=\"".$info['idpbc_minibar']."\" />
  	<div class='row'>
  		<div class='col'>
				<label for='restaurantID'><strong>Restaurant</strong></label><br />
  			" . $this->buildRestaurantSelector(0,'restaurantID',$info['restaurantID']) . "
				<br>
				<label for='company'><strong>Company Name</strong></label>
  			<input type='text' class='form-control' name ='company' value='".$info['company']."' />
				<br>
				<label for='imageFile'><strong>Order Link</strong></label>
				<input type='text' class='form-control' name ='imageFile[link]' value='".$links['link']."' />
				<br>
				<label for='outpostIdentifier'><strong>Toast Dining Option</strong></label>
				<input type='text' class='form-control' name ='outpostIdentifier' value='".$info['outpostIdentifier']."' />
				<br>
				<div class='row'>
					<div class='col'>
						<label for='delivery'>Delivery Day</label><br />
						<select name='imageFile[day][]' class=\"custom-select multipleSelect\" id='deliveryDay' multiple>
							<option value='Sunday'>Sunday</option>
							<option value='Monday'>Monday</option>
							<option value='Tuesday'>Tuesday</option>
							<option value='Wednesday'>Wednesday</option>
							<option value='Thursday'>Thursday</option>
							<option value='Friday'>Friday</option>
							<option value='Saturday'>Saturday</option>
						</select>
					</div>
					<div class='col'>
						<label for='cutoff'>Cutoff Time</label><br />
						<input class='timepicker form-control' id='cutoff' name='imageFile[cutoff]' value='".$links['cutoff']."'/><br />
					</div>
					<div class='col'>
						<label for='delivery'>Delivery Time</label><br />
						<input class='timepicker form-control' id='delivery' name='imageFile[delivery]' value='".$links['delivery']."'/><br />
					</div>
				</div>
				<label for='imageFile'><strong>Image</strong></label>
				<input type='text' class='form-control media-input' name ='imageFile[image]' value='".$links['image']."' /> <button class='media-button'>Select image</button>
			</div>
			<div class='col'>
			".$imageAdd."
			</div>
  	</div>
		<div class='row' style='padding:15px;'>
			<div class='col'>
				<button type=\"submit\" class=\"btn btn-primary\"/>Submit</button>
				<button type=\"button\" class='btn btn-warning' onclick=\"javascript:window.location='admin.php?page=pbr-edit-minibar';\">Cancel</button>
			</div>
		</div>
	</form>
</div>
<div>$sendTest</div>
		";
	}
	function pbkSaveMinibar($info){
		global $wpdb;
		$imageFile=json_encode($info['imageFile']);
		if(isset($info["idpbc_minibar"]) && $info["idpbc_minibar"]=="_NEW"){
			$wpdb->query(
				$wpdb->prepare( "
					INSERT INTO pbc_minibar (restaurantID,company,outpostIdentifier,imageFile)VALUES(%s,%s,%s,%s)",
					$info['restaurantID'],$info['company'],$info['outpostIdentifier'],$imageFile));
				if(isset($wpdb->insert_id)){$info["idpbc_minibar"]=$wpdb->insert_id;}else{die("ID ERROR");}
		}else {
			$wpdb->query(
				$wpdb->prepare( "
					REPLACE INTO pbc_minibar (idpbc_minibar,restaurantID,company,outpostIdentifier,imageFile)VALUES(%s,%s,%s,%s,%s)",
					$info['idpbc_minibar'],$info['restaurantID'],$info['company'],$info['outpostIdentifier'],$imageFile));
		}
		wp_redirect(  admin_url( 'admin.php?page=pbr-edit-minibar&id='.$info["idpbc_minibar"].'&m=1' ));
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
	function getDeviceTypes(){
		global $wpdb;
		return $wpdb->get_results("SELECT * FROM pbc2.pbc_devices_types");
	}
	function pbk_listDevices(){
		$this->deviceType=$this->getDeviceTypes();
		foreach($this->deviceType as $type){$dt[$type->idpbc_devices_types]=$type->deviceType;}
		$d=array();
		global $wpdb;
		$results=$wpdb->get_results("SELECT * FROM pbc2.pbc_devices WHERE deviceStatus!='Retired' order by deviceStatus,dateAdded");
		if($results){
			$d['Options'][]="\"order\": [ 5, 'asc' ]";
			$d['Options'][]="\"lengthMenu\": [ [25, 50, -1], [25, 50, \"All\"] ]";
			$d['File']="PBK_Device_List_";
			$d['Headers']=array("Name","Make & Model","Type","Ownership","Assigned User","Status");
			foreach($results as $r){
				$user=$wpdb->get_var("SELECT display_name FROM pbc_users WHERE ID IN (SELECT userID FROM pbc_devices_assignments WHERE deviceID='".$r->idpbc_devices."')");
				if($user){
					$assigned=$user;
				}else {
					$assigned="UNASSIGNED";
				}
				$d['Results'][]=array(
					"<a href='" . admin_url( "admin.php?page=pbr-edit-devices&id=".$r->idpbc_devices)."'>" . $r->deviceName . "</a>",
					$r->deviceBrand . " " . $r->deviceModel,
					$dt[$r->deviceType],
					$r->ownershipType,
					$assigned,
					$r->deviceStatus
				);
			}
		}
		return $d;
	}
	function pbkSaveDevice($p){
		if(!isset($p['deviceName']) || $p['deviceName']==""){wp_redirect(  admin_url( 'admin.php?page=pbr-edit-devices&m=2' ));}
		$p['dateAdded']=date("Y-m-d",strtotime($p['dateAdded']));
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"REPLACE INTO pbc_devices
				(idpbc_devices,deviceName,deviceBrand,deviceModel,deviceSerial,deviceType,ownershipType,deviceStatus,lengthTerm,dateAdded)
				VALUES
				(%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
				$p['idpbc_devices'],
				$p['deviceName'],
				$p['deviceBrand'],
				$p['deviceModel'],
				$p['deviceSerial'],
				$p['deviceType'],
				$p['ownershipType'],
				$p['deviceStatus'],
				$p['lengthTerm'],
				$p['dateAdded']));
				$insertID=$wpdb->insert_id;
		$wpdb->query(
			$wpdb->prepare("REPLACE INTO pbc_devices_assignments (deviceID,userID)VALUES(%s,%s)",$insertID,$p['userID']));
			wp_redirect(  admin_url( 'admin.php?page=pbr-edit-devices&m=3' ));
	}
	function pbk_device_editor($data){
		$allUsers=$this->getUserNames();
		$this->deviceType=$this->getDeviceTypes();
		if($data=="_NEW"){

			$d=array("deviceName"=>"","deviceBrand"=>"","deviceModel"=>"","deviceSerial"=>"","deviceType"=>"","ownershipType"=>"",
		"deviceStatus"=>"","lengthTerm"=>"","dateAdded"=>date("m/d/Y"),"idpbc_devices"=>"");
		$userID="";
	}else{
		global $wpdb;
		$d=$wpdb->get_row('SELECT * FROM pbc_devices,pbc_devices_assignments WHERE idpbc_devices="'.$data.'"', ARRAY_A);
		$userID=$wpdb->get_var("SELECT userID FROM pbc_devices_assignments WHERE deviceID='".$data."'");
	}
		$return="
		<script>
		jQuery( function() {
			jQuery( \"#tabs\" ).tabs();
			jQuery('#userID').select2({
      	theme: \"classic\"
    	});";
			if(isset($userID)){$return.="
				jQuery('#userID').val([".$userID."]);
				jQuery(\"#userID\").trigger(\"change\");";}
			$return.="
		} );
		</script>
		<div class='container-fluid;'>
					<form method=\"post\" action=\"admin-post.php\">
		         	<input type=\"hidden\" name=\"action\" value=\"pbk-save-devices\" />
							<div id='tabs'>
							<div id=\"ids\">
									<div class='form-group'>
										<div class='row'>
											<div class='col'>
												<input type='hidden' name='idpbc_devices' value='".$d['idpbc_devices']."' />
												<label for='deviceName'><strong>Device Name</strong></label><br>
												<input type='text' name='deviceName' id='deviceName' value='".$d['deviceName']."' />
											</div>
											<div class='col'>
											<label for='deviceBrand'><strong>Device Brand</strong></label><br>
											<input type='text' name='deviceBrand' id='deviceBrand' value='".$d['deviceBrand']."' />
											</div>
											<div class='col'>
											<label for='deviceModel'><strong>Device Model</strong></label><br>
											<input type='text' name='deviceModel' id='deviceModel' value='".$d['deviceModel']."' />
											</div>
										</div>
										<div class='row'>
											<div class='col'>
											<label for='deviceSerial'><strong>Serial #</strong></label><br>
											<input type='text' name='deviceSerial' id='deviceSerial' value='".$d['deviceSerial']."' />
											</div>
											<div class='col'>
												<label for='deviceType'><strong>Type</strong></label><br><select name='deviceType' id='deviceType'>
												<option value=''>Choose One</option>
												";
												foreach($this->deviceType as $t){
													if($t->idpbc_devices_types==$d['deviceType']){$selected=' selected';}else{$selected='';}
													$return.="<option value='".$t->idpbc_devices_types."'$selected>".$t->deviceType."</option>";
												}
												$return.="</select>
											</div>
											<div class='col'>
											<label for='deviceStatus'><strong>Status</strong></label><br><select name='deviceStatus' id='deviceStatus'>
											<option value=''>Choose One</option>
											";
											foreach($this->deviceStatus as $t){
												if($t==$d['deviceStatus']){$selected=' selected';}else{$selected='';}
												$return.="<option value='".$t."'$selected>".$t."</option>";
											}
											$return.="</select>
											</div>
										</div>
										<div class='row'>
											<div class='col'>
											<label for='ownershipType'><strong>Ownership</strong></label><br><select name='ownershipType' id='ownershipType'>
											<option value=''>Choose One</option>
											";
											foreach($this->ownershipType as $t){
												if($t==$d['ownershipType']){$selected=' selected';}else{$selected='';}
												$return.="<option value='".$t."'$selected>".$t."</option>";
											}
											$return.="</select>
											</div>
											<div class='col'>
											<label for='lengthTerm'><strong>Term (in months)</strong></label><br>
											<input type='text' name='lengthTerm' id='lengthTerm' value='".$d['lengthTerm']."' />
											</div>
											<div class='col'>
											<label for='dateAdded'><strong>Date Added</strong></label><br>
											<input type='text' name='dateAdded' id='dateAdded' value='".date("m/d/Y",strtotime($d['dateAdded']))."' />
											</div>
										</div>
										<div class='row'>
											<div class='col'>
											<label for='userID'><strong>Assigned to</strong></label><br>
											<select name='userID' class='form-control multipleSelect' id='userID'><option value=''>Choose One</option>";
											foreach($allUsers as $user){
												if($user->ID==$userID){$selected=' selected';}else{$selected='';}
												$return.="<option value='".$user->ID."'$selected>".$user->display_name."</option>";
											}
											$return.= "</select>
											</div>
										</div>
									</div>
								</div>
								<div class='row' style='padding:15px;'>
									<div class='col'>
										<button type=\"submit\" class=\"btn btn-primary\"/>Submit</button>
										<button type=\"button\" class='btn btn-warning' onclick=\"javascript:window.location='admin.php?page=pbr-edit-devices';\">Cancel</button>
									</div>
								</div>
							</form>
						</div>
";
	return $return;
	}
	private function restaurantRoster($r){
		$d['Headers']=array("Name","Employee ID","E-Mail");
		global $wpdb;
		$rows=$wpdb->get_results("SELECT employeeName,externalEmployeeId,email FROM pbc2.pbc_ToastEmployeeInfo where restaurantID='".$r."' AND externalEmployeeID !='' and deleted !=1 order by employeeName");
		foreach ($rows as $row) {
			$d['Results'][]=array($row->employeeName,$row->externalEmployeeId,$row->email);
		}
		$report= new ToastReport;
    return $report->showResultsTable($d);
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
	public function getRestaurantField($field) {
		global $wpdb;
		return $wpdb->get_var( "SELECT $field FROM pbc_pbrestaurants WHERE restaurantID='".$this->restaurantID."'");
	}
	public function getBulbs(){
		return $this->bulbs;
	}
	public function setRestaurantID($id){
		$this->restaurantID=$id;
		$this->loadRestaurant();
	}
	private function setUserFullName($id){
		$wpdb;
		return $wpdb->get_var("SELECT displayName FROM pbc_users WHERE ID=$id");
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
		return "<input class=\"form-control\" type='text' id='".$name."' name='".$name."' value='".$value."' required />";
	}
	public function buildUserPicker($data=null){

	}
	public function showOrderInfo($id,$pdfOnly=0){
		global $wpdb;
		$d=$wpdb->get_row( "SELECT * FROM pbc_pbk_orders,pbc_pbrestaurants WHERE idpbc_pbk_orders = $id AND pbc_pbk_orders.restaurantID=pbc_pbrestaurants.restaurantID" );
		$i=json_decode($d->orderData);
		if(isset($i->files) && count($i->files)!=0){
			foreach($i->files as $file){
				$files[]="<a href='".home_url( "wp-content/uploads/".$file )."' target='_blank'>".$file."</file>";
			}
			$files=implode("<br>",$files);
		}else {
			$files="None Added";
		}
		$return= "
		<div class='container-fluid' id='queryResults'>
		    <div class='row'>
		      <div class='col'><label for='reporterName'><strong>Name</strong><br><div class='alert alert-secondary'>".$i->reporterName."</div></div>
		      <div class='col'><label for='restaurantID'><strong>Restaurant</strong><br><div class='alert alert-secondary'>".$d->restaurantName."</div></div>
		    </div>
		    <div class='row'>
		      <div class='col'><label for='bulbs'><strong>Bulb Type</strong><br><div class='alert alert-secondary'>".$this->bulbs[$i->bulbs]."</div></div>
		      <div class='col'><label for='quantity'><strong>Quantity</strong><br><div class='alert alert-secondary'>".$i->quantity."</div></div>
		    </div>
		    <div class='row'>
		      <div class='col'><label for='other'><strong>Additional Comments</strong><div class='alert alert-secondary'>".$i->other."</div></div>
		      <div class='col' id='file_area'><label for='pictures'><strong>Images</strong><br><div class='alert alert-secondary'>$files</div></div>
		    </div>
		</div>
		  ";
		$content['format']='A4-P';
		$content['title']=$d->restaurantName . " Light Bulb Order for " . date("m/d/y",strtotime($d->orderDate));
		$content['html']=$this->docHeader($this->orderTypes[$d->orderType]." Order").$return;
		if($file=$this->buildHTMLPDF(json_encode($content))){
			if($pdfOnly==1){return $file['Local'];}
			$return.="<div class='container-fluid' id='queryResults'><div class='row'><div class='col'><a href='".$file['Link']."' target='_blank'>Printable PDF</a></div></div></div>";
		}
		if($d->orderStatus=="Pending"){
			$returnB="
			<h4>Update the Order Status</h4>
			<form method=\"post\" action=\"admin-post.php\">
				<input type=\"hidden\" name=\"action\" value=\"pbk-update-order\" />
				<input type=\"hidden\" name=\"type\" value=\"".$_GET['type']."\" />
				<input type=\"hidden\" name=\"id\" value=\"$id\" />
				<div class='container-fluid' id='queryResults'>
					<div class='row'>
						<div class='col'>".$this->buildSelectBox(array("Options"=>array("Shipped"=>"Shipped","Pickup"=>"Pickup","Cancel"=>"Cancel"),"Field"=>"orderStatus","Multiple"=>"","ID"=>"orderStatus"))."</div>
						<div class='col'><input type=\"submit\" class=\"btn btn-primary\" id='submit' value='Update'/></div>
					</div>
				</div>
			</form>
			" . $this->pbk_form_processing();
		}else{
			$returnB="
				<div class='container-fluid' id='queryResults'>
					<div class='row'>
						<div class='col'><label for='bulbs'><strong>Bulb Type</strong></label><br><div class='alert alert-secondary'>".$d->orderStatus."</div></div>
						<div class='col'></div>
					</div>
				</div>
			";
		}
			return $return . $returnB;
	}
	public function getPBKOrderinfo($data){
		global $wpdb;
		$row= $wpdb->get_row("SELECT pbc_pbk_orders.guid as 'guid',pbc_pbk_orders.restaurantID as 'restaurantID',restaurantName,userID,orderData,orderUpdated,orderStatus,idpbc_pbk_orders FROM pbc_pbk_orders,pbc_pbrestaurants WHERE pbc_pbk_orders.guid = '" . $data . "' AND pbc_pbk_orders.restaurantID=pbc_pbrestaurants.restaurantID");
		if($row){return $row;}
		return false;
	}
	public function showIAP($d){
		$i=json_decode($d->orderData);
		$report=New ToastReport;
		$docFolder=dirname(dirname($report->docSaveLocation)) ."/docs/". $d->guid;
		$return='
		<div class="container-fluid" id="queryResults" >
		  <div class="row">
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Team Member Name</strong></label><br>'.$i->name.'</div></div>
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Team Member Position</strong></label><br>'.$i->position.'</div></div>
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Violation Date</strong></label><br>'.date("m/d/Y",strtotime($i->violationDate)).'</div></div>
		  </div>
		  <div class="row">
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Manager Name</strong></label><br>'.$i->reporterName.'</div></div>
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Restaurant</strong></label><br>'.$d->restaurantName.'</div></div>
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Coaching Date</strong></label><br>'.date("m/d/Y",strtotime($i->coachingDate)).'</div></div>
		  </div>
		  <div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>Violation Level:</strong></label><br>'.$i->violationLevel.'</div></div>
	    </div>
		  <div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>Violation Type:</strong></label><br>'.implode(", ",json_decode(json_encode($i->violationType), true)).'</div></div>
	    </div>
			';
		if(isset($i->violationTypeOther) && $i->violationTypeOther!=""){
			$return.=	'
			<div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>Other Explanation</strong></label><br>'.stripslashes($i->violationTypeOther).'</div></div>
	    </div>
			';
		}
		$return.=	'
		  <div class="row">
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Has the team member received a prior action plan within the past 12 months?</strong></label><br>'.stripslashes($i->previousAction).'</div></div>
		  </div>
			';
		if(isset($i->previousActionExplain) && $i->previousActionExplain!=""){
			$return.=	'
			<div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>Previous Action Explanation</strong></label><br>'.stripslashes($i->previousActionExplain).'</div></div>
	    </div>
			';
		}
		$return.=	'
		  <div class="row">
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Violation Details:</strong></label><br>'.stripslashes($i->violationDetails).'</div></div>
		  </div>
		  <div class="row">
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>What Should be Happening / Plan for Improvement:</strong></label><br>'.stripslashes($i->plan).'</div></div>
		  </div>
		  <div class="row">
		    <div class="col"><div class="alert alert-secondary"><label for=""><strong>Supporting documentation:</strong></label><br>'.stripslashes($i->violationSupport).'</div></div>
		  </div>
			';
		if(isset($i->violationSuppotExplain) && $i->violationSuppotExplain!=""){
			$return.=	'
			<div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>Policy Explanation</strong></label><br>'.stripslashes($i->violationSuppotExplain).'</div></div>
	    </div>
			';
		}
		if(isset($i->signature->employee) && $i->signature->employee!=""){
			$return.=	'
			<div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>TM Signature</strong></label><br><img src="data:image/png;base64,'.base64_encode(file_get_contents($docFolder.'/'.$i->signature->employee)).'" alt="'.$i->name.'" /></div></div>
	    </div>
			';
		}
		if(isset($i->signature->manager) && $i->signature->manager!=""){
			$return.=	'
			<div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>Manager Signature</strong></label><br><img src="data:image/png;base64,'.base64_encode(file_get_contents($docFolder.'/'.$i->signature->manager)).'" alt="'.$i->reporterName.'" /></div></div>
	    </div>
			';
		}
		if(isset($i->signature->witness) && $i->signature->witness!=""){
			$return.=	'
			<div class="row">
				<div class="col"><div class="alert alert-secondary"><label for=""><strong>Witness Signature ('.$i->signature->witnessName.')</strong></label><br><img src="data:image/png;base64,'.base64_encode(file_get_contents($docFolder.'/'.$i->signature->witness)).'" alt="'.$i->signature->witnessName.'" /></div></div>
	    </div>
			';
		}
		$return.=	'
		</div>
';
	return $return;
	}
	public function viewKeyRelease($id,$pdfOnly=0){
		global $wpdb;
		$rpt=New ToastReport;
		$d=$wpdb->get_row( "SELECT * FROM pbc_pbk_orders,pbc_pbrestaurants WHERE pbc_pbk_orders.guid = '$id' AND pbc_pbk_orders.restaurantID=pbc_pbrestaurants.restaurantID" );
		$i=json_decode($d->orderData);
		$docFolder=dirname(dirname($rpt->docSaveLocation)) ."/docs/". $d->guid;
		$html="<div class=\"container-fluid\" >
		  <div class=\"row\">
		    <div class=\"col\">
		      <p>I acknowledge that I have received a copy of the key for my restaurant. I understand that this key is Protein Bar & Kitchen property and that I am responsible for this key as long asI am employed with the company. I will not make copies of this key for any reason.</p>
		      <p>At the timemy employment at Protein Bar & Kitchen ends, I will return the key to my manager on or before my last day of work. If I lose this key for any reason, I will immediately report the loss to my manager. I understand that I may be responsible for any and all replacement costs associated with the loss of my key as deemed necessary by Protein Bar & Kitchen.</p>
		    </div>
		  </div>
		</div>
		<div class=\"container-fluid\">
		<div class=\"row\">
		  <div class=\"col\"><strong>Restaurant</strong><br><div class=\"alert alert-secondary\">".$d->restaurantName."</div></div>
		  <div class=\"col\"><strong>Key #</strong><br><div class=\"alert alert-secondary\">".$i->key."</div></div>
		  <div class=\"col\"><strong>Date</strong><br><div class=\"alert alert-secondary\">".date("m/d/Y",strtotime($i->startDate))."</div></div>
		</div>
		<div class=\"row\">
		  <div class=\"col\"><strong>Name</strong><br><div class=\"alert alert-secondary\">".$i->name."</div></div>
		  <div class=\"col\"><strong>Signature</strong><br><div class=\"alert alert-secondary\"><img src=\"data:image/png;base64,".base64_encode(file_get_contents($docFolder."/".$i->nameSign))."\" alt=\"".$i->name."\" /></div></div>
		</div>
		<div class=\"row\">
		  <div class=\"col\"><strong>Manager</strong><br><div class=\"alert alert-secondary\">".$i->mgrName."</div></div>
		  <div class=\"col\"><strong>Signature</strong><br><div class=\"alert alert-secondary\"><img src=\"data:image/png;base64,".base64_encode(file_get_contents($docFolder."/".$i->mgrSign))."\" alt=\"".$i->name."\" /></div></div>
		</div>
		</div>";
		if($pdfOnly==1){
			$content['format']='A4-P';
			$content['Save']=$docFolder . "/";
		  $content['title']="Key Release for " . $d->restaurantName;
		  $content['html']=$this->docHeader("Key Release Form").$html;
			if($file=$this->buildHTMLPDF(json_encode($content))){
				return $file;
			}
		}
		$docName=str_replace(" ","_",str_replace("/","_", "Key Release for " . $d->restaurantName));
		if(file_exists($docFolder . "/" . $docName . ".pdf")){
			/*
			$html.="
			<div class=\"container-fluid\">
				<div class=\"row\">
				  <div class=\"col\"><strong>Printable PDF</strong><br><div class=\"alert alert-secondary\">".$i->mgrName."</div></div>
				  <div class=\"col\"></div>
				</div>
			</div>
			";
			*/
		}
		return $html;
	}
	public function showRestaurantOrders(){
		global $wpdb;
		$r=new ToastReport;
		$return="";
		$table['File']=rand(0,time());
		$table['Headers']=array("Restaurant","Order Date","Order Status","");
		$data=$wpdb->get_results( "SELECT idpbc_pbk_orders,restaurantName,orderDate FROM pbc_pbk_orders,pbc_pbrestaurants WHERE  orderType = '".$_GET['type']."' AND orderStatus='Pending' AND pbc_pbk_orders.restaurantID=pbc_pbrestaurants.restaurantID" );
		if($data){
			foreach($data as $d){
				$link="<a href='".admin_url( 'admin.php?page=pbr-orders&type=LightBulb&id='.$d->idpbc_pbk_orders)."' >View</a>";
				$table['Results'][]=array($d->restaurantName,date("m/d/Y",strtotime($d->orderDate)),"Pending",$link);
			}
			$return.=$r->showResultsTable($table);
		}else{
			$return.="<div class='row'><div class='col'><div class='alert alert-warning'>There are no Pending Orders Found.</div></div></div>";
		}
		$return.="<h4 style='padding-top:15px;'>Search for Completed Orders</h4>
		<form method=\"get\" action=\"" . admin_url( 'admin.php?page=pbr-orders') . "\">
			<input type=\"hidden\" name=\"page\" value=\"pbr-orders\" />
			<input type=\"hidden\" name=\"type\" value=\"".$_GET['type']."\" />
			<div class='row'>
				<div class='col'>".$this->buildDateSelector('startDate',"Starting Date")."</div>
				<div class='col'>".$this->buildDateSelector('endDate',"Ending Date")."</div>
				<div class='col'><label for='restaurantID'>Restaurant <i>(Optional)</i></label><br>".$this->buildRestaurantSelector()."</div>
			</div>
			<div class='row'>
				<div class='col'><input type=\"submit\" class=\"btn btn-primary\" id='submit' value='Search'/></div>
				<div class='col'></div>
				<div class='col'></div>
			</div>
		</form>
		" . $this->pbk_form_processing();
		if(isset($_GET['startDate']) && isset($_GET['endDate'])){
			$table['File']=rand(0,time());
			unset($table['Results']);
			if(isset($_GET['restaurantID']) && is_numeric($_GET['restaurantID'])){$status="pbc_pbk_orders.restaurantID='".$_GET['restaurantID']."' AND ";}else{$status="";}
			$data=$wpdb->get_results( "SELECT idpbc_pbk_orders,restaurantName,orderDate,orderStatus FROM pbc_pbk_orders,pbc_pbrestaurants
				WHERE  orderType = '".$_GET['type']."' AND $status orderDate BETWEEN '".date("Y-m-d 00:00:00",strtotime($_GET['startDate']))."' AND '".date("Y-m-d 23:59:59",strtotime($_GET['endDate']))."' AND orderStatus!='Pending' AND pbc_pbk_orders.restaurantID=pbc_pbrestaurants.restaurantID" );
			if($data){
				foreach($data as $d){
					$link="<a href='".admin_url( 'admin.php?page=pbr-orders&type=LightBulb&id='.$d->idpbc_pbk_orders)."' target='_blank'>View</a>";
					$table['Results'][]=array($d->restaurantName,date("m/d/Y",strtotime($d->orderDate)),$d->orderStatus,$link);
				}
				$return.=$r->showResultsTable($table);
			}else{
				$return.="<div class='row'><div class='col'><div class='alert alert-warning'>There are no Completed Orders Found.</div></div></div>";
			}
		}
		return $return;
	}
	public function buildRestaurantSelector($single=0,$field='restaurantID',$data=null){
		$this->getMyRestaurants($field);
		if(count($this->myRestaurants)==0){
			return "<div class='alert alert-danger'>No Restaurants Assigned</div>";
		}elseif (count($this->myRestaurants)==1) {
			$keyVal=key($this->myRestaurants);
				return "<input type='hidden' value='".$keyVal."' name='$field' /><div>".$this->myRestaurants[$keyVal]."</div>";
		}else {
			if($single==0){
				$return= "
					<select name='".$field."' class=\"custom-select multipleSelect\" id='".$field."'>
						<option value=''>Choose One</option>
					";
				foreach($this->myRestaurants as $id=>$name){
					if(isset($data) && $data!="" && $data==$id){$checked="selected";}else{$checked="";}
					$return.="
						<option value='$id' $checked>$name</option>
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
	public function pbk_addImageSelector($media="media"){
		return "		<script>
				var gk_media_init = function(selector, button_selector)  {
		    var clicked_button = false;

		    jQuery(selector).each(function (i, input) {
		        var button = jQuery(input).next(button_selector);
		        button.click(function (event) {
		            event.preventDefault();
		            var selected_img;
		            clicked_button = jQuery(this);

		            // check for media manager instance
		            if(wp.media.frames.gk_frame) {
		                wp.media.frames.gk_frame.open();
		                return;
		            }
		            // configuration of the media manager new instance
		            wp.media.frames.gk_frame = wp.media({
		                title: 'Select image',
		                multiple: false,
		                library: {
		                    type: 'image'
		                },
		                button: {
		                    text: 'Use selected image'
		                }
		            });

		            // Function used for the image selection and media manager closing
		            var gk_media_set_image = function() {
		                var selection = wp.media.frames.gk_frame.state().get('selection');

		                // no selection
		                if (!selection) {
		                    return;
		                }

		                // iterate through selected elements
		                selection.each(function(attachment) {
		                    var url = attachment.attributes.url;
		                    clicked_button.prev(selector).val(url);
		                });
		            };

		            // closing event for media manger
		            wp.media.frames.gk_frame.on('close', gk_media_set_image);
		            // image selection event
		            wp.media.frames.gk_frame.on('select', gk_media_set_image);
		            // showing media manager
		            wp.media.frames.gk_frame.open();
		        });
		   });
		};
		jQuery(document).ready(function() {
			gk_media_init('.".$media."-input', '.".$media."-button');
		});
		</script>";
	}
	public function buildSelectBox($data=array()){
		if(isset($data['Change'])){$change=' onchange="'.$data['Change'].'" ';}else{$change='';}
		$return="
		<select name='".$data['Field']."' class=\"custom-select multipleSelect\" required id='".$data['ID']."' ".$data['Multiple']."$change>
			<option value=''>Choose One</option>
			";
			foreach($data['Options'] as $id=>$option){
				$return.="
			<option value='$id'>$option</option>
				";
			}
		$return.="
		</select>";
		return $return;
	}
	function pbk_foh_attachment($file_handler,$post_id,$set_thu=false) {
	  if ($_FILES[$file_handler]['error'] !== UPLOAD_ERR_OK) __return_false();
	  require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	  require_once(ABSPATH . "wp-admin" . '/includes/file.php');
	  require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	  $attach_id = media_handle_upload( $file_handler, $post_id );
	  return $attach_id;
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
		<label for='$field' id='".$field."Label'>$label</label>
		<input class=\"form-control\" type=\"text\" id=\"".$field."\" name=\"".$field."\" value=\"".$dateValue."\"/>
		";
	}
	public function docHeader($header=""){
	return	"
    <div class=\"container\">
      <div class=\"row\">
        <div class=\"col\">
          <img src='" . PBKF_URL . "/assets/images/PBK-Logo_Primary_Full-Color_doc.png' /><br><h2>$header</h2>
        </div>
      </div>
    </div>
    ";
	}
}

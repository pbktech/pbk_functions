<?php
global $wp;
global $wpdb;
global $ret;
$cu = wp_get_current_user();
$page = home_url( add_query_arg( array(), $wp->request ) );
$actionValue="add";
$onChecked="";
$offChecked="";
$dateValue="";
$timeValue="";
if(isset($_GET['id'])){
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$tasks=new task_engine($mysqli);
	$task=$tasks->get_task(array("id"=>$_GET['id']));
	$taskActions=json_decode($task->files);
	$actionValue="update";
	$dateValue=date("Y-m-d",strtotime($task->dueDate));
	$timeValue=date("H:i:s",strtotime($task->dueDate));
	if($taskActions->action=="true"){$onChecked="checked='checked'";}else{$offChecked="checked='checked'";}
}
$query = "SELECT levelUpID,restaurantName FROM pbc2.pbc_pbrestaurants WHERE levelUpID is not null";
$records=$wpdb->get_results($query);
if(!empty($records)){
	$restaurants="<div style='width:100%;'><select style='width:100%;' class=\"custom-select multipleSelect\" id=\"restaurantPicker\" name=\"change[restaurants][]\" multiple=\"multiple\">";
	foreach($records as $rec){
		$boards[$rec->levelUpID]=$rec->restaurantName;
		if(is_array($taskActions->restaurants) && in_array($rec->levelUpID,$taskActions->restaurants)){$ch=" checked='checked' ";}else{$ch="";}
		$restaurants.="\n<option value='".$rec->levelUpID."'$ch>".$rec->restaurantName."</option>";
	}
	$restaurants.="</select>";
}
$restaurants.="</div>";
if($_SERVER['REQUEST_METHOD'] == 'POST'){
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$tasks=new task_engine($mysqli);
	if($_POST['action']=='add'){
		$date=date("Y-m-d",strtotime($_POST["startDate"]));
		$time=date("H:i:s",strtotime($_POST["time_picker"]));
		$tasks->add_task(['what'=>'execBackground',
		'target'=>"/home/jewmanfoo/levelup-website-bot/change.sh ",
		'files' => json_encode($_POST['change']),
		'dueDate' => $date . " " . $time]);
	}
	if($_POST['action']=='delete'){
		$tasks->delete_task($_POST["task_id"]);
	}
	if($_POST['action']=='update'){
		$date=date("Y-m-d",strtotime($_POST["startDate"]));
		$time=date("H:i:s",strtotime($_POST["time_picker"]));
		$tasks->update_task ($_POST['id'], array('files' => json_encode($_POST['change']),'dueDate' => $date . " " . $time));
	}
}
$jQuery="
<script>
jQuery(document).ready(function() {
	jQuery('#time_picker').timepicker({
		'timeFormat': 'h:mm p',
		interval: 15,
		    minTime: '5:00 am',
		    maxTime: '9:00 pm',
				dynamic: false,
				dropdown: true,
 	    	scrollbar: true
	});
  jQuery('#startDate').datepicker({
      dateFormat : 'yy-mm-dd'
  });
	jQuery('#restaurantPicker').select2({
  	theme: \"classic\"
	});
});
</script>
";
		$ret.=$jQuery."
	<div>
		<h4>Things to remember when using this system</h4>
		<ul>
			<li>You must set dates/times for off and on</li>
			<li>All times are in Cental. You need to adjust for CO and DC</li>
		</ul>
	</div>
		<div>
			<form method='post' action='".$page."' >
			<input type='hidden' name='action' value='".$actionValue."' />";
	if(isset($task->id)){$ret.="<input type='hidden' name='id' value='".$task->id."' />";}
			$ret.="
				<div>
				<h4>Please choose a date and time</h4>
					<label for='startDate'>Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"".$dateValue."\"/><br />
					<label for='time_picker'>Time</label><br /><input id='time_picker' name='time_picker' id='time_picker' value='' style='width: 100px;' value=\"".$timeValue."\"/><br />
					<h4>Set App State</h4>
				 <input type='radio' value='true' name='change[action]' id='ocAction-open' ".$onChecked." />	<label for='ocAction-open'>On</label> <input type='radio' value='false' name='change[action]' id='ocAction-close' ".$offChecked." /> <label for='ocAction-close'>Off</label><br />
				</div>
				<h4>Select the restaurants</h4>
				" . $restaurants . "
				<div>
					<input type='submit' value='ADD' />
				</div>
			</form>
		</div>
	";
	$query = "SELECT files,dueDate,id FROM pbc2.pbc_tasks WHERE target ='/home/jewmanfoo/levelup-website-bot/change.sh ' AND dueDate >= CURDATE() AND deleted='0' AND dateCompleted is NULL ORDER BY dueDate ";
	$records=$wpdb->get_results($query);
	$count=0;
	$stateChange["false"]="Off";
	$stateChange["true"]="On";
	if(!empty($records)){
		$ret.="<div><h4>Upcoming Changes</h4>
		<table><tr><th><strong>Date/Time</strong></th><th><strong>Restaurant(s)</strong></th><th><strong>State Change</strong></th><th></th><th></th></tr>";
		foreach($records as $rec){
			$data=json_decode($rec->files);
			$rets=array();
			foreach($data->restaurants as $r){$rets[]=$boards[$r];}
			$ret.="<tr><td>".date("m/d/Y H:i a",strtotime($rec->dueDate))."</td><td>".implode(", ",$rets)."</td><td>".$stateChange[$data->action]."</td>
			<td>
				<form method='post' action='".$page."' >
					<input type='hidden' name='action' value='delete' />
					<input type='hidden' name='task_id' value='".$rec->id."' />
					<input type='submit' value='Delete' />
				</form>
			</td>
			<td>
			<form method='get' action='".$page."' >
				<input type='hidden' name='id' value='".$rec->id."' />
				<input type='submit' value='Edit' />
			</form>
			</td>
			</tr>";
		}
		$ret.="</table></div>";
	}
	/*
function addInlineScripts_sluc(){
	wp_add_inline_script("runtime_jquery_sluc",$jQuery,'before');
}
add_action( 'wp_enqueue_scripts', 'addInlineScripts_sluc');
*/

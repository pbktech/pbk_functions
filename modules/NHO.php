<?php
global $wp;
global $wpdb;
global $ret;
$cu = wp_get_current_user();
$page = home_url( add_query_arg( array(), $wp->request ) );
$restaurant=new Restaurant();
if (isset($_REQUEST['event']) && is_numeric($_REQUEST['event'])) {
  $event=$restaurant->getNHOInfo($_REQUEST['event']);
}
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $save=$restaurant->updateNHOAttendee($_POST);
  if($save=="Success"){
    $message="<div class='alert alert-success' id='message' style='text-align:center;'><p style='padding:3px;'>The updates have been saved.</p></div>";
  }else{
    $message="<div class='alert alert-danger' id='message' style='text-align:center;'><p style='padding:3px;'>There was an error saving. This error has been reported.</p></div>";
  }
  $ret.= $message."
      <script type=\"text/javascript\">
        $(document).ready(function(){
          setTimeout(function(){
          $(\"#message\").hide(\"20000\")
        }, 30000);
        });
      </script>
";
}
if (!isset($_REQUEST['event']) || !isset($event->nhoID) || $event->nhoID=="") {
  $nhoEvents=$restaurant->getNHOEvents();
  $ret.="\n
	<div>
		<form method='get' action='".$page."'  name='restaurantSelector'>
			<select name='event' class='custom-select' onchange=\"this.form.submit()\"><option value=''>Choose an Event</option>";
	foreach($nhoEvents as $event){
		$ret.="\n<option value='".$event['nhoID']."'>".date("m/d/Y",strtotime($event['nhoDate']))." at ".$restaurant->getRestaurantName($event['nhoLocation'])."</option>";
	}
	$ret.="</select></form></div>";
}else{
  $attendees=$restaurant->getNHOAttendees($event->nhoID);
  $cutOffTime=strtotime('-1 day', strtotime($event->nhoDate))+61200;
  if(time()>$cutOffTime && $restaurant->isAboveStore==0){$disabled=1;}else {$disabled=0;}
  $ret.="
  <script>
  function changeBackground(field,fieldID,number){
    if(number==2){
      var bgColor='p-3 mb-2 bg-success text-white';
    }else{
      var bgColor='p-3 mb-2 bg-danger text-white';
    }
    document.getElementById(field+\"_td_\"+fieldID).className = bgColor;
  }
  </script>
  <div class='container'>
    <form method='post' action='".$page."'>
      <input type='hidden' name='nhoID' value='".$event->nhoID."' />
      <input type='hidden' name='event' value='".$event->nhoID."' />
      <input type='hidden' name='redirect' value='".$page . "?event=". $event->nhoID ."' />
    ";
    $ret.=$restaurant->nhoHeader(array("nhoDate"=>$event->nhoDate,"restaurantName"=>$event->restaurantName,"display_name"=>$event->display_name,"nhoTIme"=>$event->nhoTIme));
    if($attendees){
			foreach($attendees as $attendee){
        if($attendee->attendeeID!=''){
          $ret.=$restaurant->buildNHOAttendeeLine($attendee->attendeeID,$disabled);
        }
      }
    }
    if($disabled==0){
      $ret.=$restaurant->buildNHOAttendeeLine();
      $ret.="
      <div class=\"row\"><div class=\"col\"><input type='submit' value='Save' /></div></div>";
    }
    $ret.="
    </form>
    </div>
    <div style='text-align:center;'>
    <h3>Updates can be made until ".date("m/d/Y g:i a",$cutOffTime)." </h3><br>
    <h1 style='color:#d61111;'><strong>Verfy the date, time and location</strong></h1><br>
    </div>
    ";
}

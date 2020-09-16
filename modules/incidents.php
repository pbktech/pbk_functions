<?php
global $wp;
global $wpdb;
global $ret;
$aboveStore=pbk_check_privledge();
$cu = wp_get_current_user();
$page = home_url( add_query_arg( array(), $wp->request ) );
$restaurant=new Restaurant();
include dirname(__FILE__) . '/forms/incident_header.php';
include dirname(__FILE__) . '/forms/foodborneIllness.php';
include dirname(__FILE__) . '/forms/injury.php';
include dirname(__FILE__) . '/forms/lostStolenProperty.php';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $restaurant->restaurantID=$_POST['restaurantID'];
  $restaurant->loadRestaurant();
  if($GM=$restaurant->getManagerEmail("GM")){$emailAddress[]=$GM;}
  if($AGM=$restaurant->getManagerEmail("AGM")){$emailAddress[]=$AGM;}
  if($AM=$restaurant->getManagerEmail("AM")){$emailAddress[]=$AM;}
  $incidentType=$_POST['incidentType'];
  $reportInfo=json_encode($_POST['reportInfo'][$incidentType]);
  $guestInfo=json_encode($_POST['guest']);
  $dateOfIncident=date("Y-m-d",strtotime($_POST['startDate'])) . " " . date("G:i:s",strtotime($_POST['timeOfIncident']));
  $content['format']='A4-P';
  $content['title']=$restaurant->incidentTypes[$incidentType]["Name"] . ' Incident Report ' . $_POST['restaurantID'] . "-" . date("Ymd");
  $content['html']=pbk_form_incident_header($_POST)."<h3>" . $restaurant->incidentTypes[$incidentType]["Name"] . "</h3>";
  switch($incidentType){
    case "foodborneIllness":
      $content['html'].=pbk_form_foodborneIllness($_POST['reportInfo']['foodborneIllness']);
      $incidentTypeName="foodborneIllness";
      $emailAddress[]="mcrawford@theproteinbar.com";
      break;
    case "injury":
      $content['html'].=pbk_form_injury($_POST['reportInfo']['injury']);
      $incidentTypeName="injury";
      $emailAddress[]="hr@theproteinbar.com";
      $emailAddress[]="mcrawford@theproteinbar.com";
      break;
    case "lostStolenProperty":
      $content['html'].=pbk_form_lostStolenProperty($_POST['reportInfo']['lostStolenProperty']);
      $incidentTypeName="lostStolenProperty";
      $emailAddress[]="jarbitman@theproteinbar.com";
      $emailAddress[]="mcrawford@theproteinbar.com";
      break;
  }
  $email= new ToastReport();
  if($incidentTypeName=="foodborneIllness" && isset($_POST['reportInfo']['foodborneIllness']['guestCheck']) && is_numeric($_POST['reportInfo']['foodborneIllness']['guestCheck'])){
    $email->setRestaurantID($_POST['restaurantID']);
    $email->setBusinessDate($dateOfIncident);
    if($checkItems=$email->getCheckItemsFromNumber($_POST['reportInfo']['foodborneIllness']['guestCheck'])){
      $content['html'].="<h4>CHECK DETAILS</h4>
        <ol>
      ";
      foreach($checkItems as $items =>$item){
        $content['html'].="<li>(" . $item->quantity . ") " .  $item->displayName . "</li>";
      }
      $content['html'].="</ol>";
    }else{
      $content['html'].="<h4>CHECK NOT FOUND</h4>";
    }
  }
  $wpdb->query(
    $wpdb->prepare(
      "INSERT INTO pbc_incident_reports (dateOfIncident,reporterName,restaurantID,guestInfo,incidentType,reportInfo)
      VALUES(%s,%s,%s,%s,%s,%s)",$dateOfIncident,$_POST['reporterName'],$_POST['restaurantID'],$guestInfo,$incidentTypeName,$reportInfo));
  if($wpdb->last_error !== '') {
  $ret.=  pbk_show_response(array("class"=>"alert","message"=>  "There was an error saving. This error has been reported.<br>" . $wpdb->print_error()));
    $email->reportEmail("jon@theproteinbar.com","SQL Error \n".$wpdb->print_error()."\n\nPosted Data \n".print_r($_POST,true),"Incident Report Save Error");
    exit();
  }
  if($pdf=$restaurant->buildHTMLPDF(json_encode($content))){
    $emailAddress[]=$cu->user_email;
    $email->reportEmail(implode(",",$emailAddress),"Please see attached PDF","New Incident Report",$pdf);
  $ret.=  pbk_show_response(array("class"=>"success","message"=>  "The incident report for " . $_POST['guest']['Name'] . " has been saved."));
  }
}
if(isset($_POST)){unset($_POST);}
$ret.="
<script>
jQuery(document).ready(function() {
  jQuery('#dateOfIncident').datepicker({
    dateFormat : 'MM d, yy',
    maxDate: '0'
  });
  jQuery('#time_picker').timepicker({
		'timeFormat': 'h:mm p',
		interval: 5,
		    minTime: '5:00 am',
		    maxTime: '9:00 pm',
				dynamic: false,
				dropdown: true,
 	    	scrollbar: true
	});
  jQuery(\"#submit\").prop(\"disabled\", true);
  jQuery(\"#submit\").addClass(\"btn btn-secondary btn-lg disabled\");
  jQuery(\"#incidentType\").change(function () {
    var elementToChange=jQuery(\"#incidentType\").val();
    jQuery(\".to-hide\").hide();
    jQuery(\"#\" + elementToChange).show();
    jQuery(\"#incidentType\").prop(\"disabled\", true);
    jQuery(\"#submit\").removeClass(\"btn btn-secondary btn-lg disabled\");
    jQuery(\"#submit\").prop(\"disabled\", false);
    jQuery(\"#hiddenIncidentType\").html(\"<input type='hidden' name='incidentType' value='\" + elementToChange + \"' />\");
  });
  jQuery(\"#restaurantID\").select2({
  	theme: \"classic\"
	});
  jQuery(\"#submit\").click(function(event){
    var error_free=true;
    var elementToCheck=jQuery(\"#incidentType\").val();
    if(jQuery(\"#guest_Phone\").val()=='' && jQuery(\"#guest_Email\").val()==''){
      alert('Please Provide a Contact Method');
      jQuery(\"#guest_Phone\").css(\"border\",\"1px solid red\");
      jQuery(\"#guest_Email\").css(\"border\",\"1px solid red\");
      error_free=false;
    }
    if(elementToCheck=='foodborneIllness'){
      if(!jQuery(\"#conclusions\").is(':checked')){jQuery(\"#conclusions_label\").after('<span class=\"alert alert-danger\">Please Confirm</span>');error_free=false;}
      if(!jQuery(\"#contacted\").is(':checked')){jQuery(\"#contacted_label\").after('<span class=\"alert alert-danger\">Please Confirm</span>');error_free=false;}
      if(jQuery(\"#fbi_summary\").val()==''){jQuery(\"#fbi_summary\").after('<span class=\"alert alert-danger\">Please Add a Summary</span>');error_free=false;}
    }
    if(elementToCheck=='lostStolenProperty'){
      if(jQuery(\"#isStolen\").val()==''){jQuery(\"#isStolen\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#itemValue\").val()==''){jQuery(\"#contacted_label\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#property_summary\").val()==''){jQuery(\"#property_summary\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#property_witness\").val()==''){jQuery(\"#property_witness\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
    }
    if(elementToCheck=='injury'){
      if(jQuery(\"#injuryType :selected\").length==0){jQuery(\"#injuryType\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#bodyPart :selected\").length==0){jQuery(\"#bodyPart\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#bodySide :selected\").length==0){jQuery(\"#bodySide\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#injury_summary\").val()==''){jQuery(\"#injury_summary\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(jQuery(\"#injury_witness\").val()==''){jQuery(\"#injury_witness\").after('<span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(!jQuery(\"#medicalRequired_yes\").is(':checked') && !jQuery(\"#medicalRequired_no\").is(':checked')){jQuery(\"#medicalRequired_label\").after('<br><span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(!jQuery(\"#emergencyCalled_yes\").is(':checked') && !jQuery(\"#emergencyCalled_no\").is(':checked')){jQuery(\"#emergencyCalled_label\").after('<br><span class=\"alert alert-danger\">Required</span>');error_free=false;}
      if(!jQuery(\"#isEmployee_yes\").is(':checked') && !jQuery(\"#isEmployee_no\").is(':checked')){jQuery(\"#isEmployee_label\").after('<br><span class=\"alert alert-danger\">Required</span>');error_free=false;}
    }
    if (!error_free){
    		event.preventDefault();
    }else{
      window.scrollTo(0,0);
      jQuery(\"#incidentForm\").hide();
      jQuery(\"#processingGif\").show();
    }
  });
});
</script>
<div id='incidentForm'>
<form method='post' action='".home_url( add_query_arg( array(), $wp->request ) )."' id='incidentReport' >
  <div class=\"form-group\">
    ".pbk_form_incident_header()."
  </div>
  <div class='alert alert-primary to-hide' id='choose' >Please Select an Incident Type</div>
    <div class=\"form-group to-hide\" id='foodborneIllness' style=\"display: none;\">
    ".pbk_form_foodborneIllness()."
    </div>
    <div class=\"form-group to-hide\" id='injury' style=\"display: none;\">
  ".pbk_form_injury()."
    </div>
    <div class=\"form-group to-hide\" id='lostStolenProperty' style=\"display: none;\">
  ".pbk_form_lostStolenProperty()."
    </div>
    <div class=\"form-group\" id=''>
    <input type='submit' id='submit' value='Save Incident Report' />
  </div>
</form>
</div>
<div id='processingGif' style=\"display: none;text-align:center;\"><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>
";

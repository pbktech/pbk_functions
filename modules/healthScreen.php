<?php
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$r=new Restaurant;
$employees=array();
if(isset($_REQUEST['l']) && $_REQUEST['l']=='es'){
  $lang="Spanish";
  echo '
  <div class="alert alert-info" role="alert">
    <a href="' . $page . '" >View this page in English</a>
  </div>
  ';
}else{
  $lang="English";
  echo '
  <div class="alert alert-info" role="alert">
    <a href="' . $page . '?l=es" >Ver esta página en español</a>
  </div>
  ';
}


$questions[1]["English"]="Have you traveled outside of the United States or been in close contact with anyone who has traveled outside of the United States within the last 14 days?";
$questions[1]["Spanish"]="¿Ha viajado fuera de los Estados Unidos o ha estado en contacto cercano con alguien que haya viajado fuera de los Estados Unidos en los últimos 14 días?";
$questions[2]["English"]="Have you had close contact with or cared for someone diagnosed with COVID-19 within the last 14 days?";
$questions[2]["Spanish"]="¿Ha tenido contacto cercano o ha cuidado a alguien diagnosticado con COVID-19 en los últimos 14 días?";
$questions[3]["English"]="Have you experienced any cold or flu-like symptoms in the last 14 days (fever, cough, shortness of breath or other respiratory problem)?";
$questions[3]["Spanish"]="¿Ha experimentado síntomas de resfriado o gripe en los últimos 14 días (fiebre, tos, falta de aliento u otro problema respiratorio)?";
$labels["Name"]["English"]="Name";
$labels["Name"]["Spanish"]="NOMBRE";
$labels["Date"]["English"]="Date";
$labels["Date"]["Spanish"]="FECHA";
$labels["Temp1"]["English"]="Temp 1";
$labels["Temp1"]["Spanish"]="Temp 1";
$labels["Temp2"]["English"]="Temp 2";
$labels["Temp2"]["Spanish"]="Temp 2";
$labels["Yes"]["English"]="YES";
$labels["Yes"]["Spanish"]="SÍ";
$labels["No"]["English"]="NO";
$labels["No"]["Spanish"]="NO";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  $_POST['orderData']["Questions"]=$questions;
  $emp=$wpdb->get_row("SELECT * FROM pbc_ToastEmployeeInfo WHERE guid='" . $_POST['orderData']['reporterName'] . "'");
  $_POST['orderData']['name']=$emp->employeeName;
  $wpdb->query(
  $wpdb->prepare(
    "INSERT INTO pbc_pbk_orders (orderType,restaurantID,userID,orderData,orderDate,orderStatus)VALUES(%s,%d,%d,%s,%s,%s)",
          "HealthScreen", $_POST['restaurantID'],get_current_user_id(),json_encode($_POST['orderData']),date("Y-m-d H:i:s"),"Complete"
    )
  );
  if($wpdb->last_error==''){
    $guid=$wpdb->get_var("SELECT guid FROM pbc_pbk_orders WHERE idpbc_pbk_orders = '".$wpdb->insert_id."'");
    $wpdb->query(
    $wpdb->prepare(
      "INSERT INTO pbc_pbk_order_meta (searchTerm,guid)VALUES(%s,%s)",
            $_POST['orderData']['name'],$guid
      )
    );
    $d=$r->getPBKOrderinfo($wpdb->insert_id);
    $report=New ToastReport;
    $docFolder=dirname(dirname($report->docSaveLocation)) ."/docs/". $d->guid;
    if (!file_exists($docFolder)) {mkdir($docFolder);}
    $content['format']='A4-P';
    $content['Save']=$docFolder . "/";
    $content['title']="DAILY HEALTH SCREEN for " . $emp->employeeName . " at " . $d->restaurantName;
    $content['fileName']=$report->hexFileName($content['title']);
    $content['html']=$r->docHeader("DAILY HEALTH SCREEN");
    $content['html'].=$labels["Name"][$_POST['orderData']['language']] . " : " . $emp->employeeName . "<br>";
    $content['html'].="Restaurant" . " : " . $d->restaurantName . "<br>";
    $content['html'].=$labels["Date"][$_POST['orderData']['language']] . " : " . date("m/d/Y H:i:s", strtotime($_POST['orderData']['date'])) . "<br>";
    $content['html'].=$labels["Temp1"][$_POST['orderData']['language']] . " : " . $_POST['orderData']['Temp1'] . "<br>";
    $content['html'].=$labels["Temp2"][$_POST['orderData']['language']] . " : " . $_POST['orderData']['Temp2'] . "<br>";
    $content['html'].=$questions[1][$_POST['orderData']['language']] . " : " . $_POST['orderData']['question'][1] . "<br>";
    $content['html'].=$questions[2][$_POST['orderData']['language']] . " : " . $_POST['orderData']['question'][2] . "<br>";
    $content['html'].=$questions[3][$_POST['orderData']['language']] . " : " . $_POST['orderData']['question'][3];
    if($file=$r->buildHTMLPDF(json_encode($content))){
      $ret.= "
      <script>
        jQuery(document).ready(function(){
          setTimeout(function(){
          jQuery(\".alert\").hide(\"20000\")
        }, 30000);
        });
      </script>
    <div class='alert alert-success'><strong>Health Screen for " . $emp->employeeName . " has been saved.</strong></div>";
    }
  }
}


$results = $wpdb->get_results( "SELECT restaurantName, ptei.guid  as 'guid', employeeName FROM pbc_ToastEmployeeInfo ptei , pbc_pbrestaurants pp
   WHERE pp.restaurantID = ptei.restaurantID AND externalEmployeeId !='' AND deleted ='0'
   AND ptei.restaurantID IN (SELECT restaurantID FROM pbc_pbr_managers WHERE managerID='".get_current_user_id()."') ORDER BY employeeName,ptei.restaurantID");
if($results){
  foreach ($results as $value) {
    $employees[$value->restaurantName][$value->guid]=$value->employeeName;
  }
}else {
  echo '
  <div class="alert alert-danger" role="alert">
    You must have at least one restaurant assigned to you to use this page.
  </div>
  ';
  exit;
}
$ret.="
<script>
jQuery(document).ready(function() {
    jQuery('.js-example-basic-single').select2();
    jQuery('#date').datepicker({
        dateFormat : 'mm/dd/yy'
    });
});
</script>
<div class='container' id='queryResults'>
  <form method='post' action='".$page."' id='' class=\"needs-validation\" novalidate >
    <div class='row' style='background-color:#f9b58f;color:#FFFFFF;'>
      <div class='col'><label for='reporterName'>".$labels["Name"][$lang]."</label>
      <select name='reporterName' id='reporterName'  class='js-example-basic-single custom-select' required><option value=''>Choose an Employee</option>";
foreach($employees as $restaurant=>$emps){
  $ret.="<optgroup label='".$restaurant."'>";
  foreach ($emps as $id=>$item) {
    $ret.="\n<option value='".$id."'>".stripslashes($item)."</option>";
  }
}

$ret.="</select><input type='hidden' name='orderData[language]' value='".$lang."' /></div>
    </div>
    <div class='row' style='background-color:#f9b58f;color:#FFFFFF;'>
      <div class='col'><label for='date' id='dateLabel'>".$labels["Date"][$lang]."</label><input class=\"form-control\" type=\"text\" id='date' name='date' value='' required/></div>
      <div class='col'><label for='Temp1'>".$labels["Temp1"][$lang]."</label><br><input class=\"form-control\" type='text' name='orderData[Temp1]' id='Temp1' required/></div>
      <div class='col'><label for='Temp2'>".$labels["Temp2"][$lang]."</label><br><input class=\"form-control\" type='text' name='orderData[Temp2]' id='Temp2' required/></div>
    </div>
    <div class='row' style='background-color:#e7e6e6;color:#000000;'>
      <div class='col'><strong>" . $questions[1][$lang] . "</strong></div>
      <div class='col'><div class=\"custom-control custom-radio\"><input type=\"radio\" id=\"customRadio1y\" name='orderData[question][1]' value='Yes' class=\"custom-control-input\" required><label class=\"custom-control-label\" for='customRadio1y'>".$labels["Yes"][$lang]."</label></div></div>
      <div class='col'><div class=\"custom-control custom-radio\"><input type=\"radio\" id=\"customRadio1n\" name='orderData[question][1]' value='No' class=\"custom-control-input\" required><label class=\"custom-control-label\" for='customRadio1n'>".$labels["No"][$lang]."</label></div></div>
    </div>
    <div class='row' style='background-color:#e7e6e6;color:#000000;'>
      <div class='col'><strong>" . $questions[2][$lang] . "</strong></div>
      <div class='col'><div class=\"custom-control custom-radio\"><input type=\"radio\" id=\"customRadio2y\" name='orderData[question][2]' value='Yes' class=\"custom-control-input\" required><label class=\"custom-control-label\" for='customRadio2y'>".$labels["Yes"][$lang]."</label></div></div>
      <div class='col'><div class=\"custom-control custom-radio\"><input type=\"radio\" id=\"customRadio2n\" name='orderData[question][2]' value='No' class=\"custom-control-input\" required><label class=\"custom-control-label\" for='customRadio2n'>".$labels["No"][$lang]."</label></div></div>
    </div>
    <div class='row' style='background-color:#e7e6e6;color:#000000;'>
      <div class='col'><strong>" . $questions[3][$lang] . "</strong></div>
      <div class='col'><div class=\"custom-control custom-radio\"><input type=\"radio\" id=\"customRadio3y\" name='orderData[question][3]' value='Yes' class=\"custom-control-input\" required><label class=\"custom-control-label\" for='customRadio3y'>".$labels["Yes"][$lang]."</label></div></div>
      <div class='col'><div class=\"custom-control custom-radio\"><input type=\"radio\" id=\"customRadio3n\" name='orderData[question][3]' value='No' class=\"custom-control-input\" required><label class=\"custom-control-label\" for='customRadio3n'>".$labels["No"][$lang]."</label></div></div>
    </div>
    <div class='row'>
      <div class='col'><input type=\"submit\" class='btn btn-primary' id=\"submit\" value=\"Submit\"/></div>
      <div class='col'></div>
      <div class='col'></div>
    </div>
  </form>
</div>
<script>
(function() {
  'use strict';
  window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
</script>
";

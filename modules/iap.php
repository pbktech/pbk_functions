<?php
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$r=new Restaurant;
$store=$wpdb->get_var("SELECT mgrType FROM pbc_pbr_managers WHERE managerID='".get_current_user_id()."'");
if($r->isAboveStore==0 && $store=="STR"){
  $ret.="<div class='alert alert-danger'>This form must be filled out by an assistant manager or above.</div>";
  exit;
}
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if($_POST['part']==1){
    $r->setRestaurantID($_POST['restaurantID']);
    $_POST['orderData']['violationDate']=$_POST['violationDate'];
    $_POST['orderData']['coachingDate']=$_POST['coachingDate'];
    $_POST['orderData']['reporterName']=$_POST['reporterName'];
    $wpdb->query(
    $wpdb->prepare(
      "INSERT INTO pbc_pbk_orders (orderType,restaurantID,userID,orderData,orderDate,orderStatus)VALUES(%s,%d,%d,%s,%s,%s)",
            "IAP", $_POST['restaurantID'],get_current_user_id(),json_encode($_POST['orderData']),date("Y-m-d H:i:s"),"Pending"
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
      echo switchpbrMessages(10);
    }else {
      echo switchpbrMessages(9);
      exit;
    }
  }
  if($_POST['part']==2){
    $d=$r->getPBKOrderinfo($_POST['id']);
    $r->setRestaurantID($d->restaurantID);
    $report=New ToastReport;
		$docFolder=dirname(dirname($report->docSaveLocation)) ."/docs/". $d->guid;
    require_once dirname(dirname(__FILE__)) . "/classes/signature-to-image.php";
    if (!file_exists($docFolder)) {mkdir($docFolder);}
    if($_POST['orderData']['signature']['employee'] == ''){
      copy(dirname(dirname(__FILE__)) . "/assets/images/refused.png", $docFolder . "/" . "refused.png");
      $signature['signature']['employee']="refused.png";
    }else{
      $signature['signature']['employee']=saveSignImage($_POST['orderData']['signature']['employee'],$docFolder);
    }
    $signature['signature']['manager']=saveSignImage($_POST['orderData']['signature']['manager'],$docFolder);
    if($_POST['orderData']['signature']['witness'] = ''){
      $signature['signature']['witness']=saveSignImage($_POST['orderData']['signature']['witness'],$docFolder);
      $signature['signature']['witnessName']=$_POST['orderData']['signature']['witnessName'];
    }
    $orderUpdate=array_merge(json_decode($d->orderData,true),$signature);
    $wpdb->update(
      "pbc_pbk_orders",
      array("orderData"=>json_encode($orderUpdate),"orderStatus"=>"Pending"),
      array("idpbc_pbk_orders"=>$d->idpbc_pbk_orders),
      array("%s","%s"),
      array("%d")
    );
    if($wpdb->last_error==''){
      $content['format']='A4-P';
			$content['Save']=$docFolder . "/";
		  $content['title']="IAP for " . $orderUpdate['name'] . " at " . $d->restaurantName;
      $content['fileName']=$report->hexFileName($content['title']);
		  $content['html']=$r->docHeader("Individual Action Plan") . $r->showIAP($r->getPBKOrderinfo($_POST['id']));
			if($file=$r->buildHTMLPDF(json_encode($content))){
        $current_user = wp_get_current_user();
        $amEmail=$r->getManagerEmail("AM");
  //      $report->reportEmail($current_user->user_email.",laura@theproteinbar.com,lcominsky@theproteinbar.com",$r->showIAP($r->getPBKOrderinfo($_POST['id'])),"New IAP Submitted",$file['Local']);
				$ret.= switchpbrMessages(10);
			}
    }
  }
}
if(isset($_GET['id']) && $_GET['id']=="_NEW"){
$ret.='
<script>
jQuery(document).ready(function() {
  jQuery(\'#violationType_Other\').change(function(){
    if(jQuery(this).prop("checked")) {
      jQuery(\'#violationTypeOther\').show();
    } else {
      jQuery(\'#violationTypeOther\').hide();
    }
  });
  jQuery(\'#previousAction_yes\').change(function(){
    if(jQuery("#previousAction_yes").is(":checked")){
      jQuery(\'#previousActionExplain\').show();
    }
  });
  jQuery(\'#previousAction_no\').change(function(){
    if(jQuery("#previousAction_no").is(":checked")){
    jQuery(\'#previousActionExplain\').hide();
  }
  });
  jQuery(\'.violationSupport\').change(function(){
    if(jQuery("#violationSupport_Policy").is(":checked") || jQuery("#violationSupport_Other").is(":checked")){
      jQuery(\'#violationSupportExplain\').show();
    }else{
      jQuery(\'#violationSupportExplain\').hide();
    }
  });
  jQuery("#submit").click(function(event){
    var error_free=true;
    var requiredText="<span class=\'alert alert-danger toHide\'>Required</span>";
    if(jQuery("#name").val()==""){jQuery("#nameLabel").after(requiredText);error_free=false;}
    if(jQuery("#position").val()==""){jQuery("#positionLabel").after(requiredText);error_free=false;}
    if(jQuery("#violationDate").val()==""){jQuery("#violationDateLabel").after(requiredText);error_free=false;}
    if(jQuery("#reporterName").val()==""){jQuery("#reporterNameLabel").after(requiredText);error_free=false;}
    if(jQuery("#restaurantID").val()==""){jQuery("#restaurantIDLabel").after(requiredText);error_free=false;}
    if(jQuery("#coachingDate").val()==""){jQuery("#coachingDateLabel").after(requiredText);error_free=false;}
    if(jQuery("#violationDetails").val()==""){jQuery("#violationDetails_Label").after(requiredText);error_free=false;}
    if(jQuery("#violationPlan").val()==""){jQuery("#violationPlan_Label").after(requiredText);error_free=false;}

    if(!jQuery(".violationLevel").is(":checked")){jQuery("#violationLevel_label").after(requiredText);error_free=false;}
    if(!jQuery(".violationType").is(":checked")){jQuery("#violationType_label").after(requiredText);error_free=false;}
    if(!jQuery(".violationSupport").is(":checked")){jQuery("#violationSupport_label").after(requiredText);error_free=false;}
    if(!jQuery(".previousAction").is(":checked")){jQuery("#previousAction_label").after(requiredText);error_free=false;}
    if(jQuery("#previousAction_yes").is(":checked") && jQuery("#previousActionExplain").val()==""){jQuery("#previousActionExplain").after(requiredText);error_free=false;}
    if((jQuery("#violationSupport_Policy").is(":checked") || jQuery("#violationSupport_Other").is(":checked")) && jQuery("#violationSupportExplain").val()==""){jQuery("#violationSupportExplain").after(requiredText);error_free=false;}

    if (!error_free){
        event.preventDefault();
    }else{
      window.scrollTo(0,0);
      jQuery("#queryResults").hide();
      jQuery(".toHide").hide();
      jQuery("#processingGif").show();
    }

  });
});
</script>
<div class="container" id="queryResults">
  <h4>The purpose of this form is to:</h4>
  <ol>
    <li>Clarify and document performance expectations</li>
    <li>Focus on the current gap in expectations</li>
    <li>Outline a plan for improvement</li>
  </ol>
</div>
<form method="post" action="'.home_url( add_query_arg( array(), $wp->request ) ).'">
<input type="hidden" value="1" name="part" />
<div class="container-fluid toHide" id="queryResults" >
  <div class="row">
    <div class="col"><label for="name" id="nameLabel">Team Member Name</label><br><input type="text" class=\'form-control\' name=\'orderData[name]\' id=\'name\' /></div>
    <div class="col"><label for="position" id="positionLabel">Team Member Position</label><br><input type="text" class=\'form-control\' name=\'orderData[position]\' id=\'position\'/></div>
    <div class="col">'.$r->buildDateSelector('violationDate',"Violation Date").'</div>
  </div>
  <div class="row">
    <div class="col"><label for="reporterName" id="reporterNameLabel">Manager Name</label>'.$r->buildLoggedInName().'</div>
    <div class="col"><label for="restaurantID" id="restaurantIDLabel">Restaurant</label>'.$r->buildRestaurantSelector().'</div>
    <div class="col">'.$r->buildDateSelector('coachingDate',"Coaching Date").'</div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;" >
  <div class="row">
    <div class="col">
      <label for="violationLevel"  id="violationLevel_label">Violation Level:</label>
    </div>
  </div>
  <div class="row">
    ';
    foreach($r->violationLevel as $level){
  $ret.=  '
    <div class="col">
      <div class="input-group-text">
        <input class="violationLevel" type="radio" value="'.$level.'" id="violationLevel_'.$level.'" name="orderData[violationLevel]"><label for="violationLevel_'.$level.'">&nbsp;'.$level.'</label>
      </div>
    </div>
    ';
    }
  $ret.=  '
    </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;" >
  <div class="row">
    <div class="col">
      <label for="violationType_label"  id="violationType_label">Violation Type:</label>
    </div>
  </div>
  <div class="row">
    ';
    $count=0;
    foreach($r->violationType as $type){
  $ret.=  '
  <div class="col">
    <div class="input-group-text">
      <input class="violationType" type="checkbox" value="'.$type.'" id="violationType_'.$type.'" name="orderData[violationType][]"><label for="violationType_'.$type.'">&nbsp;'.$type.'</label>
    </div>
  </div>
    ';
    $count++;
    if($count % 3 == 0) {
      $ret.='</div>
      <div class="row">';
    }
    }
  $ret.=  '
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;" >
  <div id="violationTypeOther" class="row" style="display: none;">
    <div class="col">
      <textarea name="orderData[violationTypeOther]" placeholder="Please Explain"  class="form-control" ></textarea>
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;">
  <div class="row">
    <div class="col">
      <label for="previousAction_label"  id="previousAction_label">Has the team member received a prior action plan within the past 12 months?</label>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <div class="input-group-text">
        <input type="radio" value="Yes" id="previousAction_yes" name="orderData[previousAction]"><label for="previousAction_yes">&nbsp;Yes</label>
      </div>
    </div>
    <div class="col">
      <div class="input-group-text">
        <input class="previousAction" type="radio" aria-label="No" value="No" id="previousAction_no" name="orderData[previousAction]"><label for="previousAction_no">&nbsp;No</label>
      </div>
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;" >
  <div id="previousActionExplain" class="row" style="display: none;">
    <div class="col">
      <textarea name="orderData[previousActionExplain]" placeholder="Description of the incident"  class="form-control" ></textarea>
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;">
  <div class="row">
    <div class="col">
      <label for=""  id="violationDetails_Label">Violation Details:</label>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <textarea id="violationDetails" rows="10" name="orderData[violationDetails]" placeholder="Describe violation of rules, policies, standards or performance expectations. Be specific."  class="form-control" ></textarea>
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;">
  <div class="row">
    <div class="col">
      <label for="violationPlan_label"  id="violationPlan_Label">What Should be Happening / Plan for Improvement:</label>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <textarea id="violationPlan" rows="10" name="orderData[plan]" placeholder="Describe the gap between the team member’s actions and the PB Expectaions. What steps are the team member and manager going to take to improve the team member’s performance? Be specific."  class="form-control" ></textarea>
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;" >
  <div class="row">
    <div class="col">
      <label for="violationSupport_label"  id="violationSupport_label">Supporting documentation:</label>
    </div>
  </div>
  <div class="row">
    ';
    $count=0;
    foreach($r->violationSupport as $type){
  $ret.=  '
  <div class="col">
    <div class="input-group-text">
      <input type="radio" class="violationSupport" value="'.$type.'" id="violationSupport_'.$type.'" name="orderData[violationSupport]"><label for="violationSupport_'.$type.'">&nbsp;'.$type.'</label>
    </div>
  </div>
    ';
    $count++;
    if($count % 3 == 0) {
      $ret.='</div>
      <div class="row">';
    }
    }
  $ret.=  '
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;" >
  <div id="violationSupportExplain" class="row" style="display: none;">
    <div class="col">
      <textarea name="orderData[violationSuppotExplain]" placeholder="Please Explain"  class="form-control" ></textarea>
    </div>
  </div>
</div>
<div class="container-fluid toHide" id="queryResults" style="padding-top:15px;" >
<div class="row" style="padding-left:15px;">
  <div class="col">
    <input type="submit" class="btn btn-primary" id="submit" value="Save"/>
  </div>
</div>
</div>
</form>
'."<div id='processingGif' style='display: none;text-align:center;'><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>";
}elseif(isset($_GET['id']) && $data=$r->getPBKOrderinfo($_GET['id'])){
  $ret.=$r->showIAP($data);
  $i=json_decode($data->orderData);
  if(!isset($i->signature)){
    $ret.="
    <script>
    jQuery(document).ready(function() {
      jQuery(\'#refusal\').click(function(event){
        if(jQuery(\'#refusal\').is(':checked')){
          jQuery(\'#tmSignPad\').hide();
          jQuery(\'#tmRefusal\').show();
        }else{
          jQuery(\'#tmSignPad\').show();
          jQuery(\'#tmRefusal\').hide();
        }
      });
      jQuery(\'#submit\').click(function(event){
        var error_free=true;
        if(jQuery(\'#empSign\').val()=='' && !jQuery(\'#refusal\').is(':checked')){jQuery(\'#queryResults\').after('<div class=\'alert alert-danger\'>TM Signature Required</div>');error_free=false;}
        if(jQuery(\'#mgrSign\').val()==''){jQuery(\'#queryResults\').after('<div class=\'alert alert-danger\'>Manager Signature Required</div>');error_free=false;}
        if(jQuery(\'#violationLevel\').val()=='Termination'){
          if(jQuery(\'#witSign\').val()==''){jQuery(\'#queryResults\').after('<div class=\'alert alert-danger\'>Witness Signature Required</div>');error_free=false;}
          if(jQuery(\'#witName\').val()==''){jQuery(\'#queryResults\').after('<div class=\'alert alert-danger\'>Witness Name Required</div>');error_free=false;}
        }
        if (!error_free){
            event.preventDefault();
        }else{
          window.scrollTo(0,0);
          jQuery(\'#queryResults\').hide();
          jQuery(\'#signatures\').hide();
          jQuery(\'#processingGif\').show();
        }
      });
    });
    </script>
".'
    <h4>Please Sign Below</h4>
    <form method="post" action="'.home_url( add_query_arg( array(), $wp->request ) ).'">
      <input type="hidden" value="2" name="part" />
      <input type="hidden" value="'.$i->violationLevel.'" id="violationLevel" name="violationLevel" />
      <input type="hidden" value="'.$_GET['id'].'" name="id" />
      <div class="container-fluid" id="signatures" >
        <div class="row">
          <div class="col"><label for="">TM Signature</label><br>'.$i->name.'<br><input type="checkbox" name="orderData[signature][employee]" value="" id="refusal" /> Refused to sign</div>
          <div class="col" >
            <div id="tmRefusal" style="display:none;"><img src="' . PBKF_URL . '/assets/images/refused.png" alt="REFUSED" /></div>
            <div class="sigPad" id="tmSignPad" >
              <p class="drawItDesc">TM Signature</p>
                <ul class="sigNav">
                  <li class="drawIt"><a href="#draw-it" >Draw Signature</a></li>
                  <li class="clearButton"><a href="#clear">Clear</a></li>
                </ul>
              <div class="sig sigWrapper">
                <div class="typed"></div>
                <canvas class="pad" width="400" height="200"></canvas>
                <input type="hidden" name="orderData[signature][employee]" id="empSign" class="output">
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col"><label for="">Manager Signature</label><br>'.$i->reporterName.'</div>
          <div class="col">
            <div class="sigPad">
              <p class="drawItDesc">Manager Signature</p>
              <ul class="sigNav">
                <li class="drawIt"><a href="#draw-it" >Draw Signature</a></li>
                <li class="clearButton"><a href="#clear">Clear</a></li>
              </ul>
              <div class="sig sigWrapper">
                <div class="typed"></div>
                <canvas class="pad" width="400" height="200"></canvas>
                <input type="hidden" name="orderData[signature][manager]" id="mgrSign" class="output">
              </div>
            </div>
          </div>
        </div>
        ';
        if($i->violationLevel=="Termination"){
        $ret.='
        <div class="row">
          <div class="col"><label for="">Witness Signature</label><br><input type="text" class=\'form-control\' placeholder="Witness Name" name=\'orderData[signature][witnessName]\' id=\'witName\' required /></div>
          <div class="col">
            <div class="sigPad">
              <p class="drawItDesc">Witness Signature</p>
              <ul class="sigNav">
                <li class="drawIt"><a href="#draw-it" >Draw Signature</a></li>
                <li class="clearButton"><a href="#clear">Clear</a></li>
              </ul>
              <div class="sig sigWrapper">
                <div class="typed"></div>
                <canvas class="pad" width="400" height="200"></canvas>
                <input type="hidden" name="orderData[signature][witness]" id="witSign" class="output">
              </div>
            </div>
          </div>
        </div>';
        }
        $ret.='
        <div class=\'row\'>
          <div class=\'col\'><input type="submit" class=\'btn btn-primary\' id="submit" value="Submit"/></div>
          <div class=\'col\'></div>
        </div>
      </div>
    </form>
    <script>
      jQuery(document).ready(function() {
        jQuery(\'.sigPad\').signaturePad();
      });
    </script>
    '."<div id='processingGif' style=\'display: none;text-align:center;\'><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>";
  }
}else {
  if(isset($_GET['id']) && !$data=$r->getPBKOrderinfo($_GET['id'])){
    $ret.='<div class="alert alert-danger">Invalid IAP Identifier</div>';
  }
  $select["_NEW"]="Create a New IAP";
  $q="SELECT restaurantName,searchTerm,pbc_pbk_orders.guid as 'guid' FROM pbc2.pbc_pbk_orders,pbc2.pbc_pbk_order_meta,pbc_pbrestaurants WHERE
pbc_pbk_orders.guid=pbc2.pbc_pbk_order_meta.guid AND pbc_pbk_orders.restaurantID=pbc_pbrestaurants.restaurantID AND orderStatus='Pending'";
  if($store=="AM" || $store=="GM" || $store=="AGM"){
    $q.=" AND pbc_pbk_orders.restaurantID IN (SELECT restaurantID FROM pbc_pbr_managers WHERE managerID=".get_current_user_id().")";
  }
  $results=$wpdb->get_results($q);
  if($results){
    foreach($results as $result){
      $select[$result->guid]=$result->searchTerm . " at " . $result->restaurantName;
    }
  }
  $ret.='
  <form method="get" action="'.home_url( add_query_arg( array(), $wp->request ) ).'">
  <div class="container-fluid" id="queryResults" style="padding-top:15px;" >
  <div class="row" style="padding-left:15px;">
    <div class="col">
    <label for="id">Please Choose an Action</label>
      '.$r->buildSelectBox(array("Options"=>$select,"Field"=>"id","Multiple"=>"","ID"=>"id","Change"=>"this.form.submit()")).'
    </div>
  </div>
  </div>
  </form>';
}

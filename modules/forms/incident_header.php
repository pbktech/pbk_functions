<?php
function pbk_form_incident_header($data=null){
  $restaurant=new Restaurant();
  if(isset($data) && is_array($data)){
    $return="
    <div class=\"container\">
      <div class=\"row\">
        <div class=\"col\">
          <img src='" . PBKF_URL . "/assets/images/PBK-Logo_Primary_Full-Color_doc.png' /><br><h2>INCIDENT REPORT</h2>
        </div>
      </div>
    </div>
    ";
    $new=0;
  }else{
    $new=1;
    $return="";
  }
$return.="
<div class=\"container\">
  <div class=\"row\">
    <div class=\"col\">
      <label for='reporterName'>Your Name</label>
      ";
      if($new==1){$return.=$restaurant->buildLoggedInName();}else{$return.="
        <input  class=\"form-control\" type='text' value='".$data['reporterName']."' id='reporterName' />";}
    $return.= "
      </div>
    <div class=\"col\">
      <label for='dateOfIncident'>Date of Incident</label>
      ";
      if(isset($data['startDate']) && $data['startDate']!=''){$value=date("m/d/Y",strtotime($data['startDate']));}else{$value="";}
    $return.= "
      <input class=\"form-control\" type=\"text\" id=\"dateOfIncident\" name=\"startDate\" value=\"".$value."\" required />
    </div>
    <div class=\"col\">
      <label for='time_picker'>Time of Incident:</label>
      ";
      if($new==1){$value="";}else{$value=date("g:i a",strtotime($data['timeOfIncident']));}
    $return.= "
      <input type='text' id='time_picker' name='timeOfIncident' id='time_picker' class='form-control' value=\"".$value."\" required />
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
      <label for='guest[Name]'>Complainant Name</label><br />
      ";
      if($new==1){$value="";}else{$value=$data['guest']['Name'];}
      $return.= "
      <input class=\"form-control\" type=\"text\" id=\"guest[Name]\" name=\"guest[Name]\" value=\"".$value."\" required />
    </div>
    <div class=\"col\">
      <label for='guest_Phone'>Complainant Phone</label><br />
      ";
      if($new==1){$value="";}else{$value=$data['guest']['Phone'];}
      $return.= "
      <input class=\"form-control\" type=\"text\" id=\"guest_Phone\" name=\"guest[Phone]\" value=\"".$value."\"/>
    </div>
    <div class=\"col\">
      <label for='gues_Email'>Complainant Email</label><br />
      ";
      if($new==1){$value="";}else{$value=$data['guest']['Email'];}
      $return.= "
      <input class=\"form-control\" type=\"text\" id=\"guest_Email\" name=\"guest[Email]\" value=\"".$value."\"/>
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
    <label for='restaurantID'>Restaurant</label>
    ";
    if($new==1){$return.=$restaurant->buildRestaurantSelector();}else{$return.="
      <br><input  class=\"form-control\" type='text' value='".$restaurant->getRestaurantName($data['restaurantID'])."' id='restaurantID' name='restaurantID' />";}
  $return.= "
    </div>
    <div class=\"col\">
    ";
    if($new==1){
    $return.= "
      <label for='incidentType'>Type of Incident</label>
      <select class=\"custom-select\" name='incidentType' id='incidentType' required>
        <option value='choose'>Choose One</option>";
        foreach($restaurant->incidentTypes as $incidentID=>$incidentData){
          $return.= "<option value='".$incidentID."'>".$incidentData['Name']."</option>";
        }
        $return.= "
      </select>
      <div id='hiddenIncidentType' ></div>
      ";
    }
      $return.= "
    </div>
  </div>
</div>
";
return $return;
}

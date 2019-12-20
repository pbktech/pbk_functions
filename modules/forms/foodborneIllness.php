<?php
function pbk_form_foodborneIllness($data=null){
$return="
<div class=\"container\">
  <div class=\"row\">
    <div class=\"col\">
      <label for='checkNumber'>Guest Check Number</label>";
  if(isset($data['guestCheck']) && $data['guestCheck']!=''){$value=$data['guestCheck'];}else{$value='';}
  $return.=    "
      <input type='text' id='checkNumber' name='reportInfo[foodborneIllness][guestCheck]' class='form-control' value='$value'/>
    </div>
    <div class=\"col\">
    </div>
    <div class=\"col\">
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
      <label for='conclusions'>Do not give the guest information or draw conclusions. It's very important that a member of the Store Support Center works through the complaint first.</label>
      <div class=\"input-group\">
        <div class=\"input-group-prepend\">
          <div class=\"input-group-text\">";
      if(isset($data['conclusions']) && $data['conclusions']!=''){
          $return.=    "<input class=\"form-control\" type=\"text\" id=\"guest[Name]\" name=\"guest[Name]\" value=\"CONFIRMED\"/>";
      }else{
      $return.=    "
            <input type='checkbox' id='conclusions' name='reportInfo[foodborneIllness][conclusions]'/> <label id='conclusions_label' for='conclusions'>Click to Confirm</label>";
      }
      $return.=      "
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
      <label for='contacted'>Say to the guest: \"Thank you so much for the information. A member of the Store Support Center will contact you within 24 hours to investigate.\" Provide the guest with your name and phone number and encourage them to reach out in the off chance that they are not contacted in 24 hours.</label>
      <div class=\"input-group\">
        <div class=\"input-group-prepend\">
          <div class=\"input-group-text\">";
    if(isset($data['contacted']) && $data['contacted']!=''){
        $return.=    "<input class=\"form-control\" type=\"text\" id=\"guest[Name]\" name=\"guest[Name]\" value=\"CONFIRMED\"/>";
    }else{
    $return.=    "
            <input type='checkbox' id='contacted' name='reportInfo[foodborneIllness][contacted]'/> <label id='contacted_label' for='contacted'>Click to Confirm</label>";
    }
    $return.=      "
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
      <label for='summary'>After ending your conversation with the guest, please write a brief description of the complaint. Do not ask for information the guest did not volunteer.</label>";
  if(isset($data['summary']) && $data['summary']!=''){
    $return.=    "<div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='summary' name='reportInfo[foodborneIllness][summary]' class='form-control'/>".$data['summary']."</textarea></div>";
  }else{
    $return.=    "<textarea id='fbi_summary' name='reportInfo[foodborneIllness][summary]' class='form-control'/></textarea>";
  }
  $return.=    "
    </div>
  </div>
</div>";
  return $return;
}

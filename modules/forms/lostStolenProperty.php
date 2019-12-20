<?php
function pbk_form_lostStolenProperty($data=null){
$return="
<div class=\"container\">
  <div class=\"row\">
    <div class=\"col\">
      <label for='isStolen'>Do you believe the item is lost, stolen or unsure?</label>
      ";
    if(isset($data['isStolen']) && $data['isStolen']!=''){
    $return.= "
    <input  class=\"form-control\" type='text' value='".$data['isStolen']."' id='isStolen' name='isStolen' />";
    }else{
      $return.=    "
      <select class=\"custom-select\" name='reportInfo[lostStolenProperty][isStolen]' id='isStolen'>
        <option value='choose'>Choose One</option>
        <option value='Lost'>Lost</option>
        <option value='Stolen'>Stolen</option>
        <option value='Unsure'>Unsure</option>
        <option value='Other'>Other -- Explain Below</option>
      </select>";
    }
    $return.="
    </div>
    <div class=\"col\">
        <label for='itemValue'>What is the approximate value of the item?</label>";
        if(isset($data['itemValue']) && $data['itemValue']!=''){$value=$data['itemValue'];}else{$value='';}
  $return.="
        <input type='text' id='itemValue' name='reportInfo[lostStolenProperty][itemValue]' class='form-control' value=\"".$value."\"/>
      </div>
    </div>
    <div class=\"row\">
      <div class=\"col\">
        <label for='property_summary'>Please describe any activity or persons you witnessed in detail.</label>";
        if(isset($data['summary']) && $data['summary']!=''){$value=$data['summary'];}else{$value='';}
  $return.="
        <div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='property_summary' name='reportInfo[lostStolenProperty][summary]' class='form-control'/>".$value."</textarea></div>
      </div>
    </div>
    <div class=\"row\">
      <div class=\"col\">
        <label for='property_witness'>Please list any and all witnesses in detail.</label>";
        if(isset($data['witness']) && $data['witness']!=''){$value=$data['witness'];}else{$value='';}
  $return.="
        <div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='property_witness' name='reportInfo[lostStolenProperty][witness]' class='form-control'/>".$value."</textarea></div>
      </div>
    </div>
</div>
    ";
return $return;
}

<?php
function pbk_form_injury($data=null){
  if(isset($data) && is_array($data)){
    $return="";
    $new=0;
  }else{
    $new=1;
    $return="<script>
    jQuery(document).ready(function() {
      jQuery('.multipleSelect').select2({
      	theme: \"classic\"
    	});
      jQuery(\".multipleSelect\").change(function () {
        var elementVal=jQuery(this).val();
        if(elementVal==\"Other\"){
          jQuery(\"#\" + jQuery(this).attr('id') + \"Other\").show();
        }
      });
    });
    </script>
";
  }
$return.="
<div class=\"container\">
  <div class=\"row\">
    <div class=\"col\">
      <label for='injuryType'>What type of ingury injured?</label>
      ";
      if($new==0){
      $return.="
        <input  class=\"form-control\" type='text' value='".implode(", ",$data['injuryType'])."' id='injuryType' />";
        if(isset($data['injuryTypeOther']) && $data['injuryTypeOther']!=""){
          $return.="
          <div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='injuryTypeOther' class='form-control'/>".$data['injuryTypeOther']."</textarea></div>
            ";
        }
      }else{
        $return.= "
      <select class=\"custom-select multipleSelect\" style='width:100%;' name='reportInfo[injury][injuryType][]' id='injuryType' multiple>
        <option value='choose'>Please Choose</option>
        <option value='Cut'>Cut</option>
        <option value='Burn'>Burn</option>
        <option value='Fall'>Fall</option>
        <option value='Head'>Head</option>
        <option value='Back'>Back</option>
        <option value='Other'>Other -- Explain Below</option>
      </select>
      ";
      }
        $return.= "
      <div id='injuryTypeOther' style=\"display: none;\">
        <textarea name='reportInfo[injury][injuryTypeOther]' placeholder='Please Explain'  class='form-control' ></textarea>
      </div>
    </div>
    <div class=\"col\">
      <label for='bodyPart'>What part of the body was injured?</label>
      ";
      if($new==0){
      $return.="
        <input  class=\"form-control\" type='text' value='".implode(", ",$data['bodyPart'])."' id='bodyPart' />
        ";
        if(isset($data['bodyPartOther']) && $data['bodyPartOther']!=""){
          $return.="
          <div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='bodyPartOther' class='form-control'/>".$data['bodyPartOther']."</textarea></div>
            ";
        }
      }else{
        $return.= "
      <select class=\"custom-select multipleSelect\" style='width:100%;' name='reportInfo[injury][bodyPart][]' id='bodyPart' multiple>
        <option value='choose'>Please Choose</option>
        <option value='Head'>Head</option>
        <option value='Neck'>Neck</option>
        <option value='Back'>Back</option>
        <option value='Torso'>Torso</option>
        <option value='Arm'>Arm</option>
        <option value='Hand'>Hand</option>
        <option value='Leg'>Leg</option>
        <option value='Foot'>Foot</option>
        <option value='Other'>Other -- Explain Below</option>
      </select>
      ";
      }
        $return.= "
      <div id='bodyPartOther' style=\"display: none;\">
        <textarea name='reportInfo[injury][bodyPartOther]' placeholder='Please Explain'  class='form-control' ></textarea>
      </div>
    </div>
    <div class=\"col\">
      <label for='bodySide'>What side of the body was injured?</label>
      ";
      if($new==0){
      $return.="
        <input  class=\"form-control\" type='text' value='".implode(", ",$data['bodySide'])."' id='bodyPart' />
        ";
        if(isset($data['bodySideOther']) && $data['bodySideOther']!=""){
          $return.="
          <div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='bodySideOther' class='form-control'/>".$data['bodySideOther']."</textarea></div>
            ";
        }
      }else{
        $return.= "
      <select class=\"custom-select multipleSelect\" style='width:100%;' name='reportInfo[injury][bodySide][]' id='bodySide' multiple>
        <option value='choose'>Please Choose</option>
        <option value='Left'>Left</option>
        <option value='Right'>Right</option>
        <option value='Front'>Front</option>
        <option value='Back'>Back</option>
        <option value='Other'>Other -- Explain Below</option>
      </select>
      ";
      }
        $return.= "
      <div id='bodySideOther' style=\"display: none;\">
        <textarea name='reportInfo[injury][bodySideOther]' placeholder='Please Explain'  class='form-control' ></textarea>
      </div>
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
      <label for='medicalRequired'>Did they require medical attention?</label>
      <div class=\"input-group\">
        <div class=\"input-group-prepend\">
        ";
        if($new==0){
        $return.="
          <input  class=\"form-control\" type='text' value='".$data['medicalRequired']."' id='medicalRequired_label' />
          ";
        }else{
          $return.= "
          <div class=\"input-group-text\">
            <input type=\"radio\" aria-label=\"Yes\" value='Yes' id='medicalRequired' name='reportInfo[injury][medicalRequired]'> Yes
          </div>
          <div class=\"input-group-text\">
            <input type=\"radio\" aria-label=\"No\" value='No' id='medicalRequired' name='reportInfo[injury][medicalRequired]'> No
          </div>
          ";
          }
      $return.= "
        </div>
      </div>
    </div>
    <div class=\"col\">
      <label for='emergencyCalled'>Did you call 911?</label>
      <div class=\"input-group\">
        <div class=\"input-group-prepend\">
        ";
        if($new==0){
        $return.="
          <input  class=\"form-control\" type='text' value='".$data['emergencyCalled']."' id='emergencyCalled' />
          ";
        }else{
          $return.= "
          <div class=\"input-group-text\">
            <input type=\"radio\" aria-label=\"Yes\"  value='Yes' id='emergencyCalled' name='reportInfo[injury][emergencyCalled]'> Yes
          </div>
          <div class=\"input-group-text\">
            <input type=\"radio\" aria-label=\"No\" value='No' id='emergencyCalled' name='reportInfo[injury][emergencyCalled]'> No
          </div>
          ";
          }
      $return.= "
        </div>
      </div>
    </div>
    <div class=\"col\">
      <label for='isEmployee'>Is the injured a PBK Employee?</label>
      <div class=\"input-group\">
        <div class=\"input-group-prepend\">
        ";
        if($new==0){
        $return.="
          <input  class=\"form-control\" type='text' value='".$data['isEmployee']."' id='isEmployee' />
          ";
        }else{
          $return.= "
          <div class=\"input-group-text\">
            <input type=\"radio\" aria-label=\"Yes\" value='Yes' id='isEmployee' name='reportInfo[injury][isEmployee]'> Yes
          </div>
          <div class=\"input-group-text\">
            <input type=\"radio\" aria-label=\"No\" value='No' id='isEmployee' name='reportInfo[injury][isEmployee]'> No
          </div>
          ";
          }
      $return.= "
        </div>
      </div>
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
      <label for='injury_summary'>Please describe the circumstances that lead to the injury in detail.</label>
      ";
      if($new==0){
      $return.="
        <div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='bodySideOther' class='form-control'/>".$data['summary']."</textarea></div>
        ";
      }else{
        $return.= "
      <textarea id='injury_summary' name='reportInfo[injury][summary]' class='form-control'/></textarea>
      ";
      }
  $return.= "
    </div>
  </div>
  <div class=\"row\">
    <div class=\"col\">
      <label for='injury_witness'>Please list any and all witnesses in detail.</label>
      ";
      if($new==0){
      $return.="
      <div class=\"container-fluid\" style='border:solid 1px #000000;'><textarea id='bodySideOther' class='form-control'/>".$data['witness']."</textarea></div>
        ";
      }else{
        $return.= "
      <textarea id='injury_witness' name='reportInfo[injury][witness]'  placeholder='If there were no witnesses, please indicate by entering \"None\".' class='form-control'/></textarea>
      ";
      }
  $return.= "
    </div>
  </div>
</div>
";
return $return;
}

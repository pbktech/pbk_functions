<?php
global $wp;
global $wpdb;
global $ret;
$aboveStore=pbk_check_privledge();
$cu = wp_get_current_user();
$page = home_url( add_query_arg( array(), $wp->request ) );
$restaurant=new Restaurant();
if($_SERVER['REQUEST_METHOD'] == 'POST'){

}
$ret.="

<script>
jQuery(document).ready(function() {
  jQuery('#dateOfIncident').datepicker({
    dateFormat : 'dd-mm-yy'
  });
  jQuery(\"#submit\").prop(\"disabled\", true);
  jQuery(\"#incidentType\").change(function () {
    var elementToChange=jQuery(\"#incidentType\").val();
    jQuery(\"#foodborneIllness\").hide();
    jQuery(\"#injury\").hide();
    jQuery(\"#lostStolenProperty\").hide();
    jQuery(\"#choose\").hide();
    jQuery(\"#\" + elementToChange).show();
    jQuery(\"#incidentType\").prop(\"disabled\", true);
    jQuery(\"#submit\").prop(\"disabled\", false);
  });
  jQuery(\"#restaurantID\").select2({
  	theme: \"classic\"
	});
});
</script>
<form>
  <div class=\"form-group\">
    <div class=\"container\">
      <div class=\"row\">
        <div class=\"col\">
          <label for='dateOfIncident'>Date of Incident</label>
          <input class=\"form-control\" type=\"text\" id=\"dateOfIncident\" name=\"startDate\" value=\"\"/>
        </div>
        <div class=\"col\">
          <label for='reporterName'>Your Name</label>
            ".$restaurant->buildLoggedInName()."
        </div>
        <div class=\"col\">
          <label for='restaurantID'>Restaurant</label>
          ".$restaurant->buildRestaurantSelector()."
        </div>
      </div>
      <div class=\"row\">
        <div class=\"col\">
          <label for='guest[Name]'>Complaintant Name</label><br />
          <input class=\"form-control\" type=\"text\" id=\"guest[Name]\" name=\"guest[Name]\" value=\"\" required />
        </div>
        <div class=\"col\">
          <label for='guest[Phone]'>Complaintant Phone</label><br />
          <input class=\"form-control\" type=\"text\" id=\"guest[Phone]\" name=\"guest[Phone]\" value=\"\"/>
        </div>
        <div class=\"col\">
          <label for='guest[Email]'>Complaintant Email</label><br />
          <input class=\"form-control\" type=\"text\" id=\"guest[Email]\" name=\"guest[Email]\" value=\"\"/>
        </div>
      </div>
      <div class=\"row\">
        <div class=\"col\">
          <label for='incidentType'>Type of Incident</label>
          <select class=\"custom-select\" name='incidentType' id='incidentType' required>
            <option value='choose'>Choose One</option>
            <option value='foodborneIllness'>Foodborne Illness</option>
            <option value='injury'>Injury</option>
            <option value='lostStolenProperty'>Lost or Stolen Property</option>
          </select>
        </div>
      </div>
    </div>
  </div>
  <div class='alert' id='choose' >Please Select an Incident Type</div>
  <div class=\"form-group\" id='foodborneIllness' style=\"display: none;\">
    foodborneIllness
  </div>
  <div class=\"form-group\" id='injury' style=\"display: none;\">
    injury
  </div>
  <div class=\"form-group\" id='lostStolenProperty' style=\"display: none;\">
    lostStolenProperty
  </div>
  <div class=\"form-group\" id=''>
    <input type='submit' id='submit' value='Save Incident Report' />
  </div>
</form>
";

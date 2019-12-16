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
  jQuery(\"#incidentType\").change(function () {
    jQuery.ajax({
      url: example_ajax_obj.ajaxurl,
      data: {
        'action': 'pbk_load_form_elements',
        'incidentType': jQuery(\"#incidentType\").val())
      },
      success:function(data) {
          
          console.log(data);
      },
      error: function(errorThrown){
          console.log(errorThrown);
      }
    });
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
            <option value=''>Choose One</option>
            <option value='foodborneIllness'>Foodborne Illness</option>
            <option value='injury'>Injury</option>
            <option value='lostStolenProperty'>Lost or Stolen Property</option>
          </select>
        </div>
      </div>
    </div>
  </div>
  <div class=\"form-group\" id='formData'>

  </div>
</form>
";

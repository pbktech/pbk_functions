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

}
$ret.="
<script>
jQuery(document).ready(function() {
  jQuery('#dateOfIncident').datepicker({
    dateFormat : 'dd-mm-yy'
  });
});
</script>

<div class=\"container\">
  <div class=\"row\">
    <div class=\"col\">
      <label for='dateOfIncident'>Date of Incident</label><br />
      <input type=\"text\" id=\"dateOfIncident\" name=\"startDate\" value=\"\"/>
    </div>
    <div class=\"col\">
      <label for='reporterName'>Your Name</label><br />
      <input type=\"text\" id=\"reporterName\" name=\"reporterName\" value=\"\"/>
    </div>
    <div class=\"col\">
      <label for='restaurantID'>Restaurant</label><br />
      <input type=\"text\" id=\"restaurantID\" name=\"restaurantID\" value=\"\"/>
    </div>
  </div>
</div>
";

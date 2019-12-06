<?php
global $wp;
global $wpdb;
$page = home_url( add_query_arg( array(), $wp->request ) );
$toast = new ToastReport();
$rests=$toast->getAvailableRestaurants();
if(isset($_GET['startDate']) && isset($_GET['rid'])) {
	$toast->setStartTime($_REQUEST['startDate']);
  $toast->setRestaurantID($_REQUEST['rid']);
	$result=$toast->getFundraisingResults();
	$ret.="<div><h4>Total Sales: ".money_format('%.2n', $result->Amount)."</h4></div>";
}
$ret.="
<script type=\"text/javascript\">

jQuery(document).ready(function() {
jQuery('#startDate').datepicker({
    dateFormat : 'dd-mm-yy'
});
});

</script>
<div>
  <form method='get' action='$page' >";
	$ret.="
	<h4>Please Select a Restaurant</h4>
	<div>
		<select name='rid'>
			<option value=''>Choose One</option>
			";
			foreach($rests as $r){
				$checked="";
				if(isset($_REQUEST['rid']) && $_REQUEST['rid']==$r->restaurantID) {$checked="selected";}
				$ret.="
			<option value='".$r->restaurantID."' $checked>".$r->restaurantName."</option>";
			}
			$ret.="
		</select>
	</div>";
	 $ret.="
    <h4>Please choose a date</h4>
    <div>
      <label for='startDate'>Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\"/>
    </div>
    <div>
      <input type='submit' value='SEARCH' />
    </div>
  </form>
</div>


";

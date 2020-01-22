<?php
global $wpdb;
global $wp;
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$toast = new Toast();
}
$r=new Restaurant;
foreach ($r->myRestaurants as $restaurantID => $restaurantName) {
  $results=$wpdb->get_results("SELECT company,idpbc_minibar FROM pbc2.pbc_ToastOrderHeaders,pbc_minibar WHERE pbc_ToastOrderHeaders.diningOption=pbc_minibar.outpostIdentifier AND businessDate='".date("Y-m-d")."' AND pbc_ToastOrderHeaders.restaurantID='".$restaurantID."'
  AND idpbc_minibar NOT IN (SELECT outpostID FROM pbc_minibar_deliveries WHERE restaurantID='".$restaurantID."' AND deliveryDate='".date("Y-m-d")."') GROUP BY company,idpbc_minibar");
  //Added for testing
  $results = (object) array("r"=>array("company"=>"Bain & Co Chicago","idpbc_minibar"=>1));
  $ret.="
  <script>
  jQuery(document).ready(function($){
    cf = jQuery('.phone');
    cf.blur(function(e){
        phone = $(this).val();
        phone = phone.replace(/[^0-9]/g,'');
        if (phone.length != 10)
        {
            alert('Phone number must be 10 digits.');
            jQuery('.phone').val('');
            jQuery('.phone').focus();
        }
    });
});
</script>";
  if($results){
    $ret.="<div class='container'><h3>".$restaurantName."</h3>
    <form method='post' action='".home_url( add_query_arg( array(), $wp->request ) )."' id='' >
      ";
      foreach($results as $result){
        $ret.="
        <div class=\"form-group\">
          <label for='PBK".$restaurantID."-".$result->idpbc_minibar."'>".$result->company."</label><input type='text' class='form-control phone' name='phone[".$restaurantID."][".$result->idpbc_minibar."]' id='PBK".$restaurantID."-".$result->idpbc_minibar."' placeholder='Phone Number' />
        </div>";
      }
    $ret.="
      <div class=\"form-group\">
        <button type=\"submit\" class=\"btn btn-primary\"/>Submit</button>
      </div>
    </form></div>";
  }else {
    $ret.="<div class='alert alert-warning'>There were no orders for ".$restaurantName.".</div>";
  }
}

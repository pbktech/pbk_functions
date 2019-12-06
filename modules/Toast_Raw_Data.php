<?php
$page = home_url( add_query_arg( array(), $wp->request ) );
require("/var/www/html/c2.theproteinbar.com/wp-content/plugins/pbr_finance/includes/ToastFunctions/classes/Toast.php");
$ret.="\n
<div>
  <form method='get' action='".$page."'  name='restaurantSelector'>
    <select name='rid' ><option value=''>Choose a Restaurant</option>";
foreach($rests as $r){
  $ret.="\n<option value='".$r->restaurantID."'>".$r->restaurantName."</option>";
}
$ret.="</select></form></div>";

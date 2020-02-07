<?php
global $wpdb;
$toast = new ToastReport();
$r=new Restaurant;
if(isset($_GET['restaurants']) && isset($_GET['startDate']) && isset($_GET['endDate']) ){
  $orderDetails=array();
  $startDate=date("Y-m-d",strtotime($_GET['startDate']));
  $endDate=date("Y-m-d",strtotime($_GET['endDate']));
  $fileName="MiniBar_guest_export";
  $report=new ToastReport;
  $fileHeader=array("Restaurant","MiniBar","Date","Order Number","Guest Name","Guest Email","Item");
  if(file_exists($report->docSaveLocation.$fileName.date("Ymd").'.csv')) {unlink($report->docSaveLocation.$fileName.date("Ymd").'.csv');}
  $file = fopen($report->docSaveLocation.$fileName.date("Ymd").'.csv', 'w');
  fputcsv($file,$fileHeader);
  foreach($_GET['restaurants'] as $restaurant){
    $r->restaurantID=$restaurant;
    $restaurantName=$r->getRestaurantField("restaurantName");
    $toast = new Toast($r->getRestaurantField("GUID"));
    $stmt = $report->mysqli->prepare("SELECT GUID,businessDate,company FROM pbc2.pbc_ToastOrderHeaders,pbc2.pbc_minibar
    WHERE pbc_ToastOrderHeaders.diningOption=pbc2.pbc_minibar.outpostIdentifier
    AND pbc_ToastOrderHeaders.restaurantID=? AND businessDate BETWEEN ? AND ?");
    $stmt->bind_param('sss',$restaurant,$startDate,$endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while($order=$result->fetch_object()){
      $json=$toast->getOrderInfo($order->GUID);
      foreach($json->checks as $c){
        foreach($c->selections as $s){
          $orderDetails[]=array(
            $restaurantName,
            date("m/d/Y",strtotime($order->businessDate)),
          $order->company,
          $c->displayNumber,
          $c->customer->firstName . " " . $c->customer->lastName,
          $c->customer->email,
          $s->displayName);
          fputcsv($file,$orderDetails);
        }
      }
    }
  }
  if(count($orderDetails)==0){
    $ret.="<div class='alert alert-warning'>There were not any records found.</div>";
  }else {
    $ret.="
<script>
jQuery(document).ready( function () {
    jQuery('#myTable').DataTable();
} );
</script>
<div id='queryResults'>
	<table id='myTable' class=\"table table-striped table-hover\" style='width:100%;'><thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
    <tr>
    <th>".implode("</th>
    <th>",$fileHeader)."</th>
    </tr>
  </thead>";
    foreach ($orderDetails as $row) {
      $ret.="<tr><td>".implode("</td>
      <td>",$row)."</td></tr>";
    }
    $ret.="
    </table>";
    if(file_exists($report->docSaveLocation.$fileName.date("Ymd").'.csv')) {
			$ret.="<div>
      <button type=\"button\" class='btn btn-warning' onclick=\"javascript:window.open='".$report->docSaveLocation.$fileName.date("Ymd").".csv'>Download the file</button> This download is only valid for 30 minutes.</div>";
		}
    "
$ret.=  </div>";
  }
}
$ret.="
<script>
jQuery(document).ready(function() {
  jQuery('.datePicker').datepicker({
    dateFormat : 'mm/dd/yy'
  });
  jQuery('#restaurantPicker').select2({
    allowClear: true,
    theme: \"classic\"
  });
});
</script>
<div class=''>
  <form method='get' action='".get_permalink()."' >
    <div class='form-group'>
      <label for='restaurantPicker'>Please Select Your Restaurants</label>
      <select style='width:100%;' class=\"custom-select multipleSelect\" id=\"restaurantPicker\" name=\"restaurants[]\" multiple=\"multiple\">
        ";
        $rests=$wpdb->get_results("SELECT restaurantName,restaurantID FROM pbc2.pbc_pbrestaurants WHERE restaurantID IN (SELECT restaurantID from pbc2.pbc_minibar)");
        foreach($rests as $rs){
          $ret.="
        <option value='".$rs->restaurantID."' >".$rs->restaurantName."</option>";
        }
        $ret.="
        </select>
    </div>
    <label>Please choose a date range</label>
    <div class='form-group'>
      <label for='startDate'>Start Date</label>
      <input type=\"text\" id=\"startDate\" name=\"startDate\" class='form-control datePicker' value=\"\"/><br />
      <label for='endDate'>End Date</label>
      <input type=\"text\" id=\"endDate\" name=\"endDate\" class='form-control datePicker' value=\"\"/>
    </div>
    <div class='form-group'>
      <input type='submit' value='SEARCH' />
    </div>
  </form>
</div>
";
$ret.=$r->pbk_form_processing();

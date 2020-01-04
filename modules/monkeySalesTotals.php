<?php
if(isset($_REQUEST['startDate'])){$startDate=$_REQUEST['startDate'];}else{$startDate="";}
if(isset($_REQUEST['endDate'])){$endDate=$_REQUEST['endDate'];}else{$endDate="";}
if(isset($_REQUEST['items'])){$selectedRestaurants=implode(", ",$_REQUEST['items']);}
$report=new ToastReport;
$items=$report->getMonkeyActiveItems();
$itemSelector="<select id='itemSelector' name='items[]' class='js-example-basic-single js-states form-control' multiple>
";
foreach($items as $category=>$groups){
  $itemSelector.="\n<optgroup label='".$category."'></optgroup>";
  foreach($groups as $group=>$item){
    $itemSelector.="\n<optgroup label='".$group."'>";
    foreach ($item as $id => $name) {
      $itemSelector.="\n<option value='" . $id . "'>" . $name . "</option>";
    }
    $itemSelector.="\n</optgroup>";
  }
//  $itemSelector.="\n</optgroup>";
}
$itemSelector.="\n</select>";
$ret.="
<script type=\"text/javascript\">
jQuery(document).ready(function() {
  jQuery('#startDate').datepicker({
    dateFormat : 'mm/dd/yy'
  });
  jQuery('#endDate').datepicker({
    dateFormat : 'mm/dd/yy'
  });
  jQuery('#itemSelector').select2({
		allowClear: true,
  	theme: \"classic\"
	});
  jQuery(\"#submit\").click(function(){
    window.scrollTo(0,0);
    jQuery(\"#queryResults\").hide();
    jQuery(\"#notFound\").hide();
    jQuery(\"#processingGif\").show();
  });";
  if(isset($selectedRestaurants)){$ret.="
		jQuery('#itemSelector').val([".$selectedRestaurants."]);
		jQuery(\"#itemSelector\").trigger(\"change\");";
  }
$ret.=  "
});
</script>
<div id='dateSearch'>
  <form method='get' action='". get_permalink()."' >
    <h4>Please choose a date range</h4>
    <div class=\"form-group\">
      <label for='startDate'>Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"".$startDate."\"/><br />
      <label for='endDate'>End Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"".$endDate."\"/>
    </div>
    <div class=\"form-group\">
      <label for='itemSelector'>Choose your Items</label> ".$itemSelector."
    </div>
    <div class=\"form-group\">
      <input id='submit' type='submit' value='SEARCH' />
    </div>
  </form>
</div>
<div id='processingGif' style=\"display: none;text-align:center;\"><img src='" . PBKF_URL . "/assets/images/processing.gif' style='height:92px;width:92px;' /></div>
";
if(isset($_GET['endDate']) && isset($_GET['startDate']) && isset($_GET['items'])) {
  global $wpdb;
  $results=$report->getMonkeySalesFromItems($_GET);
  if(isset($results) && count($results)!=0){
    $filename= 'monkey_sales_'.date("m-d-Y",strtotime($_REQUEST['startDate'])).'_'.date("m-d-Y",strtotime($_REQUEST['endDate'])).'.csv';
    $handle = fopen($report->docSaveLocation . $filename, 'w');
    $ret.="
<script>
jQuery(document).ready( function () {
jQuery('#myTable').DataTable();
} );
</script>
<div id='queryResults'>
  <table id='myTable' class=\"table table-striped table-hover\" style='width:100%;'>";
    $ret.="
    <thead style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
      <tr>
        <th>Restaurant</th>
        <th>Monkey Order Number</th>
        <th>Due Date</th>
        <th>Order Amount</th>
        <th>Client Name</th>
        <th>Entered By</th>
      </tr>
    </thead>
";
    foreach($results as $r){
      $restaurant=$wpdb->get_var( 'SELECT restaurantName FROM pbc_pbrestaurants WHERE mnkyID="'.$r->store_id.'"');
      $ret.="
      <tr>
        <td>" . $restaurant . "</td>
        <td><a href='https://www.mnkysoft.com/dev213/_private/main.cfm?app_action=order&action=detail&order_id=".$r->order_id."&from=edit&shownav=1' target='_blank'>" . $r->order_id . "</a></td>
        <td>" . date_format($r->date_reqd,"m/d/Y") . "</td>
        <td>" . $report->switchNegNumber(($r->subtotal-$r->discount),2) . "</td>
        <td>" . $r->client_name . "</td>
        <td>" . $r->entered_by . "</td>
      </tr>
      ";
      fputcsv($handle, array($restaurant,$r->order_id,date_format($r->date_reqd,"m/d/Y"),$report->switchNegNumber(($r->subtotal-$r->discount),2),$r->client_name,$r->entered_by));
    }
    $ret.="
    <tfoot style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
      <tr>
        <th>Restaurant</th>
        <th>Monkey Order Number</th>
        <th>Due Date</th>
        <th>Order Amount</th>
        <th>Client Name</th>
        <th>Entered By</th>
      </tr>
    </tfoot>
  </table>
</div>
";
fclose($handle);
if(file_exists($report->docSaveLocation . $filename)){
  $ret.="
  <div>
    <a href='" . $report->docDownloadLocation . $filename . "' target='_blank'>Download the file</a> This download is only valid for 30 minutes.
  </div>
  ";
}
  }else {
		$ret.="<div class='alert alert-warning' id='#notFound'>There were no orders found</div>";
	}
}

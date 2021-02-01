<?php
// $reports=[N][[Title][SQL][Column Headers (SQL))][File Name]]
if(isset($_REQUEST['startDate']) && isset($_REQUEST['endDate'])) {
    $startDate = date("Y-m-d", strtotime($_REQUEST['startDate']));
    $endDate = date("Y-m-d", strtotime($_REQUEST['endDate']));
}else{
    $startDate = '';
    $endDate = '';
}
    $reports[1] = array("Title" => "Ticket Times", "SQL" => "SELECT restaurantName as 'Name', date_format(sent_time,'%c/%e/%Y') as Weekday,time_format(sent_time,'%h %p') as Sales_Hour , SEC_TO_TIME(avg(duration)) as Average_Wait,
SEC_TO_TIME(MAX(duration)) as Max_Wait, COUNT(*) as Customer_Count FROM `kds_detail`,`pbc_pbrestaurants`
WHERE `kds_detail`.`restaurantID` = `pbc_pbrestaurants`.`restaurantID` AND sent_time BETWEEN '" . $startDate . "' AND '" . $endDate . " 23:59:59' GROUP BY `kds_detail`.restaurantID,date_format(sent_time,'%c%e%Y'),hour(sent_time) ORDER BY `kds_detail`.restaurantID,date_format(sent_time,'%c%e%Y'),hour(sent_time) ",
        "Headers" => array("Name", "Weekday", "Sales_Hour", "Average_Wait", "Max_Wait", "Customer_Count"), "FN" => "tt"
    );
    $reports[2] = array("Title" => "Tenders", "SQL" => "SELECT restaurantName as 'Name', BusinessDate as 'Date_of_Business', Tender, Count, Total FROM v_R_TendersFromArchive,pbc_pbrestaurants WHERE v_R_TendersFromArchive.restaurantID=pbc_pbrestaurants.restaurantID
AND BusinessDate BETWEEN '" . $startDate . "' AND '" . $endDate . "';",
        "Headers" => array("Name", "Date_of_Business", "Tender", "Count", "Total"), "FN" => "tenders"
    );


		$ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('.datePicker').datepicker({
        dateFormat : 'mm/dd/yy'
    });
});

</script>
		<div class='container'>
			<form method='get' class='needs-validation' action='".site_url()."/finance/finance-reports/multi_reports/' novalidate>
			    <div class=\"form-row\">
			        <div class=\"col-4\">
			        <label for='startDate'>Report</label>
					<select class='form-control' name='rpt' id='report' required>
						<option value=''>Choose One</option>
						";

foreach($reports as $r=>$n){
    $checked="";
    if(isset($_REQUEST['rpt']) && $_REQUEST['rpt']==$r) {$checked="selected";}
    $ret.="
						<option value='".$r."' $checked>".$n['Title']."</option>";
}
$ret.="
					</select>
					<div class=\"invalid-feedback\">Please select a report</div>
			        </div>
			        <div class=\"col-4\">
    					<label for='startDate'>Start Date</label>
    					<input class='form-control datePicker' type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\" required/>
    					<div class=\"invalid-feedback\">Please enter a starting date</div>
			        </div>
			        <div class=\"col-4\">
                        <label for='endDate'>End Date</label>
                        <input class='form-control datePicker' type=\"text\" id=\"endDate\" name=\"endDate\" value=\"\" required/>
					<div class=\"invalid-feedback\">Please enter an ending date</div>
			        </div>
                </div>
				<div class=\"form-row\">
					<input type='submit' value='SEARCH' />
				</div>
			</form>
		</div>
<div class=\"text-center\" id='spinner' style='display: none;'>
  <div class=\"spinner-border\" role=\"status\">
    <span class=\"sr-only\">Loading...</span>
  </div>
</div>
<script>
// Example starter JavaScript for disabling form submissions if there are invalid fields
(function() {
  'use strict';
  window.addEventListener('load', function() {
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');
    // Loop over them and prevent submission
    var validation = Array.prototype.filter.call(forms, function(form) {
      form.addEventListener('submit', function(event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }else{
          jQuery(\"#spinner\").show();
        }
        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
</script>

		";
if(isset($_REQUEST['endDate']) && isset($_REQUEST['startDate']) && (isset($_REQUEST['rpt']) && is_numeric($_REQUEST['rpt']))) {
	global $wpdb;
    $toast = new ToastReport();
	if($_REQUEST['rpt']==1) {
		$lastImport = $wpdb->get_var( "SELECT MAX(sent_time) FROM pbc2.kds_detail" );
		$ret.="<div >The last imported date is ".date("m/d/Y", strtotime($lastImport)).".</div>";
	}
	$result = $wpdb->get_results($reports[$_REQUEST['rpt']]['SQL']);
    if($result){
        $ret.="<hr /><h4>".$reports[$_REQUEST['rpt']]['Title']." : " . $_REQUEST['startDate'] . " - " . $_REQUEST['endDate'] . "</h4><br />";
        $fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
        $D['Options'][]="\"order\": [ 1, 'asc' ]";
        $D['Options'][]="\"lengthMenu\": [ [10, 20, -1], [10, 20, \"All\"] ]";
        $D['Headers']=$reports[$_REQUEST['rpt']]['Headers'];
        foreach ($result as $r) {
            if($_REQUEST['rpt']==1) {
                $D['Results'][]=array(
                    $r->Name,
                    $r->Weekday,
                    $r->Sales_Hour,
                    $r->Average_Wait,
                    $r->Max_Wait,
                    $r->Customer_Count
                );
            }
            if($_REQUEST['rpt']==2) {
                $D['Results'][]=array($r->Name, $r->Date_of_Business, $r->Tender, $r->Count, $r->Total);
            }
        }
        $ret.= $toast->showResultsTable($D);
    }else{
        echo '
        <div class="alert alert-warning" role="alert">
            There were no records found.
        </div>';
    }
}
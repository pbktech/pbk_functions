<?php
$toast = new ToastReport();
$page = home_url( add_query_arg( array(), $wp->request ) );
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$startDate=date('Y-m-d',strtotime($_GET['startDate']));
	$endDate=date('Y-m-d',strtotime($_GET['endDate']));
	$dataArray[]=array("Restaurant"=>"Restaurant","businessDate"=>"Date of Business","checkNumber"=>"Check Number","tabName"=>"Check Name",
	"discount"=>"Discount","discountAmount"=>"Amount Off","appliedPromoCode"=>"Card Name",	"displayName"=>"Item Name");
//	$toast ->setStartTime(date($startDate,strtotime($bot))." 00:00:00");
//	$toast ->setEndTime(date($endDate,strtotime($latest))." 23:59:59");
	$dis=0;
	for($i=strtotime($startDate);$i<=strtotime($endDate);$i+=86400) {
		$toast->setBusinessDate(date("Y-m-d",$i));
		$ret.="<div><h4>".date("m/d/Y",$i)."</h4>";
		foreach($_POST['restaurants'] as $r){
			$toast->setRestaurantID($r);
			$restaurantInfo=$toast->getRestaurantIinfo();
			$ret.="<div><h4>".$restaurantInfo->restaurantName."</h4>";
			foreach($_POST['discounts'] as $d){
				$discounts=$toast->getDiscountActivity($d);
				foreach($discounts as $discount){
					$items=$toast->getCheckItems($discount->ToastCheckID);
					$ret.="<div>Check Number: ".$discount->checkNumber." || Check Name: ".$discount->tabName." || Discount:".$discount->discount." || Amount Off:".$discount->discountAmount." || Card Name:".$toast->getPromoName($discount->appliedPromoCode);
					foreach($items as $item){
	$dataArray[]=array("Restaurant"=>$restaurantInfo->restaurantName,"businessDate"=>date("m/d/Y",$i),"checkNumber"=>$discount->checkNumber,"tabName"=>$discount->tabName,
	"discount"=>$discount->discount,"discountAmount"=>$discount->discountAmount,"appliedPromoCode"=>$toast->getPromoName($discount->appliedPromoCode),	"displayName"=>$item->displayName);
						$ret.="<p>".$item->displayName."</p>";
					}
					$ret.="</div>";
				}
				$dis+=count($discounts);
			}
			$ret.="</div>";
		}
		$ret.="</div>";
	}
	if($dlLink=$toast->buildSCV($dataArray,"discount-report_".$startDate."--".$endDate)) {echo "<p><a href='".$dlLink."' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";}
	$ret.="Number of Discounts: ".$dis;
}else {
		$ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
	jQuery('#startDate').datepicker({
			dateFormat : 'mm/dd/yy'
	});
	jQuery('#endDate').datepicker({
			dateFormat : 'mm/dd/yy'
	});
	jQuery('#restaurantPicker').select2({
		allowClear: true,
  	theme: \"classic\"
	});
	jQuery('#discountPicker').select2({
		allowClear: true,
  	theme: \"classic\"
	});
});

</script>
		<div class=''>
			<form method='POST' action='".get_permalink()."' >
				<div class='form-group'>
					<label for='restaurantPicker'>Please Select Your Restaurants</label>
					<select style='width:100%;' class=\"custom-select multipleSelect\" id=\"restaurantPicker\" name=\"restaurants[]\" multiple=\"multiple\">
						";
						$rests=$toast -> getAvailableRestaurants();
						foreach($rests as $r){
							$ret.="
						<option value='".$r->restaurantID."' >".$r->restaurantID."</option>";
						}
						$ret.="
						</select>
				</div>
				<div class='form-group'>
					<label for='discountPicker'>Please Select Your Discounts</label>
					<select style='width:100%;' class=\"custom-select multipleSelect\" id=\"discountPicker\" name=\"discounts[]\" multiple=\"multiple\">
								";
						$discounts=$toast -> getAvailableDiscounts();
						foreach($discounts as $d){
							$ret.="
						<option value='".$d->discount."'>".$d->discount."</option>";
						}
						$ret.="
						</select>
				</div>
				<strong>Please choose a date range</strong>
				<div class='form-group'>
					<label for='startDate'>Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\"/><br />
					<label for='endDate'>End Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"\"/>
				</div>
				<div class='form-group'>
					<input type='submit' value='SEARCH' />
				</div>
			</form>
		</div>
		";
}

<?php
$toast = new ToastReport();
$page = home_url( add_query_arg( array(), $wp->request ) );
if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$dataArray[]=array("Restaurant"=>"Restaurant","businessDate"=>"Date of Business","checkNumber"=>"Check Number","tabName"=>"Check Name",
	"discount"=>"Discount","discountAmount"=>"Amount Off","appliedPromoCode"=>"Card Name",	"displayName"=>"Item Name");
//	$toast ->setStartTime(date($_REQUEST['startDate'],strtotime($bot))." 00:00:00");
//	$toast ->setEndTime(date($_REQUEST['endDate'],strtotime($latest))." 23:59:59");
	$dis=0;
	for($i=strtotime($_REQUEST['startDate']);$i<=strtotime($_REQUEST['endDate']);$i+=86400) {
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
	if($dlLink=$toast->buildSCV($dataArray,"discount-report_".$_REQUEST['startDate']."--".$_REQUEST['endDate'])) {echo "<p><a href='".$dlLink."' target='_blank'>Download the file</a> This download is only valid for 30 minutes.</p>";}
	$ret.="Number of Discounts: ".$dis;
}else {
		$ret.="
		<script type=\"text/javascript\">

jQuery(document).ready(function() {
    jQuery('#startDate').datepicker({
        dateFormat : 'yy-mm-dd'
    });
    jQuery('#endDate').datepicker({
        dateFormat : 'yy-mm-dd'
    });
});

</script>
		<div>
			<form method='POST' action='".get_permalink()."' >
				<div>
					<label for='restaurants'>Please Select Your Restaurants</label><br />
						";
						$rests=$toast -> getAvailableRestaurants();
							$count=0;
						foreach($rests as $r){
							$ret.="
						<input type='checkbox' name='restaurants[]' value='".$r->restaurantID."' id='r".$r->restaurantID."' /><label for='r".$r->restaurantID."'>".$r->restaurantName."</label>";
						if(($count % 5)==4){$ret.="<br />";}
						$count++;
						}
						$ret.="
				</div>
				<div>
					<label for='discounts'>Please Select Your Discounts</label><br />
						";
						$count=0;
						$discounts=$toast -> getAvailableDiscounts();
						foreach($discounts as $d){
							$ret.="
						<input type='checkbox' name='discounts[]' value='".$d->discount."' id='d".$count."' ><label for='d".$count."'>".$d->discount."</label>";
						if(($count % 5)==4){$ret.="<br />";}
						$count++;
						}
						$ret.="
				</div>
				<strong>Please choose a date range</strong>
				<div>
					<label for='startDate'>Start Date</label><br /><input type=\"text\" id=\"startDate\" name=\"startDate\" value=\"\"/><br />
					<label for='endDate'>End Date</label><br /><input type=\"text\" id=\"endDate\" name=\"endDate\" value=\"\"/>
				</div>
				<div>
					<input type='submit' value='SEARCH' />
				</div>
			</form>
		</div>
		";
}

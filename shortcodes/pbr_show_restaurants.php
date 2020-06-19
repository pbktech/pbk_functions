<?php
function pbr_show_restaurants() {
	$return="\n<div style='overflow:auto;background-color:#FFFFFF;'>
	\n<table  class=\"table table-striped table-hover\" style=\"width: 100%;\" autosize=\"1\">\n
<thead  style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>
<tr>
	<th>RESTAURANT</th>
	<th>EMAIL</th>
	<th>PHONE</th>
	<th>ADDRESS</th>
	<th>GM/AGM</th>
	<th>AM</th>
</tr>
</thead>
<tbody>\n
";
	global $wpdb;
	$restaurants = $wpdb->get_results("SELECT * FROM pbc_pbrestaurants");
	foreach($restaurants as $restaurant){
		if($restaurant->isOpen==1) {
			$r = new Restaurant($restaurant->restaurantID);
	$return.="\n<tr>
<td><a title=\"Restaurant Hours\" href=\"restaurant-hours/#".$restaurant->restaurantCode."\">#".$restaurant->restaurantID." ".$restaurant->restaurantName."</a><br />" .date("m/d/Y",strtotime($restaurant->openingDate)) . "</td>
<td><a href=\"mailto:".$restaurant->email."\" target=\"_blank\">".str_replace("theproteinbar.com","", $restaurant->email)."</a></td>
<td><a href=\"tel:+1".str_replace(".", '', $restaurant->phone)."\">".$restaurant->phone."</a></td>
<td><a href=\"https://maps.google.com/maps?q=Protein+Bar+".str_replace(" ", "+", $restaurant->address1). "+" . $restaurant->city."+".$restaurant->state."+".$restaurant->zip."\" target='_blank'>" . $restaurant->address1 . "<br />". $restaurant->city.", ".$restaurant->state." ".$restaurant->zip."</a></td>
<td><a href=\"mailto:".$r->getManagerEmail("GM")."\" target=\"_blank\">" .$r->getManagerName("GM"). "</a><br /><a href=\"mailto:".$r->getManagerEmail("AGM")."\" target=\"_blank\">" .$r->getManagerName("AGM"). "</a></td>
<td><a href=\"mailto:".$r->getManagerEmail("AM")."\" target=\"_blank\">" .$r->getManagerName("AM"). "</a></td>
</tr>";
		}
	}
	$r=new Restaurant;
	$return.="</div></tbody></table>";
	$content['format']='A4-P';
	$content['title']="Restaurant Directory";
	$content['html']=$r->docHeader("Restaurant Directory").$return;
	if($file=$r->buildHTMLPDF(json_encode($content))){
		$return.="
		<script type=\"text/javascript\">
			jQuery(document).ready(function(){
				setTimeout(function(){
					jQuery(\"#downloadButton\").hide();
					jQuery(\"#expiredLink\").show();
				}, 1800000);
			});
		</script>
		<div class='container-fluid' id=''>
			<div class='row' id='downloadButton'>
				<div class='col'><a href='".$file['Link']."' target='_blank' class='btn btn-primary'>Printable PDF</a></div>
			</div>
			<div class='row' id='expiredLink' style='display:none;'>
				<div class='col'>Download link has expired. Please <a href=\"javascript:history.go(0)\">refresh</a> to regenerate.</div>
			</div>
		</div>";
	}
	return $return;
}

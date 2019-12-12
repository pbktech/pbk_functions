<?php
function pbr_show_restaurants() {

	$return="\n<div style='overflow:auto;background-color:#FFFFFF;'>
	\n<table >\n
	<thead>
<tr style=\"height: 65px;\">
<td style=\"text-align: center; color: #ffffff; background-color: #f36c21; width: 994.8px; height: 65px;\" colspan=\"5\">
<h3 style=\"color: #ffffff;\">PBK Restaurant Directory</h3>
</td>
</tr>
</thead>
<tbody>\n
	<tr  style=\"background-color: #0e2244; color: #ffffff; text-align: center;\">\n<td style=\"text-align: center;\">\n<h5>RESTAURANT</h5>\n</td>\n<td style=\"text-align: center;\">\n<h5>EMAIL</h5>\n</td>\n<td style=\"text-align: center;\">
<h5>PHONE</h5>\n</td>\n<td style=\"text-align: center;\">\n<h5>ADDRESS</h5>\n</td>\n<td style=\"text-align: center;\">\n<h5>GM/AGM</h5>\n</td>\n<td style=\"text-align: center;\">\n<h5>AM</h5>\n</td>\n</tr>";
	global $wpdb;
	$restaurants = $wpdb->get_results("SELECT * FROM pbc_pbrestaurants");
	foreach($restaurants as $restaurant){
		if($restaurant->isOpen==1) {
			$r = new Restaurant($restaurant->restaurantID);
	$return.="\n<tr>
<td style=\"text-align: center;\"><a title=\"Restaurant Hours\" href=\"restaurant-hours/#".$restaurant->restaurantCode."\">".$restaurant->restaurantName."</a><br />" .date("m/d/Y",strtotime($restaurant->openingDate)) . "</td>
<td style=\"text-align: center;\"><a href=\"mailto:".$restaurant->email."\" target=\"_blank\">".str_replace("theproteinbar.com","", $restaurant->email)."</a></td>
<td style=\"text-align: center;\"><a href=\"tel:+1".str_replace(".", '', $restaurant->phone)."\">".$restaurant->phone."</a></td>
<td style=\"text-align: center;\"><a href=\"https://maps.google.com/maps?q=Protein+Bar+".str_replace(" ", "+", $restaurant->address1). "+" . $restaurant->city."+".$restaurant->state."+".$restaurant->zip."\" target='_blank'>" . $restaurant->address1 . "<br />". $restaurant->city.", ".$restaurant->state." ".$restaurant->zip."</a></td>
<td style=\"text-align: center;\"><a href=\"mailto:".$r->getManagerEmail("GM")."\" target=\"_blank\">" .$r->getManagerName("GM"). "</a><br /><a href=\"mailto:".$r->getManagerEmail("AGM")."\" target=\"_blank\">" .$r->getManagerName("AGM"). "</a></td>
<td style=\"text-align: center;\"><a href=\"mailto:".$r->getManagerEmail("AM")."\" target=\"_blank\">" .$r->getManagerName("AM"). "</a></td>
</tr>";
		}
	}
	$return.="</div></tbody></table>";
	return $return;
}

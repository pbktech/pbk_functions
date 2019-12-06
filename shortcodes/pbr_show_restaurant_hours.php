<?php
function pbr_show_restaurant_hours() {
//	echo 'date_default_timezone_set: ' . date_default_timezone_get() . '<br />';
	$return="\n<div style=\"overflow:auto;\"><table style=\"width: 100%;\">\n<tbody>\n<tr>\n<td style=\"text-align: center;\">\n<h5>RESTAURANT</h5>\n</td>";
	for($ia=1419206400;$ia<=1419724800;$ia+=86400) {
		$return.="\n<td style=\"text-align: center;\">\n<h5>".date("l",$ia)."</h5>\n</td>";
	}
	global $wpdb;
	$restaurants = $wpdb->get_results("SELECT * FROM pbc_pbrestaurants");
	foreach($restaurants as $restaurant){
		if($restaurant->isOpen==1) {
			$res = new Restaurant($restaurant->restaurantID);
			$return.="\n<tr>\n<td style=\"text-align: center;\" id='".$restaurant->restaurantCode."'>".$restaurant->restaurantName."</td>";
			for($ia=1419206400;$ia<=1419724800;$ia+=86400) {
				$openName=$res->getHours(date("l",$ia).'open');
				$closeName=$res->getHours(date("l",$ia).'close');
				if((isset($openName) && $openName!=0) && (isset($closeName) && $closeName!=0) ) {
					$return.="\n<td style=\"text-align: center;\">".$openName."<br />-<br />".$closeName."</td>";
				}else {
					$return.="\n<td style=\"text-align: center;\"><br />CLOSED<br /></td>";
				}
			}
			$return.="</tr>";
		}
	}
	$return.="</tbody></table></div>";
	return $return;
}

<?php
function pbr_show_restaurant_hours() {
//	echo 'date_default_timezone_set: ' . date_default_timezone_get() . '<br />';
add_action( 'wp_enqueue_scripts', 'pbk_scripts' );
	$return="\n<div style=\"overflow:auto;\">
	<table  class=\"table table-striped table-hover\" style=\"width: 100%;\">\n
	<thead  style='background-color:#0e2244; color: #ffffff; text-align: center;font-weight:bold;'>\n<tr>\n<th style=\"text-align: center;width: 20%;\">Restaurant\n</th>";
	for($ia=1419206400;$ia<=1419724800;$ia+=86400) {
		$return.="\n<th style=\"text-align: center;\">\n".date("l",$ia)."\n</th>";
	}
	$return.="</thead><tbody>";
	global $wpdb;
	$restaurants = $wpdb->get_results("SELECT * FROM pbc_pbrestaurants");
	foreach($restaurants as $restaurant){
		if($restaurant->isOpen==1) {
			$res = new Restaurant($restaurant->restaurantID);
			$return.="\n<tr>\n<td valign='middle' style='font-weight: bold'  id='".$restaurant->restaurantCode."'>".$restaurant->restaurantName."</td>";
			for($ia=1419206400;$ia<=1419724800;$ia+=86400) {
				$openName=$res->getHours(date("l",$ia).'open');
				$closeName=$res->getHours(date("l",$ia).'close');
				if(date("l",$ia)==date("l")){$openClass="p-3 mb-2 bg-primary text-white";}else{$openClass="";}
				if((isset($openName) && $openName!=0) && (isset($closeName) && $closeName!=0) ) {
					$return.="\n<td class=\"p-3 mb-2 text-monospace\" class=\"".$openClass."\"style=\"text-align: center;\">".$openName."<br />-<br />".$closeName."</td>";
				}else {
					$return.="\n<td class=\"p-3 mb-2 text-danger\" style=\"text-align: center;font-weight:bold;\"><br />CLOSED<br /></td>";
				}
			}
			$return.="</tr>";
		}
	}
	$return.="</tbody></table></div>";
    $r=new Restaurant;
    $content['format']='A4-P';
    $content['title']="Restaurant Hours";
    $content['html']=$r->docHeader("Restaurant Hours").$return;
    if($file=$r->buildHTMLPDF(json_encode($content))) {
        $return .= "
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
				<div class='col'><a href='" . $file['Link'] . "' target='_blank' class='btn btn-primary'>Printable PDF</a></div>
			</div>
			<div class='row' id='expiredLink' style='display:none;'>
				<div class='col'>Download link has expired. Please <a href=\"javascript:history.go(0)\">refresh</a> to regenerate.</div>
			</div>
		</div>";
    }
	return $return;
}

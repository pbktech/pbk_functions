<?php
/*
*
*
*
*
*
*
*
*/

class Finance {

	private $fy=array(2014=>'2013-12-30', 2015=>'2014-12-29', 2016=>'2015-12-28');
	public	$plTabPosition=array("fr"=>5, "rn"=>6, "pt"=>7, "ww"=>8, "sl"=>9, "pq"=>10, "ob"=>11, "nw"=>12, "mm"=>13, "nk"=>14, "rs"=>15, "sb"=>16, "cl"=>17, "bd"=>18, "us"=>19, "cb"=>20, "sk"=>21, "wb"=>22, "16"=>23, "ma"=>24);
	public function daysSincePeriodBegan() {
		$now = time();
		$your_date = strtotime($this->fy[date('Y')]);
		$datediff = $now - $your_date;
		return floor($datediff/(60*60*24));
	}

	public function getCurrentPeriod() {
		$count=1;
		$days = $this->daysSincePeriodBegan();
		for($i=1;$i<=$days;$i+=28) {
			$count++;
			if ($count % 3 == 0) {$i+=7;}
		}
		return $count-1;
	}

	public function getMyRestaurant() {
		global $wpdb;
		global $current_user;
		get_currentuserinfo();
		$restaurants = $wpdb->get_results("SELECT restaurantID, restaurantCode FROM pbc_pbrestaurants WHERE pbc_pbrestaurants.restaurantID IN 
(SELECT restaurantID FROM pbconnect.pbc_pbr_managers WHERE pbc_pbr_managers.managerID='" . get_userdata($current_user->ID) . "')", OBJECT );
		return $restaurants;
	}

/*   
	public function __construct($restID=null) {
   }
   */
}
?>

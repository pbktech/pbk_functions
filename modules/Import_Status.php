<?php
global $wpdb;
$ts[]=array("T" => "chk_detail","Q" => "SELECT date(max(chk_open_date_time))  as MaxDate  FROM pbc2.chk_detail");
$ts[]=array("T" => "kds_detail","Q" => "SELECT date(MAX(sent_time)) as MaxDate FROM pbc2.kds_detail");
$ts[]=array("T" => "pbc_paymentDetails","Q" => "SELECT date(MAX(paidDate)) FROM pbc2.pbc_paymentDetails");
$ts[]=array("T" => "itemCheckHeaders","Q" => "SELECT MAX(dateOfBusiness) as MaxDate FROM pbc2.pbc_itemCheckHeaders");
$ts[]=array("T" => "kds_detail","Q" => "SELECT date(max(clk_in_date_tm)) as MaxDate FROM pbc2.time_card_dtl;");
$ts[]=array("T" => "v_R_TendersFromArchive","Q" => "SELECT date(max(BusinessDate)) FROM pbc2.v_R_TendersFromArchive");
		$ret.="
		<div>
			<table><tr><td><strong>Table</strong></td><td><strong>Last Import Date</strong></td></tr>
		";
		foreach($ts as $t){
			$lastImport = $wpdb->get_var( $t['Q']);
			$ret.="<tr><td>".$t['T']."</td><td>".$lastImport."</td></tr>";
		}
		$ret.="
			</table>
		</div>";
?>

<?php
function pbrf_showReportBuilder( $atts ) {
  $ret=null;
  $a = shortcode_atts( array(
        'report' => '',
    ), $atts );
    $directory = dirname(__DIR__) . '/modules';
    if($a['report']=='' || !isset($a['report'])) {
      $scanned_directory = array_diff(scandir($directory), array('..', '.'));
      foreach($scanned_directory as $file){
        $ret.="<p><a href='".site_url()."/finance/finance-reports/".str_replace(".php", "", $file)."' >".str_replace(".php", "", str_replace("_", " ", $file))."</a></p>";
      }
    }else {
      if(file_exists($directory . "/" . $a['report'])) {
        include $directory . "/" . $a['report'];
      }
    }
    return $ret;
}

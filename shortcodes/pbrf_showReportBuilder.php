<?php
function pbrf_showReportBuilder( $atts ) {
  add_action( 'wp_enqueue_scripts', 'pbk_scripts' );
  $ret=null;
  $a = shortcode_atts( array(
        'report' => '',
    ), $atts );
    $directory = dirname(__DIR__) . '/modules';
    if($a['report']=='' || !isset($a['report'])) {
      $r=new Restaurant;
      $pages=$r->pbk_get_children();
      if(count($pages)>0){
        return $r->pbk_array_nav($pages);
      }else {
        return "<div class='alert  alert-primary'>There are not any pages available.</div>";
      }
      /*
      $scanned_directory = array_diff(scandir($directory), array('..', '.'));
      foreach($scanned_directory as $file){
        $ret.="<p><a href='".site_url()."/finance/finance-reports/".str_replace(".php", "", $file)."' >".str_replace(".php", "", str_replace("_", " ", $file))."</a></p>";
      }
      */
    }else {
      if(file_exists($directory . "/" . $a['report'])) {
        include $directory . "/" . $a['report'];
      }
    }
    return $ret;
}

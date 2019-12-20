<?php
error_reporting(E_ALL);
/*
Plugin Name: Protein Bar & Kitchen Custom Functions
Description: This is a combination of multiple former plugins to be streamlined and work better.
Version: 2.0.0
Author: Jon Arbitman
*/
if (!function_exists("get_option")) {
  header('HTTP/1.0 403 Forbidden');
  die;
}
global $wp;
date_default_timezone_set('America/Chicago');
setlocale(LC_MONETARY, 'en_US');
error_reporting(E_ALL);
$latLong["Chicago"]=array("Lat"=>41.885858,"Long"=>-87.632561);
$latLong["District of Columbia"]=array("Lat"=>38.893481,"Long"=>-77.022022);
$latLong["Colorado"]=array("Lat"=>39.752327,"Long"=>-105.001158);
/*Checking Above Store*/
function pbk_check_privledge(){
  $aboveStore=0;
  $cu = wp_get_current_user();
  if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
    $aboveStore=1;
  }
  return $aboveStore;
}
define( "PBKF_URL", WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) );
add_action('init','pbk_check_privledge');
/*Scripts and CSS*/
function pbk_scripts(){
  wp_enqueue_style( 'select_style', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css');
  wp_enqueue_style( 'timepicker_style', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css');
  wp_enqueue_style( 'jquery-ui_style', PBKF_URL . '/assets/css/jquery-ui.min.css');
  wp_enqueue_style( 'bootstrap_style', 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css');
  wp_enqueue_script( 'select_script', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js');
  wp_enqueue_script( 'timepicker_script', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js');
  wp_enqueue_script( 'jquery-ui_script', PBKF_URL . '/assets/js/jquery-ui.min.js');
  wp_enqueue_script( 'popper_script', 'https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js');
  wp_enqueue_script( 'bootstrap_script', 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js');
}
add_action( 'wp_enqueue_scripts', 'pbk_scripts' );

/*Classes*/
use PHPMailer\PHPMailer\PHPMailer;
use Twilio\Rest\Client;
use Twilio\Twiml\MessagingResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
require __DIR__ . '/vendor/autoload.php';

include('classes/Finance.php');
include('classes/Toast.php');
include('classes/ToastReport.php');
include('classes/task_engine.php');
include('classes/Restaurant.php');

/*Shortcodes*/
include('shortcodes/pbrf_showToastFunctions.php');
include('shortcodes/pbrf_depositCalculator.php');
include('shortcodes/pbrf_showReportBuilder.php');
include('shortcodes/pbr_show_restaurants.php');
include('shortcodes/pbr_show_restaurant_hours.php');

/*Admin Pages*/
include('admin-page.php'); // the plugin options page HTML and save functions

add_shortcode( 'toast', 'pbrf_showToastFunctions' );
add_shortcode( 'show_deposit_calculator', 'pbrf_depositCalculator' );
add_shortcode( 'show_finance_report_builder', 'pbrf_showReportBuilder' );
add_shortcode( 'show_restaurants', 'pbr_show_restaurants' );
add_shortcode( 'show_restaurant_hours', 'pbr_show_restaurant_hours' );
function pbk_show_response($m){
  return "
      <div class='alert ".$m['class']."' id='pbk_message' >".$m['message']."</div>
      <script type=\"text/javascript\">
        jQuery(document).ready(function(){
          setTimeout(function(){
          jQuery(\"#pbk_message\").hide(\"20000\")
        }, 30000);
        });
      </script>";
}
function switchpbrMessages($m) {
	switch($m) {
		case 1: return "Restaurant updated."; break;
		case 2: return "There was an error. Restaurant not updated."; break;
	}
}
  if ( isset( $_GET['m'] ) )
  {
?>
   <div id='message' class='updated fade'><p><strong><?php echo switchpbrMessages($_GET['m']);?></strong></p></div>
<?php
  }

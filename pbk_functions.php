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
function pbk_load_wp_media_files() {
  wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'pbk_load_wp_media_files' );
/*Scripts and CSS*/
function pbk_scripts(){
  wp_enqueue_style( 'select_style', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css');
  wp_enqueue_style( 'timepicker_style', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css');
  wp_enqueue_style( 'jquery-ui_style', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
  wp_enqueue_style('bootstrap_style', 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css');
//  wp_enqueue_style( 'bootstrap_style',  PBKF_URL . '/assets/css/bootstrap-grid.css');
//  wp_enqueue_style( 'bootstrap_style',  PBKF_URL . '/assets/css/bootstrap.css');
  wp_enqueue_style( 'sort_tables_style', '//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css');
  wp_enqueue_style( 'screen_signature_style', PBKF_URL . '/assets/css/signature.css');
  wp_enqueue_script( 'select_script', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js');
  wp_enqueue_script( 'timepicker_script', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js');
  wp_enqueue_script( 'jquery-ui_script', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js');
  wp_enqueue_script( 'popper_script', 'https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js');
  wp_enqueue_script( 'bootstrap_script', 'https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js');
  wp_enqueue_script( 'sort_tables_script', '//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js');
  wp_enqueue_script( 'screen_signature_script', PBKF_URL . '/assets/js/app.js', array(), false, true);
  wp_enqueue_script( 'screen_signature_script', PBKF_URL . '/assets/js/jquery.signaturepad.min.js', array(), false, true);
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
include('shortcodes/pbk_showChildPages.php');
include('shortcodes/pbk_CheckTips.php');

/*Admin Pages*/
include('admin-page.php'); // the plugin options page HTML and save functions
add_action('init','pbk_force_download');
function pbk_force_download($file) {
  if (file_exists($file)) {
    header("Content-type: application/x-msdownload",true,200);
    header("Content-Disposition: attachment; filename=".basename($file));
    header("Pragma: no-cache");
    header("Expires: 0");
    echo 'data';
    exit();
  }
}
add_shortcode( 'toast', 'pbrf_showToastFunctions' );
add_shortcode( 'tips', 'pbk_CheckTips' );
add_shortcode( 'show_deposit_calculator', 'pbrf_depositCalculator' );
add_shortcode( 'show_finance_report_builder', 'pbrf_showReportBuilder' );
add_shortcode( 'show_restaurants', 'pbr_show_restaurants' );
add_shortcode( 'show_restaurant_hours', 'pbr_show_restaurant_hours' );
add_shortcode( 'show_pbk_child_pages', 'pbr_showChildPages' );
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
    case 1: $ms= "Restaurant updated.";$alert="success"; break;
    case 2: $ms= "There was an error. Restaurant not updated.";$alert="danger"; break;
    case 3: $ms= "Device updated.";$alert="success"; break;
    case 4: $ms= "Email Sent";$alert="success"; break;
    case 5: $ms= "Unable to send email.";$alert="danger"; break;
    case 6: $ms= "Order Placed.";$alert="success"; break;
    case 7: $ms= "Order Updated.";$alert="success"; break;
    case 8: $ms= "Key Release Submitted.";$alert="success"; break;
    case 9: $ms= "There was an error. IAP not updated.";$alert="danger"; break;
    case 10: $ms= "Individual Action Plan Saved.";$alert="success"; break;
	}
  echo  "
  <script>
    jQuery(document).ready(function(){
      setTimeout(function(){
      jQuery(\".alert\").hide(\"20000\")
    }, 30000);
    });
  </script>
<div class='alert alert-".$alert."'><strong>" . $ms . "</strong></div>";
}
add_action('wp_login', 'pbk_CheckTips');

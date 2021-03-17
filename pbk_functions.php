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
define("CHICAGO_TAX", .1175);
/*Checking Above Store*/
function pbk_check_privledge(){
  $aboveStore=0;
  $cu = wp_get_current_user();
  if(in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
    $aboveStore=1;
  }
  return $aboveStore;
}
define( "PBKF_URL", WP_PLUGIN_URL . '/' . plugin_basename(__DIR__) );
    define('DOC_IMG',base64_encode(file_get_contents(PBKF_URL . '/assets/images/PBK-Logo_Primary_Full-Color_doc.png')));
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
  wp_enqueue_style('bootstrap_style', 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css');
  wp_enqueue_style( 'datatables_bootstrap_style',  'https://cdn.datatables.net/1.10.23/css/dataTables.bootstrap4.min.css');
//  wp_enqueue_style( 'bootstrap_style',  PBKF_URL . '/assets/css/bootstrap.css');
  wp_enqueue_style( 'sort_tables_style', '//v/bs4/jszip-2.5.0/dt-1.10.23/b-1.6.5/b-html5-1.6.5/b-print-1.6.5/fh-3.1.7/sp-1.2.2/datatables.min.css');
  wp_enqueue_style( 'screen_signature_style', PBKF_URL . '/assets/css/signature.css');
  wp_enqueue_script( 'select_script', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js');
  wp_enqueue_script( 'timepicker_script', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js');
  wp_enqueue_script( 'jquery-ui_script', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js');
  wp_enqueue_script( 'popper_script', 'https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js');
  wp_enqueue_script( 'bootstrap_script', 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js');
  wp_enqueue_script( 'sort_tables_script_print', '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js');
  wp_enqueue_script( 'sort_tables_script_print_fonts', '//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js');
    wp_enqueue_script( 'sort_tables_script', 'https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.23/b-1.6.5/b-html5-1.6.5/b-print-1.6.5/fh-3.1.7/sp-1.2.2/datatables.js');
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
include('classes/PBKSubscription.php');

/*Shortcodes*/
include('shortcodes/pbrf_showToastFunctions.php');
include('shortcodes/pbrf_depositCalculator.php');
include('shortcodes/pbrf_showReportBuilder.php');
include('shortcodes/pbr_show_restaurants.php');
include('shortcodes/pbr_show_restaurant_hours.php');
include('shortcodes/pbk_showChildPages.php');
include('shortcodes/pbk_checkTips.php');

/*AJAX*/
include('ajax/restClosure.php');
include('ajax/subscribers.php');
include('ajax/Nutritional.php');

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
function pbk_show_modal($message,$actionButton=null){
  return "
  <script>
    jQuery(window).on('load',function(){
      jQuery('#tipsRequired').modal('show');
    });
  </script>
  <div class=\"modal hide fade\" id=\"tipsRequired\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"tipsRequired\" aria-hidden=\"true\">
    <div class=\"modal-dialog modal-dialog-centered\" role=\"document\">
      <div class=\"modal-content\">
        <div class=\"modal-header\">
        <h5>
        <svg class=\"bi bi-exclamation-triangle-fill\" width=\"1em\" height=\"1em\" viewBox=\"0 0 16 16\" fill=\"#F36C21\" xmlns=\"http://www.w3.org/2000/svg\">
          <path fill-rule=\"evenodd\" d=\"M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5a.905.905 0 0 0-.9.995l.35 3.507a.552.552 0 0 0 1.1 0l.35-3.507A.905.905 0 0 0 8 5zm.002 6a1 1 0 1 0 0 2 1 1 0 0 0 0-2z\"/>
        </svg>
        PBK Notification
        </h5>
        </div>
        <div class=\"modal-body\">
        ".$message."
        </div>
        <div class=\"modal-footer\">
          ".$actionButton."
          <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>
        </div>
      </div>
    </div>
  </div>
  ";
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
function get_the_user_ip() {
  if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
  } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  } else {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  return apply_filters( 'wpb_get_ip', $ip );
}
add_filter( 'login_headertext', 'acme_login_logo_image' );
function acme_login_logo_image( $login_header_text ) {
    $logo_url          = 'https://c2.theproteinbar.com/wp-content/uploads/2018/04/PBK-Logo_Secondary_Full-Color-pbc2.png';
    $login_header_text = ''; // clears default output.
    $login_header_text = '<img src="' . $logo_url . '" alt="' . get_bloginfo( 'title' ) . '" />';
    return $login_header_text;
}
function acme_login_logo_image_styles() { ?>
    <style type="text/css">
        #login h1 a,
        .login h1 a {
                background-size: auto;
                background-image: none;
                background-position: center center;
                text-indent: 0;
                width: auto;
                height: auto;
                max-width: 320px;
        }

        #login h1 a img,
        .login h1 a img {
        max-width: 100%;
        }
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'acme_login_logo_image_styles' );

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
 die;  // Silence is golden, direct call is prohibited
}
date_default_timezone_set('America/Chicago');
setlocale(LC_MONETARY, 'en_US');
error_reporting(E_ALL);
/*Scripts and CSS*/
wp_enqueue_style( 'style', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/css/select2.min.css');
wp_enqueue_script( 'script', 'https://cdn.jsdelivr.net/npm/select2@4.0.12/dist/js/select2.min.js');
wp_enqueue_style( 'style', 'https//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css');
wp_enqueue_script( 'script', 'https://cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js');
wp_enqueue_script( 'script', 'https://code.jquery.com/jquery-1.10.1.min.js');

/*Classes*/
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use Twilio\Rest\Client;
use Twilio\Twiml\MessagingResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

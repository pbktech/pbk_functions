<?php
global $wpdb;
$cu = wp_get_current_user();
$latest = date("Y-m-d", time() - 60 * 60 * 24) . " 23:59:59";
$toast = new ToastReport();
$rests = $toast->getAvailableRestaurants();
$checkRestaurant = 0;
if (isset($_GET['i'])) {
    $checkRestaurant = $wpdb->get_var("SELECT restaurantID FROM pbc_ToastOrderPayment WHERE ToastCheckID='" . $_GET['i'] . "'");
    if (in_array($checkRestaurant, $rests) || in_array("administrator", $cu->roles) || in_array("editor", $cu->roles) || in_array("author", $cu->roles)) {
        $_REQUEST['rid'] = $checkRestaurant;
    } else {
        echo "<div class='alert alert-danger'>You do not have access to this location.</div>";
        exit;
    }
}
?>
    <script>
      const restaurants = <?php echo json_encode($rests);?>;
      const tippedOrders = [];
      const driver = [];
      const worked = [];
      const tipRequest = {};
      const singleOrder = '<?php echo empty($_GET['i']) ? "false" : $_GET['i'];?>';
      let selectedRestaurant = <?php echo $checkRestaurant;?>;
      let restaurantName = "";
      let p = 0;

      function goToNext() {
        driver.splice(0, driver.length);
        worked.splice(0, worked.length);
        restaurants.forEach(function(r) {
          if (r.id === parseInt(selectedRestaurant)) {
            restaurantName = r.text;
          }
        });
        $('#message').html(restaurantName);
        tipRequest.checkID = '';
        tipRequest.driver = [];
        tipRequest.worked = [];
        tipRequest.restaurantID = 0;
        $('input.group1').html('');
        $('#tipRow').hide();
        $('#noDriver').hide();
        $('#employees').empty().hide();
        $('#saveRow').hide();
        $('#d-a0').prop('checked', false);
        p += 1;
        if (tippedOrders[p]) {
          populateTipBuild();
        } else {
          if (singleOrder) {
            $('#message').append('<br>You can close this window.');
          } else {
            $('#message').append('<br>There are no orders requiring assignment.');
          }
        }
        window.scrollTo(0, 0);
      }

      function showTipBuild() {
        const rest = [];
        restaurants.forEach(function(r) {
          if (r.id === parseInt(selectedRestaurant)) {
            rest.push(r);
          }
        });
        const data = {
          'action': 'tips_get_list',
          'restaurantID': rest[0].id,
          'singleOrder': singleOrder
        };
        $('#message').addClass('alert-info').html(rest[0].text);
        jQuery.ajax({
          url: '<?php echo admin_url('admin-ajax.php') ?>',
          type: 'POST',
          data: data,
          success: function(response) {

            $('#loading').hide();
            if (response.status === 200) {
              if (response.orders.length) {
                response.orders.forEach(function(o) {
                  tippedOrders.push(o);
                });
              } else {
                $('#message').removeClass('alert-info').addClass('alert-danger').append('<br>' + response.message);
              }
              populateTipBuild();
            }
          }
        });
      }

      function populateTipBuild() {
        const o = tippedOrders[p];
        $('#message').append('<br>There are ' + (tippedOrders.length - p) + ' checks requring assignment.');
        $('#checkID').html('Check # ' + o.order.checkNumber + (o.order.tabName !== '' ? ' Tab: ' + o.order.tabName : ''));
        $('#checkOpen').html(o.order.checkOpen);
        $('#checkClose').html(o.order.checkClose);
        $('#checkPaid').html(o.order.checkPaid);
        $('#checkPayment').html(o.order.checkPayment);
        $('#checkTip').html(o.order.checkTip);
        $('#checkTotal').html(o.order.checkTotal);
        o.employees.forEach(function(e) {
          $('#employees').append('<div class=\'row toDisable\'><div class=\'col\'><span style=\'text-transform:capitalize;\'>' + e.employeeName + '</span></div>' +
            '<div class=\'col\'><label for=\'d-' + e.GUID + '\'>Driver?</label> <input class=\'group1\' type=\'checkbox\' name=\'driver\' value=\'' + e.GUID + '\' id=\'d-' + e.GUID + '\'/> </div>' +
            '<div class=\'col\'><label for=\'w-' + e.GUID + '\'>Worked On?</label> <input class=\'group1\' type=\'checkbox\' name=\'worked\' value=\'' + e.GUID + '\' id=\'w-' + e.GUID + '\'/></div>' +
            '</div>');
        });
        $('#tipRow').show();
        $('#saveRow').show();
        $('#employees').show();
        $('#noDriver').show();
      }

      jQuery(document).ready(function() {
        if (selectedRestaurant === 0) {
          if (restaurants.length === 0) {
            $('#message').addClass('alert alert-warning').html('You are not assigned to any restaurants');
          } else if (restaurants.length === 1) {
            selectedRestaurant = restaurants[0].restaurantID;
            showTipBuild();
          } else {
            $('#message').addClass('alert-warning').html('Please Select a Restaurant');
            $('#restaurantID').select2({
              placeholder: {
                id: '-1', // the value of the option
                text: 'Choose a restaurant'
              },
              allowClear: true,
              data: restaurants
            });
            $('#selectorRow').show();
          }
        }
        $(window).on('load', function() {
          if (singleOrder) {
            showTipBuild();
          }
        });
        $('#saveButton').click(function() {

          $('#saveButton').hide();
          $('#buttonSpin').show();
          if ($('#d-a0').prop('checked') === true) {
            driver.push('a0');
            worked.push('a0');
          } else {
            $('input.group1').each(function(i, obj) {
              if (obj.checked === true) {
                if (obj.name === 'driver') {
                  driver.push(obj.value);
                }
                if (obj.name === 'worked') {
                  worked.push(obj.value);
                }
              }
            });
          }

          if (driver.length && worked.length) {
            $('#serverMessage').removeClass('alert-danger').removeClass('alert-success').html('');
            let fd = new FormData();
            tipRequest.checkID = tippedOrders[p].order.checkID;
            tipRequest.driver = driver;
            tipRequest.worked = worked;
            tipRequest.restaurantID = selectedRestaurant;

            fd.append('action', 'assignTips');
            fd.append('data', JSON.stringify(tipRequest));
            $.ajax({
              type: 'POST',
              url: '<?php echo admin_url('admin-ajax.php'); ?>',
              data: fd,
              contentType: false,
              processData: false,
              success: function(response) {
                $('#buttonSpin').hide();
                $('#saveButton').show();
                if (response.status === 200) {
                  $('#serverMessage').removeClass('alert-danger').removeClass('alert-success').html('');
                  $('#serverMessage').addClass('alert-success').html('Distribution Saved');
                  goToNext();
                } else {
                  $('#serverMessage').addClass('alert-danger');
                  $('#serverMessage').append(response.message.join('<br>'));
                }
              }
            });

          } else {
            $('#serverMessage').addClass('alert-danger').html('You must select workers and drivers');
            $('#buttonSpin').hide();
            $('#saveButton').show();
          }
        });
        $('#restaurantID').on('select2:select', function(e) {
          $('#loading').show();
          const data = e.params.data;
          selectedRestaurant = data.id;
          $('#message').removeClass('alert-warning').html('');
          $('#selectorRow').hide();
          showTipBuild();
        });
        $('#d-a0').click(function() {
          if ($(this).prop('checked') === true) {
            {
              $('input.group1').prop('checked', false);
            }
            $('input.group1').attr('disabled', true);
            $('.toDisable').hide();
          } else if ($(this).prop('checked') === false) {
            $('input.group1').removeAttr('disabled');
            $('.toDisable').show();
          }
        });
      });
    </script>
    <div class="container-fluid">
        <div class="row" id="loading" style="display: none;">
            <div class="text-center">
                <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
        <div class="row" style="width: 100%;">
            <div id="message" style="width: 100%; text-align: center;" class="alert "></div>
        </div>
        <div class="row" style="width: 100%;">
            <div id="serverMessage" style="width: 100%; text-align: center;" class="alert "></div>
        </div>
        <div class="row" id="selectorRow" style="display: none;width: 100%;">
            <select class="form-control" name="restaurantID" id="restaurantID" style="width: 100%;">
                <option value="-1">Choose a restaurant</option>
            </select>
        </div>
        <div class="row" id="tipRow" style="display: none;width: 100%;">
            <div>
                <h4 id="checkID"></h4>
            </div>
            <div class='container-fluid'>
                <div class='row' style="width: 100%;">
                    <div class='col-4'>Opened: <span id="checkOpen"> </span></div>
                    <div class='col-4'>Paid: <span id="checkPaid"> </span></div>
                    <div class='col-4'>Closed: <span id="checkClose"> </span></div>
                </div>
                <div class='row' style="width: 100%;">
                    <div class='col-4'><strong>Payment Method: <span id="checkPayment"> </span></strong></div>
                    <div class='col-4'><strong>Tip Amount: <span id="checkTip"> </span></strong></div>
                    <div class='col-4'><strong>Order Total: <span id="checkTotal"> </span></strong></div>
                </div>
            </div>
            <div class='row' style="width: 100%;">
                <hr style="width: 100%;"/>
            </div>
            <div class='row' id="noDriver" style='width: 100%;'>
                <div class='col-6'>3rd Party/No One</div>
                <div class='col-3'>
                    <label for='d-a0'>Driver?</label>
                    <input type='checkbox' name='driver[]' value='a0' id='d-a0'/>
                </div>
            </div>
        </div>
        <div class="row">
            <div id="employees" class="col-12">
            </div>
        </div>
        <div class='row' style="width: 100%;">
            <hr style="width: 100%;"/>
        </div>
        <div class="row" id="saveRow" style="display: none;">
            <div class="col">
                <button type="button" class="btn btn-success" id="saveButton">Save</button>
                <button class="btn btn-success" type="button" id="buttonSpin" style="display: none;" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="sr-only">Loading...</span>
                </button>
            </div>
        </div>
    </div>
<?php
/*
    $time = microtime();
    global $wp;
    global $wpdb;
    $ret = '';
    $page = home_url(add_query_arg(array(), $wp->request));
    $latest = date("Y-m-d", time() - 60 * 60 * 24) . " 23:59:59";
    $toast = new ToastReport();
    $rests = $toast->getAvailableRestaurants();
    $cu = wp_get_current_user();
    if (in_array("administrator", $cu->roles) || in_array("editor", $cu->roles)) {
        $toast->isAboveStore = 1;
    }
    if (count($rests) === 1) {
        $_REQUEST['rid'] = $rests[0]->restaurantID;
    }
    if (isset($_REQUEST['rid']) && $_REQUEST['rid'] == 4) {
        $bot = "2018-11-26 00:00:00";
    } else {
        $bot = "2020-05-01 00:00:00";
        //	$bot="2019-01-07 00:00:00";
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['rid'])) {
        $tipShare = array();
        $toast->setRestaurantID($_POST['rid']);
        $workerPercent = 1 / count($_POST['worked']);
        $driverPercent = 1 / count($_POST['driver']);
        $order = $toast->getPaymentInfo($_POST['chkID']);
        $dateOfBusiness = date("Y-m-d", strtotime($order->closedDate));
        $share = round(($order->tipAmount / 2), 2);
        foreach ($_POST['worked'] as $e) {
            $tipShare[$e] += round($share * $workerPercent, 3);
        }
        foreach ($_POST['driver'] as $e) {
            $tipShare[$e] += round($share * $driverPercent, 3);
            if ($e == "a0") {
                $tipShare[$e] += round($share * $driverPercent, 3);
            }
        }
        foreach ($tipShare as $e => $t) {
            $cu = wp_get_current_user();
            $userID = json_encode(array("Initial" => array("Date" => date("Y-m-d G:i:s"), "User" => $cu->user_firstname . " " . $cu->user_lastname)));
            $wpdb->query($wpdb->prepare("INSERT INTO pbc_TipDistribution(employeeGUID,orderGUID,dateOfBusiness,tipAmount,userID)values(%s,%s,%s,%s,%s)", array($e, $_POST['chkID'], $dateOfBusiness, $t, $userID)));
        }
        $wpdb->update(
            'pbc_ToastOrderPayment',
            array(
                'tipsAssigned' => '1'
            ),
            array('ToastCheckID' => $_POST['chkID'])
        );
        if ($toast->isAboveStore == 1) {
            echo "<script>window.location.replace(\"" . $page . "/?rid=" . $_POST['rid'] . "\");</script>";
        } else {
            echo "<script>window.location.replace(\"" . $page . "\");</script>";
        }
    }
    if (isset($_GET['i'])) {
        $checkRestaurant = $wpdb->get_var("SELECT restaurantID FROM pbc_ToastOrderPayment WHERE ToastCheckID='" . $_GET['i'] . "'");
        if (in_array($checkRestaurant, $rests) || in_array("administrator", $cu->roles) || in_array("editor", $cu->roles) || in_array("author", $cu->roles)) {
            $_REQUEST['rid'] = $checkRestaurant;
        } else {
            echo "<div class='alert alert-danger'>You do not have access to this location.</div>";
            exit;
        }
    }
    if (!isset($_REQUEST['rid'])) {
        $ret .= "\n
	<div>
		<form method='get' action='" . $page . "'  name='restaurantSelector'>
			<select name='rid' onchange=\"this.form.submit()\"><option value=''>Choose a Restaurant</option>";
        foreach ($rests as $r) {
            $ret .= "\n<option value='" . $r->restaurantID . "'>" . $r->restaurantName . "</option>";
        }
        $ret .= "</select></form></div>";
    } else {
        //$toast->showRawArray($_REQUEST);
        $toast = new ToastReport($_REQUEST['rid']);
        $toast->setStartTime(date("Y-m-d G:i:s", strtotime($bot)));
        $toast->setEndTime(date("Y-m-d G:i:s", strtotime($latest)));
        if (!isset($checkRestaurant)) {
            $orders = $toast->getTippedOrders();
            if (isset($orders[0])) {
                $o = $orders[0];
                $order = $toast->getPaymentInfo($orders[0]->ToastCheckID);
            }
        } else {
            $order = $toast->getPaymentInfo($_GET['i']);
            $o = $wpdb->get_row("SELECT * FROM pbc2.pbc_ToastOrderPayment where ToastCheckID='" . $_GET['i'] . "'");
        }
        if (isset($order)) {
            $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
            $toast->setStartTime(date("Y-m-d 00:00:00", strtotime($order->openedDate)));
            $toast->setEndTime(date("Y-m-d 23:59:59", strtotime($order->openedDate)));
            $employees = $toast->getClockedInEmployees("Team Member");
//		$ret.="<div><strong>There are ".count($orders)." order(s) that require tip assignments.</strong></div>";
            $ret .= "<div>
		<h4>Check #" . $order->checkNumber;
            if (isset($order->tabName) && $order->tabName != "") {
                $ret .= ": " . $order->tabName;
            }
            $ret .= "</h4>";
            $ret .= "
		<div class='container'>
			<div class='row'>
				<div class='col'>Opened: " . date("m/d/Y g:i a", strtotime($order->openedDate)) . "</div>
				<div class='col'>Paid: " . date("m/d/Y g:i a", strtotime($order->paidDate)) . "</div>
				<div class='col'>Closed: " . date("m/d/Y g:i a", strtotime($order->closedDate)) . "</div>
			</div>
			<div class='row'>
				<div class='col'><strong>Payment Method: " . $order->paymentType . "</strong></div>
				<div class='col'><strong>Tip Amount: " . $fmt->formatCurrency($order->tipAmount, "USD") . "</strong></div>
				<div class='col'><strong>Order Total: " . $fmt->formatCurrency($order->totalAmount, "USD") . "</strong></div>
			</div>
		</div>
		<hr />
		<script>
		 jQuery(document).ready(function(){
			 	jQuery('#d-a0').click(function(){
					if(jQuery(this).prop(\"checked\") == true){
						jQuery(\"input.group1\"). prop(\"checked\", false);
						jQuery(\"input.group1\").attr(\"disabled\", true);
						jQuery(\".toDisable\").hide();
					}else if(jQuery(this).prop(\"checked\") == false){
						jQuery(\"input.group1\").removeAttr(\"disabled\");
						jQuery(\".toDisable\").show();
					}
			});
		});
		</script>
		<div class='container'>
		<form method='POST' action='" . $page . "' >
			<div class='row'>
				<div class='col'>3rd Party/No One</div>
				<div class='col'><label for='d-a0'>Driver?</label> <input type='checkbox' name='driver[]' value='a0' id='d-a0'/></div>
			</div>";
            if (isset($employees)) {
                foreach ($employees as $e) {
                    $ret .= "
					<div class='row toDisable'>
						<div class='col'><span style='text-transform:capitalize;'>" . $e->employeeName . "</span></div>
						<div class='col'><label for='d-" . $e->GUID . "'>Driver?</label> <input class='group1' type='checkbox' name='driver[]' value='" . $e->GUID . "' id='d-" . $e->GUID . "'/> </div>
						<div class='col'><label for='w-" . $e->GUID . "'>Worked On?</label> <input class='group1' type='checkbox' name='worked[]' value='" . $e->GUID . "' id='w-" . $e->GUID . "'/></div>
					</div>";
                }
            } else {
                $ret .= "<div class='alert alert-warning'>There are no elligable employees clocked in on " . date("m/d/Y", strtotime($order->openedDate)) . " </div>";
            }
            $ret .= "<br />
		<input type='hidden' name='chkID' value='" . $o->ToastCheckID . "' />
		<input type='hidden' name='rid' value='" . $_REQUEST['rid'] . "' />
		<input type='submit' value='Save Check #" . $order->checkNumber . "' /></form></div>";
        } else {
            $ret .= "
		<div class=\"alert alert-secondary\" role=\"alert\">
		There are not any orders that require tip assignments.
		</div>";
        }
    }
*/

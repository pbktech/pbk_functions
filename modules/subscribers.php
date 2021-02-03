<?php
global $wpdb;
global $wp;
$toast = new ToastReport();
$page = home_url(add_query_arg(array(), $wp->request));
if (isset($_REQUEST['cancel']) &&
    $_REQUEST['cancel'] === "1") {
    $wpdb->update(
        'pbc_subscriptions',
        array(
            'isActive' => 0    // integer (number)
        ),
        array('userID' => $_REQUEST['userID']),
        array(
            '%d'
        ),
        array('%d')
    );
    if (empty($wpdb->last_error)) {
        echo '<div class="alert alert-warning">The subscription has been canceled.</div>';
    } else {
        echo '<div class="alert alert-danger">The following error occured.<br>' . $wpdb->last_error . '</div>';
    }
}
if (isset($_REQUEST['active']) &&
    $_REQUEST['active'] === "0") {
    $isActive = 0;
    ?>
    <div>
        <form method="get" action="<?php echo $page; ?>">
            <button class="btn btn-brand" type="submit">View Users</button>
        </form>
    </div>
    <?php
} else {
    $isActive = 1;
    ?>
    <div>
        <form method="get" action="<?php echo $page; ?>">
            <input type="hidden" name="active" value="0"/>
            <button class="btn btn-brand" type="submit">View Inactive Users</button>
        </form>
    </div>
    <?php
}
$result = $wpdb->get_results("SELECT guestName,phoneNumber,emailAddress,planName,DATE_FORMAT(dateStarted, '%c/%d/%Y') as 'signedUp', userID FROM pbc_subscriptions ps, pbc_subscriptions_plans psp WHERE isActive = " . $isActive . " AND firstData is not null AND ps.subPlan = psp.planID ");
if ($result) {
    $D['Options'][] = "\"order\": [ 1, 'asc' ]";
    $D['Options'][] = "\"lengthMenu\": [ [10, 20, -1], [10, 20, \"All\"] ]";
    $D['Headers'] = array("Name", "Phone Number", "Email", "Plan Name", "Signed Up", "");
    foreach ($result as $r) {
        $D['Results'][] = array(
            '<a href="#" class="showModal" data-nonce="' . wp_create_nonce( '_get_trans_'.$r->userID) . '" data-uid="' . $r->userID . '" data-guest="' . $r->guestName . '" >' . $r->guestName . '</a>',
            $r->phoneNumber,
            $r->emailAddress,
            $r->planName,
            $r->signedUp,
            '<form method="post" action="' . $page . '">
        <input type="hidden" name="cancel" value="1" />
        <input type="hidden" name="userID" value="' . $r->userID . '" />
        <button type="submit" class="btn btn-outline-danger"><i class="far fa-trash-alt"></i></button>
    </form>
'
        );
    }
    echo $toast->showResultsTable($D);

} else {
    ?>
    <div class="alert alert-warning" role="alert">
        There were no subscribers found.
    </div>
    <?php
}
add_action('wp_footer', 'subscribersAJAX');

function subscribersAJAX() { ?>

    <script type="text/javascript">
      var table;

      jQuery(document).ready(function($) {
        $('.showModal').click(function(event) {
          var guestName = event.target.dataset.guest
          table = $('#modalTable').DataTable({
            lengthMenu: [[25, 50, -1], [25, 50, 'All']],
            dom: "<'row'<'col-sm-12 col-md-12'B>><'row'<'col-sm-12'tr>><'row'<'col-sm-12 col-md-4'i><'col-sm-12 col-md-8'p>>",
            buttons: [
              'copy',
              {
                extend: 'excel',
                messageTop: guestName
              },
              {
                extend: 'pdf',
                messageTop: guestName
              },
              {
                extend: 'print',
                messageTop: guestName
              }
            ],
            columns: [
              { data: "price"},
              { data: "transactionType" },
              { data: "transactionStatus" },
              { data: "datetime" }],
              ajax: '<?php echo admin_url('admin-ajax.php') ?>?action=subscribers_get_trans&uis=' + event.target.dataset.uid + '&nonce=' + event.target.dataset.nonce,
          });
          $('#txnModal').modal('show');
          $('#txnModal').on('shown.bs.modal', function (modalEvent) {
            var modal = $(this);
            modal.find('.modal-title').text(guestName);

          });
          $('#txnModal').on('hidden.bs.modal', function (e) {
            $("#modalTable").DataTable().clear().destroy();
          })
        });
      });
    </script>
    <div class="modal fade" id="txnModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel"></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id='modalTable' class="display table table-striped table-hover table-bordered" style="width:100%;">
                        <thead>
                        <tr>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                        </thead>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}


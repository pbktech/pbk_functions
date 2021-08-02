<?php
global $wpdb;
?>

<h2>Update your Issue</h2>

<div class="container-fluid">
    <div class="row">
        <div class="col">

        </div>
    </div>
</div>

<div class="modal" tabindex="-1" id="issueModal">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close closeModal" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary closeModal" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="saveButton">Save</button>
                <button class="btn btn-success" type="button" id="buttonSpin" style="display: none;" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    <span class="sr-only">Loading...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<?php

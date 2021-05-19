<script>
  jQuery(document).ready(function($) {
    $('.toClear').blur(function(e){
      const line = e.target;
      $('#' + line.dataset.name).html(line.value)
    });
    $('.saveButton').click(function(){
      const info = [];
      $('.toClear').each(function( index ) {
        info.push({field:$( this ).data('name'), value:$( this ).val()});
      });
      console.log(info);
      confirm = {
        action: 'set_signature',
        data: info
      };
      jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php');?>',
        type: 'POST',
        data: confirm,
        success: function(response) {
          console.log(response)
        }
      });
    });
  });
</script>
<div class="container-fluid">
    <div class="row">
        <div class="col-6">
            <div style="padding-left: 10px;background:#ffffff">
                <div style="float:left;padding:1px 10px 0 0">
                    <img src="https://c2.theproteinbar.com/PBK-Logo_Tertiary_Full-Color_92.png" alt=""
                         style="padding:3px 0px 0px 10px;">
                </div>
                <div style="float:left;padding:1px 10px 0 0">
                    <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#0e2244;margin:0;text-transform:capitalize">
                        <span id="name">Name</span><br>
                        <span style="color: #f36c21" id="location">Location</span>
                    </div>
                    <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;margin:0px;color: #93c47d;">
                        <span id="title">Title</span>
                    </div>
                    <div style="font-style:normal;color:#404040;font-size:12px;line-height:20px;font-family:Arial,Helvetica,sans-serif;font-size-adjust:none;margin:0px">
                        <span id="address">Address</span>
                    </div>
                    <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
                        <span>P</span> <span style="font-weight:normal;color:#404040;" id="phone">Phone</span>
                    </div>
                    <div style="font:bold 12px/20px Arial,Helvetica,sans-serif;color:#000000;margin:0">
                        <span>E</span> <span style="font-weight:normal;color:#404040;" id="email">Email</span>
                    </div>
                </div>
            </div>
            <div style="clear:both"></div>
        </div>
        <div class="col-6">
            <div class="row">
                <input type="text" class="form-control toClear" data-name="name" placeholder="Name" />
            </div>
            <div class="row">
                <input type="text" class="form-control toClear" data-name="location" placeholder="Location" />
            </div>
            <div class="row">
                <input type="text" class="form-control toClear" data-name="title" placeholder="Title" />
            </div>
            <div class="row">
                <input type="text" class="form-control toClear" data-name="address" placeholder="Address" />
            </div>
            <div class="row">
                <input type="text" class="form-control toClear" data-name="phone" placeholder="Phone" />
            </div>
            <div class="row">
                <input type="text" class="form-control toClear" data-name="email" placeholder="Email" />
            </div>
            <div class="row">
                <button class="btn-default saveButton">Save</button>
            </div>
        </div>
    </div>
</div>
<?php
$sign = '';
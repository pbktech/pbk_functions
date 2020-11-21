$('#hs-modal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget) // Button that triggered the modal
  var obj = jQuery.parseJSON(button.data('whatever')) // Extract info from data-* attributes
  var modal = $(this)
  modal.find('.modal-title').text('New message to ' + obj.name)
  modal.find('.modal-body input').val(recipient)
})

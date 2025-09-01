var ce = {};

$(document).ready( function($) {
  ce.submit = $('#ttt-body form input.submit')
  ce.revert = $('#ttt-body form input.revert')

  ce.revert.show().prop('disabled',true);
});

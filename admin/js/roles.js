( function() {

  let ce = {};

  $(document).ready(
    function($) {
    ce.form            = $('#admin-settings');
    ce.ajaxuri         = $('#admin-settings input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-settings input[name=nonce]').val();
    ce.status          = $('#ttt-status');
    ce.submit          = $('#settings_submit');

  });

})();

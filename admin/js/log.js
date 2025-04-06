( function() {

  let ce = {};

  $(document).ready(
    function($) {
    ce.form            = $('#admin-log');
    ce.ajaxuri         = $('#admin-log input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-log input[name=nonce]').val();

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );
  });

})();

( function() {

  let ce = {};

  function handle_surveys_submit(event) 
  {
  }

  function update_submit()
  {
  }

  function has_changes()
  {
    return false;
  }

  $(document).ready(
    function($) {
    ce.form            = $('#admin-roles');
    ce.ajaxuri         = $('#admin-roles input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-roles input[name=nonce]').val();
    ce.status          = $('#ttt-status');
    ce.submit          = $('#surveys_submit');
    ce.revert          = $('#surveys_revert');

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );

    ce.form.on('submit',handle_surveys_submit);

    has_change_cb = has_changes;

    update_submit();
  });

})();

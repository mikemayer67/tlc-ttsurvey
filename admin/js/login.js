( function() {

  let ce = {};

  function handle_login_submit(event) {
    event.preventDefault();
    var sender = event.originalEvent.submitter;

    if(ce.cancel.is(sender)) {
      window.location = ce.form.find('input[name=cancel]').val();
    }
    else if(ce.submit.is(sender)) {
      $.ajax( {
        type:'POST',
        url:ce.ajaxuri,
        dataType:'json',
        data:{
          'ajax':'admin/login_admin',
          'nonce':ce.nonce,
          'userid':ce.userid.val(),
          'password':ce.password.val(),
        },
      })
      .done( function(data,status,jqXHR) {
        if(data.success) {
          window.location = ce.ajaxuri + '?admin';
        } else {
          show_status('error',data.error);
        }
      })
      .fail( function(jqXHR,textStatus,errorThrown) { 
        internal_error(jqXHR); 
      });
    }
  }

  $(document).ready(
    function($) {
    ce.form     = $('#admin-login');
    ce.userid   = $('#ttt-input-userid');
    ce.password = $('#ttt-input-password');
    ce.submit   = $('#admin-login button.submit');
    ce.cancel   = $('#admin-login button.cancel');

    ce.ajaxuri = ce.form.find('input[name=ajaxuri]').val();
    ce.nonce   = ce.form.find('input[name=nonce]').val();

    // need this as there is no admin navbar on the admin login page
    ace.ajaxuri = ce.ajaxuri;

    ce.form.on('submit',handle_login_submit);

    ce.userid.on('keydown',function(e) {
      if(e.key === 'Enter') { ce.password.focus(); } 
    });
    ce.password.on('keydown',function(e) {
      if(e.key === 'Enter') { ce.userid.focus(); } 
    });
  });

})();

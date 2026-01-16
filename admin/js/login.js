( function() {

  let ce = {};

  function handle_login_submit(event) {
    event.preventDefault();
    var sender = event.originalEvent.submitter;

    if(ce.cancel.is(sender)) {
      const admin = ce.form.find('input[name=admin]').val();
      const target = admin ? 'ttt_admin' : 'ttt_survey';
      window.open( ce.form.find('input[name=cancel]').val(), target);
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
          window.open( ce.ajaxuri + '?admin', 'ttt_admin');
        } else {
          show_status('error',data.error);
        }
      })
      .fail( function(jqXHR,textStatus,errorThrown) { 
        ajax_error_hander(jqXHR,'log in as admin')
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

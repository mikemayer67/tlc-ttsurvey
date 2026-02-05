export default function init()
{
  const self = {};

  const old_pw_label  = $('<div>').addClass('label old password').append('Current Password');
  const new_pw_label  = $('<div>').addClass('label new password').append('New Password');
  const old_pw_err    = $('<div>').addClass('error');
  const new_pw_err    = $('<div>').addClass('error');
  const old_pw_input  = $('<input>').addClass('old password').setType('password');
  const new_pw_input  = $('<input>').addClass('new password').setType('password');
  const confirm_input = $('<input>').addClass('confirm password').setType('password');
  const submit_btn    = $('<button>').addClass('submit').setType('button').text('update').disable();
  const cancel_btn    = $('<button>').addClass('cancel').setType('button').text('cancel');
  const show_old_pw   = $('<img>').addClass('hide-pw');
  const show_new_pw   = $('<img>').addClass('hide-pw');
  const reset_info    = $('<img>').addClass('password info reset');
  const rules_info    = $('<img>').addClass('password info rules');

  old_pw_input.autocomplete('current-password').placeholder('current password');
  new_pw_input.autocomplete('new-password').placeholder('new password');
  confirm_input.autocomplete('new-password').placeholder('retype new password');

  const editor = $('<div>').setId('ttt-password-editor').addClass('editor-pane').append(
    $('<div>').addClass('fields').append(
      $('<div>').addClass('wrapper').append(old_pw_label, reset_info),
      $('<div>').addClass('wrapper').append(old_pw_input, show_old_pw),
      old_pw_err,
      $('<div>').addClass('wrapper').append(new_pw_label, rules_info),
      $('<div>').addClass('wrapper').append(new_pw_input, show_new_pw),
      confirm_input,
      new_pw_err,
    ),
    $('<div>').addClass('actions').append(submit_btn, cancel_btn),
  );

  ce.navbar.parent().append(editor);

  editor.find('input.password').on('input',check_inputs);

  cancel_btn.on('click', () => self.hide() );
  submit_btn.on('click', () => submit()    );

  show_old_pw.on('click',function() {
    show_old_pw.toggleClass('show-pw hide-pw');
    const show = show_old_pw.hasClass('show-pw');
    old_pw_input.setType( show ? 'text' : 'password' );
  });
  show_new_pw.on('click',function() {
    show_new_pw.toggleClass('show-pw hide-pw');
    const show = show_new_pw.hasClass('show-pw');
    new_pw_input.setType(( show ? 'text' : 'password'));
    confirm_input.setType(( show ? 'text' : 'password'));
  });

  rules_info.on('click',function() { alert(ttt_hints.password); } );
  reset_info.on('click',function() {
    if( (ttt_user.email ?? "").length > 0) {
      alert("If you cannot recall your current password:\n" +
            "  - log out of the survey\n" +
            "  - select 'forgot login info' in the login box\n" +
            "  - follow the instructions for resetting your password\n");
    } else {
        alert("If you cannot recall your current password, contact a survey admin to help you reset it.\n\n" +
              "Consider adding your email address to enable future password recovery.");
    }
  });


  self.show = function() {
    editor.addClass('visible');
    old_pw_input.val('').removeClass('error');
    new_pw_input.val('').removeClass('error');
    confirm_input.val('').removeClass('error');
    old_pw_err.text('');
    new_pw_err.text('');
    check_inputs();
  }

  self.hide = function() {
    editor.removeClass('visible');
  }

  function check_inputs(e)
  {
    const old_pw  = old_pw_input.val();
    const new_pw  = new_pw_input.val();
    const confirm = confirm_input.val();

    const confirmed = (new_pw.length > 0) && (new_pw === confirm);
    const can_submit = confirmed && (old_pw.length > 0);

    old_pw_input.toggleClass('missing', old_pw.length === 0);
    new_pw_input.toggleClass('missing', new_pw.length === 0);
    confirm_input.toggleClass('missing', confirm.length === 0);

    if(e) {
      const tgt = $(e.currentTarget);
      if( tgt.hasClass('new') || tgt.hasClass('confirm')) {
        new_pw_err.text('');
        new_pw_input.removeClass('error');
        confirm_input.removeClass('error');
      }
      if(tgt.hasClass('old')) {
        old_pw_err.text('');
        old_pw_input.removeClass('error');
      }
    }
    confirm_input.toggleClass('mismatch', new_pw.length > 0 && !confirmed);

    old_pw_err.text('');
    new_pw_err.text('');

    submit_btn.disable(!can_submit);
  }

  function submit()
  {
    submit_btn.disable();

    const nonce   = $('form input[name=nonce]').val();
    const ajaxuri = $('form input[name=ajaxuri]').val();

    const old_pw = old_pw_input.val();
    const new_pw = new_pw_input.val();
    
    $.ajax({
      type:'POST',
      url:ajaxuri,
      dataType:'json',
      data:{ 
        ajax:  'survey/update_password',
        nonce: nonce,
        userid: ttt_user.userid,
        old_pw: old_pw,
        new_pw: new_pw,
      },
    })
    .done( function(data,status,jqXHR) {
      if(data.success) {
        show_status('info','Password Updated');
        self.hide();

        if(data.email) {
          $.ajax({
            type:'POST',
            url:ajaxuri,
            data:{ 
              ajax:  'survey/notify_updated_password',
              nonce:  nonce,
              userid: ttt_user.userid,
              email:  data.email,
            },
          });
        }

      } else {
        // update password failed ... update the UX to show the errors
        old_pw_err.text(data.old_error);
        old_pw_input.toggleClass('error',data.old_error.length>0);
        new_pw_err.text(data.new_error);
        new_pw_input.toggleClass('error',data.new_error.length>0);
        confirm_input.toggleClass('error',data.new_error.length>0);
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      ajax_error_handler(jqXHR,'update password');
    })
    .always( function() {
      submit_btn.enable();
    });
  }

  return self;
}

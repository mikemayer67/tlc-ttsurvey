export default function init()
{
  const self = {};

  const name_label  = $('<div>').addClass('label name' ).text('Name');
  const email_label = $('<div>').addClass('label email').text('Email');
  const name_err    = $('<div>').addClass('error');
  const email_err   = $('<div>').addClass('error');
  const name_input  = $('<input>').addClass('name').setType('text');
  const email_input = $('<input>').addClass('email').setType('email');
  const submit_btn  = $('<button>').addClass('submit').setType('button').text('update').disable();
  const cancel_btn  = $('<button>').addClass('cancel').setType('button').text('cancel');
  const name_info   = $('<img>').addClass('name info');
  const email_info  = $('<img>').addClass('email info');

  name_input.autocomplete('name').placeholder('required');
  email_input.autocomplete('email').placeholder('optional, but recommended');

  const editor = $('<div>').setId('ttt-user-editor').addClass('editor-pane').append(
    $('<div>').addClass('fields').append(
      $('<div>').addClass('wrapper').append( name_label, name_info ),
      name_input,
      name_err,
      $('<div>').addClass('wrapper').append( email_label, email_info ),
      email_input,
      email_err,
    ),
    $('<div>').addClass('actions').append(submit_btn, cancel_btn),
  );

  ce.navbar.parent().append(editor);

  editor.find('input').on('input',check_inputs);

  cancel_btn.on('click', () => self.hide() );
  submit_btn.on('click', () => submit()    );

  name_info.on('click',  () => alert(ttt_hints.name)  );
  email_info.on('click', () => alert(ttt_hints.email) );


  self.show = function() {
    editor.addClass('visible');
    name_input.val(ttt_user.name).removeClass('error');
    email_input.val(ttt_user.email).removeClass('error');
    name_err.text('');
    email_err.text('');
    check_inputs();
  }

  self.hide = function() {
    editor.removeClass('visible');
  }

  function check_inputs(e)
  {
    const name  = name_input.val();
    const email = email_input.val();

    const has_change = (name !== ttt_user.name) || (email !== ttt_user.email);

    name_input.toggleClass('missing',name.length===0);

    if(e) {
      const tgt = $(e.currentTarget);
      if(tgt.hasClass('name')) {
        name_err.text('');
        name_input.removeClass('error');
      }
      if(tgt.hasClass('email')) {
        email_err.text('');
        email_input.removeClass('error');
      }
    }
    const has_error = name_input.hasClass('error') || email_input.hasClass('error');

    const can_submit = has_change && !has_error && (name.length > 0);

    submit_btn.disable(!can_submit);
  }

  function submit() {
    submit_btn.disable();

    const nonce   = $('form input[name=nonce]').val();
    const ajaxuri = $('form input[name=ajaxuri]').val();

    const new_name  = name_input.val();
    const new_email = email_input.val();
    const old_name  = ttt_user.name;
    const old_email = ttt_user.email;

    $.ajax({
      type:'POST',
      url:ajaxuri,
      dataType:'json',
      data:{ 
        ajax:  'survey/update_user_info',
        nonce:  nonce,
        userid: ttt_user.userid,
        name:   new_name,
        email:  new_email,
      },
    })
    .done( function(data,status,jqXHR) {
      if(data.success) {
        show_status('info','User Profile Updated');
        self.hide();

        ttt_user.name  = data.name;
        ttt_user.email = data.email;

        name_input.val(data.name);
        email_input.val(data.email);

        ce.navbar.find('span.username span').text(data.name);

        if(data.email || old_email) {
          $.ajax({
            type:'POST',
            url:ajaxuri,
            data:{ 
              ajax:  'survey/notify_updated_userinfo',
              nonce:  nonce,
              userid: ttt_user.userid,
              old_name:  old_name,
              new_name:  data.name,
              old_email: old_email,
              new_email: data.email,
            },
          })
        }

        $(document).trigger('UserProfileUpdated',[old_email,new_email] );

      } else {
        name_err.text(data.name_error);
        name_input.toggleClass('error',data.name_error.length>0);
        email_err.text(data.email_error);
        email_input.toggleClass('error',data.email_error.length>0);
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      if(jqXHR.status===405) {
        location.replace('405.php');
      } else {
        internal_error(jqXHR);
      }
    })
    .always( function() {
      submit_btn.enable();
    });
  }

  return self;
}

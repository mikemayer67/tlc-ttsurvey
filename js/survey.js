import './jquery_helpers.js'

var ce = {};

function internal_error(jqXHR)
{
  alert(
    "Internal error (#" + jqXHR.status + "): "
    + jqXHR.statusText
    + "\nPlease let the survey admin know something went wrong."
  );
}

function hide_status()
{
  ce.status.removeClass().addClass('none');
  setTimeout(() => {ce.status.html('')},0);
}

function show_status(level,msg)
{
  ce.status.removeClass().addClass(level).html(msg);
}


function setup_hints()
{
  ce.hint_toggles = $('label.hint-toggle');

  if (window.matchMedia("(hover: hover)").matches) 
  {
    ce.hint_toggles.on('pointerenter', function(e) {
      const id = $(this).data('question-id');
      const hintlock = $('#hint-lock-'+id);
      const hint = $('#hint-'+id);

      // reentering the trigger icon when the hint is locked visible should hide it
      // otherwise, this action should show the hint
      // either way, the trigger should be unlocked
      hint.toggleClass('hovering', !hintlock.isChecked());
      hintlock.setUnchecked();
    });

    ce.hint_toggles.on('pointerleave', function(e) {
      const id = $(this).data('question-id');
      const hint = $('#hint-'+id);
      hint.removeClass('hovering')
    });
  }
}

function logout_user(e)
{
  hide_user_menu();
  if(ttt_preview) { 
    alert('Logout is disabled in preview mode');
    return; 
  }
  if( ce.confirm_logout ) {
    if (!confirm( "You have unsaved changes.  Logging out now will lose those changes.")) { return }
  }
  const url = new URL(location.href);
  url.searchParams.set('logout',1);
  location.replace(url.toString());
}

//
// User Menu 
//

function setup_user_menu()
{
  const navbar  = $('#ttt-navbar');
  const trigger = $('<div>').addClass('menu-trigger').append(
    $('<img>').attr('src',ttt_menu_icon).attr('alt','User Menu'),
  );

  navbar.find('.username').append(trigger);

  const profile = $('<button>').setType('button').append('edit profile');
  const passwd = $('<button>').setType('button').append('change password');
  const logout = $('<button>').setType('button').append('logout');
  const menu = $('<div>').setId('ttt-user-menu').append(profile,passwd,logout);
  menu.insertAfter(navbar);

  trigger.on('click', function(e) { menu.toggleClass('locked') } );

  ce.menu_hover_timer = null;
  trigger.on('pointerenter', start_menu_hover);
  trigger.on('pointerleave', end_menu_hover);
  menu.on('pointerenter', start_menu_hover);
  menu.on('pointerleave', end_menu_hover);

  profile.on('click',show_user_editor);
  passwd.on('click',show_password_editor);
  logout.on('click',logout_user);

  ce.navbar            = navbar;
  ce.user_menu_trigger = trigger;
  ce.user_menu         = menu;

  const ro = new ResizeObserver( entries => { 
    for( let entry of entries ) {
      console.log("resize triggered " + entry.contentRect.height);
      ce.user_menu.css('top',(5+entry.contentRect.bottom));
    }
  });
  ro.observe(navbar[0]);
}

function start_menu_hover(e)
{
  if( ce.menu_hover_timer) {
    clearTimeout(ce.menu_hover_timer);
    ce.menu_hover_timer = null;
  }
  ce.user_menu.addClass('hover');
}

function end_menu_hover(e)
{
  clearTimeout(ce.menu_hover_timer);
  ce.menu_hover_timer = setTimeout( function() {
    ce.user_menu.removeClass('hover');
    ce.menu_hover_timer = null;
  }, 200);
}

function hide_user_menu()
{
  clearTimeout(ce.menu_hover_timer);
  ce.user_menu.removeClass('locked').removeClass('hover');
}

//
// User Profile Editor
//

function show_user_editor()
{
  hide_user_menu();
  hide_password_editor();
  if(ttt_preview) { 
    alert('Profile editor is disabled in preview mode');
    return;
  }
  const editor = get_user_editor();
  editor.addClass('visible');
}

function get_user_editor()
{
  if(!ce.user) {
    ce.user = {
      name_err  : $('<div>').addClass('error'),
      email_err : $('<div>').addClass('error'),
      name      : $('<input>').addClass('name').setType('text'),
      email     : $('<input>').addClass('email').setType('email'),
      submit    : $('<button>').addClass('submit').setType('button').text('update').disable(),
      cancel    : $('<button>').addClass('cancel').setType('button').text('cancel'),
      nameinfo  : $('<img>').addClass('name info'),
      emailinfo : $('<img>').addClass('email info'),
    },
    ce.user.name.autocomplete('name').placeholder('as you wish it to appear');
    ce.user.email.autocomplete('email').placeholder('optional, but recommended');

    const name_label  = $('<div>').addClass('label name' ).append('Name');
    const email_label = $('<div>').addClass('label email').append('Email');

    ce.user.editor = $('<div>').setId('ttt-user-editor').addClass('editor-pane').append(
      $('<div>').addClass('fields').append(
        $('<div>').addClass('wrapper').append( name_label, ce.user.nameinfo ),
        ce.user.name,
        ce.user.name_err,
        $('<div>').addClass('wrapper').append( email_label, ce.user.emailinfo ),
        ce.user.email,
        ce.user.email_err,
      ),
      $('<div>').addClass('actions').append(ce.user.submit, ce.user.cancel),
    );

    ce.navbar.parent().append(ce.user.editor);

    ce.user.cancel.on('click', hide_user_editor);
    ce.user.submit.on('click', function() { alert('update user')} );

    ce.user.nameinfo.on('click',  function() { alert(ttt_hints.name);  } );
    ce.user.emailinfo.on('click', function() { alert(ttt_hints.email); } );
  }
  return ce.user.editor;
}

function hide_user_editor()
{
  ce.user?.editor.removeClass('visible');
}

//
// Password Editor
//

function show_password_editor()
{
  hide_user_menu();
  hide_user_editor();
  if(ttt_preview) { 
    alert('Password editor is disabled in preview mode');
    return;
  }
  const editor = get_password_editor();
  editor.find('input').val('');
  editor.addClass('visible');
}

function get_password_editor()
{
  if(!ce.pw) {
    ce.pw = {
      old_err  : $('<div>').addClass('error'),
      new_err  : $('<div>').addClass('error'),
      old_pw   : $('<input>').addClass('old password').setType('password'),
      new_pw   : $('<input>').addClass('new password').setType('password'),
      confirm  : $('<input>').addClass('conform password').setType('password'),
      submit   : $('<button>').addClass('submit').setType('button').text('update').disable(),
      cancel   : $('<button>').addClass('cancel').setType('button').text('cancel'),
      show_old : $('<img>').addClass('hide-pw'),
      show_new : $('<img>').addClass('hide-pw'),
      reset    : $('<img>').addClass('password info reset'),
      rules    : $('<img>').addClass('password info rules'),
    };
    ce.pw.old_pw.autocomplete('current-password').placeholder('current password');
    ce.pw.new_pw.autocomplete('new-password').placeholder('new password');
    ce.pw.confirm.autocomplete('new-password').placeholder('retype new password');

    const old_label = $('<div>').addClass('label old password').append('Current Password');
    const new_label = $('<div>').addClass('label new password').append('New Password');

    ce.pw.editor = $('<div>').setId('ttt-password-editor').addClass('editor-pane').append(
      $('<div>').addClass('fields').append(
        $('<div>').addClass('wrapper').append(old_label, ce.pw.reset),
        $('<div>').addClass('wrapper').append(ce.pw.old_pw, ce.pw.show_old),
        ce.pw.old_err,
        $('<div>').addClass('wrapper').append(new_label, ce.pw.rules),
        $('<div>').addClass('wrapper').append(ce.pw.new_pw, ce.pw.show_new),
        ce.pw.confirm,
        ce.pw.new_err,
      ),
      $('<div>').addClass('actions').append(ce.pw.submit, ce.pw.cancel),
    );

    ce.navbar.parent().append(ce.pw.editor);

    ce.pw.editor.find('input.password').on('input',check_password_inputs);

    ce.pw.cancel.on('click', hide_password_editor);
    ce.pw.submit.on('click', submit_password_change);

    ce.pw.show_old.on('click',function() {
      ce.pw.show_old.toggleClass('show-pw hide-pw');
      const show_old = ce.pw.show_old.hasClass('show-pw');
      ce.pw.old_pw.setType(( show_old ? 'text' : 'password'));
    });
    ce.pw.show_new.on('click',function() {
      ce.pw.show_new.toggleClass('show-pw hide-pw');
      const show_new = ce.pw.show_new.hasClass('show-pw');
      ce.pw.new_pw.setType(( show_new ? 'text' : 'password'));
      ce.pw.confirm.setType(( show_new ? 'text' : 'password'));
    });

    ce.pw.rules.on('click',function() { alert(ttt_hints.password); } );
    ce.pw.reset.on('click',function() {
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
  }
  return ce.pw.editor;
}

function hide_password_editor()
{
  ce.pw?.editor.removeClass('visible');
}

function check_password_inputs()
{
  const old_pw  = ce.pw.old_pw.val();
  const new_pw  = ce.pw.new_pw.val();
  const confirm = ce.pw.confirm.val();

  const confirmed = (new_pw.length > 0) && (new_pw === confirm);
  const can_submit = confirmed && (old_pw.length > 0);

  ce.pw.old_pw.toggleClass('missing', old_pw.length === 0);
  ce.pw.new_pw.toggleClass('missing', new_pw.length === 0);
  ce.pw.confirm.toggleClass('missing', confirm.length === 0);
  ce.pw.confirm.toggleClass('error', new_pw.length > 0 && !confirmed);

  ce.pw.old_err.html('');
  ce.pw.new_err.html('');

  ce.pw.submit.disable(!can_submit);
}

function submit_password_change()
{
  ce.pw.submit.disable();

  const nonce   = $('#survey input[name=nonce]').val();
  const ajaxuri = $('#survey input[name=ajaxuri]').val();
  
  $.ajax({
    type:'POST',
    url:ajaxuri,
    dataType:'json',
    data:{ 
      ajax:  'survey/update_password',
      nonce: nonce,
      userid: ttt_user.userid,
      old_pw: ce.pw.old_pw.val(),
      new_pw: ce.pw.new_pw.val(),
    },
  })
  .done( function(data,status,jqXHR) {
    if(data.success) {
      show_status('info','Password Updated');
      hide_password_editor();

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
      ce.pw.old_err.html(data.old_error);
      ce.pw.new_err.html(data.new_error);
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
    ce.pw.submit.enable();
  });
}

//
// Ready / Setup
//

$(document).ready( function() {
  ce.submit = $('#ttt-body form input.submit');
  ce.revert = $('#ttt-body form input.revert');
  ce.status = $('#ttt-status');

  setup_hints();
  setup_user_menu();

  ce.confirm_logout = true;

  ce.revert.removeClass('hidden');

  ce.status.on('click',hide_status);
});

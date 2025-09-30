var ce = {};


function setup_hints()
{
  ce.hint_toggles = $('label.hint-toggle');

  if (window.matchMedia("(hover: hover)").matches) 
  {
    ce.hint_toggles.on('pointerenter', function(e) {
      const id = $(this).data('question-id');
      const hintlock = $('#hint-lock-'+id);
      const hint = $('#hint-'+id);
      if(hintlock.prop('checked')) {
        // reentering the trigger icon when the hint is locked visible should hide it
        hintlock.prop('checked',false);
        hint.removeClass('hovering')
      } else {
        // otherwise, this action should show the hint
        hint .addClass('hovering')
      }
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

  const profile = $('<button>').attr('type','button').append('edit profile');
  const passwd = $('<button>').attr('type','button').append('change password');
  const logout = $('<button>').attr('type','button').append('logout');
  const menu = $('<div>').attr('id','ttt-user-menu').append(profile,passwd,logout);
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
  const editor = get_user_editor();
  editor.addClass('visible');
}

function get_user_editor()
{
  if(!ce.user_editor) {
    const name     = $('<input>').addClass('name');
    const email    = $('<input>').addClass('email');
    const submit   = $('<button>').addClass('submit').attr('type','button').append('update');
    const cancel   = $('<button>').addClass('cancel').attr('type','button').append('cancel');

    ce.user_editor = $('<div>').attr('id','ttt-user-editor').addClass('editor-pane').append(
      $('<div>').addClass('fields').append(
        $('<span>').addClass('label name').append('Name'),  name,
        $('<span>').addClass('label email').append('Email'), email,
      ),
      $('<div>').addClass('actions').append(submit, cancel),
    );

    ce.navbar.parent().append(ce.user_editor);

    cancel.on('click', hide_user_editor);
  }
  return ce.user_editor;
}

function hide_user_editor()
{
  ce.user_editor?.removeClass('visible');
}

//
// Password Editor
//

function show_password_editor()
{
  hide_user_menu();
  hide_user_editor();
  const editor = get_password_editor();
  editor.addClass('visible');
}

function get_password_editor()
{
  if(!ce.password_editor) {
    const password = $('<input>').addClass('password');
    const confirm  = $('<input>').addClass('password');
    const submit   = $('<button>').addClass('submit').attr('type','button').append('update');
    const cancel   = $('<button>').addClass('cancel').attr('type','button').append('cancel');

    ce.password_editor = $('<div>').attr('id','ttt-password-editor').addClass('editor-pane').append(
      $('<div>').addClass('fields').append(
        $('<span>').addClass('label password').append('New Password'), password,
        $('<span>').addClass('label confirm').append('Confirm Password'), confirm,
      ),
      $('<div>').addClass('actions').append(submit, cancel),
    );

    ce.navbar.parent().append(ce.password_editor);

    cancel.on('click', hide_password_editor);
  }
  return ce.password_editor;
}

function hide_password_editor()
{
  ce.password_editor?.removeClass('visible');
}

//
// Ready / Setup
//

$(document).ready( function() {
  ce.submit = $('#ttt-body form input.submit')
  ce.revert = $('#ttt-body form input.revert')

  setup_hints();
  setup_user_menu();

  ce.confirm_logout = true;

  ce.revert.removeClass('hidden');
});

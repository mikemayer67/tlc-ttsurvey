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

function setup_user_menu()
{
  const navbar  = $('#ttt-navbar');
  const trigger = $('<div>').addClass('menu-trigger').append(
    $('<img>').attr('src',ttt_menu_icon).attr('alt','User Menu'),
  );

  navbar.find('.username').append(trigger);

  const profile = $('<button>').attr('type','button').append('edit profile');
  const logout = $('<button>').attr('type','button').append('logout');
  const menu = $('<div>').attr('id','ttt-user-menu').append(profile,logout);
  menu.insertAfter(navbar);

  trigger.on('click', function(e) { menu.toggleClass('locked') } );

  ce.menu_hover_timer = null;
  trigger.on('pointerenter', start_menu_hover);
  trigger.on('pointerleave', end_menu_hover);
  menu.on('pointerenter', start_menu_hover);
  menu.on('pointerleave', end_menu_hover);

  profile.on('click',edit_user);
  logout.on('click',logout_user);

  ce.user_menu_trigger = trigger;
  ce.user_menu         = menu;
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

function edit_user(e)
{
  hide_user_menu();
  alert('edit_user');
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

$(document).ready( function() {
  ce.submit = $('#ttt-body form input.submit')
  ce.revert = $('#ttt-body form input.revert')

  setup_hints();
  setup_user_menu();

  ce.confirm_logout = true;

  ce.revert.removeClass('hidden');
});

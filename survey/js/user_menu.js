import profile_editor     from './profile_editor.js';
import password_editor from './password_editor.js';

const is_preview = (typeof ttt_preview === 'undefined' ? false : ttt_preview);

let menu_timer = null;

export default function init()
{
  const canHover = !window.matchMedia('(any-hover: none)').matches;
  
  const self = {
    is_preview,
  };

  const trigger = $('<div>').addClass('menu-trigger').append(
    $('<img>').addClass('menu-trigger').attr('src',ttt_menu_icon).attr('alt','User Menu'),
  );

  ce.navbar.find('.username').append(trigger);

  const profile = $('<button>').setType('button').append('edit profile');
  const passwd = $('<button>').setType('button').append('change password');
  const logout = $('<button>').setType('button').append('logout');
  const menu = $('<div>').setId('ttt-user-menu').append(profile,passwd,logout);
  menu.insertAfter(ce.navbar);

  trigger.on('click', function(e) { 
    console.log('click trigger');
    menu.toggleClass('locked') 
  });

  if(canHover) {
    trigger.on('pointerenter', start_menu_hover);
    trigger.on('pointerleave', end_menu_hover);
    menu.on(   'pointerenter', start_menu_hover);
    menu.on(   'pointerleave', end_menu_hover);
  }

  profile.on('click', () => self.show_profile_editor() );
  passwd.on( 'click', () => self.show_password_editor() );
  logout.on( 'click', () => self.logout() );

  self.trigger = trigger;
  self.menu    = menu;

  const ro = new ResizeObserver( entries => { 
    for( let entry of entries ) {
      console.log("resize triggered " + entry.contentRect.height);
      self.menu.css('top',(5+entry.contentRect.bottom));
    }
  });
  ro.observe(ce.navbar[0]);

  function start_menu_hover(e)
  {
    console.log('start_menu_hover');
    if( menu_timer) {
      clearTimeout(menu_timer);
      menu_timer = null;
    }
    self.menu.addClass('hover');
  }

  function end_menu_hover(e)
  {
    console.log('end_menu_hover');
    clearTimeout(menu_timer);
    menu_timer = setTimeout( function() {
      menu_timer = null;
      self.menu.removeClass('hover');
    }, 200);
  }

  function hide_user_menu()
  {
    console.log('hide_user_menu');
    clearTimeout(menu_timer);
    self.menu.removeClass('locked').removeClass('hover');
  }

  function show_profile_editor() {
  }

  if( is_preview ) {
    self.logout = function(e) {
      alert('Logout is disabled in preview mode');
      return;
    }
    self.show_profile_editor = function(e) {
      alert('Profile editor is disabled in preview mode');
      return;
    }
    self.show_password_editor = function(e) {
      alert('Password editor is disabled in preview mode');
      return;
    }
  } else {
    self.logout = function(e) {
      if( self.confirm_logout ) {
        if (!confirm( "You have unsaved changes.  Logging out now will lose those changes.")) { 
          return;
        }
      }
      const url = new URL(location.href);
      url.searchParams.set('logout',1);
      location.replace(url.toString());
    }
    self.show_profile_editor = function(e) {
      hide_user_menu();
      hide_status();
      self.password_editor?.hide();
      if( ! self.profile_editor ) {
        self.profile_editor = profile_editor();
      }
      self.profile_editor.show();
    }
    self.show_password_editor = function(e) {
      hide_user_menu();
      hide_status();
      self.profile_editor?.hide();
      if( ! self.password_editor ) {
        self.password_editor = password_editor();
      }
      self.password_editor.show();
    }
  }

  return self;
}

$(document).ready( function() {
  ce.user_menu = init();
});


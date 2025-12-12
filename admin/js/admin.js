// The following are used by all Admin Dashboard pages

var ace = {};

var has_change_cb = null;
var status_timer=null;
var lock_timer=null;
var expires_timer=null;

const lock_hold_freq  = 20; // seconds (I know, this is a period, not a frequency... )
const check_hold_freq = 10; // seconds (... deal with it. )
var overdue_lock_hold = false;
var active=false;

function internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
                         + "): "+ jqXHR.statusText
                         + "\nPlease let the survey admin know something went wrong."
       );
}

function hide_status()
{
  ace.status.removeClass().addClass('none');
  clearTimeout(status_timer);
  status_timer = setTimeout(() => {ace.status.html('')},750);
}

function show_status(level,msg)
{
  clearTimeout(status_timer);
  status_timer = null;
  ace.status.removeClass('none').addClass(level).html(msg);
}

function handle_tab_change(event)
{
  if(has_change_cb && has_change_cb()) {
    event.preventDefault();

    var tgt_action = event.target.action
    var new_tab = $(event.originalEvent.submitter).val();
    var new_tab_uri = tgt_action + '&tab=' + new_tab;

    var tsm = $('#tab-switch-modal');
    tsm.find('.tsm-type').html('tabs');
    tsm.find('button.cancel').off('click').on('click',function() { 
      tsm.hide();
    });
    tsm.find('button.confirm').off('click').on('click',function() { 
      tsm.hide();
      window.location.replace(new_tab_uri);
    }).html("Switch Tabs");
    tsm.show();
  }
}

function handle_exit_admin(event)
{
  if(has_change_cb && has_change_cb()) {
    var tsm = $('#tab-switch-modal');
    tsm.find('button.cancel').on('click',function() { 
      tsm.hide();
    });
    tsm.find('button.confirm').on('click',function() { 
      tsm.hide();
      window.open(ace.ajaxuri,'ttt_survey');
    });
    tsm.show();
  } else {
    window.open(ace.ajaxuri,'ttt_survey');
  }
}

function handle_admin_login() {
  console.log(ace.ajaxuri);
  window.location = ace.ajaxuri + '?admin=login';
}

function handle_admin_logout() {
  $.ajax( {
    type:'POST',
    url: ace.ajaxuri,
    dataType:'json',
    data:{ 'ajax':'admin/logout_admin','nonce':ace.nonce},
  })
  .done( function(data,status,jqXHR) {
    window.location = ace.ajaxuri + '?admin';
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    internal_error(jqXHR);
  });
}

function handle_user_logout() {
  $.ajax( {
    type:'POST',
    url: ace.ajaxuri,
    dataType:'json',
    data:{ 'ajax':'admin/logout_user','nonce':ace.nonce},
  })
  .done( function(data,status,jqXHR) {
    window.location = ace.ajaxuri + '?admin';
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    internal_error(jqXHR);
  });
}

function check_lock()
{
  if(admin_lock.next_check > 0) {
    $('.admin-lock.timeout').html(seconds_to_mmss(admin_lock.expires_in));
    admin_lock.next_check = admin_lock.next_check - 1;
    admin_lock.expires_in = Math.max(0,admin_lock.expires_in - 1);
    clearTimeout(lock_timer);
    lock_timer = setTimeout(check_lock,1000);
  } else {
    $.ajax({
      type:'POST',
      url: ace.ajaxui,
      dataType:'json',
      data:{'ajax':'admin/obtain_admin_lock','nonce':ace.nonce},
    })
    .done( function(data,status,jqXHR) {
      if(data.has_lock) {
        alert("Admin Dashboard lock has been released. You are free to start making edits");
        window.location.reload();
      } else {
        admin_lock.expires_in = data.expires_in;
        admin_lock.next_check = check_hold_freq;
        $('.admin-lock.timeout').html(seconds_to_mmss(data.expires_in));
        $('.admin-lock.name').html(data.locked_by);
        clearTimeout(lock_timer);
        lock_timer = setTimeout(check_lock,1000);
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    });
  }
}

function seconds_to_mmss(seconds) {
  var min = Math.floor(seconds/60);
  var sec = seconds - 60*min;
  return min.toString() + ":" + sec.toString().padStart(2,'0');
}

function hold_lock()
{
  if(!active) { 
    overdue_lock_hold = true;
    clearTimeout(lock_timer);
    lock_timer = setTimeout(hold_lock,1000*lock_hold_freq);
    return; 
  }
  // reset for next hold_lock timeout
  active = false;
  overdue_lock_hold = false;

  clearTimeout(expires_timer);
  expires_timer = null;

  $.ajax({
    type:'POST',
    url: ace.ajaxui,
    dataType:'json',
    data:{'ajax':'admin/obtain_admin_lock','nonce':ace.nonce},
  })
  .done( function(data,status,jqXHR) {
    if(data.has_lock) {
      if(data.new_token) {
        alert("While you were away, someone else obtained and released a lock on the Admin Dashboard. " +
              "This page will be reloaded to pick up any changes they may have made.");
        window.location.reload();
      }
      clearTimeout(lock_timer);
      lock_timer = setTimeout(hold_lock,1000*lock_hold_freq);
      var warn_in = Math.max(0,data.expires_in-60);
      clearTimeout(expires_timer);
      expires_timer = setTimeout(
        function() { 
          alert("You are about to lose your lock on the Admin Dashboard due to inactivity.");
          active = true;
          hold_lock();
        },
        1000*warn_in
      );
    }
    else {
      alert("While you were away, " + data.locked_by + " obtained a lock on the Admin Dashboard.");
      window.location.reload();
    }
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    internal_error(jqXHR);
  });
}

$(document).ready(
  function($) {

  $('#ttt-body').show();

  ace.status = $('#ttt-status');
  ace.form   = $('#admin-tabs');
  ace.ajaxuri = ace.form.find('input[name=ajaxuri]').val();
  ace.nonce   = ace.form.find('input[name=nonce]').val();

  ace.tabs = {};
  ace.form.find('button').each(
    function() { ace.tabs[$(this).attr('value')] = $(this) }
  );

  $('.left-box *').on('click', handle_exit_admin);
  $('#admin-tabs a.admin.login').on('click',handle_admin_login);
  $('#admin-tabs a.admin.logout').on('click',handle_admin_logout);
  $('#admin-tabs a.user.logout').on('click',handle_user_logout);

  ace.form.on('submit',handle_tab_change);

  if( !admin_lock.has_lock ) {
    admin_lock.next_check = check_hold_freq;
    show_status('warning',"<div>Admin Dashboard is locked by <span class='admin-lock name'>" + 
                admin_lock.locked_by + "</span>.</div><div>" +
                "Unless they renew it, the lock will expire in <span class='admin-lock timeout'>?</span></div>");
    check_lock();
  }
  else {
    // We only want to extend our lock if the user is active.  This includes interacting with
    //   the form AND simply moving the mouse, typing keys, etc.   As interacting with the
    //   form requires the latter, we will add a event handler for each of these which
    //   simply sets the active flag to true.
    active = false;
    const passiveEvents = ['mousemove', 'mousedown', 'scroll', 'touchstart', 'focus'];
    const nonPassiveEvents = ['keydown','visibilitychange'];
    passiveEvents.concat(nonPassiveEvents).forEach( eventName => {
      document.addEventListener(eventName, function() {
        active = true;
        if(overdue_lock_hold) {
          clearTimeout(lock_timer);
          hold_lock();
        }
      }, 
      { passive: passiveEvents.includes(eventName) } 
      )
    });

    hold_lock();
  }
});

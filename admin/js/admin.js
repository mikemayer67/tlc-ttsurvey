// The following are used by all Admin Dashboard pages

var ace = {};

var has_change_cb = null;
var status_timer=null;
var lock_timer=null;
var expires_timer=null;

const lock_hold_freq  = 20; // seconds (I know, this is a period, not a frequency... )
const check_hold_freq = 10; // seconds (... deal with it. )
const lock_warning_lead = 60; // seconds
var overdue_lock_hold = false;
var active=false;

function internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
                         + "): "+ jqXHR.statusText
                         + "\nPlease let the survey admin know something went wrong."
       );
}

function ajax_error_handler(jqXHR,activity)
{
  if(jqXHR.status == 400) {
    // bad request
    //   do nothing other than to warn the user (should be an admin)
    const reason = jqXHR.responseJSON?.reason || 'invalid request';
    alert('Failed to ' + activity + ': ' + reason);
  } else if(jqXHR.status == 401) {
    // authentication error.
    //   send them back to main entry point to reauthenticate
    window.location.href = ce.ajaxuri;
  } else if(jqXHR.status == 403) {
    // forbidden
    //   for the admin dashboard, should mean there was an expired nonce
    //   reload the page for a new nonce value
    alert("Form timed out... reloading the admin dashboard");
    location.reload();
  } else { 
    // not sure what went wrong
    //   treat it as an internal error
    internal_error(jqXHR);
  }
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
    ajax_error_handler(jqXHR,'log out admin');
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
    ajax_error_handler(jqXHR,'log out admin');
  });
}

function admin_alert(line_one,line_two,ok_callback)
{
  var aam = $('#admin-alert-modal');
  aam.find('.line.one').html(line_one);
  aam.find('.line.two').html(line_two);
  aam.find('button').off('click').on('click',function() {
    aam.hide();
    ok_callback();
  });
  aam.show();
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
      url: ace.ajaxuri,
      dataType:'json',
      data:{'ajax':'admin/obtain_admin_lock','nonce':ace.nonce},
    })
    .done( function(data,status,jqXHR) {
      if(data.has_lock) {
        admin_alert(
          "Admin Dashboard lock has been released",
          "You are free to start making edits",
          function() {
            window.location.reload();
          }
        );
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
      ajax_error_handler(jqXHR,'obtain admin lock');
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
    url: ace.ajaxuri,
    dataType:'json',
    data:{'ajax':'admin/obtain_admin_lock','nonce':ace.nonce},
  })
  .done( function(data,status,jqXHR) {
    if(data.has_lock) {
      if(data.new_token) {
        admin_alert(
          "While you were away, someone else obtained and released a lock on the Admin Dashboard.",
          "This page will be reloaded to pick up any changes they may have made.",
          function() {
            window.location.reload();
          }
        );
      }
      clearTimeout(lock_timer);
      lock_timer = setTimeout(hold_lock,1000*lock_hold_freq);
      var warn_in = Math.max(0,data.expires_in-lock_warning_lead);
      clearTimeout(expires_timer);
      expires_timer = setTimeout(raise_lock_warning,1000*warn_in);
    }
    else {
      alert_lost_lock(data.locked_by);
    }
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    ajax_error_handler(jqXHR,'hold admin lock');
  });
}

function raise_lock_warning()
{
  $.ajax({
    type:'POST',
    url: ace.ajaxuri,
    dataType:'json',
    data:{'ajax':'admin/check_admin_lock','nonce':ace.nonce},
  })
  .done( function(data,status,jqXRR) {
    if(!data.has_lock) {
      // should never get here... but just in case
      alert_lost_lock(data.locked_by);
    }
    else if(data.is_expired || data.expires_in<=lock_warning_lead) {
      admin_alert(
        "You are about to lose your lock on the Admin Dashboard due to inactivity.",
        "Acknowledge to try to retain it",
        function() {
          // responding to the alert is an action, so set active to true and try to hold onto the lock
          active = true;
          hold_lock();
        }
      );
    } else {
      // still have lock, but too early to raise the warning
      var warn_in = data.expires_in - lock_warning_lead;
      clearTimeout(expires_timer);
      expires_time = setTimeout(raise_lock_warning,1000*warn_in);
    }
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    ajax_error_handler(jqXHR,'raise lock warning');
  });

}

function alert_lost_lock(locked_by)
{
  admin_alert(
    "While you were away, you lost your lock on the Admiin Dashboard.",
    "It was acquired by "+locked_by,
    function() {
      window.location.reload();
    }
  );
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

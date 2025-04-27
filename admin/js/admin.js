// The following are used by all Admin Dashboard pages

var ace = {};

var has_change_cb = null;
var status_timer=null;
var lock_timer=null;

function internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
                         + "): "+ jqXHR.statusMessage
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
      window.location = new_tab_uri;
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
      window.location = ace.ajaxuri;
    });
    tsm.show();
  } else {
    window.location = ace.ajaxuri;
  }
}

function handle_admin_login() {
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
    if(data.success) {
      show_status('info','Admin logged out');
    } else {
      show_status('error','Failed to log Admin out');
    }
    window.location = ace.ajaxuri + '?admin';
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    internal_error(jqXHR);
  });
}

function check_lock_status()
{
  if(admin_lock.next_check > 0) {
    console.log("check_lock_status: " + admin_lock.next_check);
    $('.admin-lock.timeout').html(admin_lock.next_check);
    admin_lock.next_check = admin_lock.next_check - 1;
    lock_timer = setTimeout(check_lock_status,1000);
  } else {
    console.log("ajax call to obtain lock");
    $.ajax({
      type:'POST',
      url: ace.ajaxui,
      dataType:'json',
      data:{'ajax':'admin/obtain_admin_lock','nonce':ace.nonce},
    })
    .done( function(data,status,jqXHR) {
      console.log("ajax call returned: " + JSON.stringify(data));
      admin_lock.has_lock = data.has_lock;
      admin_locked_by = data.locked_by;
      if(data.has_lock) {
        alert("Admin Dashboard lock has been released. You are free to start making edits");
        window.location.reload();
      } else {
        $('.admin-lock.timeout').html(admin_lock.next_check);
        $('.admin-lock.name').html(admin_lock.locked_by);
        admin_lock.next_check=60;
        lock_timer = setTimeout(check_lock_status,1000);
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    });
  }
}

function hold_lock()
{
  console.log("hold lock");
  $.ajax({
    type:'POST',
    url: ace.ajaxui,
    dataType:'json',
    data:{'ajax':'admin/obtain_admin_lock','nonce':ace.nonce},
  })
  .done( function(data,status,jqXHR) {
    console.log("ajax call returned: " + JSON.stringify(data));
    admin_lock.has_lock = data.has_lock;
    if(data.has_lock) {
      if(data.new_token) {
        alert("While you were away, someone else obtained a lock on the Admin Dashboard.\n" +
              "This page will be reloaded to pick up any changes they may have made.");
        window.location.reload();
      }
      lock_timer = setTimeout(hold_lock,30000);
    }
    else {
      admin_lock.locked_by = data.locked_by;
      alert("While you were away, " + data.locked_by + " obtained a lock on the Admin Dashboard.\n" +
            "This page will be reloaded.");
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

  $('span.ttt-title-box *').on('click', handle_exit_admin);
  $('#admin-tabs a.admin.login').on('click',handle_admin_login);
  $('#admin-tabs a.admin.logout').on('click',handle_admin_logout);

  ace.form.on('submit',handle_tab_change);

  if( !admin_lock.has_lock ) {
    admin_lock.next_check = 60;
    show_status('warning',"<div>Admin Dashboard is locked by <span class='admin-lock name'>" + 
                admin_lock.locked_by + "</span>.</div><div>" +
                "Will check again in <span class='admin-lock timeout'>?</span> seconds</div>");
    check_lock_status();
  }
  else {
    setTimeout(hold_lock,30000);
  }
});

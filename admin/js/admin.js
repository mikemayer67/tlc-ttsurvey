// The following are used by all Admin Dashboard pages

var ace = {};

var has_change_cb = null;
var status_timer=null;

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
    tsm.find('button.cancel').on('click',function() { 
      tsm.hide();
    });
    tsm.find('button.confirm').on('click',function() { 
      tsm.hide();
      window.location = new_tab_uri;
    });
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
});

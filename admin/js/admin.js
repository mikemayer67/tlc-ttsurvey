 /**
 * @typedef {Object} AdminInitArgs
 * @property {null|string} [ajaxuri]
 * @property {null|string} [nonce]
 * @property {null|function(): boolean} [has_change_hook]
 */

let _initialized = false;

let $_admin_status;
let _status_timer;

let $_admin_navbar;
const $_admin_tabs = {};

let _ajaxuri;
let _admin_nonce;
let _has_change_hook;

/**
 * Initializes the shared admin javascript functionality
 * @param {AdminInitArgs} [kwargs={}]
 */
export default function( kwargs={} )
{
  if(_initialized) { throw new Error('admin.js initialized more than once'); }

  _has_change_hook = kwargs.has_change_hook;

  $('#ttt-body').show();

  $_admin_status = $('#ttt-status');
  $_admin_navbar = $('#admin-tabs');

  $_admin_navbar.find('button').each(
    function() { $_admin_tabs[$(this).attr('value')] = $(this) }
  );

  _ajaxuri     = kwargs.ajaxuri ?? $_admin_navbar.find('input[name=ajaxuri]').val();
  _admin_nonce = kwargs.nonce   ?? $_admin_navbar.find('input[name=nonce]').val();

  $('.left-box *').on('click', handle_exit_admin);
  $('#admin-tabs a.admin.login').on('click',handle_admin_login);
  $('#admin-tabs a.admin.logout').on('click',handle_admin_logout);
  $('#admin-tabs a.user.logout').on('click',handle_user_logout);

  $_admin_navbar.on('submit',handle_tab_change);

  _initialized = true;
}

/**
 * Add this to the top of all exported functions (other than init) to 
 *   raise an Error if it is called prior to initialization
 * @param {string} function_name 
 */
function requires_initialization(function_name)
{
  if(!_initialized) {
    throw new Error('admin.'+function_name+' called before initialization of admin.js');
  }
}

function handle_exit_admin(event)
{
  if( _has_change_hook?.() ) {
    const tsm = $('#tab-switch-modal');
    if (!tsm) { throw new Error('admin tab DOM is missing #tab-switch-modal <div>'); }
    tsm.find('button.cancel').on('click',function() { 
      tsm.hide();
    });
    tsm.find('button.confirm').on('click',function() { 
      tsm.hide();
      window.open(_ajaxuri,'ttt_survey');
    });
    tsm.show();
  } else {
    window.open(_ajaxuri,'ttt_survey');
  }
}

function handle_admin_login() 
{
  console.log(_ajaxuri);
  window.location = _ajaxuri + '?admin=login';
}

function handle_admin_logout() {
  $.ajax( {
    type:'POST',
    url: _ajaxuri,
    dataType:'json',
    data:{ 'ajax':'admin/logout_admin','nonce':_admin_nonce},
  })
  .done( function(data,status,jqXHR) {
    window.location = _ajaxuri + '?admin';
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    ajax_error_handler(jqXHR,'log out admin');
  });
}

function handle_user_logout() {
  $.ajax( {
    type:'POST',
    url: _ajaxuri,
    dataType:'json',
    data:{ 'ajax':'admin/logout_user','nonce':_admin_nonce},
  })
  .done( function(data,status,jqXHR) {
    window.location = _ajaxuri + '?admin';
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    ajax_error_handler(jqXHR,'log out user');
  });
}

function handle_tab_change(event)
{
  if(_has_change_hook?.()) {
    event.preventDefault();

    var tgt_action = event.target.action
    var new_tab = $(event.originalEvent.submitter).val();
    var new_tab_uri = tgt_action + '&tab=' + new_tab;

    var tsm = $('#tab-switch-modal');
    if (!tsm) { throw new Error('admin tab DOM is missing #tab-switch-modal <div>'); }
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

/**
 * Hides the admin status
 */
function hide_status()
{
  $_admin_status.removeClass().addClass('none');
  clearTimeout(_status_timer);
  _status_timer = setTimeout(() => {$_admin_status.html('')},750);
}

/**
 * Displays the admin status, setting its display level (color) and
 *   message.
 * @param {'error'|'warning'|'info"'} level 
 * @param {string} msg 
 */
function show_status(level,msg)
{
  clearTimeout(_status_timer);
  _status_timer = null;
  $_admin_status.removeClass('none').addClass(level).html(msg);
}


/**
 * Displays the common admin alert modal dialog box and call the (optional)
 *   callback when the ok button is pressed
 * @param {string} line_one 
 * @param {null|string} line_two 
 * @param {null|function} ok_callback 
 */
export function admin_alert(line_one,line_two=null,ok_callback=null)
{
  var aam = $('#admin-alert-modal');
  if(!aam) { throw new Error('admin tab DOM is missing #admin-alert-modal <div>'); }

  aam.find('.line.one').html(line_one);
  if(line_two) { aam.find('.line.two').html(line_two).show(); }
  else         { aam.find('.line.two').hide(); }

  aam.find('button').off('click').on('click',function() {
    aam.hide();
    if(ok_callback) { ok_callback(); }
  });
  aam.show();
}

/**
 * Common AJAX error handler for the admin dashboard
 * @param {JQuery.jqXHR} jqXHR 
 * @param {string} activity 
 */
export function ajax_error_handler(jqXHR,activity)
{
  requires_initialization('ajax_error_handler');

  if(jqXHR.status == 400) {
    // bad request
    //   do nothing other than to warn the user (should be an admin)
    const reason = jqXHR.responseJSON?.reason || 'invalid request';
    alert('Failed to ' + activity + ': ' + reason);
  } else if(jqXHR.status == 401) {
    // authentication error.
    //   send them back to main entry point to reauthenticate
    window.location.href = _ajaxuri;
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

/**
 * Common AJAX internal error handler for the admin dashboard
 * @param {JQuery.jqXHR} jqXHR 
 */
export function internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
                         + "): "+ jqXHR.statusText
                         + "\nPlease let the survey admin know something went wrong."
       );
}

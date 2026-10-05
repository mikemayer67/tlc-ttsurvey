import AdminLock from './admin_lock.js';

/**
 * @typedef {Object} AdminInitArgs
 * @property {null|function(): boolean} [is_dirty_fn]
 */

let _initialized = false;

let $_admin_status;
let _status_timer;

let _admin_nonce;
let $_admin_navbar;
const $_admin_tabs = {};

let _is_dirty_fn;

let _admin_lock;

/**
 * Initializes the shared admin javascript functionality
 * @param {AdminInitArgs} [kwargs={}]
 */
export default function init(kwargs={})
{
  if(_initialized) { throw new Error('admin.js initialized more than once'); }

  _is_dirty_fn = kwargs.is_dirty_fn;

  $('#ttt-body').show();

  $_admin_status = $('#ttt-status');
  $_admin_navbar = $('#admin-tabs');
  
  _admin_nonce = $_admin_navbar.find('input[name=nonce]').val();

  $_admin_navbar.find('button').each(
    function() { $_admin_tabs[$(this).attr('value')] = $(this) }
  );

  $('.left-box *').on('click', handle_exit_admin);
  $('#admin-tabs a.admin.login').on('click',handle_admin_login);
  $('#admin-tabs a.admin.logout').on('click',handle_admin_logout);
  $('#admin-tabs a.user.logout').on('click',handle_user_logout);

  $_admin_navbar.on('submit',handle_tab_change);

  // Note tbat admin_lock is a global set up in pip via a <script> tag
  if( !admin_lock.has_lock ) {
    show_status(
      'warning',
      "<div>Admin Dashboard is locked by <span class='admin-lock name'>"
      + admin_lock.locked_by + "</span>.</div><div>"
      + "Unless they renew it, the lock will expire in <span class='admin-lock timeout'>?</span></div>"
    );
  }
  _admin_lock = AdminLock( admin_lock, _admin_nonce,
    {
      'lockAvailable': handle_lock_available,
      'lockKeyChanged': handle_lock_key_changed,
      'lockExpiring': handle_expiring_lock,
      'lostLock': handle_lost_lock,
      'updateLockState': handle_lock_state_update,
    }
  );
  
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
  if( _is_dirty_fn?.() ) {
    const tsm = $('#tab-switch-modal');
    
    tsm.find('button.cancel').on('click',function() { 
      tsm.hide();
    });
    tsm.find('button.confirm').on('click',function() { 
      tsm.hide();
      window.open(window.location.pathname,'ttt_survey');
    });
    tsm.show();
  } else {
    window.open(window.location.pathname,'ttt_survey');
  }
}

function handle_admin_login() 
{
  window.location.assign( window.location.pathname + '?admin=login' );
}

function handle_admin_logout() {
  $.ajax( {
    type:'POST',
    url: window.location.pathname,
    dataType:'json',
    data:{ 'ajax':'admin/logout_admin','nonce':_admin_nonce},
  })
  .done( function(data,status,jqXHR) {
    window.location.assign( window.location.pathname + '?admin' );
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    ajax_error_handler(jqXHR,'log out admin');
  });
}

function handle_user_logout() {
  $.ajax( {
    type:'POST',
    url: window.location.pathname,
    dataType:'json',
    data:{ 'ajax':'admin/logout_user','nonce':_admin_nonce},
  })
  .done( function(data,status,jqXHR) {
    window.location.assign( window.location.pathname + '?admin' );
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    ajax_error_handler(jqXHR,'log out user');
  });
}

function handle_tab_change(event)
{
  if(_is_dirty_fn?.()) {
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

function handle_lock_available() 
{
  admin_alert(
    "Admin Dashboard lock has been released",
    "You are free to start making edits",
    function () { window.location.reload(); }
  );
}

function handle_lock_key_changed()
{
  admin_alert(
    "While you were away, someone else obtained and released a lock on the Admin Dashboard.",
    "This page will be reloaded to pick up any changes they may have made.",
    function() { window.location.reload(); }
  );
}

function handle_expiring_lock()
{
  admin_alert(
    "You are about to lose your lock on the Admin Dashboard due to inactivity.",
    "Acknowledge to try to retain it",
    function() {
      // responding to the alert is an action, so let the admin lock know
      _admin_lock.jiggle();
    }
  );
}

function handle_lost_lock(locked_by)
{
  admin_alert(
    "While you were away, you lost your lock on the Admiin Dashboard.",
    "It was acquired by "+locked_by,
    function() { window.location.reload(); }
  );
}

function handle_lock_state_update(timeout, locked_by=null) 
{
  const min = Math.floor(timeout/60);
  const sec = timeout - 60*min;
  const mmss = min.toString() + ":" + sec.toString().padStart(2,'0');
  // .admin-lock elements created in admin init function above
  $('.admin-lock.timeout').html(mmss);
  if(locked_by) { $('.admin-lock.name').html(locked_by); }
}

/**
 * Hides the admin status
 */
export function hide_status()
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
export function show_status(level,msg)
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
  const aam = $('#admin-alert-modal');

  aam.find('.line.one').html(line_one);
  if(line_two) { aam.find('.line.two').html(line_two).show(); }
  else         { aam.find('.line.two').hide(); }

  aam.find('button').prop('disabled',false).off('click').on('click',function() {
    aam.hide();
    if(ok_callback) { ok_callback(); }
  });
  aam.show();
}

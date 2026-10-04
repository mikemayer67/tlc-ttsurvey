/**
 * The admin lock function sets up an object that supports the admin dashboard
 *   locking mechanism.  It handles the ajax calls necessary to obtain and
 *   keep the lock as well as the ajax calls necessary to watch for release
 *   of the lock in other browswer sessions.
 *
 * @typedef {Object} AdminLockState
 * @property {boolean} has_lock
 * @property {boolean} [is_expired]
 * @property {string}  [locked_by]
 * @property {number}  [expires_in]
 * @property {string}  [new_token]
 * 
 * @typedef {Object} AdminLockAPI
 * @property {(void) => void} jiggle - informs AdminLock of user activity
 * 
 * @typedef { AdminLockState & AdminLockAPI } AdminLock
 *
 * @typedef { (void) => void } AdminLockCallback
 * @typedef { (timeout:number, locked_by?:string|null) => void } LockStateCallback
 * @typedef { (locked_by:string) => void } LostLockCallback
 * 
 * @typedef {Object} AdminLockCallbacks
 * @property {AdminLockCallback} [lockAvailable]
 * @property {AdminLockCallback} [lockKeyChanged]
 * @property {AdminLockCallback} [lockExpiring]
 * @property {LostLockCallback}  [lostLock]
 * @property {LockStateCallback} [updateLockState]
 */

/**
 * Initializes an AdminLock object
 * @param {AdminLockState} lock_state
 * @param {string} admin_nonce
 * @param {AdminLockCallbacks} callbacks
 * @returns {AdminLock}
 */
export default function init(lock_state, admin_nonce, callbacks={})
{
  console.log("admin_lock.js: init()");
  const LOCK_HOLD_PERIOD      = 20; // seconds
  const WATCH_FOR_LOCK_PERIOD = 10; // seconds
  const LOCK_WARNING_LEAD     = 60; // seconds

  let status_heartbeat = null;
  let lock_timer       = null;
  let warning_timer    = null;

  let user_is_idle     = false; // no registered events since last hold_lock call
  let user_was_idle    = false;

  // fill in missing callbacks with no-op functions
  callbacks = {
    lockAvailable:    () => {},
    lockKeyChanged:   () => {},
    lockExpiring:     () => {},
    lostLock:         () => {},
    updateLockState:  () => {},
    ...callbacks
  };

  // set up event handlers to watch for user activity
  const passiveEvents = ['mousemove', 'mousedown', 'scroll', 'touchstart', 'focus'];
  const nonPassiveEvents = ['keydown','visibilitychange'];
  const allEvents = passiveEvents.concat(nonPassiveEvents);
  allEvents.forEach( eventName => { 
    const isPassive = passiveEvents.includes(eventName);
    document.addEventListener( 
      eventName, handle_user_action, {passive:isPassive} 
    );
  });

  function handle_user_action() 
  {
    console.log("admin.js: handle_user_action()");
    user_is_idle = false;
    if (user_was_idle) {
      // last call to hold_lock did not attempt to reacquire lock due
      //   to inactivity... we have exceeded the timeout and should attempt 
      //   to reacqire the lock immediately
      clearTimeout(lock_timer);
      hold_lock();
    }
  }

  const self = {
    jiggle: handle_user_action, 
    ...lock_state
  };

  /**
   * Attempts to hold the admin lock by renewing it on the server
   */
  function hold_lock()
  {
    console.log("admin.js: hold_lock()");
    // only attempt to renew lock if user is not idle
    if(user_is_idle) {
      user_was_idle = true;
      clearTimeout(lock_timer);
      lock_timer = setTimeout(hold_lock,1000*LOCK_HOLD_PERIOD);
      return;
    }

    // reset idle flags
    user_was_idle = false;
    user_is_idle  = true;

    // reset the timer for lock expiration timer
    clearTimeout(warning_timer);
    warning_timer = null;

    // attempt to renew lock
    $.ajax({
      type:'POST',
      url:window.location.pathname,
      dataType:'json',
      data:{ajax:'admin/obtain_admin_lock',nonce:admin_nonce},
    })
    .done( function(data,status,jqXHR) {
      console.log("admin.js: hold_lock/obtain_admin_lock done(): "+data);
      if(!data.has_lock) 
      {
        // lock has been lost
        callbacks.lostLock(data.locked_by);
      } 
      else {
        // we still have the lock
        if(data.new_token) { 
          // .. but we lost it for a bit
          callbacks.lockKeyChanged(); 
          // note that there is a good chance that the lockKeyChanged
          //   callback will not return as it will probably reload the page
        }
        
        // queue up next attempt to hold the lock
        clearTimeout(lock_timer);
        lock_timer = setTimeout(hold_lock,1000*LOCK_HOLD_PERIOD);
        
        // raise or queue up the warning for expiring lock
        clearTimeout(warning_timer);
        if(data.expires_in <= LOCK_WARNING_LEAD) {
          raise_lock_warning();
        } else {
          warning_timer = setTimeout(
            raise_lock_warning,
            1000*(data.expires_in - LOCK_WARNING_LEAD)
          );
        }
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      ajax_error_handler(jqXHR,'hold admin lock');
    });
  }

  /**
   * Determines whether a warning should be raised that the lock is about to expire.
   * There are three possibilities:
   *   - lock already obtained by someone else (lost lock)
   *   - lock is or is about to expir (raise warning)
   *   - too soon to raise warning (requeue this function)
   */
  function raise_lock_warning()
  {
    console.log("admin.js: raise_lock_arning")
    $.ajax({
      type: 'POST',
      url:window.location.pathname,
      dataType: 'json',
      data: { ajax: 'admin/check_admin_lock', nonce: admin_nonce },
    })
    .done(function (data, status, jqXRR) {
      console.log("admin.js: raise_lock_warning/check_admin_lock: " + data);
      if(!data.has_lock) {
        // should never get here if lock mechanism is working... but just in case
        callbacks.lostLock(data.locked_by);
      }
      else if(data.is_expired || data.expires_in<=LOCK_WARNING_LEAD) {
        // raise warning
        callbacks.lockExpiring();
      } 
      else { // too soon (requeue raise_lock_warning)
        clearTimeout(warning_timer);
        warning_timer = setTimeout(
          raise_lock_warning,
          1000*(data.expires_in - LOCK_WARNING_LEAD)
        );
      }
    })
    .fail(function(jqXHR,textStatus,errorThrown) {
      ajax_error_handler(jqXHR,'raise lock warning');
    });
  }

  /**
   * Invoked under a timer to execute the AJAX call to try to obtain the
   *   admin lock when we don't currently have it
   */
  function watch_for_lock()
  {
    console.log("admin.js: watch_for_lock");
    clearInterval(status_heartbeat);
    clearTimeout(lock_timer);
    status_heartbeat = lock_timer = null;

    $.ajax({ 
      type:'POST', 
      url:window.location.pathname,
      dataType:'json', 
      data:{ajax:'admin/obtain_admin_lock',nonce:admin_nonce}, 
    })
    .done( function(data,status,jqXHR) {
      console.log("admin.js: watch_for_lock/obtain_admin_lock: " + data);
      if(data.has_lock) {
        // we got the lock, invoke the lockAvailable callback to allow
        //   the clients to respond accordingly.
        callbacks.lockAvailable();
      } else {
        self.expires_in = data.expires_in;
        callbacks.updateLockState(self.expires_in, data.locked_by);
        status_heartbeat = setInterval(function() {
          self.expires_in = self.expires_in - 1;
          callbacks.updateLockState(self.expires_in);
          if(self.expires_in<=0) {
            clearInterval(status_heartbeat);
            status_heartbeat = null;
          }
        },1000);
        const next_check = Math.max(1,Math.min(self.expires_in,WATCH_FOR_LOCK_PERIOD));
        lock_timer = setTimeout(watch_for_lock,1000*next_check);
      }
    })
    .fail( function(jqHXR,textStatus,errorThrown) {
      ajax_error_handler(jqHXR,'obtain admin lock');
    });
  }

  clearTimeout(lock_timer);
  if(self.has_lock) {
    lock_timer = setTimeout(hold_lock,1000*LOCK_HOLD_PERIOD);
  } else {
    watch_for_lock();
  }

  return self;
}

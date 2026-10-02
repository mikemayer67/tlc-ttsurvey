/**
 * The admin lock function sets up an object that supports the admin dashboard
 *   locking mechanism.  It handles the ajax calls necessary to obtain and
 *   keep the lock as well as the ajax calls necessary to watch for release
 *   of the lock in other browswer sessions.
 *
 * @typedef {Object} AdminLock
 * TODO: Flesh the following out as methods are added
 * @property { }
 * 
 * @typedef {Object} AdminLockStatus
 * @property {boolean} has_lock
 * @property {boolean} [is_expired]
 * @property {string}  [locked_by]
 * @property {number}  [expires_in]
 * @property {string}  [new_token]

 * @typedef { (void) => void } AdminLockCallback
 * @typedef { (timeout:number, locked_by?:string|null) => void } UpdateLockStatus
 * 
 * @typedef {Object} AdminLockCallbacks
 * @property {AdminLockCallback} [lockAvailable]
 * @property {UpdateLockStatus}  [updateLockStatus]
 */

/**
 * Initializes an AdminLock object
 * @param {AdminLockStatus} status
 * @param {AdminLockCallbacks} callbacks
 * @returns {AdminLock}
 */
export default function init(status, callbacks={})
{
  const LOCK_HOLD_FREQ  = 20; // seconds (I know, this is a period, not a frequency... )
  const WATCH_FOR_LOCK_FREQ = 10; // seconds (... deal with it)
  const LOCK_WARNING_LEAD = 60; // seconds

  let status_heartbeat = null;
  let lock_timer       = null;

  // fill in missing callbacks with no-op functions
  callbacks = {
    locksReleased:    () => {},
    updateLockStatus: () => {},
    ...callbacks
  };

  let next_check_for_lock = AJAX_FREQ_WITHOUT_LOCK;

  const self = {
    status: {...status},
  };

  /**
   * Invoked under a timer to execute the AJAX call to try to obtain the
   *   admin lock when we don't currently have it
   */
  function watch_for_lock()
  {
    clearInterval(status_heartbeat);
    clearTimeout(lock_timer);

    $.ajax({ 
      type:'POST', 
      url:window.location.pathname,
      dataType:'json', 
      data:{'ajax':'admin/obtain_lock'} 
    })
    .done( function(data,status,jqXHR) {
      if(data.has_lock) {
        callbacks.lockAvailable();
      } else {
        self.status.expires_in = data.expires_in;
        callbacks.updateLockStatus(self.status.expires_in, data.locked_by);
        status_heartbeat = setInterval(function() {
          self.status.expires_in = self.status.expires_in - 1;
          callbacks.updateLockStatus(self.status.expires_in);
        },1000);
        lock_timer = setTimeout(watch_for_lock,1000*WATCH_FOR_LOCK_FREQ);
      }
    })
    .fail( function(jqHXR,textStatus,errorThrown) {
      ajax_error_handler(jqHXR,'obtain admin lock');
    });
  }

  return self;
}

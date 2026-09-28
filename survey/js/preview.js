const lock_hold_freq  = 20; // seconds (I know, this is a period, not a frequency... )
var lock_timer=null;
var overdue_lock_hold = false;
var active=false;

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

  $.ajax({
    type:'POST',
    url: ajaxuri,
    dataType:'json',
    data:{'ajax':'admin/obtain_admin_lock'},
  })
  .done( function(data,status,jqXHR) {
    clearTimeout(lock_timer);
    lock_timer = setTimeout(hold_lock, 1000 * lock_hold_freq);
  })
  .fail( function(jqXHR,textStatus,errorThrown) {
    alert("Failed to hold admin lock: " + jqXHR.status);
  });
}

$(document).ready( function() {
  if(admin_lock.has_lock) {
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
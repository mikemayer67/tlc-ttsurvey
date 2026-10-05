import AdminLock from '../../admin/js/admin_lock.js';

function preview_alert(line_one,line_two,ok_callback=null)
{
  const pam = $('#preview-lock-modal');

  pam.find('.line.one').html(line_one);
  pam.find('.line.two').html(line_two);
  pam.find('button').prop('disabled',false).off('click').on('click',function() {
    pam.hide();
    if(ok_callback) { ok_callback(); }
  });
  pam.show();
}

let _admin_lock = AdminLock(
  admin_lock, admin_nonce, 
  {
    lockKeyChanged: function() { 
      preview_alert(
        "While you were away, someone else obtained and released a lock on the Admin Dashboard.",
        "Youl may want to reloaded the page to pick up any changes they may have made."
      );
    },
    lockExpiring: function() { 
      preview_alert(
        "You are about to lose your lock on the Admin Dashboard due to inactivity.",
        "Hit OK to try to retain it",
        function() { _admin_lock.jiggle(); }
      );
    },
  }
);

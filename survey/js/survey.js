var ce = {};

function internal_error(jqXHR)
{
  alert(
    "Internal error (#" + jqXHR.status + "): "
    + jqXHR.statusText
    + "\nPlease let the survey admin know something went wrong."
  );
}

function hide_status()
{
  ce.status.removeClass().addClass('none');
  setTimeout(() => {ce.status.html('')},0);
}

function show_status(level,msg)
{
  ce.status.removeClass().addClass(level).html(msg);
}


function setup_hints()
{
  ce.hint_toggles = $('label.hint-toggle');

  if (window.matchMedia("(hover: hover)").matches) 
  {
    ce.hint_toggles.on('pointerenter', function(e) {
      const id = $(this).data('question-id');
      const hintlock = $('#hint-lock-'+id);
      const hint = $('#hint-'+id);

      // reentering the trigger icon when the hint is locked visible should hide it
      // otherwise, this action should show the hint
      // either way, the trigger should be unlocked
      hint.toggleClass('hovering', !hintlock.isChecked());
      hintlock.setUnchecked();
    });

    ce.hint_toggles.on('pointerleave', function(e) {
      const id = $(this).data('question-id');
      const hint = $('#hint-'+id);
      hint.removeClass('hovering')
    });
  }
}

function enable_radio_button_deselect()
{
  const radio_buttons = $('#ttt-body form input[type=radio]');

  radio_buttons.each(function() {
    $(this).data('is-checked',$(this).is(':checked'));
  });

  radio_buttons.on('click', function(e) {
    const rb = $(this);
    const is_checked  = rb.is(':checked');
    const was_checked = rb.data('is-checked');
    if( is_checked && was_checked ) {
      rb.data('is-checked',false);
      rb.prop('checked',false);
    } else if(is_checked) {
      rb.data('is-checked',true);
      radio_buttons.filter('input[type=radio]').not(rb).data('is-checked',false);
    } else {
      rb.data('is-checked',false);
    }
  });
}

//
// User Menu Hooks
//

function logout_user(e)
{
  const url = new URL(location.href);
  url.searchParams.set('logout',1);
  location.replace(url.toString());
}

//
// Ready / Setup
//

$(document).ready( function() {
  ce.navbar = $('#ttt-navbar');
  ce.submit = $('#ttt-body form input.submit');
  ce.revert = $('#ttt-body form input.revert');
  ce.status = $('#ttt-status');

  setup_hints();

  ce.confirm_logout = false;

  ce.revert.removeClass('hidden');
  ce.status.on('click',hide_status);

  enable_radio_button_deselect();
});

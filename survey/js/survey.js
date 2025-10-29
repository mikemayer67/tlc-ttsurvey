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
    handle_input_change();
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
// Caching of initial responses
//

function cache_input_values()
{
  ce.checkable.each( function() {
    const input = $(this);
    input.data('cached',input.is(':checked'));
  });
  ce.inputs.each( function() {
    const input = $(this);
    input.data('cached',input.val());
  });
}

function restore_cached_values()
{
  ce.checkable.each( function() {
    const input = $(this);
    input.prop('checked', input.data('cached'));
  });
  ce.inputs.each( function() {
    const input = $(this);
    input.val(input.data('cached'));
  });
  ce.dirty = false;
  update_submit_buttons();
}

function has_changes()
{
  ce.dirty = false;
  ce.checkable.each( function() {
    const input   = $(this);
    const cached  = input.data('cached');
    const current = input.is(':checked');
    if( current !== cached ) {
      ce.dirty = true;
      return false;
    }
  });
  if(ce.dirty) { return true; }
  ce.inputs.each( function() {
    const input   = $(this);
    const cached  = input.data('cached');
    const current = input.val();
    if( current !== cached ) {
      ce.dirty = true;
      return false;
    }
  });
  return ce.dirty;
}

function handle_cancel(e)
{
  e.preventDefault();
  restore_cached_values();
}

function handle_save()
{
  // Cache the scroll position before submitting the form for saving a draft
  // Do NOT call preventDefault as this would block the actual form submission
  cache_scroll_position();
}

//
// Change tracking
//

function handle_input_change(e)
{
  const dirty = has_changes();
  update_submit_buttons();
}

function update_submit_buttons()
{
  // the submit, save, and cancel buttons should be disabled if there are no changes.
  ce.submit.prop('disabled',!ce.dirty);
  ce.save.prop(  'disabled',!ce.dirty);
  ce.cancel.prop('disabled',!ce.dirty);
}

//
// Form state (detail toggling + vertical scroll)
//

const cache_key = 'tlc-tt-survey-ui-state';

function setup_toggle_cache(e)
{
  // create a set of the open details sections
  let open_details = new Set();
  let scroll_pos   = 0;

  // check if there is an existing cache in memory and if it applies
  //   is so, use this to update the open_details set
  let cache = localStorage.getItem(cache_key);
  if(cache) {
    cache = JSON.parse(cache);

    const nonce = ce.form.find('input[name=prior-nonce]').val();

    if(nonce === cache.nonce) { 
      open_details = new Set(cache.open_details || []);
      scroll_pos = cache.scroll_pos || 0; 

      ce.details.each(function() {
        const section = $(this).data('section');
        $(this).prop( 'open', open_details.has(section) );
      });
    }

    if(scroll_pos > 0) {   
      Promise.resolve().then(() => window.scrollTo(0, scroll_pos));
    }
  }

  // start a new cache and it it local storage
  cache = { nonce:ce.nonce, open_details: [...open_details], scroll_pos };
  localStorage.setItem(cache_key, JSON.stringify(cache));
}

function update_toggle_cache(e)
{
  let cache = localStorage.getItem(cache_key);
  if(!cache) { return; }

  cache = JSON.parse(cache);
  if(cache.nonce !== ce.nonce) { return; }

  const section = $(this).data('section');
  const is_open = $(this).prop('open');

  const open_details = new Set(cache.open_details || []);
  if(is_open) { open_details.add(section);    }
  else        { open_details.delete(section); }

  cache.open_details = [...open_details];

  localStorage.setItem(cache_key, JSON.stringify(cache));
}

function cache_scroll_position() 
{
  let cache = localStorage.getItem(cache_key);
  if(!cache) { return; }

  cache = JSON.parse(cache);
  if(cache.nonce !== ce.nonce) { return; }

  cache.scroll_pos = window.scrollY ?? window.pageYOffset ?? 0;
  localStorage.setItem(cache_key, JSON.stringify(cache));
}

//
// Ready / Setup
//

$(document).ready( function() {
  ce.navbar  = $('#ttt-navbar');
  ce.status  = $('#ttt-status');
  ce.form    = $('#ttt-body form');
  ce.nonce   = ce.form.find('input[name=nonce]').val();
  ce.details = ce.form.find('details');
  ce.submit  = ce.form.find('button.submit');
  ce.save    = ce.form.find('button.save');
  ce.delete  = ce.form.find('button.delete');
  ce.cancel  = ce.form.find('button.cancel');

  ce.checkable = ce.form.find('input:is([type=checkbox],[type=radio])[name]').not('.hint-toggle');
  ce.inputs    = ce.form.find('input[name], textarea[name]').not('[type=hidden]').not('[type=checkbox]').not('[type=radio]');

  setup_hints();

  ce.confirm_logout = false;

  ce.status.on('click',hide_status);

  setup_toggle_cache();
  ce.details.on('toggle',update_toggle_cache);

  ce.cancel.on('click',handle_cancel);
  ce.save.on('click',handle_save);

  enable_radio_button_deselect();

  ce.dirty = false;
  update_submit_buttons();
  ce.checkable.on('change',handle_input_change);
  ce.inputs.on(   'input', handle_input_change);

  cache_input_values();
});

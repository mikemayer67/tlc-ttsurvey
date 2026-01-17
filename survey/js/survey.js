var ce = {};

function internal_error(jqXHR)
{
  alert(
    "Internal error (#" + jqXHR.status + "): "
    + jqXHR.statusText
    + "\nPlease let the survey admin know something went wrong."
  );
}

function ajax_error_hander(jqXHR,activity)
{
  if(jqXHR.status == 400) {
    // bad request
    //   do nothing other than to warn the user (should be an admin)
    const log_id = jqXHR.responseJSON?.log_id || '121467';
    alert(
      'Failed to ' + activity + ".\n" + 
      "Please let the survey admin something went wrong\n" +
      "  and givem them logID=" + log_id
    );
  } 
  else if(jqXHR.status == 401) {
    // authentication error.
    //   send them back to main entry point to reauthenticate
    window.location.href = ce.ajaxuri;
  } 
  else if(jqXHR.status == 403) {
    // forbidden
    //   for the survey form, should mean there was an expired nonce
    //   reload the page for a new nonce value
    alert("Form timed out... reloading the survey");
    location.reload();
  } 
  else { 
    // not sure what went wrong
    //   treat it as an internal error
    internal_error(jqXHR);
  }
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
// Navbar and User Menu Hooks
//

function handle_admin_link(e)
{
  e.preventDefault();
  const href = $(this).data('href');
  const win = window.open(href,'ttt_admin');
  if(win) { win.focus(); }
}

function logout_user(e)
{
  const url = new URL(window.location.href);
  url.search='';
  url.searchParams.set('logout',1);
  window.open(url.toString,'ttt_survey');
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
  // If currently showing the latest submitted responses:
  //   the submit, save, and cancel buttons should be disabled if there are no changes.
  // If currently showing a working draft:
  //   the submit button should always be enabled
  //   the save and cancel buttons should be disabled if there are no changes.
  ce.save.prop(  'disabled',!ce.dirty);
  ce.cancel.prop('disabled',!ce.dirty);
  if(ttt_user_responses.state === 'submitted') {
    ce.submit.prop('disabled',!ce.dirty);
  } else {
    ce.submit.prop('disabled',false);
  }
  ce.confirm_logout = ce.dirty;
}

//
// Form state (detail toggling + vertical scroll)
//

const cache_key = 'tlc-tt-survey-ui-state';

function setup_toggle_cache(e)
{
  // create a set of the open details sections
  let open_section = undefined;
  let scroll_pos   = 0;

  // check if there is an existing cache in memory and if it applies
  //   is so, use this to update the open_details and scroll position
  let cache = localStorage.getItem(cache_key);
  if(cache) {
    cache = JSON.parse(cache);

    const nonce = ce.form.find('input[name=prior-nonce]').val();

    if(nonce === cache.nonce) { 
      open_section = cache.open_details || undefined;
      scroll_pos   = cache.scroll_pos   || 0; 

      ce.details.each(function() {
        const section = $(this).data('section');
        $(this).prop( 'open', section === open_section );
      });
    }

    if(scroll_pos > 0) {   
      Promise.resolve().then(() => window.scrollTo(0, scroll_pos));
    }
  }

  // start a new cache and it it local storage
  cache = { nonce:ce.nonce, open_details:open_section, scroll_pos };
  localStorage.setItem(cache_key, JSON.stringify(cache));
}

function update_toggle_cache(e)
{
  const section = $(this).data('section');
  const is_open = $(this).prop('open');

  if(is_open) {
    ce.details.each(function() {
      if( $(this).data('section') !== section ) { $(this).prop('open',false); }
    })
  }

  let cache = localStorage.getItem(cache_key);
  if(!cache) { return; }

  cache = JSON.parse(cache);
  if(cache.nonce !== ce.nonce) { return; }

  cache.open_details = section;

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
// Survey heartbeat
// Sends an ajax request every 10 minutes in an attempt to keep the session alive
//

let heartbeatTimer = null;
const startHeartbeat = (() => {
  let timerID = null;
  return () => {
    if(timerID) { return; }
    timerID = setInterval( () => {
      $.ajax({
        type:'POST',
        url:ce.ajaxuri,
        dataType:'json',
        data: {
          ajax: 'survey/heartbeat',
          nonce: ce.nonce,
        }
      })
      .done(function(data,status,jqXHR) {
        console.log('heartbeat heard');
      })
      .fail( function(jqXHR,textStatus,errorThrown) {
        console.log('heartbeam missed');
      });
    },
    600000); // beat once every 10 minutes for now...
  };
})();

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
  ce.ajaxuri   = ce.form.find('input[name=ajaxuri]').val();

  // add a hidden input to let PHP know that we have javascript enabled on the browswer
  $('<input>',{type:'hidden',name:'js_enabled',value:'1'}).appendTo(ce.form);

  setup_hints();

  ce.confirm_logout = false;

  ce.status.on('click',hide_status);

  ce.navbar.find('.admin.link a').on('click',handle_admin_link);

  if(ce.submit.length) {
    // the following only apply if there is a submit button bar
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

    startHeartbeat();
  }
});

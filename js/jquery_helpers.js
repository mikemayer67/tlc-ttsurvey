// Adds helper functions to accomplish common jquery attribute and property setters

$.fn.extend({
  setId(id)                 { return this.attr('id',id); },
  setType(type)             { return this.attr('type', type); },
  placeholder(text)         { return this.attr('placeholder', text); },
  disable(state=true)       { return this.prop('disabled', state).toggleClass('disabled',state); },
  enable()                  { return this.prop('disabled', false).removeClass('disabled'); },
  autocomplete(token)       { return this.attr('autocomplete', token); },
  isChecked()               { return this.prop('checked'); },
  setChecked(state=true)    { return this.prop('checked',state); },
  setUnchecked()            { return this.prop('checked',false); },
  toggleChecked(state=null) { 
    return this.prop('checked', state!==null ? state : !this.prop('checked'));
  },
});

/**
 * Common AJAX error handler for the admin dashboard
 * @param {JQuery.jqXHR} jqXHR 
 * @param {string} activity 
 */
function ajax_error_handler(jqXHR,activity)
{
  requires_initialization('ajax_error_handler');

  if(jqXHR.status == 400) {
    // bad request
    //   do nothing other than to warn the user (should be an admin)
    const reason = jqXHR.responseJSON?.reason || 'invalid request';
    const log_id = jqXHR.responseJSON?.log_id || '121467';
    alert(
      'Failed to ' + activity + ".\n" + 
      "Please let the survey admin something went wrong\n" +
      "  and givem them logID=" + log_id
    );
    console('Failed to ' + activity + ': ' + reason);
  } else if(jqXHR.status == 401) {
    // authentication error.
    //   send them back to main entry point to reauthenticate
    window.location.href = window.location.pathname;
  } else if(jqXHR.status == 403) {
    // forbidden
    //   for the admin dashboard, should mean there was an expired nonce
    //   reload the page for a new nonce value
    alert("Form timed out... current page must be reloaded");
    location.reload();
  } else { 
    // not sure what went wrong
    //   treat it as an internal error
    ajax_internal_error(jqXHR);
  }
}

/**
 * Common AJAX internal error handler for the admin dashboard
 * @param {JQuery.jqXHR} jqXHR 
 */
function ajax_internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
                         + "): "+ jqXHR.statusText
                         + "\nPlease let the survey admin know something went wrong."
       );
}
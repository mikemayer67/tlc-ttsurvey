import survey_controls from './survey_controls.js';
import active_handler from './surveys_active.js';
import draft_handler from './surveys_draft.js';
import closed_handler from './surveys_closed.js';
import new_handler from './surveys_new.js';

const ce = (window._survey_ce = window._survey_ce || {});

async function dispatch(f,...args)
{
  const status = ce.survey_controls.cur_status();
  if( status in ce.eventHandlers && f in ce.eventHandlers[status] ) {
    return ce.eventHandlers[status][f](...args);
  }
  return null;
}

$(document).ready(
  function($) {
  // common form elements
  ce.form    = $('#admin-surveys');
  ce.ajaxuri = $('#admin-surveys input[name=ajaxuri]').val();
  ce.pdfuri  = $('#admin-surveys input[name=pdfuri]').val();
  ce.nonce   = $('#admin-surveys input[name=nonce]').val();
  ce.status  = $('#ttt-status');

  ce.survey_controls = survey_controls(ce);

  ce.eventHandlers = {};
  ce.eventHandlers['active'] = active_handler(ce);
  ce.eventHandlers['draft']  = draft_handler(ce);
  ce.eventHandlers['closed'] = closed_handler(ce);
  ce.eventHandlers['new']    = new_handler(ce);

  ce.dispatch = dispatch;

});


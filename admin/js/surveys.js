import survey_controls from './survey_controls.js';
import survey_info from './survey_info.js';
import active_controller from './surveys_active.js';
import draft_controller from './surveys_draft.js';
import closed_controller from './surveys_closed.js';
import new_controller from './surveys_new.js';

const ce = (window._survey_ce = window._survey_ce || {});

// Survey status agnostic stuff

function enforce_alphanum_only(event)
{
  var v = $(this).val();
  v = v.replace(/[^a-zA-Z0-9& ]/g,'');
  $(this).val(v);
}

function handle_submit(e)
{
  e.preventDefault();
  var sender = $(e.originalEvent.submitter);
  if(ce.submit.is(sender)) {
    ce.dispatch('handle_submit');
  }
}

function handle_revert(e)
{
  e.preventDefault();
  ce.dispatch('handle_revert');
}


// Survey status dependencies

function dispatch(f,...args)
{
  const status = ce.cur_survey.status;
  if( status in ce.surveyControllers && f in ce.surveyControllers[status] ) {
    return ce.surveyControllers[status][f](...args);
  }
  return null;
}

async function dispatch_async(f,...args)
{
  const status = ce.cur_survey.status;
  if( status in ce.surveyControllers && f in ce.surveyControllers[status] ) {
    return ce.surveyControllers[status][f](...args);
  }
  return null;
}

function has_changes()
{
  var rval = dispatch('has_changes');
  if( rval === null || rval === undefined ) { rval = false; }
  return rval;
}

// Entry point

$(document).ready(
  function($) {
  // common form elements
  ce.form    = $('#admin-surveys');
  ce.ajaxuri = $('#admin-surveys input[name=ajaxuri]').val();
  ce.pdfuri  = $('#admin-surveys input[name=pdfuri]').val();
  ce.nonce   = $('#admin-surveys input[name=nonce]').val()
  ce.status  = $('#ttt-status');

  ce.button_bar = ce.form.find('div.button-bar');
  ce.submit     = $('#changes-submit');
  ce.revert     = $('#changes-revert');

  ce.form.find('input.alphanum-only').on('input',enforce_alphanum_only);
  ce.form.on('submit', handle_submit);
  ce.revert.on('click',handle_revert);

  // Load additional modules

  ce.dispatch       = dispatch;
  ce.dispatch_async = dispatch_async;
  
  ce.surveyControllers = {};
  ce.surveyControllers['active'] = active_controller(ce);
  ce.surveyControllers['draft']  = draft_controller(ce);
  ce.surveyControllers['closed'] = closed_controller(ce);
  ce.surveyControllers['new']    = new_controller(ce);

  ce.survey_controls = survey_controls(ce);
  ce.survey_info     = survey_info(ce);

  // Support global form

  has_change_cb = has_changes;
});


import survey_data  from './surveys/survey_data.js';
import controls     from './surveys/controls.js';
import metadata     from './surveys/metadata.js';
import locked_view  from './surveys/views/locked_view.js';
import new_view     from './surveys/views/new_view.js';
import draft_view   from './surveys/views/draft_view.js';
import controller   from './surveys/controller.js';
import undo_manager from './undo.js';

const ce = (window._survey_ce = window._survey_ce || {});

let validation_timer = null;

// Survey status agnostic stuff

function enforce_alphanum_only(event)
{
  var v = $(this).val();
  v = v.replace(/[^a-zA-Z0-9& ]/g,'');
  $(this).val(v);
}

function handle_input(e)
{
  hide_status();
  clearTimeout(validation_timer);
  $(this).removeClass('invalid-value');
  validation_timer = setTimeout(validate_input,250,$(this),e);
}

function handle_change(e)
{
  hide_status()
  clearTimeout(validation_timer);
  validation_timer = null;
  validate_input($(this),e);
}

function validate_input(sender,event)
{
  ce.dispatch('validate_input',sender,event);
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

function handle_preview(e)
{
  e.preventDefault();
  ce.dispatch('handle_preview');
}


// Survey status dependencies

function dispatch_status()
{
  if(!ce.has_admin_lock) { return 'locked'; }
  if(ce.cur_survey.status === 'active') { return 'locked'; }
  if(ce.cur_survey.status === 'closed') { return 'locked'; }
  return ce.cur_survey.status;
}

function dispatch(f,...args)
{
  const status = dispatch_status();
  if( status in ce.surveyViews && f in ce.surveyViews[status] ) {
    return ce.surveyViews[status][f](...args);
  }
  return null;
}

async function dispatch_async(f,...args)
{
  const status = dispatch_status();
  if( status in ce.surveyViews && f in ce.surveyViews[status] ) {
    return ce.surveyViews[status][f](...args);
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

  ce.submit_bar = ce.form.find('div.submit-bar');
  ce.submit     = $('#changes-submit');
  ce.revert     = $('#changes-revert');
  ce.preview    = $('#survey-preview');

  ce.has_admin_lock = admin_lock.has_lock;
  ce.isMac = /Mac|iPod|iPhone|iPad/.test(navigator.platform);

  // event handlers

  ce.handle_input = handle_input;
  ce.handle_change = handle_change;
  ce.validate_input = validate_input;

  ce.form.find('input.alphanum-only').on('input',enforce_alphanum_only);
  ce.form.on('submit', handle_submit);
  ce.revert.on('click',handle_revert);
  ce.preview.on('click',handle_preview);

  // Load additional modules

  ce.dispatch       = dispatch;
  ce.dispatch_async = dispatch_async;
  
  ce.surveyViews = {};
  ce.surveyViews['draft']  = draft_view(ce);
  ce.surveyViews['locked'] = locked_view(ce);
  ce.surveyViews['new']    = new_view(ce);

  ce.survey_data  = survey_data(ce);
  ce.metadata     = metadata(ce);
  ce.controller   = controller(ce);
  ce.controls     = controls(ce);
  ce.undo_manager = undo_manager(ce);

  // Support global form

  has_change_cb = has_changes;
});


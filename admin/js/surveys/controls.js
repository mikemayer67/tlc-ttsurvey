export default function init(ce)
{
  const _survey_select  = $('#survey-select');
  const _survey_status  = ce.form.find('span.survey-status');
  const _survey_actions = ce.form.find('a.action');

  // initialize survey select list

  const draft_surveys = ce.survey_data.drafts();
  if( draft_surveys.length > 0 ) {
    _survey_select.append($('<option>',{ 'text':'---Draft---', 'disabled':true }));
    for( var survey of draft_surveys ) {
      _survey_select.append($('<option>',{
        'value':  survey.id,
        'text':   survey.title,
        'class':  'draft',
        'status': 'draft',
      }));
    }
  }

  const active_survey = ce.survey_data.active();
  if(!(active_survey===null || Array.isArray(active_survey))) {
    _survey_select.append($('<option>',{ 'text':'---Active---', 'disabled':true }));
    _survey_select.append($('<option>',{
      'value':  active_survey.id,
      'text':   active_survey.title,
      'class':  'active',
      'status': 'active',
    }));
  }

  const closed_surveys = ce.survey_data.closed();
  if( closed_surveys.length > 0 ) {
    _survey_select.append($('<option>',{ 'text':'---Closed---', 'disabled':true }));
    for( var survey of closed_surveys ) {
      _survey_select.append($('<option>',{
        'value':  survey.id,
        'text':   survey.title,
        'class':  'closed',
        'status': 'closed',
      }));
    }
  }

  if( ce.has_admin_lock && ttt_is_admin ) {
    _survey_select.append($('<option>',{ 'text':'---New---', 'class':'new', 'disabled':true }));
    _survey_select.append($('<option>',{ 
      'value':  'new', 
      'text':   'Open New Survey...', 
      'class':  'new', 
      'status': 'new'
    }));
  }

  //
  // methods
  //

  function select_survey(id) {
    if(ce.has_admin_lock) {
      hide_status();  // global scope 
    }

    const prior_survey = ce.cur_survey;

    _survey_select.val(id);
    ce.cur_survey = ce.survey_data.lookup(id);

    const status = ce.cur_survey.status;

    _survey_status.html(status.charAt(0).toUpperCase() + status.slice(1));

    _survey_actions.hide();
    _survey_actions.filter(`.${status}`).show();

    // update the info bar and editor to reflect the selected survey
    ce.metadata.update_for_survey(ce.cur_survey);

    // perform common config... status views can override these later
    ce.controller.show_content();
    ce.submit_bar.hide();
    ce.form.find('input.watch').off('input').off('change');
    ce.form.find('select.watch').off('change');

    ce.dispatch('select_survey',prior_survey);
  }

  var options = _survey_select.find('option[value]').not('.new');
  if(options.length)    { select_survey(options.first().val()); }
  else                  { select_survey('new');                 }

  function cloneable_surveys()
  {
    return _survey_select.find('option[value]').filter(':not(.new)').clone();
  }

  function add_new_survey(survey)
  {
    survey.status = 'draft';
    ce.survey_data.add(survey);

    var new_option = _survey_select.find('option[value=new]');
    new_option.after(
      $('<option>',{value:survey.id, text:survey.title, class:'draft', status:'draft'})
    );
    
    select_survey(survey.id);
  }

  function handle_data_changed()
  {
    const id = ce.cur_survey.id;
    const name = ce.cur_survey.title;
    _survey_select.find(`option[value=${id}]`).html(name);
  }

  // event handlers

  function handle_action_link(e)
  {
    alert('handle action link');
  }

  async function handle_survey_select(e) 
  {
    const blocked = await ce.dispatch_async('block_survey_select');
    if( blocked ) { _survey_select.val( ce.cur_survey.id ); }
    else          { select_survey( _survey_select.val() );  }
  }

  _survey_select.on('change',handle_survey_select);
  _survey_actions.on('click',handle_action_link);

  $(document).on('SurveyDataChanged',handle_data_changed);

  return {
    cloneable_surveys:  cloneable_surveys,
    select_survey:      select_survey,
    add_new_survey:     add_new_survey,
  };
};

export default function survey_controls(ce)
{
  const _survey_select = $('#survey-select');
  const _survey_status = ce.form.find('span.survey-status');
  const _pdf_actions   = ce.form.find('a.action');

  // initialize survey select list

  var survey = ce.survey_data.active();
  if(!(survey===null || Array.isArray(survey))) {
    _survey_select.append($('<option>',{ 'text':'---Active---', 'disabled':true }));
    _survey_select.append($('<option>',{
      'value':  survey.id,
      'text':   survey.title,
      'class':  'active',
      'status': 'active',
    }));
  }

  if( ce.has_admin_lock ) {
    _survey_select.append($('<option>',{ 'text':'---New---', 'class':'new', 'disabled':true }));
    _survey_select.append($('<option>',{ 
      'value':  'new', 
      'text':   'Open New Survey...', 
      'class':  'new', 
      'status': 'new'
    }));
  }

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

    _pdf_actions.hide();
    _pdf_actions.filter(`.${status}`).show();

    // update the info bar and editor to reflect the selected survey
    ce.survey_info.update_for_survey(ce.cur_survey);

    // perform common config... status controllers can override these later
    ce.survey_editor.show();
    ce.submit_bar.hide();
    ce.form.find('input.watch').off('input').off('change');
    ce.form.find('select.watch').off('change');

    ce.dispatch('select_survey',prior_survey);
  }

  var options = _survey_select.find('option[value]').filter(':not(.new)');
  select_survey(options.length ? options.first().val() : 'new');

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
  _pdf_actions.on('click',handle_action_link);

  $(document).on('SurveyDataChanged',handle_data_changed);

  return {
    cloneable_surveys:  cloneable_surveys,
    select_survey:      select_survey,
    add_new_survey:     add_new_survey,
  };
};

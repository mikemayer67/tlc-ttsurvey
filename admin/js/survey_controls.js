export default function survey_controls(ce)
{
  const _surveys = {};

  const _survey_select = $('#survey-select');
  const _survey_status = ce.form.find('span.survey-status');
  const _pdf_actions   = ce.form.find('a.action');

  // initialize survey select list
  // requires that the php code creates the javascript object ttt_all_surveys in a 
  //   <script> element.  @@@ TODO build a suveys data script

  var survey = ttt_all_surveys['active'];
  if(!(survey===null || Array.isArray(survey))) {
    survey.status = 'active';
    _surveys[survey.id] = survey;

    _survey_select.append($('<option>',{ 'text':'---Active---', 'disabled':true }));
    _survey_select.append($('<option>',{
      'value':  survey.id,
      'text':   survey.title,
      'class':  'active',
      'status': 'active',
    }));
  }

  _survey_select.append($('<option>',{ 'text':'---New---', 'class':'new', 'disabled':true }));
  _survey_select.append($('<option>',{ 
    'value':  'new', 
    'text':   'Open New Survey...', 
    'class':  'new', 
    'status': 'new'
  }));

  if( ttt_all_surveys['draft'].length>0 ) {
    _survey_select.append($('<option>',{ 'text':'---Draft---', 'disabled':true }));
    for( var survey of ttt_all_surveys['draft']) {
      survey.status = 'draft';
      _surveys[survey.id] = survey;

      _survey_select.append($('<option>',{
        'value':  survey.id,
        'text':   survey.title,
        'class':  'draft',
        'status': 'draft',
      }));
    }
  }

  if( ttt_all_surveys['closed'].length>0 ) {
    _survey_select.append($('<option>',{ 'text':'---Closed---', 'disabled':true }));
    for( var survey of ttt_all_surveys['closed']) {
      survey.status = 'closed';
      _surveys[survey.id] = survey;

      _survey_select.append($('<option>',{
        'value':  survey.id,
        'text':   survey.title,
        'class':  'closed',
        'status': 'closed',
      }));
    }
  }

  // create a pseudo-survey for handling the New Survey select
  _surveys["new"] = {'id':'new', 'status':'new'};

  //
  // methods
  //

  function select_survey(id) {
    hide_status();  // global scope 

    const prior_survey = ce.cur_survey;

    _survey_select.val(id);
    ce.cur_survey = _surveys[id];

    const status = ce.cur_survey.status;

    _survey_status.html(status.charAt(0).toUpperCase() + status.slice(1));

    _pdf_actions.hide();
    _pdf_actions.filter(`.${status}`).show();

    // update the info bar and editor to reflect the selected survey
    ce.survey_info.update_for_survey(ce.cur_survey);

    // perform common config... status controllers can override these later
    ce.survey_editor.show();
    ce.button_bar.hide();
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
    _surveys[survey.id] = survey;

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
    _surveys[id].title = name;
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

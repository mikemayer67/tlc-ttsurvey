( function() {

  let ce = {};

  function handle_surveys_submit(event) 
  {
    event.preventDefault();
    alert('handle_submit');
  }

  function handle_survey_select(event)
  {
    update_display_state();
  }

  function update_display_state() 
  {
    var selected = ce.survey_select.find(':selected');
    var status = selected.attr('status');
    ce.survey_status.html(status);
    ce.action_links.addClass('hidden');
    ce.action_links.filter('.'+status).removeClass('hidden');
    ce.new_survey_table.hide();
    if(status=='draft') {
      ce.button_bar.removeClass('hidden');
      ce.submit.val('Save Changes');
      ce.revert.val('Revert');
    }
    else if(status=='new') {
      ce.button_bar.removeClass('hidden');
      ce.submit.val('Create Survey');
      ce.revert.val('Cancel');
      ce.new_survey_table.show();
    } else {
      ce.button_bar.addClass('hidden');
    }
  }

  function enforce_alphanum_only(event)
  {
    var v = $(this).val();
    console.log(v);
    v = v.replace(/[^a-zA-Z0-9& ]/g,'');
    $(this).val(v);
  }

  function handle_action_link(event)
  {
    alert('handle action link');
  }

  function update_submit()
  {
  }

  function has_changes()
  {
    return false;
  }

  function init_survey_lists()
  {
    ce.all_surveys = JSON.parse(ce.hidden['surveys'].val());
    ce.survey_map = {};

    ce.active_surveys = [];
    ce.draft_surveys  = [];
    ce.closed_surveys = [];

    for (const [status, survey_list] of Object.entries(ce.all_surveys)) {
      for( const survey of survey_list ) {
        ce.survey_map[survey.id] = survey;
        switch(status) {
          case 'active': ce.active_surveys.push(survey); break;
          case 'drafts': ce.draft_surveys.push(survey); break;
          case 'closed': ce.closed_surveys.push(survey); break;
        }
      }
    }

    // populate the survey select menu
    populate_survey_options(ce.survey_select);
    populate_survey_options(ce.new_survey_clone);
  }


  function populate_survey_options(sel)
  {
    var is_primary = sel===ce.survey_select;
    if(is_primary) { ce.cur_survey = null; }

    if(ce.active_surveys.length) {
      sel.append($('<option>',{'text':'Active','disabled':true}));
      // SHOULD only be one at most... but just in case
      for( survey of ce.active_surveys ) {
        sel.append($('<option>',{
          'value':survey.id,
          'text':survey.title,
          'class':'active',
          'status':'active',
          'selected':(ce.cur_survey === null),
        }));
        if(is_primary && ce.cur_survey === null) {ce.cur_survey = survey.id;}
      }
    }
    if(ce.draft_surveys.length) {
      sel.append($('<option>',{'text':'Drafts','disabled':true}));
      if(is_primary) {
        sel.append($('<option>',{ 'value':'new', 'text':'New...', 'class':'new', 'status':'new'}));
      }
      for( survey of ce.draft_surveys ) {
        sel.append($('<option>',{
          'value':survey.id,
          'text':survey.title,
          'class':'draft',
          'status':'draft',
          'selected':(ce.cur_survey === null),
        }));
        if(is_primary && ce.cur_survey === null) {ce.cur_survey = survey.id;}
      }
    }
    if(ce.closed_surveys.length) {
      sel.append($('<option>',{'text':'Closed','disabled':true}));
      for( survey of ce.closed_surveys ) {
        sel.append($('<option>',{
          'value':survey.id,
          'text':survey.title,
          'class':'closed',
          'status':'closed',
          'selected':(ce.cur_survey === null),
        }));
        if(is_primary && ce.cur_survey === null) {ce.cur_survey = survey.id;}
      }
    }
  }

  $(document).ready(
    function($) {
    ce.form             = $('#admin-surveys');
    ce.ajaxuri          = $('#admin-surveys input[name=ajaxuri]').val();
    ce.nonce            = $('#admin-surveys input[name=nonce]').val();
    ce.status           = $('#ttt-status');
    ce.survey_select    = $('#survey-select');
    ce.survey_status    = ce.form.find('span.survey-status');
    ce.action_links     = ce.form.find('a.action');
    ce.button_bar       = ce.form.find('div.button-bar');
    ce.new_survey_table = $('#new-survey');
    ce.new_survey_clone = $('#new-survey-clone');
    ce.submit           = $('#changes-submit');
    ce.revert           = $('#changes-revert');

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );

    ce.form.on('submit',handle_surveys_submit);
    ce.survey_select.on('change',handle_survey_select);
    ce.action_links.on('click',handle_action_link);

    ce.form.find('input.alphanum-only').on('input',enforce_alphanum_only);

    has_change_cb = has_changes;

    init_survey_lists();

    update_display_state();
    update_submit();
  });

})();

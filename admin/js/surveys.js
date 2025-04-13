( function() {

  let ce = {};

  function handle_surveys_submit(event) 
  {
    event.preventDefault();
    
    var sender = $(event.originalEvent.submitter);
    if(sender.hasClass('hidden')) { return; }

    if(ce.revert.is(sender)) {
      if(ce.cur_survey === 'new') {
        if(ce.prior_survey !== 'new') { 
          ce.cur_survey = ce.prior_survey;
          ce.survey_select.val(ce.cur_survey);
          update_display_state();
        }
      }
      else {
        alert("revert survey changes");
      }

      return;
    }

    if(ce.submit.is(sender)) {
      if(ce.cur_survey === 'new') {
        submit_new_survey();
      }
      else {
        submit_survey_updates();
      }
      return;
    }
  }

  function submit_new_survey()
  {
    var formData = new FormData();
    formData.append('nonce',ce.nonce);
    formData.append('ajax','admin/create_new_survey');
    formData.append('name',ce.new_survey_name.val());
    var clone = ce.new_survey_clone.val();
    if(!isNaN(clone)) { formData.append('clone',clone); }
    formData.append('new_survey_pdf',ce.new_survey_pdf[0].files[0]);

    $.ajax({
      type: 'POST',
      url: ce.ajaxuri,
      contentType: false,
      processData: false,
      data: formData,

    })
    .done( function(data,start,jqHXR) {
      alert("received response: "+data);
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    });
  }

  function submit_survey_updates()
  {
    alert('submit survey updates');
  }

  function handle_survey_select(event)
  {
    var next_survey = $(this).val();
    if(next_survey === "new") {
      if(ce.cur_survey !== "new") {
        ce.prior_survey = ce.cur_survey;
      }
    }
    ce.cur_survey = next_survey;
    update_display_state();
  }

  function update_display_state() 
  {
    ce.action_links.addClass('hidden');

    if(ce.cur_survey === "new") {
      ce.survey_status.html('New Survey');
      ce.button_bar.removeClass('hidden');
      ce.submit.val('Create Survey');
      ce.revert.val('Cancel');
      ce.new_survey_table.show();

      $('input[required]').removeAttr('required');
      ce.new_survey_name.attr('required',true);
    } 
    else {
      var survey = ce.survey_map[ce.cur_survey];
      var status = survey.status;

      ce.new_survey_table.hide();

      ce.survey_status.html(status);
      ce.action_links.filter('.'+status).removeClass('hidden');

      if(status=='draft') {
        $('input[required]').removeAttr('required');
        // TODO: reset required attributes for fields that need it
        console.log('reset required attribute for fields that need it');
        ce.button_bar.removeClass('hidden');
        ce.submit.val('Save Changes');
        ce.revert.val('Revert');
      } 
      else {
        ce.button_bar.addClass('hidden');
      }
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
      for( var survey of survey_list ) {
        survey.status = status;
        ce.survey_map[survey.id] = survey;
        switch(status) {
          case 'active': ce.active_surveys.push(survey); break;
          case 'draft':  ce.draft_surveys.push(survey); break;
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
      sel.append($('<option>',{'text':'draft','disabled':true}));
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
    ce.new_survey_name  = $('#new-survey-name');
    ce.new_survey_clone = $('#new-survey-clone');
    ce.new_survey_pdf   = $('#new-survey-pdf');
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

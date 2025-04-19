( function() {

  let ce = {};

  //
  // Simple Support Functions
  //

  function format_date(date)
  {
    var date = dayjs(date);
    date = date.format('MMM D, YYYY h:mma');
    return date;
  }

  function enforce_alphanum_only(event)
  {
    var v = $(this).val();
    v = v.replace(/[^a-zA-Z0-9& ]/g,'');
    $(this).val(v);
  }

  //
  // Functions dealing with the list of surveys
  //

  function init_survey_lists()
  {
    ce.all_surveys = {};

    var all_surveys = JSON.parse(ce.hidden['surveys'].val());
    
    // Populate the survey select 

    var survey = all_surveys['active'];
    if(!(survey===null || Array.isArray(survey))) {
      ce.survey_select.append($('<option>',{
        'text':'---Active---',
        'disabled':true,
      }));
      survey.status = 'active';
      ce.all_surveys[survey.id] = survey;
      ce.survey_select.append($('<option>',{
        'value':survey.id,
        'text':survey.title,
        'class':'active',
        'status':'active',
      }));
    }

    ce.survey_select.append($('<option>',{
      'text':'---New---',
      'class':'new',
      'disabled':true,
    }));
    ce.survey_select.append($('<option>',{ 
      'value':'new', 
      'text':'Open New Survey...', 
      'class':'new', 
      'status':'new'
    }));

    if(all_surveys['draft'].length>0) {
      ce.survey_select.append($('<option>',{
        'text':'---Draft---',
        'disabled':true,
      }));
      for( var survey of all_surveys['draft']) {
        survey.status = 'draft';
        ce.all_surveys[survey.id] = survey;
        ce.survey_select.append($('<option>',{
          'value':survey.id,
          'text':survey.title,
          'class':'draft',
          'status':'draft',
        }));
      }
    }

    if(all_surveys['closed'].length>0) {
      ce.survey_select.append($('<option>',{
        'text':'---Closed---',
        'disabled':true,
      }));
      for( var survey of all_surveys['closed']) {
        survey.status = 'closed';
        ce.all_surveys[survey.id] = survey;
        ce.survey_select.append($('<option>',{
          'value':survey.id,
          'text':survey.title,
          'class':'closed',
          'status':'closed',
        }));
      }
    }

    // create a pseudo-survey for handling the New Survey select
    ce.all_surveys["new"] = {'id':'new', 'status':'new'};

    // Copy the survey select options to the clone from select

    var all_options = ce.survey_select.find('option').clone();
    all_options = all_options.filter(':not(.new)');
    ce.survey_clone.append(all_options);

    // Set the currently displayed survey

    all_options = all_options.filter('[value]');
    if(all_options.length) {
      var survey_id = all_options.first().val();
      ce.cur_survey = ce.all_surveys[survey_id];
    } else {
      ce.cur_survey = ce.all_surveys('new');
    }
  }

  //
  // Event Handler Dispatch
  //
  
  function handle_survey_select(event)
  {
    var survey_id = $(this).val();
    select_survey(ce.all_surveys[survey_id]);
  }

  function select_survey(survey)
  {
    ce.action_links.hide();
    ce.button_bar.hide();
    ce.info_bar.hide();
    ce.info_edit.hide();
    ce.info_edit.find('.clone-from').hide();
    ce.clear_pdf.hide();

    $('input[required]').removeAttr('required');
    
    switch(survey.status)
    {
      case "new":    select_new_survey(survey);    break;
      case "active": select_active_survey(survey); break;
      case "closed": select_closed_survey(survey); break;
      case "draft":  select_draft_survey(survey);  break;
    }
  }

  function handle_surveys_submit(event)
  {
    event.preventDefault();
    var sender = $(event.originalEvent.submitter);
    if(sender.hasClass('hidden')) { return; }

    switch(ce.cur_survey.status)
    {
      case "new":   submit_new_survey();   break;
      case "draft": submit_draft_survey(); break;
    }
  }

  function handle_surveys_revert(event)
  {
    event.preventDefault();
    switch(ce.cur_survey.status)
    {
      case "new":   revert_new_survey();   break;
      case "draft": revert_draft_survey(); break;
    }
  }

  function handle_action_link(event)
  {
    alert('handle action link');
  }

  function has_changes()
  {
    return false;
  }

  //
  // Survey Functions supporting existing surveys (i.e., not new)
  //

  function update_survey_controls(survey)
  {
    var status = survey.status;
    ce.survey_status.html(status.charAt(0).toUpperCase() + status.slice(1));
    ce.action_links.filter('.'+survey.status).show();
  }

  function show_info_bar(survey)
  {
    ce.info_bar.show();

    ce.info_bar.find('.info-label.created .date').html(format_date(survey.created));
    if(survey.active) {
      ce.info_bar.find('.info-label.opened').show();
      ce.info_bar.find('.info-label.opened .date').html(format_date(survey.active));
    }
    else {
      ce.info_bar.find('.info-label.opened').hide();
    }

    if(survey.closed) {
      ce.info_bar.find('.info-label.closed').show();
      ce.info_bar.find('.info-label.closed .date').html(format_date(survey.closed));
    }
    else {
      ce.info_bar.find('.info-label.closed').hide();
    }

    if(survey.has_pdf) {
      ce.info_bar.find('.pdf-link .no-link').hide();
      ce.info_bar.find('.pdf-link a').attr('href',ce.pdfuri+survey.id).show();
    } else {
      ce.info_bar.find('.pdf-link .no-link').show();
      ce.info_bar.find('.pdf-link a').hide();
    }
  }

  function handle_survey_pdf(events)
  {
    var pdf_file = ce.survey_pdf.val();
    if(pdf_file) { ce.clear_pdf.show(); }
    else         { ce.clear_pdf.hide(); }
  }

  function clear_survey_pdf(events)
  {
    ce.survey_pdf.val('');
    ce.clear_pdf.hide();
  }

  function select_existing_survey(survey)
  {
    ce.cur_survey = survey;
    ce.survey_select.val(survey.id);

    update_survey_controls(survey);
    show_info_bar(survey);
  }

  //
  // Active Survey Functions
  //
  
  function select_active_survey(survey)
  {
    select_existing_survey(survey);
  }

  //
  // Closed Survey Functions
  //

  function select_closed_survey(survey)
  {
    select_existing_survey(survey);
  }

  // 
  // Draft Survey Functions
  //

  function select_draft_survey(survey)
  {
    select_existing_survey(survey);

    ce.info_edit.show();
    ce.button_bar.show();
    ce.survey_name.attr('placeholder',survey['title']);
    ce.submit.val('Save Changes');
    ce.revert.val('Revert');
  }

  function revert_draft_survey()
  {
    alert('handle_revert_draft_survey');
  }

  function submit_draft_survey()
  {
    alert('handle_submit_draft_survey');
  }

  //
  // New Survey Functions
  //
  
  function select_new_survey(survey)
  {
    ce.prior_survey = ce.cur_survey;
    ce.cur_survey = ce.all_surveys['new'];

    ce.survey_status.html('New Survey');
    ce.button_bar.show();
    ce.submit.val('Create Survey');
    ce.revert.val('Cancel');
    ce.info_edit.show();
    ce.info_edit.find('.clone-from').show();
    ce.survey_name.attr('required',true);
    ce.survey_name.attr('placeholder','required');
  }

  function revert_new_survey()
  {
    select_survey(ce.prior_survey);
  }

  function submit_new_survey()
  {
    var formData = new FormData();
    formData.append('nonce',ce.nonce);
    formData.append('ajax','admin/create_new_survey');
    formData.append('name',ce.survey_name.val());
    var clone = ce.survey_clone.val();
    if(!isNaN(clone)) { formData.append('clone',clone); }
    formData.append('survey_pdf',ce.survey_pdf[0].files[0]);

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

  //
  // Entry point
  //
  
  $(document).ready(
    function($) {
    ce.form             = $('#admin-surveys');
    ce.ajaxuri          = $('#admin-surveys input[name=ajaxuri]').val();
    ce.pdfuri           = $('#admin-surveys input[name=pdfuri]').val();
    ce.nonce            = $('#admin-surveys input[name=nonce]').val();
    ce.status           = $('#ttt-status');
    ce.survey_select    = $('#survey-select');
    ce.survey_status    = ce.form.find('span.survey-status');
    ce.action_links     = ce.form.find('a.action');
    ce.button_bar       = ce.form.find('div.button-bar');
    ce.info_edit        = $('#info-edit');
    ce.survey_name      = $('#survey-name');
    ce.survey_clone     = $('#survey-clone-from');
    ce.survey_pdf       = $('#survey-pdf');
    ce.clear_pdf        = ce.info_edit.find('button.clear-pdf');
    ce.info_bar         = ce.form.find('.content-box .info-bar');
    ce.submit           = $('#changes-submit');
    ce.revert           = $('#changes-revert');

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );

    ce.form.on('submit',handle_surveys_submit);
    ce.revert.on('click',handle_surveys_revert);
    ce.survey_select.on('change',handle_survey_select);
    ce.survey_pdf.on('change',handle_survey_pdf);
    ce.clear_pdf.on('click',clear_survey_pdf);
    ce.action_links.on('click',handle_action_link);

    ce.form.find('input.alphanum-only').on('input',enforce_alphanum_only);

    has_change_cb = has_changes;

    init_survey_lists();
    select_survey(ce.cur_survey);
  });

})();

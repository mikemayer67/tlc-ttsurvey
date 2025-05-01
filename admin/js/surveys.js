( function() {

  let ce = {};

  let validation_timer=null;

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
  
  function handle_input(event)
  {
    hide_status();
    clearTimeout(validation_timer);
    $(this).removeClass('invalid-value');
    validation_timer = setTimeout(validate_all,150);
  }

  function handle_change(event)
  {
    hide_status();
    clearTimeout(validation_timer);
    validation_timer = null;
    validate_all();
  }

  function validate_all()
  {
    ce.form.children('input').removeClass('invalid-value');
    ce.form.find('div.error').hide();

    switch(ce.cur_survey.status)
    {
      case "new":   validate_new_survey();   break;
      case "draft": validate_draft_survey(); break;
    }

    update_submit();
  }
  
  function handle_survey_select(event)
  {
    var survey_id = $(this).val();
    select_survey(ce.all_surveys[survey_id]);
  }

  function select_survey(survey)
  {
    if(block_survey_select()) { return; }

    hide_status();
    ce.action_links.hide();
    ce.button_bar.hide();
    ce.info_bar.hide();
    ce.info_edit.hide();
    ce.info_edit.find('.clone-from').hide();
    ce.existing_pdf.hide();
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

  function block_survey_select()
  {
    if(ce.cur_survey.status !== 'draft') { return false; }
    if(!has_draft_changes()) { return false; }

    var cand_survey = ce.survey_select.val();

    var tsm = $('#tab-switch-modal');
    tsm.find('.tsm-type').html('surveys');
    tsm.find('button.cancel').off('click').on('click',function() { 
      tsm.hide();
      ce.survey_select.val(ce.cur_survey.id);
    });
    tsm.find('button.confirm').off('click').on('click',function() { 
      tsm.hide();
      revert_draft_survey();
      select_survey(ce.all_surveys[cand_survey]);
    }).html("Switch Surveys");
    tsm.show();

    return true;
  }

  function handle_surveys_submit(event)
  {
    event.preventDefault();
    var sender = $(event.originalEvent.submitter);
    if( ce.submit.is(sender) ) {
      switch(ce.cur_survey.status)
      {
        case "new":   submit_new_survey();   break;
        case "draft": submit_draft_survey(); break;
      }
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
    validate_all();
  }

  function handle_action_link(event)
  {
    alert('handle action link');
  }

  function has_changes()
  {
    if(ce.cur_survey.status !== 'draft') { return false; }
    return has_draft_changes();
  }

  function update_submit()
  {
    switch(ce.cur_survey.status)
    {
      case "new":   update_new_submit();   break;
      case "draft": update_draft_submit(); break;
    }
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

  function handle_survey_pdf()
  {
    if(!ce.cur_survey.has_pdf) {
      var pdf_file = ce.survey_pdf.val();
      if(pdf_file) { ce.clear_pdf.show(); }
      else         { ce.clear_pdf.hide(); }
    }
    validate_all();
  }

  function clear_survey_pdf()
  {
    ce.survey_pdf.val('');
    ce.clear_pdf.hide();
    validate_all();
  }

  function select_existing_survey(survey)
  {
    ce.cur_survey = survey;
    ce.survey_select.val(survey.id);

    update_survey_controls(survey);
    show_info_bar(survey);
  }

  function validate_survey_name()
  {
    var survey_name = ce.survey_name.val().trim();
    if(survey_name.length > 0) {
      if(survey_name.length < 5) {
        ce.survey_name.addClass('invalid-value');
        ce.form.find('div.error[name=survey_name]').show().html("too short");
      }
    }
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
    ce.submit.val('Save Changes');
    ce.revert.val('Revert');
    ce.content_editor.show();

    ce.survey_name.attr({
      required:false,
      placeholder:survey['title'],
    }).val('');

    clear_survey_pdf();
    if(survey.has_pdf) {
      ce.existing_pdf.val('keep').show();
      ce.survey_pdf.hide();
      ce.info_edit.find('.pdf-file td.label').html('Existing PDF');
    } else {
      ce.existing_pdf.hide();
      ce.survey_pdf.show();
      ce.info_edit.find('.pdf-file td.label').html('Downloadable PDF');
    }
    ce.survey_pdf.val('');

    ce.saved_values = current_draft_values();
    update_submit();
  }

  function handle_pdf_action(event)
  {
    if( ce.existing_pdf.val() === "replace" ) {
      ce.survey_pdf.show();
    } else {
      ce.survey_pdf.hide();
    }
    validate_all();
  }

  function revert_draft_survey()
  {
    var survey_name = ce.saved_values['survey_name'].trim();
    if( survey_name === ce.cur_survey.title ) { survey_name=''; }

    ce.survey_name.val(survey_name);
    ce.survey_pdf.val('');
    clear_survey_pdf();
    ce.form.find('div.error').hide();

    if(ce.cur_survey.has_pdf) {
      ce.existing_pdf.val('keep');
      ce.survey_pdf.val('').hide();
    }

    //@@@TODO: Add survey elements
    
    validate_all();
  }

  function submit_draft_survey()
  {
    var cur_values = current_draft_values();
    var survey_name = cur_values['survey_name'].trim();
    if( survey_name.length == 0 ) { survey_name = ce.cur_survey.title; }

    var formData = new FormData();
    formData.append('nonce',ce.nonce);
    formData.append('ajax','admin/update_survey');
    formData.append('survey_id',ce.cur_survey.id);
    formData.append('name',survey_name);

    if(ce.cur_survey.has_pdf) {
      switch(ce.existing_pdf.val()) {
        case 'drop':
          formData.append('existing_pdf','drop');
          break;
        case 'replace':
          formData.append('existing_pdf','replace');
          formData.append('survey_pdf',ce.survey_pdf[0].files[0]);
          break;
      }
    } else {
      if(ce.survey_pdf.val()) {
        formData.append('existing_pdf','add');
        formData.append('survey_pdf',ce.survey_pdf[0].files[0]);
      }
    }

    $.ajax( {
      type: 'POST',
      ulr: ce.ajaxuri,
      contentType: false,
      processData: false,
      dataType: 'json',
      data:formData,
    })
    .done( function(data,status,jqXHR) {
      if(data.success) {
        ce.saved_values = cur_values;
        ce.saved_values['survey_name'] = '';

        ce.cur_survey.title = survey_name;
        ce.cur_survey.has_pdf = data.has_pdf;

        clear_survey_pdf();
        ce.existing_pdf.val('keep');

        ce.survey_select.find('option[value='+ce.cur_survey.id+']').html(survey_name);
        ce.survey_clone.find('option[value='+ce.cur_survey.id+']').html(survey_name);

        select_draft_survey(ce.cur_survey);
        show_status('info','Changes Saved');
      } 
      else {
        if( 'bad_nonce' in data ) {
          alert("Somthing got out of sync.  Reloading page.");
          location.reload();
        } else {
          alert("handle bad input notices");
//          --- copied from settings.js ---
//          for( const [key,error] of Object.entries(data) ) {
//            if( key in ce.inputs     ) { ce.inputs[key].addClass('invalid-value'); }
//            if( key in ce.error_divs ) { ce.error_divs[key].show().html(error);    }
//          }
        }
      }
      validate_all();
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    } )
    ;
  }

  function update_draft_submit()
  {
    var errors = $('input.invalid-value');
    var dirty = has_draft_changes();
    var can_submit = dirty && errors.length==0;

    ce.submit.prop('disabled',!can_submit);

    if(dirty) {
      ce.revert.prop('disabled',false).css('opacity',1);
    } else {
      ce.revert.prop('disabled',false).css('opacity',0);
    }
  }

  function current_draft_values()
  {
    var values = {
      survey_name: ce.survey_name.val(),
    };
    //@@@TODO: Add survey elements
    return values;
  }

  function has_draft_changes()
  {
    var current_values = current_draft_values();
    
    var current_survey_name = current_values['survey_name'].trim();
    if(current_survey_name.length>0) {
      var saved_survey_name = ce.saved_values['survey_name'];
      if( saved_survey_name.length == 0 ) { saved_survey_name=ce.cur_survey.title; }

      if( current_values['survey_name'] !== saved_survey_name ) { return true; }
    }

    if(ce.existing_pdf.val() !=='keep') { return true; }
    if(ce.survey_pdf.val()) { return true; }

    return false;
  }

  function validate_draft_survey()
  {
    validate_survey_name();

    ce.survey_pdf.removeClass('invalid-value');
    if(ce.cur_survey.has_pdf) {
      if(ce.existing_pdf.val() === 'replace') {
        if(ce.survey_pdf.val() === '') {
          ce.survey_pdf.addClass('invalid-value');
        }
      }
    }
  }


  //
  // New Survey Functions
  //
  
  function select_new_survey(survey)
  {
    ce.prior_survey = ce.cur_survey;
    ce.cur_survey = ce.all_surveys['new'];
    ce.survey_pdf.show();

    ce.survey_status.html('New Survey');
    ce.button_bar.show();
    ce.submit.val('Create Survey');
    ce.revert.val('Cancel');
    ce.info_edit.show();
    ce.content_editor.hide();

    ce.survey_name.attr({
      required:true,
      placeholder:'required',
    }).val('');

    ce.info_edit.find('.clone-from').show();
    ce.survey_clone.val('none');

    ce.info_edit.find('.pdf-file td.label').html('Downloadable PDF');

    clear_survey_pdf();
    update_new_submit();
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
      dataType: 'json',
      data: formData,

    })
    .done( function(data,start,jqHXR) {
      if(data.success) { add_new_survey(data.survey); }
      else             { alert('Failed to create new survey: '+error); }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    });
  }

  function update_new_submit()
  {
    var survey_name = ce.survey_name.val().trim();
    var can_submit = survey_name.length > 4;
    ce.submit.prop('disabled',!can_submit);
    ce.revert.prop('disabled',false).css('opacity',1);
  }


  function add_new_survey(survey)
  {
    survey.status = 'draft';
    ce.all_surveys[survey.id] = survey;

    var new_option = ce.survey_select.find('option[value=new]');
    new_option.after(
      $('<option>',{value:survey.id, text:survey.title, class:'draft', status:'draft'})
    );
    
    select_survey(survey);
  }

  function validate_new_survey()
  {
    validate_survey_name();
    update_new_submit();
  }

  //
  // Content editor resizing
  //

  function start_survey_tree_resize(e) {
    e.preventDefault();
    ce.content_editor.css('cursor','col-resize');
    ce.resizing = { 
      min_x : 200 - ce.survey_tree.width(),
      max_x : ce.element_editor.width() - 300,
      start_x : e.pageX,
      start_w : ce.survey_tree.width(),
      in_editor : true,
      last_move : 0,
    };
    ce.content_editor.on('mouseenter', function(e) { ce.resizing.in_editor = true;  } );
    ce.content_editor.on('mouseleave', function(e) { ce.resizing.in_editor = false; } );
  }

  function track_mouse(e) {
    e.preventDefault();
    if(!ce.resizing) { return; }
    const now = Date.now();
    if(now < ce.lastMove + 10) { return; }
    ce.lastMove = now;

    const dx = e.pageX - ce.resizing.start_x;
    if( dx > ce.resizing.min_x && dx < ce.resizing.max_x ) {
      ce.survey_tree.width(ce.resizing.start_w + dx);
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(!ce.resizing) { return; }
    ce.content_editor.css('cursor','');
    if(!ce.resizing.in_editor) { 
      ce.survey_tree.width(ce.resizing.start_w); 
    }
    ce.content_editor.off('mouseenter');
    ce.content_editor.off('mouseleave');
    ce.resizing = null;
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
    ce.existing_pdf     = $('#existing-pdf-action');
    ce.clear_pdf        = ce.info_edit.find('button.clear-pdf');
    ce.info_bar         = ce.form.find('.content-box .info-bar');
    ce.content_editor   = ce.form.find('#content-editor');
    ce.survey_tree      = ce.content_editor.find('#survey-tree');
    ce.element_editor   = ce.content_editor.find('#element-editor');
    ce.resizer          = ce.content_editor.find('.resizer');
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
    ce.existing_pdf.on('change',handle_pdf_action);
    ce.action_links.on('click',handle_action_link);

    ce.form.find('input.alphanum-only').on('input',enforce_alphanum_only);
    ce.survey_name.on('input',handle_input);
    ce.survey_name.on('change',handle_change);

    ce.resizing = null;
    ce.resizer.on('mousedown',start_survey_tree_resize);
    $(document).on('mousemove',track_mouse);
    $(document).on('mouseup',stop_tracking_mouse);

    has_change_cb = has_changes;

    init_survey_lists();
    
    var lock_status = null;
    if(!admin_lock.has_lock) { lock_status = ce.status.html(); }

    select_survey(ce.cur_survey);

    if(!admin_lock.has_lock) {
      show_status('warning',lock_status);
      $('button').not('[name=tab]').attr('disabled',true);
      $('select').attr('disabled',true);
      $('input').attr('disabled',true);
    }
  });

})();

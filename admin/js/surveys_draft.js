export default function draft_controller(ce)
{
  const _info_edit    = $('#info-edit');
  const _survey_name  = $('#survey-name');
  const _survey_pdf   = $('#survey-pdf');
  const _pdf_action   = $('#existing-pdf-action');
  const _clear_pdf    = $('#clear-pdf');

  async function block_survey_select()
  {
    if( !has_changes() ) { return Promise.resolve(false); }

    return new Promise((resolve) =>  {
      var tsm = $('#tab-switch-modal');
      tsm.find('.tsm-type').html('surveys');
      tsm.find('button.cancel').off('click').on('click',function() { 
        $(this).off('click');
        tsm.hide();
        resolve(true);
      });
      tsm.find('button.confirm').off('click').on('click',function() { 
        $(this).off('click');
        tsm.hide();
        resolve(false);
      }).html("Switch Surveys");
      tsm.show();
    });
  }

  function select_survey()
  {
    ce.survey_info.update_for_survey();

    _survey_name.attr({ required:false, placeholder:ce.cur_survey.title}).val('');

    ce.form.find('input.watch').on('input',ce.handle_input).on('change',ce.handle_change);
    ce.form.find('select.watch').on('change',ce.handle_change);

    ce.submit_bar.show();
    ce.submit.val('Save Changes').prop('disabled',true);
    ce.revert.val('Revert').prop('disabled',true).css('opacity',0);

    // change the ContentDataLoaded handler
    $(document)
    .off('ContentDataLoaded')
    .on('ContentDataLoaded', function(e,id,data) { 
      ce.survey_editor.update(data);
      validate_all();
    } );

    // note that if the content associated with the current survey has not yet been
    //   retrieved from the server, the returned content value will be null and an
    //   AJAX call will be issued to retrieve the data.  On completion of retrieving
    //   the data, a ContentDataLoaded event will be triggered.
    const content = ce.survey_data.content( ce.cur_survey.id );

    ce.survey_editor.enable();
    ce.survey_editor.update(content);
    _last_saved = current_values();
    validate_all();
  }


  function update_info()
  {
    _info_edit.show();

    _survey_pdf.val('');
    _pdf_action.hide();
    _clear_pdf.hide();

    if(ce.cur_survey.has_pdf) {
      _pdf_action.val('keep').show();
      _survey_pdf.hide();
      _info_edit.find('.pdf-file td.label').html('Existing PDF');
    } else {
      _pdf_action.hide();
      _survey_pdf.show();
      _info_edit.find('.pdf-file td.label').html('Downloadable PDF');
    }
  }

  // Input Validation
  
  function handle_pdf_action(action)
  {
    hide_status();
    validate_all();
  }

  function validate_input(sender,event)
  {
    hide_status();
    validate_all();
  }

  function validate_all()
  {
    ce.survey_info.validate_survey_name();
    validate_pdf_action();
    update_submit_revert();
  }

  function update_submit_revert()
  {
    const dirty = has_changes();

    const can_submit = (
      dirty &&
      ( ce.form.find('.invalid-value').length === 0) &&
      ( ce.form.find('.incomplete').length    === 0) &&
      ( ce.survey_editor.can_submit() )
    );

    if(dirty) { ce.revert.prop('disabled',false).css('opacity',1); } 
    else      { ce.revert.prop('disabled',true).css('opacity',0); }

    ce.submit.prop('disabled', can_submit === false);
  }

  $(document).on('SurveyWasReordered',update_submit_revert);
  $(document).on('SurveyWasModified',update_submit_revert);

  function validate_pdf_action()
  {
    if(ce.cur_survey.has_pdf) {
      const action = _pdf_action.val();

      var ok = true;
      if( action === "replace" ) {
        if( !_survey_pdf.val() ) { ok = false; }
      } else {
        _survey_pdf.val('');
      }

      if(ok) { _survey_pdf.removeClass('invalid-value'); }
      else   { _survey_pdf.addClass('invalid-value');    }
    } 
    else {
      if(_survey_pdf.val()) { _clear_pdf.show(); }
      else                  { _clear_pdf.hide(); }
    }
  }


  function current_values()
  {
    var rval = {}
    ce.form.find('.watch').each( function(index) {
      const e = $(this);
      rval[ e.attr('name') ] = e.val();
    });
    rval['pdf_action'] = _pdf_action.val();
    return rval;
  }

  function has_changes()
  {
    let found_change = false;
    Object.entries(_last_saved).forEach(([key,value]) => {
      const e = ce.form.find(`[name=${key}]`);
      if( value !== e.val() ) {
        found_change = true; 
        return false;  // no need to continue loop
      }
    });
    if(found_change) { return true; }

    if( ce.undo_manager?.hasUndo() ) { return true; }

    return false;
  }

  function handle_revert()
  {
    for( let key in _last_saved ) {
      ce.form.find(`[name=${key}]`).val(_last_saved[key]);
    }
    if(_last_saved.pdf_action === 'replace') { _survey_pdf.show(); } 
    else                                     { _survey_pdf.hide(); }

    const content = ce.survey_data.content( ce.cur_survey.id );
    ce.survey_editor.update(content);

    $(document).trigger('SurveyDataChanged');
    validate_all();
  }

  function handle_submit()
  {
    const cur_values = current_values();
    var survey_name = cur_values.survey_name.trim();
    if( survey_name.length == 0 ) { survey_name = ce.cur_survey.title; }

    const content = ce.survey_editor.content();

    var formData = new FormData();
    formData.append('nonce',ce.nonce);
    formData.append('ajax','admin/update_survey');
    formData.append('survey_id',ce.cur_survey.id);
    formData.append('name',survey_name);
    formData.append('content',JSON.stringify(content));

    if(ce.cur_survey.has_pdf) {
      switch(_pdf_action.val()) {
        case 'drop':
          formData.append('existing_pdf','drop');
          break;
        case 'replace':
          formData.append('existing_pdf','replace');
          formData.append('survey_pdf',_survey_pdf[0].files[0]);
          break;
      }
    } else {
      if(_survey_pdf.val()) {
        formData.append('existing_pdf','add');
        formData.append('survey_pdf',_survey_pdf[0].files[0]);
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
        _last_saved = cur_values;
        _last_saved.survey_name = '';

        ce.cur_survey.title = survey_name;
        ce.cur_survey.has_pdf = data.has_pdf;
        ce.survey_data.content(ce.cur_survey.id,content);

        _survey_name.val('');

        $(document).trigger('SurveyDataChanged');
        show_status('info','Changes Saved');

        select_survey();
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

  var _last_saved = current_values();

  return {
    state:'draft',
    block_survey_select: block_survey_select,
    select_survey: select_survey,
    update_info: update_info,
    handle_pdf_action:handle_pdf_action,
    has_changes: has_changes,
    validate_input:validate_input,
    handle_revert:handle_revert,
    handle_submit:handle_submit,
  };
};

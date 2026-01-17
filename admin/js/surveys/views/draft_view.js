export default function init(ce)
{
  const _info_edit    = $('#info-edit');
  const _survey_name  = $('#survey-name');

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
    ce.metadata.update_for_survey();

    _survey_name.attr({ required:false, placeholder:ce.cur_survey.title}).val('');

    ce.form.find('input.watch').on('input',ce.handle_input).on('change',ce.handle_change);
    ce.form.find('select.watch').on('change',ce.handle_change);

    ce.submit.val('Save Changes').prop('disabled',true);
    ce.revert.val('Revert').prop('disabled',true).css('opacity',0);
    ce.submit.show();
    ce.revert.show();
    ce.preview.show();
    ce.preview_js.show();
    ce.printable.css('margin-right','0.5em').show();

    // change the ContentDataLoaded handler
    $(document)
    .off('ContentDataLoaded')
    .on('ContentDataLoaded', function(e,id,data) { 
      ce.controller.update_content(data);
      validate_all();
    } );

    // note that if the content associated with the current survey has not yet been
    //   retrieved from the server, the returned content value will be null and an
    //   AJAX call will be issued to retrieve the data.  On completion of retrieving
    //   the data, a ContentDataLoaded event will be triggered.
    const content = ce.survey_data.content( ce.cur_survey.id );

    ce.controller.enable_edits();
    ce.controller.update_content(content);
    _last_saved = current_values();
    validate_all();
  }


  function update_info()
  {
    _info_edit.show();
  }

  // Input Validation
  
  function validate_input(sender,event)
  {
    hide_status();
    validate_all();
  }

  function validate_all()
  {
    ce.metadata.validate_survey_name();
    update_submit_revert();
  }

  function update_submit_revert()
  {
    const dirty = has_changes();

    const can_submit = (
      dirty &&
      ( ce.form.find('.invalid-value').length === 0) &&
      ( ce.form.find('.incomplete').length    === 0) &&
      ( ce.controller.can_submit() )
    );

    ce.preview.prop('disabled',!has_content());
    ce.preview_js.parent().toggleClass('hidden',!has_content());

    if(dirty) { 
      ce.revert.prop('disabled',false).css('opacity',1); 
      ce.controls?.disable_action_links();
    } 
    else      
    { 
      ce.revert.prop('disabled',true).css('opacity',0); 
      ce.controls?.enable_action_links();
    }

    ce.submit.prop('disabled', can_submit === false);
  }

  $(document).on('SurveyWasReordered',update_submit_revert);
  $(document).on('SurveyWasModified',update_submit_revert);


  function current_values()
  {
    var rval = {}
    ce.form.find('.watch').each( function(index) {
      const e = $(this);
      rval[ e.attr('name') ] = e.val();
    });
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

  function has_content()
  {
    const content = ce.controller.content();
    if(!content)          { return false; }
    if(!content.sections) { return false; }
    return Object.keys(content.sections).length > 0;
  }

  function handle_revert()
  {
    for( let key in _last_saved ) {
      ce.form.find(`[name="${key}"]`).val(_last_saved[key]);
    }

    const content = ce.survey_data.content( ce.cur_survey.id );
    ce.controller.update_content(content);

    $(document).trigger('SurveyDataChanged');
    validate_all();
  }

  function handle_submit()
  {
    const cur_values = current_values();
    var survey_name = cur_values.survey_name.trim();
    if( survey_name.length == 0 ) { survey_name = ce.cur_survey.title; }

    const content = ce.controller.content();
    const json_content = JSON.stringify(content);

    var formData = new FormData();
    formData.append('nonce',ce.nonce);
    formData.append('ajax','admin/update_survey');
    formData.append('id',ce.cur_survey.id);
    formData.append('name',survey_name);
    formData.append('content',json_content);

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
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
        ce.survey_data.content(ce.cur_survey.id,data.content);
        _survey_name.val('');

        $(document).trigger('SurveyDataChanged');
        show_status('info','Changes Saved');

        select_survey();

      } else {
        // should never get here...
        internal_error(jqXHR);
      }
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      ajax_error_hander(jqXHR,'update survey');
    } )
    .always( function() {
      validate_all();
    });
  }

  let _previewWindow = null;
  const _previewTabName = 'ttt_preview';

  function handle_preview()
  {
    // create or reuse the preview tab
    var can_reuse = !!_previewWindow && !_previewWindow.closed;
    if( can_reuse ) {
      try       { _previewWindow.location.replace('about:blank'); } 
      catch (e) { can_reuse = false;                              }
    }

    if( !can_reuse ) {
      if(!(_previewWindow = window.open('',_previewTabName))) {
        alert("Failed to open a new tab to display the preview.  Do you have popup blockers installed?");
        return;
      }
    }

    // bring it to front if browser allows that, otherwise raise an alert to user
    _previewWindow.focus();
    setTimeout(() => {
      if(document.hasFocus()) { alert("Preview opened in another tab."); }
    }, 800);

    // create temporary form to request preview
    const nonce = ce.form.find('[name=preview-nonce]').val();

    const content = ce.controller.content();
    const json_content = JSON.stringify(content);
    const title = _survey_name.val() || ce.cur_survey.title;

    const form = $('<form>', {
      method:'POST',
      action:(ce.ajaxuri + '?preview'),
      target:_previewTabName,
    })
    .append( 
      $('<input>',{ type:'hidden', name:'nonce', value:nonce }),
      $('<input>',{ type:'hidden', name:'title', value:title}),
      $('<input>',{ type:'hidden', name:'content', value: json_content }),
      $('<input>',{ type:'hidden', name:'preview_js', value:ce.preview_js.prop('checked') })
    )
    .appendTo('body');

    form.submit();

    setTimeout(() => form.remove(), 0);
  }


  var _last_saved = current_values();

  return {
    state:'draft',
    block_survey_select, select_survey,
    update_info, has_changes, validate_input,
    handle_revert, handle_submit, handle_preview,
  };
};

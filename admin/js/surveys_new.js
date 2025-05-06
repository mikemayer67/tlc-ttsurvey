export default function new_controller(ce)
{
  const _info_edit    = $('#info-edit');
  const _survey_name  = $('#survey-name');
  const _survey_clone = $('#survey-clone-from');
  const _survey_pdf   = $('#survey-pdf');
  const _pdf_action   = $('#existing-pdf-action');
  const _clear_pdf    = $('#clear-pdf');

  let _prior_id = null;

  function validate_input(sender)
  {
    if(_survey_name.is(sender)) {
      update_submit();
    }
  }

  function update_submit()
  {
    // other status handlers will have more complicated logic, but for new surveys
    //   the only check is on survey name

    ce.survey_info.validate_survey_name();

    const survey_name = _survey_name.val().trim();

    const bad_name = (survey_name.length == 0 || _survey_name.hasClass('invalid-value'));
    ce.submit.prop('disabled',bad_name);
  }

  function select_new(prior)
  {
    if(prior.id !== 'new') {
      _prior_id = prior.id;
    }

    _info_edit.show();

    _survey_name.attr({ required:true, placeholder:'required', }).val('');

    _info_edit.find('tr.clone-from').show();

    _survey_clone.find('option:not(:first)').remove();
    _survey_clone.append(ce.survey_controls.cloneable_surveys());
    _survey_clone.val('none');

    _info_edit.find('.pdf-file td.label').html('Downloadable PDF');
    _survey_pdf.val('').show();
    _pdf_action.hide();
    _clear_pdf.hide();

    // The only new survey field that needs validation is the survey name
    _survey_name.on('input',ce.handle_input);
    _survey_name.on('change',ce.handle_change);

    ce.button_bar.show();

    ce.submit.val('Create Survey');
    ce.revert.val('Cancel').prop('disabled',false).css('opacity',1);
    update_submit();
  }

  function submit_new()
  {
    const formData = new FormData();
    formData.append('nonce',ce.nonce);
    formData.append('ajax','admin/create_new_survey');
    formData.append('name',_survey_name.val().trim());
    const clone = _survey_clone.val();
    if(!isNaN(clone)) { formData.append('clone',clone); }
    formData.append('survey_pdf',_survey_pdf[0].files[0]);

    $.ajax({
      type: 'POST',
      url: ce.ajaxuri,
      contentType: false,
      processData: false,
      dataType: 'json',
      data: formData,

    })
    .done( function(data,start,jqHXR) {
      if(data.success) { ce.survey_controls.add_new_survey(data.survey); }
      else             { alert('Failed to create new survey: '+error);   }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    });
  }


  function cancel_new()
  {
    ce.survey_controls.select_survey(_prior_id);
  }

  return {
    status:'new',
    select_survey:select_new,
    handle_revert:cancel_new,
    handle_submit:submit_new,
    validate_input:validate_input,
  };
};

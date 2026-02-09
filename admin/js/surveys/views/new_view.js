export default function init(ce)
{
  const _info_edit    = $('#info-edit');
  const _survey_name  = $('#survey-name');
  const _survey_clone = $('#survey-clone-from');

  let _prior_id = null;

  function validate_input(sender,event)
  {
    if(_survey_name.is(sender)) {
      update_submit();
    }
  }

  function update_submit()
  {
    // other status handlers will have more complicated logic, but for new surveys
    //   the only check is on survey name

    ce.metadata.validate_survey_name();

    const survey_name = _survey_name.val().trim();

    const bad_name = (survey_name.length == 0 || _survey_name.hasClass('invalid-value'));
    ce.submit.prop('disabled',bad_name);
  }

  function select_new(prior)
  {
    if(prior?.id !== 'new') {
      _prior_id = prior?.id;
    }

    _info_edit.show();
    ce.controller.hide_content();

    _survey_name.attr({ required:true, placeholder:'required', }).val('');

    _info_edit.find('tr.clone-from').show();

    _survey_clone.find('option:not(:first)').remove();
    var cur_status = '';
    const cloneable = ce.controls?.cloneable_surveys() ?? [];
    for(const opt of cloneable) {
      var status = $(opt).attr('status');
      if(status !== cur_status) {
        cur_status = status;
        status = status.charAt(0).toUpperCase() + status.slice(1);
        _survey_clone.append($('<option>',{'text':status,'disabled':true}));
      }
      _survey_clone.append(opt);
    }
    _survey_clone.prop('disabled',cloneable.length===0);
    _survey_clone.closest('tr').toggleClass('disabled',cloneable.length===0);

    // The only new survey field that needs validation is the survey name
    _survey_name.on('input',ce.handle_input);
    _survey_name.on('change',ce.handle_change);

    ce.submit.val('Create Survey');
    ce.revert.val('Cancel').prop('disabled',!_prior_id).css('opacity',_prior_id?1:0.25);
    ce.submit.show();
    ce.preview.hide();
    ce.download_pdf.hide();

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

    $.ajax({
      type: 'POST',
      url: ce.ajaxuri,
      contentType: false,
      processData: false,
      dataType: 'json',
      data: formData,

    })
    .done( function(data,start,jqHXR) {
      if(data.success) {
        ce.controls.add_new_survey(data.survey);
      } else {
        alert('Failed to create new survey: ' + error);
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      ajax_error_handler(jqXHR,'create new survey');
    });
  }

  function cancel_new()
  {
    ce.controls.select_survey(_prior_id);
  }

  return {
    status:'new',
    select_survey:select_new,
    handle_revert:cancel_new,
    handle_submit:submit_new,
    validate_input:validate_input,
  };
};

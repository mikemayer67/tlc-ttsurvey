export default function new_controller(ce)
{
  const _info_edit    = $('#info-edit');
  const _survey_name  = $('#survey-name');
  const _survey_clone = $('#survey-clone-from');
  const _survey_pdf   = $('#survey-pdf');
  const _pdf_action   = $('#existing-pdf-action');
  const _clear_pdf    = $('#clear-pdf');

  let _prior_id = null;

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

    ce.button_bar.show();
    ce.submit.val('Create Survey');
    ce.revert.val('Cancel');
  }

  function cancel_new()
  {
    ce.survey_controls.select_survey(_prior_id);
  }

  return {
    status:'new',
    select_survey:select_new,
    handle_revert:cancel_new,
  };

};

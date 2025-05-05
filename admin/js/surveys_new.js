const self = {};

function select_new(prior)
{
  self.prior_id = prior.id;

  self.ce.button_bar.show();
  self.ce.submit.val('Create Survey');
  self.ce.revert.val('Cancel');
}

function cancel_new()
{
  self.ce.survey_controls.select_survey(self.prior_id);
}

function update_info_edit()
{
  self.info_edit.show();

  self.survey_name.attr({ required:true, placeholder:'required', }).val('');

  self.info_edit.find('tr.clone-from').show();
  self.survey_clone.val('none');

  self.info_edit.find('.pdf-file td.label').html('Downloadable PDF');
  self.survey_pdf.val('').show();
  self.pdf_action.hide();
  self.clear_pdf.hide();
}

function new_controller(ce) {
  self.ce = ce;
  self.info_edit    = $('#info-edit');
  self.survey_name  = $('#survey-name');
  self.survey_clone = $('#survey-clone');
  self.survey_pdf   = $('#survey-pdf');
  self.pdf_action   = $('#existing-pdf-action');
  self.clear_pdf    = $('#clear-pdf');

  return {
    status:'new',
    update_info_edit:update_info_edit,
    select_survey:select_new,
    handle_revert:cancel_new,
  };
};

export default new_controller;

const self = {};

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

function update_info_edit()
{
  const survey = self.ce.cur_survey;

  self.info_edit.show();

  self.survey_name.attr({ required:false, placeholder:survey['title'], }).val('');

  self.survey_pdf.val('');
  self.pdf_action.hide();
  self.clear_pdf.hide();

  if(survey.has_pdf) {
    self.pdf_action.val('keep').show();
    self.survey_pdf.hide();
    self.info_edit.find('.pdf-file td.label').html('Existing PDF');
  } else {
    self.pdf_action.hide();
    self.survey_pdf.show();
    self.info_edit.find('.pdf-file td.label').html('Downloadable PDF');
  }
  self.survey_pdf.val('');
}

//function current_values()
//{
//  var values = {
//    survey_name: self.ce.survey_name.val(),
//  };
//  // @@@ TODO: Add survey elements
//  return values;
//}

function has_changes()
{
//  const survey  = self.ce.cur_survey;
//  const current = current_values();
//
//  var current_name = current['survey_name'].trim();
//  if(current_name.length>0) {
//    var saved_name = ce.saved_values['survey_name'];
//    if( saved_name.length == 0 ) { saved_name = survey.title; }
//
//    if( current_values['survey_name'] !== saved_name ) { return true; }
//  }

  // @@@ TODO: Add pdf actions
  // if(ce.existing_pdf_action.val() !=='keep') { return true; }
  // if(ce.survey_pdf.val()) { return true; }

  // @@@ TODO: Add survey elements

//  return false;
 
return true;
}

function draft_controller(ce) {
  self.ce = ce;
  self.info_edit    = $('#info-edit');
  self.survey_name  = $('#survey-name');
  self.survey_pdf   = $('#survey-pdf');
  self.pdf_action   = $('#existing-pdf-action');
  self.clear_pdf    = $('#clear-pdf');

  return {
    state:'draft',
    block_survey_select: block_survey_select,
    update_info_edit: update_info_edit,
    has_changes: has_changes,
  }
};

export default draft_controller;

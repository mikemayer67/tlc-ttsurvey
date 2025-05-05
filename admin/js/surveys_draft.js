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
    _info_edit.show();

    _survey_name.attr({ required:false, placeholder:ce.cur_survey['title'], }).val('');

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
    _survey_pdf.val('');
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

  return {
    state:'draft',
    block_survey_select: block_survey_select,
    select_survey: select_survey,
    has_changes: has_changes,
  };
};

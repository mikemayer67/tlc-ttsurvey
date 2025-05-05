let self = {
  surveys: {},
};

function format_date(date)
{
  var date = dayjs(date);
  date = date.format('MMM D, YYYY h:mma');
  return date;
}

function update_info()
{
  const survey = self.ce.cur_survey;

  self.info_bar.hide();

  var item;

  item = self.info_bar.find('.info-label.created');
  if(survey.created) {
    self.info_bar.show();
    item.show();
    item.find('.date').html(format_date(survey.created));
  } else { 
    item.hide();
  }

  item = self.info_bar.find('.info-label.opened'); // note the mismatch between active/opened
  if(survey.active) {
    self.info_bar.show();
    item.show();
    item.find('.date').html(format_date(survey.active));
  } else { 
    item.hide();
  }

  item = self.info_bar.find('.info-label.closed');
  if(survey.closed) {
    self.info_bar.show();
    item.show();
    item.find('.date').html(format_date(survey.closed));
  } else { 
    item.hide();
  }

  item = self.info_bar.find('.pdf-link');
  if(survey.has_pdf) {
    item.find('.no-link').hide();
    item.find('a').attr('href',self.ce.pdfuri+survey.id).show();
  } else {
    item.find('.no-link').show();
    item.find('a').hide();
  }

  // The following are dependent on survey status.  As each status event handler
  //   should only need to know about the fields that it cares about and not those
  //   that it does not care about,  We reset and hide the entire info edit box here
  //   and ask each event handler to turn it on and customize it as the need it.

  self.info_edit.hide();
  self.info_edit.find('tr.clone-from').hide();
  self.survey_pdf.val('');
  self.pdf_action.hide();
  self.clear_pdf.hide();

  self.ce.dispatch('update_info_edit');
}

function handle_survey_pdf()
{
  const survey = self.ce.cur_survey;

  if(!self.ce.cur_survey.has_pdf) {
    var pdf_file = self.survey_pdf.val();
    if(pdf_file) { self.clear_pdf.show(); }
    else         { self.clear_pdf.hide(); }
  }
//  validate_all();
}

function clear_survey_pdf()
{
  self.survey_pdf.val('');
  self.clear_pdf.hide();
}

function handle_pdf_action()
{
  if(self.pdf_action.val() === 'replace') {
    self.survey_pdf.show();
  } else {
    self.survey_pdf.hide();
  }
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


function survey_info(ce) {
  self.ce = ce;

  self.info_bar = ce.form.find('.content-box .info-bar');
  self.info_edit = $('#info-edit');

  self.survey_clone = $('survey-clone');
  self.survey_pdf   = $('#survey-pdf');
  self.pdf_action   = $('#existing-pdf-action');
  self.clear_pdf    = $('#clear-pdf');

  self.survey_pdf.on('change',handle_survey_pdf);
  self.clear_pdf.on('click',clear_survey_pdf);
  self.pdf_action.on('change',handle_pdf_action);

  $(document).on('surveySelectionChanged',update_info);

  update_info();

  return {
  };
}

export default survey_info;

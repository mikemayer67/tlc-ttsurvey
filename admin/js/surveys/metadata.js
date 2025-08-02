function format_date(date)
{
  var date = dayjs(date);
  date = date.format('MMM D, YYYY h:mma');
  return date;
}

export default function init(ce)
{
  const _info_bar     = ce.form.find('.content-box .info-bar');
  const _info_edit    = $('#info-edit');

  const _survey_name  = $('#survey-name');
  const _survey_clone = $('#survey-clone');
  const _survey_pdf   = $('#survey-pdf');
  const _pdf_action   = $('#existing-pdf-action');
  const _clear_pdf    = $('#clear-pdf');

  function update_info()
  {
    _info_bar.hide(); // we'll unhide it if/when we find something to put in it
    
    var item = _info_bar.find('.info-label.created');
    if(ce.cur_survey.created) {
      _info_bar.show();
      item.show();
      item.find('.date').html(format_date(ce.cur_survey.created));
    } else { 
      item.hide();
    }
  
    item = _info_bar.find('.info-label.opened'); // note the mismatch between active/opened
    if(ce.cur_survey.active) {
      _info_bar.show();
      item.show();
      item.find('.date').html(format_date(ce.cur_survey.active));
    } else { 
      item.hide();
    }
  
    item = _info_bar.find('.info-label.closed');
    if(ce.cur_survey.closed) {
      _info_bar.show();
      item.show();
      item.find('.date').html(format_date(ce.cur_survey.closed));
    } else { 
      item.hide();
    }
  
    item = _info_bar.find('.pdf-link');
    if(ce.cur_survey.has_pdf) {
      item.find('.no-link').hide();
      item.find('a').attr('href',ce.pdfuri+ce.cur_survey.id).show();
    } else {
      item.find('.no-link').show();
      item.find('a').hide();
    }
  
    // The following are dependent on the survey status.  As each status controller
    //   should only need to know about the fields that it cares about and not those
    //   that it does not care about,  We reset and hide the entire info edit box here
    //   and ask each controller to turn it on and customize it as the need it.
  
    _info_edit.hide();
    _info_edit.find('tr.clone-from').hide();
    _pdf_action.hide();
    _clear_pdf.hide();
    _survey_pdf.val('');
  
    ce.dispatch('update_info');
  }

  // event handlers

  function handle_survey_pdf()
  {
    if(!ce.cur_survey.has_pdf) {
      if(_survey_pdf.val()) { _clear_pdf.show(); }
      else                  { _clear_pdf.hide(); }
    }
  }

  function clear_survey_pdf()
  {
    _survey_pdf.val('');
    _clear_pdf.hide();
  }

  function handle_pdf_action()
  {
    const action = _pdf_action.val();
    if(action === 'replace') { _survey_pdf.show(); }
    else                     { _survey_pdf.hide(); }
    ce.dispatch('handle_pdf_action',action)
  }

  _survey_pdf.on('change',handle_survey_pdf);
  _clear_pdf.on('click',clear_survey_pdf);
  _pdf_action.on('change',handle_pdf_action);

  // validation methods

  function validate_survey_name()
  {
    const name = _survey_name.val().trim();
    const err = ce.form.find('div.error[name=survey_name]');
    err.hide();
    _survey_name.removeClass('invalid-value');
    if(name.length > 0) {
      if(name.length < 10) {
        _survey_name.addClass('invalid-value');
        err.html("too short").show();
      }
    }
  }

  $(document).on('SurveyDataChanged',update_info);

  return {
    update_for_survey: update_info,
    validate_survey_name: validate_survey_name,
  };
}

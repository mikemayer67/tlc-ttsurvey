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
  
    // The following are dependent on the survey status.  As each status controller
    //   should only need to know about the fields that it cares about and not those
    //   that it does not care about,  We reset and hide the entire info edit box here
    //   and ask each controller to turn it on and customize it as the need it.
  
    _info_edit.hide();
    _info_edit.find('tr.clone-from').hide();
  
    ce.dispatch('update_info');
  }

  // validation methods

  function validate_survey_name()
  {
    const name = _survey_name.val().trim();
    const err = ce.form.find('div.error[name=survey_name]');
    err.hide();
    _survey_name.removeClass('invalid-value');
    if(name.length > 0) {
      if(name.length < 8) {
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

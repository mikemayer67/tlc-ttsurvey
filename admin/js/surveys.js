( function() {

  let ce = {};

  function handle_surveys_submit(event) 
  {
    event.preventDefault();
    alert('handle_submit');
  }

  function handle_survey_select(event)
  {
    update_display_state();
  }

  function update_display_state() 
  {
    var selected = ce.survey_select.find(':selected');
    var status = selected.attr('status');
    ce.survey_status.html(status);
    ce.action_links.addClass('hidden');
    ce.action_links.filter('.'+status).removeClass('hidden');
    ce.new_survey_table.hide();
    if(status=='draft') {
      ce.button_bar.removeClass('hidden');
      ce.submit.val('Save Changes');
      ce.revert.val('Revert');
    }
    else if(status=='new') {
      ce.button_bar.removeClass('hidden');
      ce.submit.val('Create Survey');
      ce.revert.val('Cancel');
      ce.new_survey_table.show();
    } else {
      ce.button_bar.addClass('hidden');
    }
  }

  function enforce_alphanum_only(event)
  {
    var v = $(this).val();
    console.log(v);
    v = v.replace(/[^a-zA-Z0-9& ]/g,'');
    $(this).val(v);
  }

  function handle_action_link(event)
  {
    alert('handle action link');
  }

  function update_submit()
  {
  }

  function has_changes()
  {
    return false;
  }

  $(document).ready(
    function($) {
    ce.form             = $('#admin-surveys');
    ce.ajaxuri          = $('#admin-surveys input[name=ajaxuri]').val();
    ce.nonce            = $('#admin-surveys input[name=nonce]').val();
    ce.status           = $('#ttt-status');
    ce.survey_select    = $('#survey-select');
    ce.survey_status    = ce.form.find('span.survey-status');
    ce.action_links     = ce.form.find('a.action');
    ce.button_bar       = ce.form.find('div.button-bar');
    ce.new_survey_table = $('#new-survey');
    ce.submit           = $('#changes-submit');
    ce.revert           = $('#changes-revert');

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );

    ce.form.on('submit',handle_surveys_submit);
    ce.survey_select.on('change',handle_survey_select);
    ce.action_links.on('click',handle_action_link);

    ce.form.find('input.alphanum-only').on('input',enforce_alphanum_only);

    has_change_cb = has_changes;

    update_display_state();
    update_submit();
  });

})();

( function() {

  let ce = {};

  function handle_surveys_submit(event) 
  {
    event.preventDefault();
    alert('handle_submit');
  }

  function handle_survey_select(event)
  {
    var selected = $(this).find(':selected');
    var status = selected.attr('status');
    ce.survey_status.html(status);
    ce.action_links.addClass('hidden');
    ce.action_links.filter('.'+status).removeClass('hidden');
    if(status=='active') {
      ce.button_bar.removeClass('hidden');
      ce.submit.val('Save Changes');
      ce.revert.val('Revert');
    }
    else if(status=='new') {
      ce.button_bar.removeClass('hidden');
      ce.submit.val('Create Survey');
      ce.revert.val('Cancel');
    } else {
      ce.button_bar.addClass('hidden');
    }
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
    ce.form            = $('#admin-surveys');
    ce.ajaxuri         = $('#admin-surveys input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-surveys input[name=nonce]').val();
    ce.status          = $('#ttt-status');
    ce.survey_select   = $('#survey-select');
    ce.survey_status   = ce.form.find('span.survey-status');
    ce.action_links    = ce.form.find('a.action');
    ce.button_bar      = ce.form.find('div.button-bar');
    ce.submit          = $('#changes-submit');
    ce.revert          = $('#changes-revert');

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );

    ce.form.on('submit',handle_surveys_submit);
    ce.survey_select.on('change',handle_survey_select);
    ce.action_links.on('click',handle_action_link);

    has_change_cb = has_changes;

    update_submit();
  });

})();

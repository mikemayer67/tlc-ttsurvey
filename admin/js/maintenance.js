( function() {

  let ce = {};

  function cleanup_options(e)
  {
    e.preventDefault();

    var data = {ajax:'admin/cleanup_options', 'nonce':ce.nonce};

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(data.count == 0 ) {
        alert('No unused select options found in the database');
      } else if(data.count == 1) {
        alert('Removed 1 unused select option from the database');
      } else {
        alert('Removed ' + data.count + ' unused select options from the database');
      }
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      ajax_error_handler(jqXHR,'maintenance options');
    } );
  }

  function handle_survey_select(e)
  {
    const $selected_opt = ce.survey_sel.find(':selected')
    if($selected_opt.val()) {
      const $first_opt = ce.survey_sel.children().first();
      if(ce.survey_prompt) { 
        ce.survey_sel.children().first().remove();
        ce.survey_prompt = false;
      }
      ce.confirm_survey_div.show();
      ce.confirm_survey.val('');
      ce.confirm_responses.val('');

      const created  = $selected_opt.data('created');
      if(created) { 
        ce.created.show();
        ce.created.find('.value').text(dayjs(created).format('MMM D, YYYY h:mma'));
      } else {
        ce.created.hide();
      }

      const modified = $selected_opt.data('modified');
      if(modified) { 
        ce.modified.show();
        ce.modified.find('.value').text(dayjs(modified).format('MMM D, YYYY h:mma'));
      } else {
        ce.modified.hide();
      }

      ce.num_responses   = $selected_opt.data('responses');
      if(ce.num_responses) 
      { 
        ce.confirm_responses_div.show(); 
        ce.responses.show();
        ce.responses.find('.value').text(ce.num_responses);
      } 
      else 
      {
        ce.confirm_responses_div.hide();
        ce.responses.hide();
      }
    }
    else 
    {
      ce.survey_info.html('');
    }

    toggle_survey_button();
  }

  function toggle_survey_button()
  {
    if(ce.confirm_survey.prop('placeholder') !== ce.confirm_survey.val()) {
      ce.survey_btn.disable();
      return;
    }
    if(ce.num_responses) {
      if (ce.confirm_responses.prop('placeholder') !== ce.confirm_responses.val()) {
        ce.survey_btn.disable();
        return;
      }
    }
    ce.survey_btn.enable();
  }

  function delete_survey(e)
  {
    e.preventDefault();
    
    const $selected_opt = ce.survey_sel.find(':selected');
    const survey_id = $selected_opt.val();
    const survey_name = $selected_opt.text();

    var data = { ajax:'admin/delete_survey', nonce:ce.nonce, survey_id };

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(data.success) {
        alert('Survey '+survey_name+' was deleted');
        $selected_opt.remove();
        handle_dropped_survey()
      } else {
        alert('Survey was not deleted: '+data.reason);
      }
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      ajax_error_handler(jqXHR,'maintenance survey');
    } );
  }

  function handle_dropped_survey()
  {
    const num_drafts = ce.survey_sel.children().length;
    const $header = ( num_drafts
      ? ce.form.find('input[name=select_header]').val()
      : ce.form.find('input[name=empty_header]').val()
    );
    $('<option>').val('').text($header).prependTo(ce.survey_sel);
    ce.survey_sel.val('');
    ce.survey_prompt=true;

    ce.created.hide();
    ce.modified.hide();
    ce.responses.hide();
    
    ce.confirm_responses_div.hide();
    ce.confirm_survey_div.hide();
    ce.survey_btn.disable();
  }

  $(document).ready( function($) {
    ce.form    = $('#admin-maintenance');
    ce.ajaxuri = ce.form.find('input[name=ajaxuri]').val();
    ce.nonce   = ce.form.find('input[name=nonce]').val();

    ce.options_btn = ce.form.find('button.maintenance.options');

    ce.survey_sel            = ce.form.find('select.draft-surveys');
    ce.survey_btn            = ce.form.find('button.maintenance.survey').disable();
    ce.confirm_survey_div    = ce.form.find('div.delete.survey').hide();
    ce.confirm_responses_div = ce.form.find('div.delete.responses').hide();
    ce.confirm_survey        = ce.form.find('input.confirm.survey');
    ce.confirm_responses     = ce.form.find('input.confirm.responses');
    ce.created               = ce.form.find('.draft-info .created').hide();
    ce.modified              = ce.form.find('.draft-info .modified').hide();
    ce.responses             = ce.form.find('.draft-info .responses').hide();

    ce.survey_prompt = true;

    ce.survey_sel.on('change',handle_survey_select)
    ce.confirm_survey.on('input change',toggle_survey_button);
    ce.confirm_responses.on('input change',toggle_survey_button);

    ce.options_btn.on('click',cleanup_options);
    ce.survey_btn.on('click',delete_survey);
  });

})();

export default function draft_controller(ce)
{
  const _info_edit    = $('#info-edit');
  const _survey_name  = $('#survey-name');
  const _survey_pdf   = $('#survey-pdf');
  const _pdf_action   = $('#existing-pdf-action');
  const _clear_pdf    = $('#clear-pdf');

  var _last_saved = {};
  var _changes    = {};

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

    ce.form.find('input.watch').on('input',ce.handle_input).on('change',ce.handle_change);
    ce.form.find('select.watch').on('change',ce.handle_change);

    ce.button_bar.show();
    ce.submit.val('Save Changes').prop('disabled',true);
    ce.revert.val('Revert').prop('disabled',true).css('opacity',0);

    validate_all();
  }

  // Input Validation
  
  function handle_pdf_action(action)
  {
    validate_all();
  }

  function validate_input(sender,event)
  {
    // before validating the input/select, record changes
    const key   = sender.attr('name');
    const value = sender.val();
    if( _last_saved[key] !== value ) { _changes[key] = value; } 
    else                             { delete _changes[key];  }

    // now let's validatehe input change
    validate_all();
  }

  function validate_all()
  {
    ce.survey_info.validate_survey_name()
    validate_pdf_action();

    const has_changes = Object.keys(_changes).length > 0;
    const has_errors = ce.form.find('.invalid-value').length > 0;

    if(has_changes) {
      ce.revert.prop('disabled',false).css('opacity',1);
    }
    else {
      ce.revert.prop('disabled',true).css('opacity',0);
    }

    ce.submit.prop('disabled',has_errors || !has_changes);
  }

  function validate_pdf_action()
  {
    const action = _pdf_action.val();
    if(action !==_last_saved.pdf_action) { _changes.pdf_action = action; } 
    else                                 { delete _changes.pdf_action;   }

    var ok = true;
    if( action === "replace" ) {
      if( !_survey_pdf.val() ) { ok = false; }
    } else {
      _survey_pdf.val('');
      delete _changes.survey_pdf;
    }

    if(ok) { _survey_pdf.removeClass('invalid-value'); }
    else   { _survey_pdf.addClass('invalid-value');    }
  }


  function current_values()
  {
    var rval = {}
    ce.form.find('.watch').each( function(index) {
      const e = $(this);
      rval[ e.attr('name') ] = e.val();
    });
    rval['pdf_action'] = _pdf_action.val();
    return rval;
  }

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

  _last_saved = current_values();

  return {
    state:'draft',
    block_survey_select: block_survey_select,
    select_survey: select_survey,
    handle_pdf_action:handle_pdf_action,
    has_changes: has_changes,
    validate_input:validate_input,
  };
};

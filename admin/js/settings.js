( function() {

  let ce = {};
  let saved_settings = {};

  let validation_timer=null;

  function handle_smtp_auth_change()
  {
    if(this.value == '0') {
      ce.inputs.smtp_port.attr('placeholder','465');
    } else {
      ce.inputs.smtp_port.attr('placeholder','587');
    };
  }

  function handle_settings_submit(event)
  {
    event.preventDefault();
    var sender = event.originalEvent.submitter;

    if(ce.revert.is(sender)) { 
      revert_values();
      return;
    }
    if(!ce.submit.is(sender)) { return; }

    var cur_values = current_values();
    var data = {...cur_values, 'ajax':'admin/update_settings', 'nonce':ce.nonce};

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(data.success) {
        ce.nonce = data.nonce;
        ce.hidden['nonce'].attr('value',data.nonce);
        saved_settings = cur_values;
        show_status('info','Changes Saved');
      } 
      else {
        if( 'bad_nonce' in data ) {
          alert("Somthing got out of sync.  Reloading page.");
          location.reload();
        } else {
          for( const [key,error] of Object.entries(data) ) {
            if( key in ce.inputs     ) { ce.inputs[key].addClass('invalid-value'); }
            if( key in ce.error_divs ) { ce.error_divs[key].show().html(error);    }
          }
          update_submit();
        }
      }
      update_submit();
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    } )
    ;
  }

  function validate_all()
  {
    $('div.error').hide().html('');
    ce.form.children('input').removeClass('invalid-value');

    var data = {
      ajax:"admin/validate_settings",
      nonce:ce.nonce, 
    };
    for( const [key,field] of Object.entries(ce.inputs)) {
      data[key] = field.val();
    }

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(!data.success) {
        for( const [key,value] of Object.entries(data) ) {
          if(key in ce.inputs) {
            ce.inputs[key].addClass('invalid-value');
            ce.error_divs[key].show().html(value);
          }
        }
      }
      update_submit();
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    } )
    ;
  }

  function handle_test_smtp()
  {
    var data = {
      ajax:"admin/validate_smtp",
      nonce:ce.nonce, 
    };
    for( const [key,field] of Object.entries(ce.inputs) ) {
      data[key] = field.val(); 
    }

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(data.success) {
        ce.test_response.removeClass('error').html('Success');
      } else {
        ce.test_response.addClass('error').html(data.reason);
      }
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    } )
    ;
  }

  function update_submit()
  {
    ce.test_response.html('');

    var errors = $('input.invalid-value');
    var dirty = has_changes();
    var can_submit = dirty && errors.length==0;
    ce.submit.prop('disabled',!can_submit);

    if(dirty) {
      ce.revert.prop('disabled',false).css('opacity',1);
    } else {
      ce.revert.prop('disabled',false).css('opacity',0);
    }
  }

  function handle_change(event)
  {
    hide_status();
    clearTimeout(validation_timer);
    validation_timer = null;
    validate_all();
  }

  function handle_input(event)
  {
    hide_status();
    clearTimeout(validation_timer);
    $(this).removeClass('invalid-value');
    validation_timer = setTimeout(validate_all,750);
  }

  function current_values()
  {
    var values = {};
    Object.entries(ce.inputs).forEach(  ([key,field]) => { values[key] = field.val(); } );
    Object.entries(ce.selects).forEach( ([key,field]) => { values[key] = field.val(); } );
    return values;
  }

  function revert_values()
  {
    Object.entries(ce.inputs).forEach(  ([key,field]) => { field.val(saved_settings[key]); } );
    Object.entries(ce.selects).forEach( ([key,field]) => { field.val(saved_settings[key]); } );
    validate_all();
  }

  function has_changes()
  {
    var current = current_values();
    for( var k in saved_settings ) {
      if( saved_settings[k] != current[k] ) { 
        return true;
      }
    }
    return false;
  }

  $(document).ready(
    function($) {
    ce.form            = $('#admin-settings');
    ce.ajaxuri         = $('#admin-settings input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-settings input[name=nonce]').val();
    ce.test_response   = $('#test_connection_response');
    ce.submit          = $('#settings_submit');
    ce.revert          = $('#settings_revert');

    ce.submit.prop('disabled',true);
    ce.revert.prop('disabled',true).css('opacity',0);

    ce.hidden = {}
    ce.form.find('input[type=hidden]').each(
      function() { ce.hidden[$(this).attr('name')] = $(this) }
    );
    ce.inputs = {}
    ce.form.find('input').not('.hidden').not('[type=hidden]').not('[type=submit]').each(
      function() { ce.inputs[$(this).attr('name')] = $(this) }
    );
    ce.selects = {}
    ce.form.find('select').each(
      function() { ce.selects[$(this).attr('name')] = $(this) }
    );
    ce.error_divs = {}
    ce.form.find('div.error').each(
      function() { ce.error_divs[$(this).attr('name')] = $(this) }
    );

    saved_settings = current_values();

    ce.form.on('submit',handle_settings_submit);
    for( const e of Object.values(ce.selects) ) {
      e.on('change',handle_change);
    }
    for( const e of Object.values(ce.inputs) ) {
      e.on('change',handle_change);
      e.on('input',handle_input);
    };

    // smtp specific fields
    ce.selects.smtp_auth.on('change',handle_smtp_auth_change);
    $('#test_connection_button').on('click',handle_test_smtp);

    has_change_cb = has_changes;

    handle_smtp_auth_change();  // to set initial placeholder value
    validate_all();
  });

})();

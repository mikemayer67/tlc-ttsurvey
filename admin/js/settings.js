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
    const formData = new FormData(ce.form[0]);
    formData.append('ajax','admin/update_settings');
    formData.append('nonce',ce.nonce);

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: formData,
      processData: false,
      contentType: false,
    } )
    .done( function(data,status,jqXHR) {
      if('nonce' in data) { ce.nonce = data.nonce; }
      if(data.success) {
        ce.hidden['nonce'].attr('value',data.nonce);
        saved_settings = cur_values;
        ce.logo_select.find('option.new').removeClass('new');
        show_status('info','Changes Saved');
      } 
      else {
        for (const [key, error] of Object.entries(data)) {
          if (key in ce.inputs) { ce.inputs[key].addClass('invalid-value'); }
          if (key in ce.error_divs) { ce.error_divs[key].show().html(error); }
        }
        if('app_logo' in data) { revert_logo(); }
        show_status('error','Failed to Save Changes');

        requestAnimationFrame(() => {
          $('body').removeClass('flash-error'); 
          requestAnimationFrame(() => { 
            $('body').addClass('flash-error');
          });
        });
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
      clear_logo_file();
      handle_logo_select();
      update_submit();
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      ajax_error_handler(jqXHR,'update settings')
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
      ajax_error_handler(jqXHR,'validate settings')
    } )
    ;
  }

  function handle_logo_select(e)
  {
    const nav_logo = $('#ttt-navbar img.ttt-logo');
    const selected_logo = ce.logo_select.val();
    const upload_file = ce.logo_file[0].files[0];
    const upload_logo = upload_file?.name ?? '';

    if(ce.uploadLogoURI) { 
      URL.revokeObjectURL(ce.uploadLogoURI); 
      ce.uploadLogoURI = null;
    }

    let logo_uri = '';
    let clear_upload = true;

    if(selected_logo) {
      if(selected_logo === upload_logo) {
        logo_uri = URL.createObjectURL(upload_file);
        ce.uploadLogoURI = logo_uri;
        clear_upload = false;
      } else {
        logo_uri = 'img/uploads/' + encodeURIComponent(selected_logo);
      }
    }

    if (clear_upload) { clear_logo_file(); }

    if(logo_uri) { nav_logo.prop('src', logo_uri).removeClass('missing'); } 
    else         { nav_logo.addClass('missing'); }
  }

  function clear_logo_file()
  {
    ce.logo_file.val('');
    ce.logo_select.find('option.new').remove();
  }

  function handle_logo_file(e)
  {
    const input = ce.logo_file[0];
    if(!input.files.length) { return; }
    const filename = input.files[0].name;
    const option = $('<option>').val(filename).text(filename).addClass('new');
    ce.logo_select.find('option.new').remove();
    ce.logo_select.append(option);
    ce.logo_select.val(filename);

    ce.logo_select.trigger('change');
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
      ajax_error_handler(jqXHR,'validate SMTP')
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
    handle_logo_select();
    validate_all();
  }

  function revert_logo()
  {
    ce.logo_select.val(saved_settings['app_logo']);
    handle_logo_select();
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
    ce.submit          = $('#changes-submit');
    ce.revert          = $('#changes-revert');

    ce.submit.prop('disabled',true);
    ce.revert.prop('disabled',true).css('opacity',0);

    ce.logo_select = $('#app_logo_select');
    ce.logo_select.on('change', handle_logo_select);
    ce.logo_file = $('#app_logo_file');
    ce.logo_file.on('change', handle_logo_file);

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

    if(admin_lock.has_lock) {
      validate_all();
    }
    else {
      $('button').not('[name=tab]').attr('disabled',true);
      $('select').attr('disabled',true);
      $('input').attr('disabled',true);
    }
  });

})();

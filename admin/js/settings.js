var ce = {};
var bad_inputs = {};

function handle_smtp_auth_change(event)
{
  console.log('SMTP auth changed: ' + this.value);
  console.log('SMTP port: ' + ce.smtp_port.attr('placeholder'));
  if(this.value == '0') {
    ce.smtp_port.attr('placeholder','465');
  } else {
    ce.smtp_port.attr('placeholder','587');
  };
}

function handle_settings_submit(event)
{
  event.preventDefault();
  alert('Need to implement submit');
}

function internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
        + "): "+ jqXHR.statusMessage
        + "\nPlease let the survey admin know something went wrong."
       );
}

function validate_setting(event)
{
  var key      = event.data[0];
  var optional = event.data[2];
  var input    = ce[key];
  var value    = input.val();

  if(optional && value.length == 0) {
    if(key in bad_inputs) {
      input.removeClass('invalid-value');
      delete bad_inputs[key];
      update_status();
    }
    return;
  }

  $.ajax( {
    type: 'POST',
    url: ce.ajaxuri,
    dataType: 'json',
    data: {
      ajax:"admin/validate_settings",
      nonce:ce.nonce, 
      [key]:value,
    }
  } )
  .done( function(data,status,jqXHR) {
    if(data.success) {
      if(key in bad_inputs) {
        input.removeClass('invalid-value');
        delete bad_inputs[key];
      }
      update_status();
    }
    else {
      input.addClass('invalid-value');
      bad_inputs[key] = data[key];
      status = "<div class='" + key + "'>" + data[key] + "</div>";
      ce.status.removeClass().addClass('error').html(status);
      ce.submit.prop('disabled',true);
    } 
  } )
  .fail( function(jqXHR,textStatus,errorThrown) { 
    internal_error(jqXHR); 
  } )
  ;
}

function validate_all()
{
  $.ajax( {
    type: 'POST',
    url: ce.ajaxuri,
    dataType: 'json',
    data: {
      ajax:"admin/validate_settings",
      nonce:ce.nonce, 
      app_logo:ce.app_logo.val(),
      timezone:ce.timezone.val(),
      admin_email:ce.admin_email.val(),
      pwreset_timeout:ce.pwreset_timeout.val(),
      pwreset_length:ce.pwreset_length.val(),
    },
  } )
  .done( function(data,status,jqXHR) {
    ce.form.children('input').removeClass('invalid-value');
    if(data.success) {
      bad_inputs = {};
    }
    else {
      bad_inputs = data;
      delete bad_inputs.success;
    }
    update_status();
  } )
  .fail( function(jqXHR,textStatus,errorThrown) { 
    internal_error(jqXHR); 
  } )
  ;

}

function update_status()
{
  if($.isEmptyObject(bad_inputs)) {
    ce.submit.prop('disabled',false);
    ce.status.removeClass().addClass('none').html('');
  } else {
    var status = '';
    for(var [k,v] of Object.entries(bad_inputs)) {
      status += "<div class='" + k + "'>" + v + "</div>";
    }
    ce.status.removeClass().addClass('error').html(status);
    ce.submit.prop('disabled',true);
  }
}

$(document).ready(
  function($) {
    console.log("Hello from settings.js");
    ce.smtp_auth       = $('#smtp_auth_select');
    ce.smtp_port       = $('#smtp_port_input');
    ce.form            = $('#admin-settings');
    ce.ajaxuri         = $('#admin-settings input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-settings input[name=nonce]').val();
    ce.status          = $('#ttt-status');
    ce.submit          = $('#settings_submit');

    ce.submit.prop('disabled',true);

    ce.app_logo = $('#app_logo_input');
    ce.timezone = $('#timezone_input');
    ce.admin_email = $('#admin_email_input');
    ce.pwreset_timeout = $('#pwreset_timeout_input');
    ce.pwreset_length = $('#pwreset_length_input');

    ce.app_logo.on('change','app_logo',validate_setting);
    ce.timezone.on('change','timezone',validate_setting);
    ce.admin_email.on('change','admin_email',validate_setting);
    ce.pwreset_timeout.on('change','pwreset_timeout',validate_setting);
    ce.pwreset_length.on('change','pwreset_length',validate_setting);

    ce.smtp_auth.on('change',handle_smtp_auth_change);

    ce.form.on('submit',handle_settings_submit);

    validate_all();
  }
);

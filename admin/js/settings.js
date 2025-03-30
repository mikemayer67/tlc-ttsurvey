var ce = {};

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

function validate_timezone()
{
  timezone = ce.timezone.val();

  if(timezone.length == 0) {
    // blank is ok
    if(ce.timezone.hasClass('invalid-value')) {
      ce.timezone.removeClass('invalid-value');
      validate_all();
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
      timezone:timezone,
    }
  } )
  .done( function(data,status,jqXHR) {
    if(data.success) {
      if(ce.timezone.hasClass('invalid-value')) {
        ce.timezone.removeClass('invalid-value');
        validate_all();
      }
    }
    else {
      ce.timezone.addClass('invalid-value');
      set_error_status(data['timezone']);
    } 
  } )
  .fail( function(jqXHR,textStatus,errorThrown) { 
    internal_error(jqXHR); 
  } )
  ;
}

function internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
        + "): "+ jqXHR.statusMessage
        + "\nPlease let the survey admin know something went wrong."
       );
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
      timezone:ce.timezone.val(),
    }
  } )
  .done( function(data,status,jqXHR) {
    ce.form.children('input').removeClass('invalid-value');
    if(data.success) {
      ce.submit.prop('disabled',false);
      ce.status.html('').removeClass('error warning info').addClass('none');
    } 
    else {
      ce.submit.prop('disabled',true);
      ce.status.html('').removeClass('none warning info').addClass('error');
      if('timezone' in data) {
        ce.timezone.addClass('invalid-value');
        ce.status.html( ce.status.html() + "<div>" + data.timezone + "</div>");
      }
    }
  } )
  .fail( function(jqXHR,textStatus,errorThrown) { 
    internal_error(jqXHR); 
  } )
  ;


}

function set_error_status(error)
{
  ce.status.html(error);
  ce.status.addClass('error').removeClass('none info warning');
  ce.submit.prop('disabled',true);
}

$(document).ready(
  function($) {
    console.log("Hello from settings.js");
    ce.smtp_auth = $('#smtp_auth_select');
    ce.smtp_port = $('#smtp_port_input');
    ce.timezone  = $('#app_timezone_input');

    ce.form      = $('#admin-settings');
    ce.ajaxuri   = $('#admin-settings input[name=ajaxuri]').val();
    ce.nonce     = $('#admin-settings input[name=nonce]').val();
    ce.status    = $('#ttt-status');
    ce.submit    = $('#settings_submit');

    ce.submit.prop('disabled',true);
    ce.smtp_auth.on('change',handle_smtp_auth_change);
    ce.timezone.on('change',validate_timezone);
    ce.form.on('submit',handle_settings_submit);

    validate_all();
  }
);

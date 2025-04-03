var ce = {};
var saved_settings = {};

var validation_timer=null;

function handle_smtp_auth_change(event)
{
  if(this.value == '0') {
    ce.smtp_port.attr('placeholder','465');
  } else {
    ce.smtp_port.attr('placeholder','587');
  };
}

function handle_settings_submit(event)
{
  event.preventDefault();
  var sender = event.originalEvent.submitter;
  if(ce.submit.is(sender)) {
    alert('Need to implement submit');
  }
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
  $('div.error').hide().html('');
  ce.form.children('input').removeClass('invalid-value');

  var data = {
    ajax:"admin/validate_settings",
    nonce:ce.nonce, 
  };
  ce.optional_inputs.forEach( (key) => { data[key] = ce[key].val() } );
  ce.required_inputs.forEach( (key) => { data[key] = ce[key].val() } );
  $.ajax( {
    type: 'POST',
    url: ce.ajaxuri,
    dataType: 'json',
    data: data,
  } )
  .done( function(data,status,jqXHR) {
    if(!data.success) {
      Object.entries(data).forEach( (entry) => {
        const [key,value] = entry;
        if( key !== 'success' ) {
          ce[key+'_error'].show().html(value);
          ce[key].addClass('invalid-value');
        }
      });
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
  var errors = $('input.invalid-value');
  var dirty = has_changes();
  var can_submit = dirty && errors.length==0;
  ce.submit.prop('disabled',!can_submit);
}

function handle_change(event)
{
  dirty=true;
  clearTimeout(validation_timer);
  validation_timer = null;
  validate_all();
}

function handle_input(event)
{
  clearTimeout(validation_timer);
  dirty=true;
  $(this).removeClass('invalid-value');
  validation_timer = setTimeout(validate_all,750);
}

function current_values()
{
  var inputs = ce.form.find('input:not([type=hidden], [type=submit])');
  var selects = ce.form.find('select');
  var values = {};
  inputs.each( function() { 
    values[$(this).attr('name')] = $(this).val();
  });
  selects.each( function() { 
    values[$(this).attr('name')] = $(this).val();
  });
  return values;
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
    ce.smtp_auth       = $('#smtp_auth_select');
    ce.smtp_port       = $('#smtp_port_input');
    ce.form            = $('#admin-settings');
    ce.ajaxuri         = $('#admin-settings input[name=ajaxuri]').val();
    ce.nonce           = $('#admin-settings input[name=nonce]').val();
    ce.status          = $('#ttt-status');
    ce.submit          = $('#settings_submit');

    ce.submit.prop('disabled',true);

    saved_settings = current_values();

    ce.optional_inputs = [
      'app_logo','timezone','admin_email',
      'pwreset_timeout','pwreset_length',
      'smtp_port','smtp_reply_email','smtp_reply_name',
    ];
    ce.required_inputs = [
      'smtp_host','smtp_username','smtp_password',
    ];
    ce.optional_inputs.forEach( (key) => {
      ce[key] = $('#'+key+'_input');
      ce[key+'_error'] = $('#'+key+'_error');
    } );
    ce.required_inputs.forEach( (key) => {
      ce[key] = $('#'+key+'_input');
      ce[key+'_error'] = $('#'+key+'_error');
    } );

    ce.form.on('submit',handle_settings_submit);

    ce.smtp_auth.on('change',handle_smtp_auth_change);

    $('select').on('change', handle_change );
    $('input').on('change',  handle_change );
    $('input').on('input',   handle_input  );

    validate_all();
  }
);

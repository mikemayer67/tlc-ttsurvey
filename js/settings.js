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
  if(validate_settings()) {
    alert('All ok');
  }
}

function validate_settings() {
}

$(document).ready(
  function($) {
    console.log("Hello from settings.js");
    ce.smtp_auth = $('#smtp_auth_select');
    ce.smtp_port = $('#smtp_port_input');
    ce.form      = $('#admin-settings');

    ce.smtp_auth.on('change',handle_smtp_auth_change);
    ce.form.on('submit',handle_settings_submit);
  }
);

<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-settings');

require_once(app_file('admin/elements.php'));

$form_uri = app_uri('admin');
echo "<form id='admin-settings' method='post' action='$form_uri'>";
add_hidden_input('nonce',$nonce);
add_hidden_submit('action','settings');

add_input_section('Look and Feel', [
  [ 
    'app_name',
    'info' => [
      'The name of the survey app as it appears to survey participants',
      'Shown in the navbar when there is no active survey',
      'Shown in the from line in any email sent out by the survey',
    ],
    'default' => 'Time and Talent Survey',
  ],
  [ 
    'app_logo',
    'info' => [
      'Location of the application log image',
      'Path is relative to '.APP_URI.'/img',
    ],
    'optional'=>true,
  ],
  [ 
    'app_timezone',
    'info' => [
      'Used for logging timestamps and any dates/times presented to the user.',
      "See the <a href='https://en.wikipedia.org/wiki/List_of_tz_database_time_zones' target='_blank'>" .
      "Wikipedia Timezone page</a> for possible values",
    ],
    'default'=>'UTC',
  ],
]);

add_input_section('Admin',[
  [
    'primary_admin',
    'info' => 'Userid for the registered user to serve as primary admin',
    'optional'=>true,
  ], [
    'admin_name', 
'info' => [
      'Name of the site admin',
      '(if no registered user is identified as primary admin)',
    ],
    'default'=>'the survey admin',
  ], [
    'admin_email',
    'info' => [
      'Email address for the site admin',
      '(if no registered user is identified as primary admin)',
    ],
    'optional'=>true,
  ],
]);

add_input_section('Logging',[
  [
    'log_file',
    'info' => [
      'Location of the survey app log file on the server',
      'Path may be absolute or relative to the survey app directory',
    ],
    'default' => PKG_NAME.'.log',
  ], [
    'log_level',
    'info' => [
      'Level of information to include in the survey app log file',
      '0 = errors only',
      '1 = errors and warnings',
      '2 = errors, warnings, and informational notices',
      '3 = all above + developer probes',
    ],
    'default' => 2,
  ],
]);

add_input_section('Password Reset',[
  [
    'pwreset_timeout',
    'info' => 'How long a password token is valid before it expires (minutes)',
    'default' => 15,
  ], [
    'pwreset_length',
    'info' => 'Number of characters in a passsword reset token (4-20)',
    'default' => 10,
  ],

]);

add_input_section('Email Server',[
  [
    'smtp_host',
    'info' => 'URL for the SMTP server (e.g. smtp.gmail.com)',
  ], [
    'smtp_auth',
    'info' => [
      'method used to authenticate to the SMTP server',
      '0 = SMTPS',
      '1 = STARTTLS',
    ],
    'default' => 1,
  ], [
    'smtp_port',
    'info' => [
      'SMTP server port',
      'Normal values are 465 for SMTPS and 587 for STARTTLS',
    ],
    'default' => '587',
  ], [
    'smtp_username',
    'info' => 'authenticaion credential to connect to the SMTP server',
  ], [
    'smtp_password',
    'info' => [
      'authenticaion credential to connect to the SMTP server',
      '(should be app password if using gmail)',
    ],
  ], [
    'smtp_reply_email',
    'info' => 'email address used in the reply-to field',
    'optional' => true,
  ], [
    'smtp_reply_name',
    'info' => 'addressee name used in the reply-to field',
    'optional' => true,
  ], [
    'smtp_debug',
    'info' => [
      'SMTP debugging level',
      '0 = diabled',
      '1 = messages sent from server to client',
      '2 = all client/server messages',
      '3 = all messages + additional connection info',
      'Will be added to the survey app log at the info level'
    ],
    'default'=>0,
  ],
]);

echo "<div class='button-bar'>";
echo "<input id='settings_submit' class='submit' type='submit' value='Save Changes' disabled>";
echo "</div>";

echo "</form>";




<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

$nonce = gen_nonce('admin-settings');

require_once(app_file('admin/elements.php'));
require_once(app_file('include/users.php'));

$users = ['' => "--nobody--"];
foreach(User::all_users() as $user) {
  $userid = $user->userid();
  $name   = $user->fullname();
  $users[$userid] = "$userid: $name";
}

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
    'info' => 'Location of the application log image (relative to '.APP_URI.'/img)',
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
    'options' => $users,
    'info' => 'Userid for the registered user to serve as primary admin',
    'default' => '',
  ], [
    'admin_name', 
    'info' => 'Name of the site admin if no registered user is identified as primary admin',
    'default'=>'the survey admin',
  ], [
    'admin_email',
    'type' => 'email',
    'info' => 'Email address for the site admin if no registered user is identified as primary admin',
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
    'options' => [
      'errors only',
      'errors and warnings',
      'errors, warnings, and informational notices',
      'errors, warnings, info, and developer probes',
    ],
    'info' => 'Level of information to include in the survey app log file',
    'default' => 2,
  ],
]);

add_input_section('Password Reset',[
  [
    'pwreset_timeout',
    'type'=>'number', 'min'=>1,
    'info' => 'How long a password token is valid before it expires (minutes)',
    'default' => 15,
  ], [
    'pwreset_length',
    'type'=>'number', 'min'=>4, 'max'=>20, 'step'=>1,
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
    'options' => ['SMTPS','STARTTLS'],
    'info' => 'Method used to authenticate to the SMTP server',
    'default' => 1,
  ], [
    'smtp_port',
    'type'=>'number', 'min'=>1, 'step'=>1,
    'info' => [
      'SMTP server port (use default unless you must override normal values)',
      'Normal values are 465 for SMTPS and 587 for STARTTLS',
    ],
    'default' => '587',
  ], [
    'smtp_username',
    'info' => 'Authenticaion credential to connect to the SMTP server',
  ], [
    'smtp_password',
    'info' => [
      'Authenticaion credential to connect to the SMTP server',
      '(should be app password if using gmail)',
    ],
  ], [
    'smtp_reply_email',
    'type' => 'email',
    'info' => 'Email address used in the reply-to field',
    'optional' => true,
  ], [
    'smtp_reply_name',
    'info' => 'Addressee name used in the reply-to field',
    'optional' => true,
  ], [
    'smtp_debug',
    'options' => [
      'diabled',
      'messages sent from server to client',
      'all client/server messages',
      'all messages + additional connection info',
    ],
    'info' => 'SMTP debugging level (added to the survey app log at the info level)',
    'default' => 0,
  ],
]);

echo "<div class='button-bar'>";
echo "<input id='settings_submit' class='submit' type='submit' value='Save Changes'>";
echo "</div>";

echo "</form>";

$js_uri = resource_uri('admin/js/settings.js');
echo "<script src='$js_uri'></script>";

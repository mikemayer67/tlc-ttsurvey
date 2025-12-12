<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/redirect.php'));
require_once(app_file('login/elements.php'));

$redirect_data = get_redirect_data() ?? [];
$message = $redirect_data['message'] ?? null;
$email   = $redirect_data['email'] ?? null;

start_login_page('close');

if($message) { echo "<div class='close message'>$message</div>"; }
if($email)   { echo "<div class='close notification'>Notification of this change was sent to $email</div>"; }
echo "<div class='close'>You may now close this page</div>";

end_page();
die();

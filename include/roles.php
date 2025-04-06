<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

function survey_roles()
{
  return MySQLSelectRows('select * from tlc_tt_roles');
}

function add_user_role($userid,$role)
{
  $query = "insert into tlc_tt_roles (userid,$role) values (?,1) on duplicate key update userid=?,$role=1";
  return MySQLExecute($query,'ss',$userid,$userid);
}

function drop_user_role($userid,$role)
{
  $query = "update tlc_tt_roles set $role=0 where userid=?";
  return MySQLExecute($query,'s',$userid);
}

function lookup_userids_by_role($role)
{
  $roles = survey_roles();
  $rval = Array();
  foreach ($roles as $r) {
    if($r[$role]??false) { $rval[] = $r['userid']; }
  }
  return $rval;
}

function survey_admins()  { return lookup_userids_by_role('admin'); }
function content_admins() { return lookup_userids_by_role('content'); }
function tech_admins()    { return lookup_userids_by_role('tech'); }

function verify_role($userid,$role)
{
  return in_array($userid, lookup_userids_by_role($role));
}

function admin_contacts($role='admin')
{
  if($role == 'admin') {
    $contacts = array();
    if($primary = primary_admin()) { $conacts[] = $primary; }
    foreach(survey_admins('admin') as $userid) {
      if(!in_array($userid,$contacts)) { $contacts[] = $userid; }
    }
  } else {
    $contacts = lookup_userids_by_role($role);
  }

  $rval = array();
  foreach($contacts as $userid) {
    if($user = User::lookup($userid)) {
      $name = $user->fullname();
      if($email = $user->email()) {
        $subject = "Question regarding ".app_name();
        $rval[] = "<a href='mailto:$name<$email>?subject=$subject'>$name</a>";
      } else {
        $rval[] = $name;
      }
    }
  }

  switch(count($rval)) {
  case 0:
    $rval = '';
    break;
  case 1:
    $rval = $rval[0];
    break;
  case 2:
    $rval = "$rval[0] or $rval[1]";
    break;
  default:
    $last = array_pop($rval);
    $rval = implode(', ',$rval) . ", or $last";
    break;
  }

  return $rval;
}

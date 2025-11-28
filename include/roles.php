<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));

function survey_roles()
{
  return MySQLSelectRows('select * from tlc_tt_active_roles');
}

function add_user_role($userid,$role)
{
  $userid = strtolower($userid);

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

function user_roles($userid)
{
  if( $userid === primary_admin() ) { return ['admin', 'content', 'tech', 'summary']; }
  return assigned_roles($userid);
}

function assigned_roles($userid)
{
  $roles =  MySQLSelectRow(
    'select admin,content,tech,summary from tlc_tt_roles where userid=?','s',$userid
  );
  $rval = [];
  if($roles) { 
    foreach(array_keys($roles) as $role) {
      if($roles[$role]) { $rval[] = $role; }
    }
  }
  if($userid === primary_admin()) { $rval[] = 'primary-admin'; }
  return $rval;
}

function has_summary_access($userid)
{
  if($userid===primary_admin()) { return true; }

  $roles = user_roles($userid);
  return in_array('admin',$roles);
  return in_array('summary',$roles);
}

//function verify_role($userid,$role)
//{
//  if($role === 'admin' && $userid === primary_admin()) { return true; }
//  return in_array($userid, lookup_userids_by_role($role));
//}

function admin_contacts($role='admin')
{
  if($role == 'admin') {
    $contacts = array();
    if($primary = primary_admin()) { $contacts[] = $primary; }
    foreach(survey_admins('admin') as $userid) {
      if(!in_array($userid,$contacts)) { $contacts[] = $userid; }
    }
  } else {
    $contacts = lookup_userids_by_role($role);
  }

  $rval = array();
  foreach($contacts as $userid) {
    if($user = User::lookup($userid)) {
      $rval[] = [
        'name'  => $user->fullname(),
        'email' => $user->email(),
      ];
    }
  }

  return $rval;
}

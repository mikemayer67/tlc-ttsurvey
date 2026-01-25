import { deepCopy } from '../admin/js/utils.js';

const ce = {};

// DO NOT INCLUDE ANY TESTS THAT MODIFY ANY NON-TRANSIENT DATABASE CONTENT
const tests = [
  // admin/logout_admin
  { caller:'admin.js', api: 'admin/logout_admin', pass:true, input:{nonce: "AJAXTEST"} },
  { caller:'admin.js', api: 'admin/logout_admin', pass:false, input:{nonce: "BADNONCE"} },
  // admin/logout_user
  'reset',
  { caller:'admin.js', api: 'admin/logout_user', pass:true,  input:{nonce: "AJAXTEST"} },
  { caller:'admin.js', api: 'admin/logout_user', pass:false, input:{nonce: "BADNONCE"} },
  // admin/obtain_lock
  'reset',
  { caller:['admin.js','hold_lock','check_lock'], api:'admin/obtain_admin_lock', pass:true, input:{} },
  { caller:['admin.js','hold_lock','check_lock'], api:'admin/obtain_admin_lock', pass:true, input:{nonce: "BADNONCE"} },
  // admin/cleanup
  { caller:'cleanup.js', api:'admin/cleanup_strings', pass:true,  input:{nonce: "AJAXTEST"} },
  { caller:'cleanup.js', api:'admin/cleanup_strings', pass:false, input:{nonce: "BADNONCE"} },
  { caller:'cleanup.js', api:'admin/cleanup_options', pass:true,  input:{nonce: "AJAXTEST"} },
  { caller:'cleanup.js', api:'admin/cleanup_options', pass:false, input:{nonce: "BADNONCE"} },
  // admin/get_log
  { caller:['logs.js','ready','refresh_logs'], api:'admin/get_log', pass:true, input:{nonce:"AJAXTEST"} },
  { caller:['logs.js','ready','refresh_logs'], api:'admin/get_log', pass:true, input:{nonce:"AJAXTEST", lines:5} },
  { caller:['logs.js','ready','refresh_logs'], api:'admin/get_log', pass:true, input:{nonce:"AJAXTEST", lines:10, level:1} },
  { caller:['logs.js','ready','refresh_logs'], api:'admin/get_log', pass:true, input:{nonce:"AJAXTEST", lines:10, level:2} },
  { caller:['logs.js','ready','refresh_logs'], api:'admin/get_log', pass:true, input:{nonce:"AJAXTEST", lines:10, level:3} },
  { caller:['logs.js','ready','refresh_logs'], api:'admin/get_log', pass:false, input:{nonce:"BADNONCE"} },
  { caller:['logs.js','ready','refresh_logs'], api:'admin/get_log', pass:false, input:{nonce:"AJAXTEST", lines:"cat"} },
  // admin/login_admin
  { caller:'1.login.js', api: 'admin/login_admin', pass: true, admin:true, input: { nonce: 'AJAXTEST' } },
  { 
    caller:'login.js', api: 'admin/login_admin', pass: false, admin:true,
    input: { nonce: 'AJAXTEST', userid: 'bad_userid', password: 'bad_passwd' } },
  { caller:'login.js',api:'admin/login_admin', pass:false, input:{nonce:'BADNONCE'}},
  { caller:'login.js',api:'admin/login_admin', pass:false, input:{nonce:'AJAXTEST'}},
  { caller:'login.js',api:'admin/login_admin', pass:false, input:{nonce:'AJAXTEST',userid:'bad_userid'}},
  { caller:'login.js',api:'admin/login_admin', pass:false, input:{nonce:'AJAXTEST'}},
  // send_reminder_emails
  // uncommenting the following will send out reminder emails
  // { caller:'participants.js',api:'admin/send_reminder_emails', pass:true, userids:true, input:{nonce:"AJAXTEST"}},
  { caller:'participants.js',api:'admin/send_reminder_emails', pass:false, input:{}},
  { caller:'participants.js',api:'admin/send_reminder_emails', pass:false, input:{nonce:"BADNONCE"}},
  { caller:'participants.js',api:'admin/send_reminder_emails', pass:false, input:{nonce:"AJAXTEST"}},
  { caller:'participants.js',api:'admin/send_reminder_emails', pass:false, input:{nonce:"AJAXTEST",userids:6}},
  { caller:'participants.js',api:'admin/send_reminder_emails', pass:false, input:{nonce:"AJAXTEST",userids:{red:1,blue:2}}},
  // password reset
  // uncommenting the following will send out an email to first userid in the list of all userids
  // { caller:'participants.js',api:'admin/get_password_reset_token', pass:true, userid:true, input:{nonce:"AJAXTEST"}},
  { caller:'participants.js',api:'admin/get_password_reset_token', pass:false, input:{}},
  { caller:'participants.js',api:'admin/get_password_reset_token', pass:false, input:{nonce:"BADNONCE"}},
  { caller:'participants.js',api:'admin/get_password_reset_token', pass:false, input:{nonce:"AJAXTEST"}},
  { caller:'participants.js',api:'admin/get_password_reset_token', pass:false, input:{nonce:"AJAXTEST",userids:'invalid_userid'}},
  { caller:'participants.js',api:'admin/get_password_reset_token', pass:false, input:{nonce:"AJAXTEST",userids:{red:1,blue:2}}},
  // validate_settings
  { caller:'settings.js',api:'admin/validate_settings',pass:true,input:{nonce:'AJAXTEST'} },
  { caller:'settings.js',api:'admin/validate_settings',pass:false,input:{nonce:'BADNONCE'} },
  { caller:'settings.js',api:'admin/validate_settings',pass:true,input:{ nonce:'AJAXTEST', 
    admin_email:'nobdy@nowhere.com', is_dev:1, log_level:2, summary_flags:0, timezone:'America/New_York'} },
  { caller:'settings.js',api:'admin/validate_settings',pass:false,input:{ nonce:'AJAXTEST', 
    admin_email:'nobdynowhere.com', app_logo:'Junk.png', log_level:8, summary_flags:0, timezone:'America/SesameStreet'} },
  // validate_smtp
  {caller:'settings.js',api:'admin/validate_smtp', pass:true, smtp:true, input:{}},
  {caller:'settings.js',api:'admin/validate_smtp', pass:false, smtp:true, input:{smtp_port:123}},
];

async function run_all_tests()
{
  const context = {};
  const pending = [];
  let num_fail = 0;

  for(const test of tests) {
    if(test.smtp) { test.async = true; }

    if(test.async) {
      const context_snapshot = deepCopy(context);
      pending.push(
        run_test(test,context_snapshot)
        .then( result => { num_fail += (result ? 0 : 1) } )
      );
    } else {
      const result = await run_test(test, context);
      if (!result) { num_fail += 1; }
    }
  }
  await Promise.all(pending);

  return num_fail;
}

async function run_test(test, context)
{
  let rval = true;

  if(test === 'reset') {
    for(const k of Object.keys(context)) { delete context[k]; }
    return true;
  }
  let input = deepCopy(test.input);
  input.ajax = test.api;

  var caller = test.caller;
  if( Array.isArray(caller) ) { 
    const [file, ...funcs] = caller;
    caller = file + funcs.map(f => `<br><i>${f}</i>`).join('');
  }

  const tr = $('<tr>').addClass('result').appendTo(ce.table);
  tr.append("<td>" + test.api + "</td>");
  tr.append("<td>" + caller + "</td>");

  if(test.admin) {
    if(!input.userid)   { input.userid   = ce.userid.val(); }
    if(!input.password) { input.password = ce.passwd.val(); }
  }
  if(test.userids) {
    input.userids = ce.userids;
  }
  if(test.userid) {
    input.userid = ce.userids[0];
  }
  if(test.smtp) {
    input = {...ce.smtp_inputs, ...input };
  }

  tr.append("<td>" + object_table(input) + "</td>");
  
  try {
    const data = await $.ajax({
      type:'POST', 
      url:ce.ajaxuri, 
      dataType:'json', 
      data:input,
    });
    let result = 'pass';
    if(data.success === true)       { result = (test.pass ? 'pass' : 'fail'); }
    else if(data.success === false) { result = (test.pass ? 'fail' : 'expected_fail'); }
    tr.addClass(result);

    tr.append("<td>" + object_table(data) + "</td>");
  }
  catch (jqXHR) {
    tr.addClass(test.pass ? 'fail' :'expected_fail');
    tr.append("<td>" + jqXHR.status + ": " + jqXHR.statusText + "<br>" + object_table(jqXHR.responseJSON) + "</td>");
    return false;
  }

  return true;
}

function object_table(x)
{
  let rval = "<table class='jsobj'>";
  let lines = 0;
  let key = '';
  let value = '';
  for( key in x)  {
    value = x[key];
    lines += 1;
    if(lines < 15) {
      rval += "<tr><td class='key'>" + key + "</td><td class='value'>" + value + "</td></tr>";
    } else if(lines == 15) {
      rval += "<tr><td></td><td class='value'>...</td></tr>";
    }
  }
  if(lines > 15) {
    rval += "<tr><td class='key'>" + key + "</td><td class='value'>" + value + "</td></tr>";
  }

  rval += "</table>";
  return rval;
}

$(document).ready( function() {
  ce.ajaxuri = $('#ajaxuri').val();
  ce.table = $('table.results');
  ce.userid = $('#userid');
  ce.passwd = $('#passwd');
  ce.run_button = $('table.inputs button');

  ce.userids = JSON.parse($('#all-userids').val());
  ce.smtp_inputs = JSON.parse($('#smtp-inputs').val());

  ce.run_button.on('click', async function(e) {
    e.preventDefault();
    ce.table.find('tr.result').remove();
    ce.run_button.prop('disabled',true);
    const num_fail = await run_all_tests();
    ce.run_button.text('Rerun All Tests').prop('disabled',false);
  });
});
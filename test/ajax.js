import { deepCopy } from '../admin/js/utils.js';

const ce = {};

class AJAXTest
{
  constructor(caller,api,should_pass,kwargs={})
  {
    const {
      nonce = true,
      ...ajaxInput
    } = kwargs;

    this._caller = caller;
    this._api = api;
    this._should_pass = should_pass;

    if(nonce === 'bad') { ajaxInput.nonce = 'BADNONCE'; }
    else if(nonce)      { ajaxInput.nonce = 'AJAXTEST'; }

    this._input = {...ajaxInput, ajax:api};
  }

  add_to_table(table) 
  {
    this.tr = $('<tr>').addClass('result').appendTo(table);
    this.tr.append("<td>" + this._api + "</td>");
    this.tr.append("<td>" + this._caller + "</td>");
    this.tr.append($('<td>').append(object_table(this._input)));
    this.tr.append("<td>" + (this._should_pass ? "Pass" : "Fail") + "</td>");
    this.td = $('<td>').html("<b>Queued</b>").appendTo(this.tr);
  }

  async run(context=null)
  {
    this._result = {};
    try {
      const data = await $.ajax({
        type: 'POST',
        url: ce.ajaxuri,
        dataType: 'json',
        data: this._input,
      });
      if(this._should_pass) {
        this._result.status = (data.success !== false ? 'pass' : 'fail');
      } else {
        this._result.status = (data.success !== true ? 'expected_fail' : 'fail');
      }
      this._result.info = '';
      this._result.data = data;
    }
    catch (jqXHR) {
      this._result.status = (this._should_pass ? 'fail' : 'expected_fail');
      this._result.info = $('<div>').text(jqXHR.status+": "+jqXHR.statusText);
      this._result.data = jqXHR.responseJSON;
      return false;
    }
    return true;
  }

  update_result()
  {
    this.tr.addClass(this._result.status);
    this.td.html(this._result.info);
    this.td.append(object_table(this._result.data));
  }
}

class PassTest extends AJAXTest {
  constructor(caller,api,kwargs={}) { super(caller,api,true,kwargs); }
}

class FailTest extends AJAXTest {
  constructor(caller,api,kwargs={}) { super(caller,api,false,kwargs); }
}

class Reset {
  // DuckTyped Test... not really a test, but can be invoked like one
  constructor() {}
  async run(context) {
    // resets the input context to an empty object
    for(const k of Object.keys(context)) { delete context[k]; }
    return true;
  }
  add_to_table(table) {}
  update_result() {}
}

function all_tests() {
  const userid    = $('#userid').val();
  const password  = $('#passwd').val();
  const survey_id = $('#survey-id').val();
  const userids   = JSON.parse($('#all-userids').val());
  const smtp      = JSON.parse($('#smtp-inputs').val());

  // DO NOT INCLUDE ANY TESTS THAT MODIFY ANY NON-TRANSIENT DATABASE CONTENT
  const rval = [];
  // admin/logout_admin
  rval.push(new PassTest('admin.js', 'admin/logout_admin'));
  rval.push(new FailTest('admin.js', 'admin/logout_admin', {nonce:'bad'}));
  // admin/logout_user
  rval.push(new PassTest('admin.js', 'admin/logout_user'));
  rval.push(new FailTest('admin.js', 'admin/logout_user', {nonce:'bad'}));
  // admin/obtain_lock
  rval.push(new PassTest('admin.js', 'admin/obtain_admin_lock'));
  // admin/cleanup
  rval.push(new PassTest('cleanup.js', 'admin/cleanup_strings'));
  rval.push(new FailTest('cleanup.js', 'admin/cleanup_strings', {nonce:'bad'}));
  rval.push(new PassTest('cleanup.js', 'admin/cleanup_options'));
  rval.push(new FailTest('cleanup.js', 'admin/cleanup_options', {nonce:'bad'}));
  // admin/get_log
  rval.push(new PassTest('logs.js', 'admin/get_log'));
  rval.push(new FailTest('logs.js', 'admin/get_log', {nonce:'bad'}));
  rval.push(new PassTest('logs.js', 'admin/get_log', {lines: 5}));
  rval.push(new PassTest('logs.js', 'admin/get_log', {lines: 10, level: 1}));
  rval.push(new PassTest('logs.js', 'admin/get_log', {lines: 10, level: 2}));
  rval.push(new PassTest('logs.js', 'admin/get_log', {lines: 10, level: 3}));
  rval.push(new FailTest('logs.js', 'admin/get_log', {lines: "cat"}));
  // admin/login_admin
  rval.push(new PassTest('login.js', 'admin/login_admin', {userid, password}));
  rval.push(new FailTest('login.js', 'admin/login_admin', {userid, password, nonce:'bad'}));
  rval.push(new FailTest('login.js', 'admin/login_admin', {userid:'bad_userid'}));
  rval.push(new FailTest('login.js', 'admin/login_admin', {userid:'bad_userid', password:'bad_passwd'}));
  rval.push(new FailTest('login.js', 'admin/login_admin'));
  // send_reminder_emails
  // uncommenting the following will send out reminder emails
  // new PassTest('participants.js', 'admin/send_reminder_emails', input:{nonce:"AJAXTEST", userids}),
  rval.push(new FailTest('participants.js', 'admin/send_reminder_emails'));
  rval.push(new FailTest('participants.js', 'admin/send_reminder_emails', {nonce:'bad'}));
  rval.push(new FailTest('participants.js', 'admin/send_reminder_emails'));
  rval.push(new FailTest('participants.js', 'admin/send_reminder_emails', {userids:6}));
  rval.push(new FailTest('participants.js', 'admin/send_reminder_emails', {userids:{red:1, blue:2}}));
  // password reset
  // uncommenting the following will send out an email to first userid in the list of all userids
  // new PassTest('participants.js', 'admin/get_password_reset_token', { nonce:"AJAXTEST", userid:userids[0]}),
  rval.push(new Reset());
  rval.push(new FailTest('participants.js', 'admin/get_password_reset_token', {nonce:null}));
  rval.push(new FailTest('participants.js', 'admin/get_password_reset_token', {nonce:'bad'}));
  rval.push(new FailTest('participants.js', 'admin/get_password_reset_token'));
  rval.push(new FailTest('participants.js', 'admin/get_password_reset_token', {userids:'invalid_userid'}));
  rval.push(new FailTest('participants.js', 'admin/get_password_reset_token', {userids:{red:1, blue:2}}));
  // validate_settings
  rval.push(new PassTest('settings.js', 'admin/validate_settings'));
  rval.push(new FailTest('settings.js', 'admin/validate_settings', {nonce:'bad'}));
  rval.push(new PassTest('settings.js', 'admin/validate_settings', {
    admin_email: 'nobdy@nowhere.com', is_dev: 1, log_level: 2, summary_flags: 0, timezone: 'America/New_York' }));
  rval.push(new FailTest('settings.js', 'admin/validate_settings', {
    admin_email: 'nobdynowhere.com', app_logo: 'Junk.png', log_level: 8, summary_flags: 0, timezone: 'America/SesameStreet'}));
  // validate_smtp
  rval.push(new PassTest('settings.js', 'admin/validate_smtp', smtp));
  rval.push(new FailTest('settings.js', 'admin/validate_smtp', {...smtp, smtp_port:123}));
  // get_survey_content
  rval.push(new PassTest('survey_data.js', 'admin/get_survey_content', {survey_id}));
  rval.push(new FailTest('survey_data.js', 'admin/get_survey_content', {survey_id, nonce:'bad'}));
  rval.push(new FailTest('survey_data.js', 'admin/get_survey_content'));

  return rval;
}

async function run_all_tests()
{
  const context = {};
  let num_fail = 0;

  const tests = all_tests();

  for(const test of tests) {
    test.add_to_table(ce.table);
  }
  for(const test of tests) {
    const result = await test.run(context);
    test.update_result();
    num_fail += (result ? 0 : 1);
  }
  return num_fail;
}

function object_table(x)
{
  let rval = $('<table>').addClass('jsobj');
  let lines = 0;
  let key = '';
  let value = '';
  for( key in x)  {
    value = x[key];
    lines += 1;
    if(lines < 15) {
      rval.append(
        $('<tr>')
          .append($('<td>').addClass('key').text(key))
          .append($('<td>').addClass('value').text(value))
      );
    } else if(lines == 15) {
      rval.append(
        $('<tr>')
          .append($('<td>'))
          .append($('<td>').addClass('value').text('...'))
      );
    }
  }
  if(lines > 15) {
    rval.append(
      $('<tr>')
        .append($('<td>').addClass('key').text(key))
        .append($('<td>').addClass('value').text(value))
    );
  }
  return rval;
}

$(document).ready( function() {
  ce.ajaxuri = $('#ajaxuri').val();
  ce.table = $('table.results');
  ce.run_button = $('table.inputs button');

  ce.run_button.on('click', async function(e) {
    e.preventDefault();
    ce.table.find('tr.result').remove();
    ce.run_button.prop('disabled',true);
    const num_fail = await run_all_tests();
    ce.run_button.text('Rerun All Tests').prop('disabled',false);
  });
});
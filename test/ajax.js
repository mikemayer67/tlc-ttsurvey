const ce = {};

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
];

async function run_all_tests()
{
  const context = {};
  for(const test of tests) {
    const result = await run_test(test, context);
  }
}

async function run_test(test, context)
{
  let rval = true;

  if(test === 'reset') {
    for(const k of Object.keys(context)) { delete context[k]; }
  }
  else 
  {
    test.input.ajax = test.api;
    
    var caller = test.caller;
    if( Array.isArray(caller) ) { 
      const [file, ...funcs] = caller;
      caller = file + funcs.map(f => `<br><i>${f}</i>`).join('');
    }

    const tr = $('<tr>').appendTo(ce.table);
    tr.append("<td>" + test.api + "</td>");
    tr.append("<td>" + caller + "</td>");
    tr.append("<td>" + object_table(test.input) + "</td>");

    try {
      const data = await $.ajax({
        type:'POST', 
        url:ce.ajaxuri, 
        dataType:'json', 
        data:test.input,
      });
      tr.addClass(test.pass ? 'pass' : 'fail');
      tr.append("<td>" + object_table(data) + "</td>");
    }
    catch(jqXHR) {
      tr.addClass(test.pass ? 'fail' :'expected_fail');
      tr.append("<td>" + jqXHR.status + ": " + jqXHR.statusText + "<br>" + object_table(jqXHR.responseJSON) + "</td>");
      rval = false;
    }
  }
  return rval;
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
  ce.table = $('#results');
  run_all_tests();
});
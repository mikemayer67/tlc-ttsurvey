// The following are used by all Admin Dashboard pages

var ace = {};

var has_change_cb = null;
var status_timer=null;

function internal_error(jqXHR)
{
  alert("Internal error (#" + jqXHR.status
                         + "): "+ jqXHR.statusMessage
                         + "\nPlease let the survey admin know something went wrong."
       );
}

function hide_status()
{
  ace.status.removeClass().addClass('none');
  clearTimeout(status_timer);
  status_timer = setTimeout(() => {ace.status.html('')},750);
}

function show_status(level,msg)
{
  clearTimeout(status_timer);
  status_timer = null;
  ace.status.removeClass('none').addClass(level).html(msg);
}

// The following is used by all, but is protected to prevent name collision

function handle_tab_change(event)
{
  if(has_change_cb && has_change_cb()) {
    event.preventDefault();

    var tgt_action = event.target.action
    var new_tab = $(event.originalEvent.submitter).val();
    var new_tab_uri = tgt_action + '&tab=' + new_tab;

    var tsm = $('#tab-switch-modal');
    tsm.find('button.cancel').on('click',function() { 
      tsm.hide();
    });
    tsm.find('button.confirm').on('click',function() { 
      tsm.hide();
      window.location = new_tab_uri;
    });
    tsm.show();
  }
}

$(document).ready(
  function($) {
  $('#ttt-body').show();

  ace.status = $('#ttt-status');
  ace.form   = $('#admin-tabs');

  ace.tabs = {};
  ace.form.find('button').each(
    function() { ace.tabs[$(this).attr('value')] = $(this) }
  );

  ace.form.on('submit',handle_tab_change);
});

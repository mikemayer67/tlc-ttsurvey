
var tab_change_cb = null;


( function() {

  var ce = {};

  function handle_tab_change(event)
  {
    if(tab_change_cb) { 
      var tgt_action = event.target.action
      var new_tab = $(event.originalEvent.submitter).val();
      var new_tab_uri = tgt_action + '&tab=' + new_tab;
      if(!tab_change_cb(new_tab_uri)) { event.preventDefault(); }
    }
  }

  $(document).ready(
    function($) {
    $('#ttt-body').show();

    ce.form = $('#admin-tabs');

    ce.tabs = {};
    ce.form.find('button').each(
      function() { ce.tabs[$(this).attr('value')] = $(this) }
    );

    ce.form.on('submit',handle_tab_change);
  }
  );

})();


var cache_cb = null;


( function() {

  var ce = {};

  function handle_tab_change(event)
  {
    if(cache_cb) { cache_cb() }
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

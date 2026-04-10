( function() {

  let ce = {};

  function handle_breadcrumb(e) {
    e.preventDefault();
    const tgt = $(e.target);
    const page = tgt.data('page');
    const pos = tgt.data('position');
    alert('Handle breadcrumb['+String(pos)+']: '+page);
    ce.breadcrumbs.slice(pos+1).remove();
  }

  $(document).ready(
    function($) {
      ce.form        = $('#admin-docs');
      ce.ajaxuri     = $('#admin-docs input[name=ajaxuri]').val();
      ce.nonce       = $('#admin-docs input[name=nonce]').val();
      ce.breadcrumbs = ce.form.find('a.breadcrumb');

      // should only be one breadcrumb, but let's not assume that here
      ce.breadcrumbs.each(function (i) {$(this).data('position', i)});
      ce.breadcrumbs.on('click',handle_breadcrumb);
    }
  );

})();
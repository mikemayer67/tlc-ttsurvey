( function() {

  let ce = {};

  function handle_breadcrumb(e) {
    e.preventDefault();
    const tgt = $(e.target);
    const topic = tgt.data('topic');
    const pos = tgt.data('position');
    ce.breadcrumbs.slice(pos+1).remove();
    ce.breadcrumbs = ce.breadcrumbs.slice(0,pos+1);
    load_doc_topic(topic);
  }

  function handle_doc_link(e)
  {
    e.preventDefault();
    const tgt = $(e.target);
    const label = tgt.text();
    let topic = tgt.data('topic').trim();
    if(topic.endsWith('.md')) { topic = topic.slice(0,-3); }

    const pos = ce.breadcrumbs.length;
    const new_breadcrumb = $('<a>').addClass('breadcrumb')
    new_breadcrumb.data('topic',topic).data('position',pos);
    new_breadcrumb.text(label);
    new_breadcrumb.on('click',handle_breadcrumb);
    $('div.breadcrumbs').append(new_breadcrumb);
    ce.breadcrumbs = ce.breadcrumbs.add(new_breadcrumb);
    load_doc_topic(topic);
  }

  function load_doc_topic(topic) {
    ce.doc_links.off('click');
    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: {
        'ajax':'admin/get_docs_topic',
        'nonce':ce.nonce,
        'topic':topic,
      }
    })
    .done( function(data,status,jqHXR) {
      ce.docs_display.html(data.html);
      ce.docs_display.scrollTop(ce.docs_display[0].scrollHeight);
      ce.doc_links = $('a.doc-link');
      ce.doc_links.on('click',handle_doc_link);
    })
    .fail( function(jqXHR,textStatus,errorThrown) { 
      ajax_error_handler(jqXHR,'get docs topic');
    });
  }

  $(document).ready(
    function($) {
      ce.form         = $('#admin-docs');
      ce.ajaxuri      = $('#admin-docs input[name=ajaxuri]').val();
      ce.nonce        = $('#admin-docs input[name=nonce]').val();
      ce.breadcrumbs  = ce.form.find('a.breadcrumb');
      ce.docs_display = $('#docs-display');
      ce.doc_links    = $('a.doc-link');

      // should only be one breadcrumb, but let's not assume that here
      ce.breadcrumbs.each(function (i) {$(this).data('position', i)});
      ce.breadcrumbs.on('click',handle_breadcrumb);

      ce.doc_links.on('click',handle_doc_link);
    }
  );

})();
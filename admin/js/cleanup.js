( function() {

  let ce = {};


  function cleanup_strings(e)
  {
    e.preventDefault();

    var data = {ajax:'admin/cleanup_strings', 'nonce':ce.nonce};

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(data.success) {
        const n = data.count;
        switch(n) {
          case 0:
            alert('No unused text strings found in the database');
            break;
          case 1:
            alert('Removed 1 unused text string from the database');
            break;
          default:
            alert('Removed ' + n + ' unused text strings from the database');
            break;
        }
      } else {
        alert(data.reason);
      }
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    } );
  }

  function cleanup_options(e)
  {
    e.preventDefault();

    var data = {ajax:'admin/cleanup_options', 'nonce':ce.nonce};

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: data,
    } )
    .done( function(data,status,jqXHR) {
      if(data.success) {
        const n = data.count;
        switch(n) {
          case 0:
            alert('No unused select options found in the database');
            break;
          case 1:
            alert('Removed 1 unused select option from the database');
            break;
          default:
            alert('Removed ' + n + ' unused select options from the database');
            break;
        }
      } else {
        alert(data.reason);
      }
    } )
    .fail( function(jqXHR,textStatus,errorThrown) { 
      internal_error(jqXHR); 
    } );
  }

  $(document).ready( function($) {
    ce.form    = $('#admin-cleanup');
    ce.ajaxuri = ce.form.find('input[name=ajaxuri]').val();
    ce.nonce   = ce.form.find('input[name=nonce]').val();

    ce.form.find('.cleanup.strings').on('click',cleanup_strings);
    ce.form.find('.cleanup.options').on('click',cleanup_options);
  });

})();

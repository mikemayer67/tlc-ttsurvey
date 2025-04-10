$(document).ready(
  function($) {
    $('#ttt-body').show();

    $('#ttt-navbar span.ttt-title-box *').on('click', function() {
      var uri = $('#ttt-body form.login input[name=ajaxuri]').val();
      if(uri) {
        window.location=uri;
      }
    });
    
  }
);


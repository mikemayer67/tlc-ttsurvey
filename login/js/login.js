$(document).ready(
  function($) {
    $('#ttt-body').show();

    $('#ttt-navbar .left-box *').on('click', function() {
      var uri = $('#ttt-body form.login input[name=ajaxuri]').val();
      if(uri) {
        window.open(uri,'ttt_survey');
      }
    });
    
  }
);


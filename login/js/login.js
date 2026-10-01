$(document).ready(
  function($) {
    $('#ttt-body').show();

    $('#ttt-navbar .left-box *').on('click', function() {
      window.open(window.location.pathname,'ttt_survey');
    });
  }
);


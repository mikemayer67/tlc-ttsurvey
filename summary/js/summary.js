var ce = {};

function handle_exit_summary(e) {
  url = new URL(window.location.href);
  url.search='';
  window.open(url,'ttt_survey');
}

$(document).ready( function() {
  $('.left-box').on('click',handle_exit_summary);
});

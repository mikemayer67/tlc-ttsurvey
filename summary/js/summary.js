var ce = {};

function handle_exit_summary(e) {
  alert("go home");
}

$(document).ready( function() {
  $('.left-box').on('click',handle_exit_summary);
});

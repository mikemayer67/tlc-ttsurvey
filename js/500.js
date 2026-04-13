( function() {

  let ce = {};

  function update_submit_state(e)
  {
    ce.submit.prop('disabled', !(ce.form[0].checkValidity()));
  }

  $(document).ready(
    function($) {
      ce.form = $('#bug-reporting');
      ce.submit = ce.form.find('button.submit');

      update_submit_state();

      ce.form.on('input',update_submit_state);
    }
  );
})()
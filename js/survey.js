var ce = {};


function setup_hints()
{
  ce.hint_toggles = $('label.hint-toggle');

  if (window.matchMedia("(hover: hover)").matches) 
  {
    ce.hint_toggles.on('mouseenter', function(e) {
      const id = $(this).data('question-id');
      const hintlock = $('#hint-lock-'+id);
      const hint = $('#hint-'+id);
      if(hintlock.prop('checked')) {
        // reentering the trigger icon when the hint is locked visible should hide it
        hintlock.prop('checked',false);
        hint.removeClass('hovering')
      } else {
        // otherwise, this action should show the hint
        hint .addClass('hovering')
      }
    });

    ce.hint_toggles.on('mouseleave', function(e) {
      const id = $(this).data('question-id');
      const hint = $('#hint-'+id);
      hint.removeClass('hovering')
    });
  }
}

$(document).ready( function() {
  ce.submit = $('#ttt-body form input.submit')
  ce.revert = $('#ttt-body form input.revert')

  setup_hints();

  ce.revert.removeClass('hidden');
});

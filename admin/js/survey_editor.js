( function(ce) {

  //
  // Editor population
  //

  populate_editor()

  //
  // Content editor resizing
  //

  function start_survey_tree_resize(e) {
    e.preventDefault();
    ce.content_editor.css('cursor','col-resize');
    ce.resizing = { 
      min_x : 200 - ce.survey_tree.width(),
      max_x : ce.element_editor.width() - 300,
      start_x : e.pageX,
      start_w : ce.survey_tree.width(),
      in_editor : true,
      last_move : 0,
    };
    ce.content_editor.on('mouseenter', function(e) { ce.resizing.in_editor = true;  } );
    ce.content_editor.on('mouseleave', function(e) { ce.resizing.in_editor = false; } );
  }

  function track_mouse(e) {
    e.preventDefault();
    if(!ce.resizing) { return; }
    const now = Date.now();
    if(now < ce.lastMove + 10) { return; }
    ce.lastMove = now;

    const dx = e.pageX - ce.resizing.start_x;
    if( dx > ce.resizing.min_x && dx < ce.resizing.max_x ) {
      ce.survey_tree.width(ce.resizing.start_w + dx);
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(!ce.resizing) { return; }
    ce.content_editor.css('cursor','');
    if(!ce.resizing.in_editor) { 
      ce.survey_tree.width(ce.resizing.start_w); 
    }
    ce.content_editor.off('mouseenter');
    ce.content_editor.off('mouseleave');
    ce.resizing = null;
  }

  //
  // Entry Point
  //

  $(document).ready( function($) {
    ce.survey_tree      = ce.content_editor.find('#survey-tree');
    ce.element_editor   = ce.content_editor.find('#element-editor');
    ce.resizer          = ce.content_editor.find('.resizer');

    ce.resizing = null;
    ce.resizer.on('mousedown',start_survey_tree_resize);
    $(document).on('mousemove',track_mouse);
    $(document).on('mouseup',stop_tracking_mouse);

    populate_editor();
  });

})(window._survey_ce);

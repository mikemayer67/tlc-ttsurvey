export default function setup_editor_resizer(ce,editor_tree)
{
  const _resizer      = $('#content-editor .resizer');
  const _editor_body  = $('#content-editor div.body');
  const _survey_tree  = $('#survey-tree');
  const _editor_frame = $('#editor-frame');

  let _tracking = null;

  function start_resize(e) {
    e.preventDefault();
    _editor_body.css('cursor','col-resize');
    _tracking = { 
      min_x : 200 - _survey_tree.width(),
      max_x : _editor_frame.width() - 300,
      start_x : e.pageX,
      start_w : _survey_tree.width(),
      in_editor : true,
      last_move : 0,
    };
    _editor_body.on('mouseenter', function(e) { _tracking.in_editor = true;  } );
    _editor_body.on('mouseleave', function(e) { _tracking.in_editor = false; } );
    $(document).on('mousemove',track_mouse);
    $(document).on('mouseup',stop_tracking_mouse);
  }

  function track_mouse(e) {
    e.preventDefault();
    if(_tracking) {
      const now = Date.now();
      if( now > _tracking.last_move + 10 ) {
        _tracking.last_move = now;
        const dx = e.pageX - _tracking.start_x;
        if( dx > _tracking.min_x && dx < _tracking.max_x ) {
          _survey_tree.width(_tracking.start_w + dx);
        }
      }
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(_tracking) {
      _editor_body.css('cursor','');
      if(!_tracking.in_editor) { 
        _survey_tree.width(_tracking.start_w); 
      }
      _editor_body.off('mouseenter');
      _editor_body.off('mouseleave');
      _tracking = null;
    }
    $(document).off('mousemove',track_mouse);
    $(document).off('mouseup',stop_tracking_mouse);
  }

  _resizer.on('mousedown',start_resize);
}

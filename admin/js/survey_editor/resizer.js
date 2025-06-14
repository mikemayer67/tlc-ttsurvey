export default function setup_resizer(ce, container, left_frame, right_frame)
{
  const _resizer      = $('#content-editor .resizer');

  let _tracking = null;

  function start_resize(e) {
    e.preventDefault();
    container.css('cursor','col-resize');
    _tracking = { 
      min_x : 200 - left_frame.width(),
      max_x : right_frame.width() - 300,
      start_x : e.pageX,
      start_w : left_frame.width(),
      in_editor : true,
      last_move : 0,
    };
    container.on('mouseenter', function(e) { _tracking.in_editor = true;  } );
    container.on('mouseleave', function(e) { _tracking.in_editor = false; } );
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
          left_frame.width(_tracking.start_w + dx);
        }
      }
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(_tracking) {
      container.css('cursor','');
      if(!_tracking.in_editor) { 
        left_frame.width(_tracking.start_w); 
      }
      container.off('mouseenter');
      container.off('mouseleave');
      _tracking = null;
    }
    $(document).off('mousemove',track_mouse);
    $(document).off('mouseup',stop_tracking_mouse);
  }

  _resizer.on('mousedown',start_resize);
}

export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');
  const _survey_tree     = _content_editor.find('#survey-tree');
  const _element_editor  = _content_editor.find('#element-editor');
  const _resizer         = _content_editor.find('.resizer');

  // editor pane resizing

  var _resize_data = null;

  function start_resize(e) {
    e.preventDefault();
    _content_editor.css('cursor','col-resize');
    _resize_data = { 
      min_x : 200 - _survey_tree.width(),
      max_x : _element_editor.width() - 300,
      start_x : e.pageX,
      start_w : _survey_tree.width(),
      in_editor : true,
      last_move : 0,
    };
    _content_editor.on('mouseenter', function(e) { _resize_data.in_editor = true;  } );
    _content_editor.on('mouseleave', function(e) { _resize_data.in_editor = false; } );
    $(document).on('mousemove',track_mouse);
    $(document).on('mouseup',stop_tracking_mouse);
  }

  function track_mouse(e) {
    e.preventDefault();
    if(_resize_data) {
      const now = Date.now();
      if( now > _resize_data.last_move + 10 ) {
       _resize_data.last_move = now;

       const dx = e.pageX - _resize_data.start_x;
       if( dx > _resize_data.min_x && dx < _resize_data.max_x ) {
         _survey_tree.width(_resize_data.start_w + dx);
       }
      }
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(_resize_data) {
      _content_editor.css('cursor','');
      if(!_resize_data.in_editor) { 
        _survey_tree.width(_resize_data.start_w); 
      }
      _content_editor.off('mouseenter');
      _content_editor.off('mouseleave');
      _resize_data = null;
    }
    $(document).off('mousemove',track_mouse);
    $(document).off('mouseup',stop_tracking_mouse);
  }

  _resizer.on('mousedown',start_resize);

  // editor content

  function update_all_content(content) {
    console.log('update all content');
  }
  $(document).on('NewContentData', function(e,data) {
    update_all_content(data);
  });

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    update_all_content: update_all_content,
  }
};

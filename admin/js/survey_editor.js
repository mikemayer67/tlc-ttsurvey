import editor_tree from './editor_tree.js';
import editor_menubar from './editor_menubar.js';
import { deepCopy } from './utils.js';

export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');
  const _editor_mbar     = _content_editor.find('div.menubar');
  const _editor_body     = _content_editor.find('div.body');
  const _element_editor  = _editor_body.find('#element-editor');
  const _resizer         = _editor_body.find('.resizer');

  const _editor_menubar = editor_menubar(ce);
  const _editor_tree    = editor_tree(ce,_editor_menubar);

  let _editable = false;
  let _content = null;

  // editor pane resizing

  var _resize_data = null;

  function start_resize(e) {
    e.preventDefault();
    _editor_body.css('cursor','col-resize');
    _resize_data = { 
      min_x : 200 - _editor_tree.box_width(),
      max_x : _element_editor.width() - 300,
      start_x : e.pageX,
      start_w : _editor_tree.box_width(),
      in_editor : true,
      last_move : 0,
    };
    _editor_body.on('mouseenter', function(e) { _resize_data.in_editor = true;  } );
    _editor_body.on('mouseleave', function(e) { _resize_data.in_editor = false; } );
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
         _editor_tree.box_width(_resize_data.start_w + dx);
       }
      }
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(_resize_data) {
      _editor_body.css('cursor','');
      if(!_resize_data.in_editor) { 
        _editor_tree.box_width(_resize_data.start_w); 
      }
      _editor_body.off('mouseenter');
      _editor_body.off('mouseleave');
      _resize_data = null;
    }
    $(document).off('mousemove',track_mouse);
    $(document).off('mouseup',stop_tracking_mouse);
  }

  _resizer.on('mousedown',start_resize);

  // editor content

  function update_content(survey_id) {
    const survey = ce.survey_data.lookup(survey_id);
    const content = ce.survey_data.content(survey_id);

    if(!content)  { _content=null; return; }
    if(_editable) { _content = deepCopy(content) }
    else          { _content = content; }

    _editor_tree.reset();
    _editor_tree.update_content(_content, _editable);
    
    if(_editable) { _editor_mbar.show(); }
    else          { _editor_mbar.hide(); }
  }

  $(document).on('NewContentData', function(e,survey_id) {
    update_content(survey_id);
  });

  // survey tree 

  function reset_survey_tree()
  {
    _editor_tree.reset();
    _editor_mbar.hide();
  }

  // return editor object

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update_content: update_content,
  }
};

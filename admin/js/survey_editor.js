import editor_tree from './editor_tree.js';
import { deepCopy } from './utils.js';

function setup_buttons(ce)
{
  const mbar = $('#content-editor div.menubar');
  const tree = $('#survey-tree ul.sections');

  const buttons = {
    up:                mbar.find('button.up'),
    down:              mbar.find('button.down'),
    add_section_below: mbar.find('button.add.section.below'),
    add_section_above: mbar.find('button.add.section.above'),
    add_element_below: mbar.find('button.add.element.below'),
    add_element_above: mbar.find('button.add.element.above'),
    add_element_clone: mbar.find('button.add.element.clone'),
    delete:            mbar.find('button.delete'),
    undo:              mbar.find('button.undo'),
    redo:              mbar.find('button.redo'),
  };

  if(ce.isMac) {
    // u2318: command key (aka splat or propeller)
    // u21e7: shift key
    buttons.undo.attr('title','Undo edit (\u2318Z)');
    buttons.redo.attr('title','redo edit (\u2318\u21e7Z)');
  } else {
    // u2303: control key
    buttons.undo.attr('title','Undo edit (\u2303Z)');
    buttons.redo.attr('title','redo edit (\u21e7\u2303Z)');
  }

  for(const b of Object.values(buttons) ) { b.attr('disabled',true); }

  $(document).on('UndoStackChanged', function() {
    buttons.undo.attr('disabled',!ce.undo_manager.hasUndo());
    buttons.redo.attr('disabled',!ce.undo_manager.hasRedo());
  });

  buttons.undo.on('click', function() { ce.undo_manager.undo(); } );
  buttons.redo.on('click', function() { ce.undo_manager.redo(); } );

  buttons.new_section_selected = function(sid) {
    const sections = tree.find('li.section');
    const first_sid = sections.first().data('section');
    const last_sid = sections.last().data('section');
    buttons.up.attr('disabled',sid===first_sid);
    buttons.down.attr('disabled',sid===last_sid);

    buttons.add_section_below.attr('disabled',false);
    buttons.add_section_above.attr('disabled',false);
    buttons.add_element_below.attr('disabled',false);
    buttons.add_element_above.attr('disabled',true);
    buttons.add_element_clone.attr('disabled',true);
    buttons.delete.attr('disabled',false);
  }

  buttons.new_element_selected = function(eid) {
    const sections = tree.find('li.section');
    const elements = tree.find('li.element');
    const first_eid = elements.first().data('element');
    const last_eid = elements.last().data('element');

    if(eid!==first_eid) {
      buttons.up.attr('disabled',false);
    } else if(sections.first().find('li').length==0) {
      // we're first, but there is at least one section above the current
      // that is empty, we can move the element up there
      buttons.up.attr('disabled',false);
    } else {
      // we're the very first element and the first section has elements,
      // so we must be the first element in the first section.
      // there is nowhere to move the element up to
      buttons.up.attr('disabled',true);
    }

    if(eid!==last_eid) {
      buttons.down.attr('disabled',false);
    } else if(sections.last().find('li').length==0) {
      // we're last, but there is at least one section below the current
      // that is empty, we can move the element down there
      buttons.down.attr('disabled',false);
    } else {
      // we're the very last element and the last section has elements,
      // so we must be the last element in the last section.
      // there is nowhere to move the element down to
      buttons.down.attr('disabled',true);
    }

    buttons.add_section_below.attr('disabled',true);
    buttons.add_section_above.attr('disabled',true);
    buttons.add_element_below.attr('disabled',false);
    buttons.add_element_above.attr('disabled',false);
    buttons.add_element_clone.attr('disabled',false);
    buttons.delete.attr('disabled',false);
  }

  buttons.clear_selection = function() {
    for(const [k,b] of Object.entries(buttons)) {
      if ( b instanceof jQuery ) {
        if(k!=='undo' && k!=='redo') {
          b.attr('disabled',true); 
        }
      }
    }
  };

  return buttons;
}

export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');
  const _editor_mbar     = _content_editor.find('div.menubar');
  const _editor_body     = _content_editor.find('div.body');
  const _element_editor  = _editor_body.find('#element-editor');
  const _resizer         = _editor_body.find('.resizer');

  const _buttons = setup_buttons(ce);
  const _editor_tree = editor_tree(ce,_buttons);

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

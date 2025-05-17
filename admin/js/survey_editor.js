import Sortable from '../../js/sortable.esm.js';
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
  const _tree_box        = _editor_body.find('#survey-tree');
  const _tree_info       = _tree_box.find('.info');
  const _survey_tree     = _tree_box.find('ul.sections');
  const _element_editor  = _editor_body.find('#element-editor');
  const _resizer         = _editor_body.find('.resizer');

  const _buttons = setup_buttons(ce);

  let _editable = false;
  let _content = null;

  // editor pane resizing

  var _resize_data = null;

  function start_resize(e) {
    e.preventDefault();
    _editor_body.css('cursor','col-resize');
    _resize_data = { 
      min_x : 200 - _tree_box.width(),
      max_x : _element_editor.width() - 300,
      start_x : e.pageX,
      start_w : _tree_box.width(),
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
         _tree_box.width(_resize_data.start_w + dx);
       }
      }
    }
  }

  function stop_tracking_mouse(e) {
    e.preventDefault();
    if(_resize_data) {
      _editor_body.css('cursor','');
      if(!_resize_data.in_editor) { 
        _tree_box.width(_resize_data.start_w); 
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
    console.log('update all content');
    const survey = ce.survey_data.lookup(survey_id);
    const content = ce.survey_data.content(survey_id);

    reset_survey_tree();

    if(!content)  { _content=null; return; }
    if(_editable) { _content = deepCopy(content) }
    else          { _content = content; }

    // Add sections to the survey tree

    Object.keys(content.sections)
    .map(Number)
    .sort((a,b) => a-b)
    .forEach( sid => { // section id
      const section = content.sections[sid];

      const btn = $('<button>').addClass('toggle');
      const span = $('<span>').text(section.name).addClass('name');
      const div = $('<div>').append(btn,span);

      const li = $('<li>')
        .addClass('section closed')
        .attr('data-section',sid)
        .html(div)
        .appendTo(_survey_tree);

      const ul = $('<ul>').addClass('elements').appendTo(li);

      btn.on('click', function(e) {
        const li = $(this).parent().parent();
        if( li.hasClass('closed') ) { li.removeClass('closed'); }
        else                        { li.addClass('closed');    }
      });
      span.on('click', new_section_selected);

      Object.entries(content.elements)
      .filter( ([eid,element]) => element.section == sid )
      .sort( ([aid,a],[bid,b]) => a.sequence - b.sequence )
      .forEach( ([eid,element]) => {

        const eli = $('<li>')
        .addClass('element')
        .addClass(element.type.toLowerCase())
        .attr('data-element',eid)
        .text(element.label);
        if(element.multiple) { 
          eli.addClass('multi'); 
        }
        eli.appendTo(ul);
        eli.on('click',new_element_selected);
      });
    });

    // Tree sorting
    if( _editable ) {
      _editor_mbar.show();
      setup_tree_sorting();
      enable_tree_sorting();
    }
  }

  $(document).on('NewContentData', function(e,survey_id) {
    update_content(survey_id);
  });

  // update handlers

  function clear_current_selection()
  {
    _survey_tree.find('.selected').removeClass('selected');
    _survey_tree.find('.selected-child').removeClass('selected-child');
    _buttons.clear_selection();
  }

  function new_section_selected(e)
  {
    clear_current_selection();

    $(this).parent().addClass('selected');
    const li = $(this).parent().parent();
    const sid = li.data('section');
    _buttons.new_section_selected(sid);
  }

  function new_element_selected(e)
  {
    clear_current_selection();

    $(this).addClass('selected');
    const eid = $(this).data('element');
    $(this).parent().parent().addClass('selected-child');
    _buttons.new_element_selected(eid);
  }
  
//  function send_change_notification(what,info)
//  {
//    if(info !== undefined) {
//      $(document).trigger('ContentModifiedByUser',{what:what,info:info});
//    } else {
//      $(document).trigger('ContentModifiedByUser',{what:what});
//    }
//  }
//  
//  function handle_user_updates(info)
//  {
//    console.log('handle user updates: ' + info.what);
//  }
//
//  $(document).on('ContentModifiedByUser', function(e,info) {
//    handle_user_updates(info);
//  });

  // survey tree 

  let _element_sorters = [];

  const _section_sorter = new Sortable(
    _survey_tree[0],
    {
      group: 'sections',
      animation: 150,
      filter: '.element',
      onEnd: handle_drop_section,
    }
  );

  function reset_survey_tree()
  {
    _section_sorter.option('disabled',true);
    _element_sorters.forEach( (s) => s.destroy() );
    _element_sorters = [];
    _survey_tree.empty();
    _tree_info.hide();
    _editor_mbar.hide();
  }

  function setup_tree_sorting()
  {
    _section_sorter.option('disabled',false);
    _survey_tree.find('ul.elements').each( function() {
      const sorter = new Sortable(
        this,
        {
          group: { name:'elements', pull:true, pust:true },
          animation: 150,
          onEnd: handle_drop_element,
        }
      );
      _element_sorters.push(sorter);
    });
  }

  function handle_drop_section(e)
  {
    _survey_tree.find('.drop-target').removeClass('drop-target');

    if(e.oldIndex === e.newIndex) { return false; }

    const action = {
      element: e.item,
      oldIndex: e.oldIndex,
      newIndex: e.newIndex,
      undo() {
        const st = _survey_tree[0];
        const peers = st.children;
        var tgt_index = this.oldIndex;
        if( tgt_index > this.newIndex ) { tgt_index +=1; }
        if( tgt_index >= peers.length ) {
          console.log('undo append');
          st.appendChild(this.element);
        } else {
          console.log('undo insert at ' + tgt_index);
          st.insertBefore(this.element, peers[tgt_index]);
        }
      },
      redo() {
        const st = _survey_tree[0];
        const peers = st.children;
        var tgt_index = this.newIndex;
        if( tgt_index > this.oldIndex ) { tgt_index +=1; }
        if( tgt_index >= peers.length ) {
          console.log('undo append');
          st.appendChild(this.element);
        } else {
          console.log('redo insert at ' + tgt_index);
          st.insertBefore(this.element, peers[tgt_index]);
        }
      },
    };

    ce.undo_manager.add(action);

    return true;
  }

  function handle_drop_element(e)
  {
    _survey_tree.find('.drop-target').removeClass('drop-target');

    if(e.from === e.to && e.oldIndex === e.newIndex) { return false; }

    const action = {
      element: e.item,
      from: e.from,
      to: e.to,
      oldIndex: e.oldIndex,
      newIndex: e.newIndex,
      undo() {
        var tgt_index = this.oldIndex;
        if( tgt_index > this.newIndex ) { tgt_index +=1; }
        const peers = this.from.children;
        if( tgt_index >= peers.length ) {
          console.log('undo append');
          this.from.appendChild(this.element);
        } else {
          console.log('undo insert at ' + tgt_index);
          this.from.insertBefore(this.element, peers[tgt_index]);
        }
      },
      redo() {
        var tgt_index = this.newIndex;
        if( tgt_index > this.oldIndex ) { tgt_index +=1; }
        const peers = this.to.children;
        if( tgt_index >= peers.length ) {
          console.log('redo append');
          this.to.appendChild(this.element);
        } else {
          console.log('redo insert at ' + tgt_index);
          this.to.insertBefore(this.element, peers[tgt_index]);
        }
      },
    };

    ce.undo_manager.add(action);

    return true;
  }

  function disable_tree_sorting()
  {
    _tree_info.hide();
    _section_sorter.option('disabled',true);
    _element_sorters.forEach( (s) => s.option('disabled',true) );
  }

  function enable_tree_sorting()
  {
    _tree_info.show();
    _section_sorter.option('disabled',false);
    _element_sorters.forEach( (s) => s.option('disabled',false) );
  }

  _tree_box.on('click', function(e) {
    const clicked_li = $(e.target).closest('li');
    if(clicked_li.length === 0) {
      clear_current_selection();
    }
  });


  // return editor object

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update_content: update_content,
  }
};

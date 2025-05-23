import editor_tree from './editor_tree.js';
import editor_menubar from './editor_menubar.js';
import setup_editor_resizer from './editor_resizer.js';
import { deepCopy } from './utils.js';

export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');

  const _editor_tree    = editor_tree(ce);
  const _editor_menubar = editor_menubar(ce);

  let _editable = false;
  let _content = null;
  let _next_section_id = 1;
  let _next_element_id = 1;

  setup_editor_resizer(ce,_editor_tree);

  // editor content

  function update(content) {

    _editor_tree.reset();

    if(content) {
      // if editing is enabled, we want to work on a copy of the content.
      // if editing is disabled, it's ok to view the content directly.
      if(_editable) { _content = deepCopy(content) }
      else          { _content = content; }

      _editor_tree.update(_content);

      if(_editable) {
        const sections = $('#survey-tree li.section').map(function() { 
          return Number($(this).data('section')); 
        }).get();
        _next_section_id = 1 + Math.max(...sections)
        _next_element_id = _content.next_ids.element;
        _editor_tree.enable(); 
      }

    } else {
      // no content... 
      _content = null;
    }

    _editor_menubar.show(_editable);

    ce.undo_manager?.revert();
  }

  // insertion handlers
  
  $(document).on('AddNewSection',function(e,where) {
    const new_section_id = _next_section_id++;
    const new_section = { name:"", description:"", show:false, feedback:false };
    const cur_highlight = _editor_tree.cache_highlight();

    _content.sections[ new_section_id ] = new_section;

    ce.undo_manager.add_and_exec( {
      redo() {
        _editor_tree.add_section( new_section_id, new_section, where );
      },
      undo() {
        _editor_tree.remove_section(new_section_id);
        _editor_tree.restore_highlight(cur_highlight);
      },
    });
  });

  $(document).on('AddNewElement',function(e,where) {
    const new_element_id = _next_element_id++;
    const new_element = { type:null };
    const cur_highlight = _editor_tree.cache_highlight();

    _content.elements[ new_element_id ] = new_element;

    ce.undo_manager.add_and_exec( {
      redo() { 
        _editor_tree.add_element(new_element_id, new_element, where);
      },
      undo() {
        _editor_tree.remove_element(new_element_id);
        _editor_tree.restore_highlight(cur_highlight);
      },
    });
  });

  $(document).on('CloneElement',function(e,data) {
    if( data.parent_id in _content.elements ) {
      const new_element_id = _next_element_id++;
      const new_element = deepCopy( _content.elements[data.parent_id] );
      new_element.label = null;
      const cur_highlight = _editor_tree.cache_highlight();

      _content.elements[new_element_id] = new_element; 

      const where = { offset:1, element_id:data.parent_id };

      ce.undo_manager.add_and_exec( {
        redo() {
          _editor_tree.add_element(new_element_id, new_element, where);
        },
        undo() {
          // note that we are leaving the new element in _content.elements
          //   this will make it more efficient to redo the clone later...
          //   If the form is submitted without readding it to the DOM, it
          //   simply will not be part of what gets submitted.
          _editor_tree.remove_element(new_element_id);
          _editor_tree.restore_highlight(cur_highlight);
        },
      });
    }
  });

  // deletion handlers

  function delete_section(sectionId) {
    alert(`delete section ${sectionId}`);
  }
  $(document).on('RequestDeleteSection',function(e,sid) { delete_section(sid); } );

  function delete_element(to_delete) {
    if( to_delete.length !== 1 ) { return; }

    const element_id = to_delete.data('element');
    const element = _content.elements[element_id];

    const prev = to_delete.prev();
    if( prev.length === 1 ) { 
      ce.undo_manager.add_and_exec({
        redo() { 
          _editor_tree.remove_element(element_id);
        },
        undo() {
          _editor_tree.add_element(element_id, element, {offset:1, element_id:prev.data('element')});
        },
      });
    } else {
      const sectionId = to_delete.parent().parent().data('section');
      ce.undo_manager.add_and_exec({
        redo() { 
          _editor_tree.remove_element(element_id);
        },
        undo() {
          _editor_tree.add_element(element_id, element, {section_id:sectionId});
        },
      });
    }
  }
  $(document).on('RequestDeleteElement',function(e,eid) { delete_element(eid); } );

  // return editor object

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update: update,
  };
};

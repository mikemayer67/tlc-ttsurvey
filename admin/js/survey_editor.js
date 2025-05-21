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
        _editor_tree.enable(); 
      }

    } else {
      // no content... 
      _content = null;
    }

    _editor_menubar.show(_editable);

    ce.undo_manager?.revert();
  }

  // deletion handlers

  function delete_section(sectionId) {
    alert(`delete section ${sectionId}`);
  }
  $(document).on('RequestDeleteSection',function(e,sid) { delete_section(sid); } );

  function delete_element(elementId) {
    alert(`delete element ${elementId}`);
  }
  $(document).on('RequestDeleteElement',function(e,eid) { delete_element(eid); } );

  // return editor object

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update: update,
  }
};

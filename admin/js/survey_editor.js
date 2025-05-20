import editor_tree from './editor_tree.js';
import editor_menubar from './editor_menubar.js';
import setup_editor_resizer from './editor_resizer.js';
import { deepCopy } from './utils.js';

export default function survey_editor(ce)
{
  const _content_editor  = $('#content-editor');

  const _editor_menubar = editor_menubar(ce);
  const _editor_tree    = editor_tree(ce,_editor_menubar);

  let _editable = false;
  let _content = null;

  setup_editor_resizer(ce,_editor_tree);

  // editor content

  function update_content(survey_id) {
    const survey = ce.survey_data.lookup(survey_id);
    const content = ce.survey_data.content(survey_id);

    if(!content)  { _content=null; return; }
    if(_editable) { _content = deepCopy(content) }
    else          { _content = content; }

    revert();
  }

  $(document).on('NewContentData', function(e,survey_id) {
    update_content(survey_id);
  });

  function revert() {
    _editor_tree.reset();
    _editor_tree.update_content(_content,_editable);
    _editor_menubar.show(_editable);
    ce.undo_manager?.revert();
  }


  // deletion handlers

  function delete_section(sectionId) {
    alert(`delete section ${sectionId}`);
  }

  function delete_element(elementId) {
    alert(`delete element ${elementId}`);
  }

  _editor_menubar.set_delete_section_hook(delete_section);
  _editor_menubar.set_delete_element_hook(delete_element);

  // return editor object

  return {
    show() { _content_editor.show(); },
    hide() { _content_editor.hide(); },
    disable() { _editable = false },
    enable() { _editable = true },
    update_content: update_content,
    revert: revert,
  }
};

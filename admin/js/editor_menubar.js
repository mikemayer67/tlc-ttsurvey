export default function editor_menubar(ce)
{
  const _mbar = $('#content-editor div.menubar');
  const _tree = $('#survey-tree ul.sections');

  const _up                = _mbar.find('button.up');
  const _down              = _mbar.find('button.down');
  const _add_section_below = _mbar.find('button.add.section.below');
  const _add_section_above = _mbar.find('button.add.section.above');
  const _add_element_below = _mbar.find('button.add.element.below');
  const _add_element_above = _mbar.find('button.add.element.above');
  const _add_element_clone = _mbar.find('button.add.element.clone');
  const _delete            = _mbar.find('button.delete');
  const _undo              = _mbar.find('button.undo');
  const _redo              = _mbar.find('button.redo');

  const _buttons = [
    _up, _down,
    _add_section_below, _add_section_above,
    _add_element_below, _add_element_above, _add_element_clone,
    _delete, _undo, _redo,
  ];

  if(ce.isMac) {
    // u2318: command key (aka splat or propeller)
    // u21e7: shift key
    _undo.attr('title','Undo edit (\u2318Z)');
    _redo.attr('title','redo edit (\u2318\u21e7Z)');
  } else {
    // u2303: control key
    _undo.attr('title','Undo edit (\u2303Z)');
    _redo.attr('title','redo edit (\u21e7\u2303Z)');
  }

  _buttons.forEach( (b) => { b.attr('disabled',true); } );

  $(document).on('UndoStackChanged', function() {
    _undo.attr('disabled',!ce.undo_manager.hasUndo());
    _redo.attr('disabled',!ce.undo_manager.hasRedo());
  });

  _undo.on('click', function() { ce.undo_manager.undo(); } );
  _redo.on('click', function() { ce.undo_manager.redo(); } );

  function new_section_selected(sid) {
    const sections = _tree.find('li.section');
    const first_sid = sections.first().data('section');
    const last_sid = sections.last().data('section');

    _up.attr('disabled',sid===first_sid);
    _down.attr('disabled',sid===last_sid);

    _add_section_below.attr('disabled',false);
    _add_section_above.attr('disabled',false);
    _add_element_below.attr('disabled',false);
    _add_element_above.attr('disabled',true);
    _add_element_clone.attr('disabled',true);
    _delete.attr('disabled',false);
  }

  function new_element_selected(eid) {
    const sections = _tree.find('li.section');
    const elements = _tree.find('li.element');
    const first_eid = elements.first().data('element');
    const last_eid = elements.last().data('element');

    if(eid!==first_eid) {
      _up.attr('disabled',false);
    } else if(sections.first().find('li').length==0) {
      // we're first, but there is at least one section above the current
      // that is empty, we can move the element up there
      _up.attr('disabled',false);
    } else {
      // we're the very first element and the first section has elements,
      // so we must be the first element in the first section.
      // there is nowhere to move the element up to
      _up.attr('disabled',true);
    }

    if(eid!==last_eid) {
      _down.attr('disabled',false);
    } else if(sections.last().find('li').length==0) {
      // we're last, but there is at least one section below the current
      // that is empty, we can move the element down there
      _down.attr('disabled',false);
    } else {
      // we're the very last element and the last section has elements,
      // so we must be the last element in the last section.
      // there is nowhere to move the element down to
      _down.attr('disabled',true);
    }

    _add_section_below.attr('disabled',true);
    _add_section_above.attr('disabled',true);
    _add_element_below.attr('disabled',false);
    _add_element_above.attr('disabled',false);
    _add_element_clone.attr('disabled',false);
    _delete.attr('disabled',false);
  }

  function clear_selection() {
    [ _up, _down, _delete,
      _add_section_below, _add_section_above,
      _add_element_below, _add_element_above, _add_element_clone,
    ].forEach( (b) => { b.attr('disabled',true); });
  }

  return {
    new_section_selected: new_section_selected,
    new_element_selected: new_element_selected,
    clear_selection: clear_selection,
    show(v=true) { if(v) { _mbar.show() } else { _mbar.hide() } },
    hide()  { _mbar.hide() },
  };
}

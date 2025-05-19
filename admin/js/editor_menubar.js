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

  let move_section = null;
  let move_element = null;

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

  _up.on('click',-1,move_selected);
  _down.on('click',1,move_selected);

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

  function move_selected(e) {
    const delta = e.data;
    const selected_item = _tree.find('li.selected');

    // there is an implicit design assumption that any selected item will have 
    //   either the 'section' or 'element' class and never both
    const isSection = selected_item.hasClass('section');
    if(isSection) { move_selected_section(selected_item,delta); } 
    else          { move_selected_element(selected_item,delta); }
  }

  function move_selected_section(item,delta) {
    // input item should be a li.section jquery object

    // but only do something if the hook into editor_tree was set.
    if(!move_section) { return false; }
    
    const allSections = _tree.children('li.section');
    const curIndex    = allSections.index(item);
    const newIndex    = curIndex + delta;

    // If the UI is working correctly, the following test should never
    // fail, but let's not take that chance.  Only move to a valid index.
    if(newIndex < 0 || newIndex >= allSections.length) { return false; }

    const sectionId = item.data('section');
    // note below that while we're moving the selected section and not actually
    //   selecting a new section, the new_section_selected function captures all
    //   the necessary DOM element state updates associated with a move.
    ce.undo_manager.add_and_exec( {
      redo() { move_section(sectionId,newIndex); new_section_selected(sectionId); },
      undo() { move_section(sectionId,curIndex); new_section_selected(sectionId); },
    });

    return true;
  }

  function move_selected_element(item,delta) {
    // input item should be a li.element jquery object
    // input delta must be +/- 1.  Other values could break moving between sections

    // but only do something if the hook into editor_tree was set.
    if(!move_element) { return false; }
    
    const elementId    = item.data('element');
    const curSectionLI = item.parent().parent();
    const curSectionId = curSectionLI.data('section');
    const curPeers     = curSectionLI.find('li.element');
    const curIndex     = curPeers.index(item);

    // assume for a second that we're moving the element within the current section
    let newIndex     = curIndex + delta;
    let newSectionId = curSectionId;

    if(newIndex < 0 || newIndex >= curPeers.length) {
      // nope, we're attempting to move the element to a different section
      // see if we can move the item to prior (delta<0) or next (delta>0) section
      const allSections     = _tree.children('li.section');
      const curSectionIndex = allSections.index(curSectionLI);
      const newSectionIndex = curSectionIndex + delta;
      const newSectionLI    = allSections.eq(newSectionIndex);

      // if the UI is working correctly, the following test should never
      // fail, but let's not take that chance.  Only move to a valid section
      if( newSectionLI.length !== 1 ) { return false; }

      newSectionId = newSectionLI.data('section');

      if( delta > 0 ) { newIndex = 0;                                      } // start of next section
      else            { newIndex = newSectionLI.find('li.element').length; } // end of prior section
    }
       
    ce.undo_manager.add_and_exec( {
      redo() { move_element(elementId,newSectionId,newIndex); new_element_selected(elementId); },
      undo() { move_element(elementId,curSectionId,curIndex); new_element_selected(elementId); },
    });

    return true;
  }

  return {
    new_section_selected: new_section_selected,
    new_element_selected: new_element_selected,
    clear_selection: clear_selection,
    show(v=true) { if(v) { _mbar.show() } else { _mbar.hide() } },
    hide()  { _mbar.hide() },
    set_move_tree_section_hook(f) { move_section = f; },
    set_move_tree_element_hook(f) { move_element = f; },
  };
}

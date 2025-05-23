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
    _up, _down, _delete, _undo, _redo,
    _add_section_below, _add_section_above,
    _add_element_below, _add_element_above, _add_element_clone,
  ];

  const _selection_buttons = [
    _up, _down, _delete,
    _add_section_below, _add_section_above,
    _add_element_below, _add_element_above, _add_element_clone,
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


  // Undo/Redo Buttons

  $(document).on('UndoStackChanged', function() {
    _undo.attr('disabled',!ce.undo_manager.hasUndo());
    _redo.attr('disabled',!ce.undo_manager.hasRedo());
  });

  _undo.on('click', function() { ce.undo_manager.undo(); } );
  _redo.on('click', function() { ce.undo_manager.redo(); } );


  // Delete Button

  _delete.on('click', function() {
    const item = _tree.find('li.selected');

    const isSection = item.hasClass('section');
    if( isSection ) { $(document).trigger('RequestDeleteSection', [item.data('section')]) } 
    else            { $(document).trigger('RequestDeleteElement', [item.data('element')]) }
  });

  // Up/Down Buttons

  _up.on(  'click', -1, request_move);
  _down.on('click',  1, request_move);

  function request_move(e) {
    const delta = e.data;

    // if everything is working, there will never be more than one <li> selected
    const selected_item = _tree.find('li.selected');
    if(selected_item.length !== 1) { return; }

    // there is an implicit design assumption and that selected <li> will have 
    //   either the 'section'  or 'element' class and never both.
    const isSection = selected_item.hasClass('section');
    if(isSection) { request_move_section(selected_item,delta); } 
    else          { request_move_element(selected_item,delta); }
  }

  function request_move_section(item,delta) {
    // input item should be a li.section jquery object

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
      redo() { 
        $(document).trigger('RequestMoveSection', {
          sectionId:sectionId,
          toIndex:newIndex,
        });
      },
      undo() { 
        $(document).trigger('RequestMoveSection', {
          sectionId:sectionId,
          toIndex:curIndex,
        });
      },
    } );

    return true;
  }

  function request_move_element(item,delta) {
    // input item should be a li.element jquery object
    // input delta must be +/- 1.  Other values could break moving between sections

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
      redo() { 
        $(document).trigger('RequestMoveElement', {
          elementId:elementId,
          toSectionId:newSectionId,
          toIndex:newIndex,
        });
      },
      undo() { 
        $(document).trigger('RequestMoveElement', {
          elementId:elementId,
          toSectionId:curSectionId,
          toIndex:curIndex,
        });
      },
    } );

    return true;
  }

  // Add section/element buttons

  _add_section_above.on('click', () => request_new_section(-1) );
  _add_section_below.on('click', () => request_new_section( 1) );

  function request_new_section(delta) {
    const curSelection = _tree.find('li.section.selected'); 
    if(curSelection.length === 1) {
      $(document).trigger('AddNewSection',{
        offset:delta,
        section_id:curSelection.data('section'),
      });
    }
  }

  _add_element_above.on('click', () => request_new_element(-1) );
  _add_element_below.on('click', () => request_new_element( 1) );

  function request_new_element(delta) {
    const curSelection = _tree.find('li.selected');
    if(curSelection.length === 1) { 
      $(document).trigger('AddNewElement', {
        offset:delta,
        section_id:curSelection.data('section'),
        element_id:curSelection.data('element'),
      });
    }
  }

  _add_element_clone.on('click', function() {
    const curSelection = _tree.find('li.element.selected');
    if(curSelection.length === 1) {
      $(document).trigger('CloneElement', {
        parent_id: curSelection.data('element'),
      });
    }
  });

  //
  // Selection change handlers
  //

  function update_selection_buttons()
  {
    const selected_item = _tree.find('li.selected');

    if(selected_item.length !== 1) {
      _selection_buttons.forEach( (b) => b.attr('disabled',true) );
    }
    else if(selected_item.hasClass('section')) {
      _add_section_below.attr('disabled',false);
      _add_section_above.attr('disabled',false);
      _add_element_below.attr('disabled',false);
      _add_element_above.attr('disabled',true);
      _add_element_clone.attr('disabled',true);
      _delete.attr('disabled',false);
    }
    else { // element selected
      _add_section_below.attr('disabled',true);
      _add_section_above.attr('disabled',true);
      _add_element_below.attr('disabled',false);
      _add_element_above.attr('disabled',false);
      _add_element_clone.attr('disabled',false);
      _delete.attr('disabled',false);
    }

    update_up_down_buttons();
  }
  $(document).on('UserSelectedSection',update_selection_buttons);
  $(document).on('UserSelectedElement',update_selection_buttons);
  $(document).on('UserClearedSelection',update_selection_buttons);

  function update_up_down_buttons()
  {
    const selected_item = _tree.find('li.selected');

    if(selected_item.length !== 1) {
      _up.attr('disabled',true);
      _down.attr('disabled',true);
      return;
    } 

    // there has to be at least one section, otherwise selected_item would be empty
    const sections  = _tree.find('li.section');
    const first_section = sections.first();
    const last_section  = sections.last();

    if(selected_item.hasClass('section')) {
      // handle case where selected item is a section
      _up.attr(  'disabled', selected_item.is(first_section));  // cannot move first section up
      _down.attr('disabled', selected_item.is(last_section));   // cannot move last section down
    } 
    else {
      // handle case where selected item is an element
      const elements = _tree.find('li.element');
      const first_element = elements.first();
      const last_element  = elements.last();

      if(!selected_item.is(first_element)) { // not first element, can move up
        _up.attr('disabled',false);
      } else if( first_section.find('li.element').length == 0 ) {
        // the selected item is first, but there is at least one section above
        // that is empty, we can move the element up there
        _up.attr('disabled',false);
      } else {
        // the selected item is the first element in the first section
        // there is nowhere to move it up to
        _up.attr('disabled',true);
      }

      if(!selected_item.is(last_element)) { // not last element, can move down
        _down.attr('disabled',false);
      } else if( last_section.find('li.element').length == 0 ) {
        // the selected item is last, but there is at least one section below
        // that is empty, we can move the element down there
        _down.attr('disabled',false);
      } else {
        // the selected item is the last element in the last section
        // there is nowhere to move it down to
        _down.attr('disabled',true);
      }
    }
  }
  $(document).on('SurveyContentWasReordered',update_up_down_buttons);


  return {
    show(v=true) { if(v) { _mbar.show() } else { _mbar.hide() } },
    hide()       { _mbar.hide() },
  };
}

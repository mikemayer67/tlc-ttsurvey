export default function editor_menubar(ce,controller)
{
  const _mbar = $('#content-editor div.menubar');
  const _tree = $('#survey-tree ul.sections');

  const _up                 = _mbar.find('button.up');
  const _down               = _mbar.find('button.down');
  const _add_section_below  = _mbar.find('button.add.section.below');
  const _add_section_above  = _mbar.find('button.add.section.above');
  const _add_question_below = _mbar.find('button.add.question.below');
  const _add_question_above = _mbar.find('button.add.question.above');
  const _add_question_clone = _mbar.find('button.add.question.clone');
  const _delete             = _mbar.find('button.delete');
  const _undo               = _mbar.find('button.undo');
  const _redo               = _mbar.find('button.redo');

  const _buttons = [
    _up, _down, _delete, _undo, _redo,
    _add_section_below, _add_section_above,
    _add_question_below, _add_question_above, _add_question_clone,
  ];

  const _selection_buttons = [
    _up, _down, _delete,
    _add_section_below, _add_section_above,
    _add_question_below, _add_question_above, _add_question_clone,
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
    if( isSection ) { controller.delete_section(item); }
    else            { controller.delete_question(item); }
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
    //   either the 'section'  or 'question' class and never both.
    const isSection = selected_item.hasClass('section');
    if(isSection) { request_move_section(selected_item,delta); } 
    else          { request_move_question(selected_item,delta); }
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
      action:'mb-move-section',
      redo() { controller.move_section(sectionId,newIndex) },
      undo() { controller.move_section(sectionId,curIndex) },
    } );

    return true;
  }

  function request_move_question(item,delta) {
    // input item should be a li.question jquery object
    // input delta must be +/- 1.  Other values could break moving between sections

    const questionId   = item.data('question');
    const curSectionLI = item.parent().parent();
    const curSectionId = curSectionLI.data('section');
    const curPeers     = curSectionLI.find('li.question');
    const curIndex     = curPeers.index(item);

    // assume for a second that we're moving the question within the current section
    let newIndex     = curIndex + delta;
    let newSectionId = curSectionId;

    if(newIndex < 0 || newIndex >= curPeers.length) {
      // nope, we're attempting to move the question to a different section
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
      else            { newIndex = newSectionLI.find('li.question').length; } // end of prior section
    }

    ce.undo_manager.add_and_exec( {
      action:'mb-move-question',
      redo() { controller.move_question(questionId,newSectionId,newIndex) },
      undo() { controller.move_question(questionId,curSectionId,curIndex) },
    } );

    return true;
  }

  // Add section/question buttons

  _add_section_above.on('click', () => request_new_section(-1) );
  _add_section_below.on('click', () => request_new_section( 1) );

  function request_new_section(delta) {
    const curSelection = _tree.find('li.section.selected'); 
    if(curSelection.length === 1) {
      controller.add_new_section({
        offset:delta,
        section_id:curSelection.data('section'),
      });
    } else {
      controller.add_new_section({
        offset:0,
        section_id:null,
      });
    }
  }

  _add_question_above.on('click', () => request_new_question(-1) );
  _add_question_below.on('click', () => request_new_question( 1) );

  function request_new_question(delta) {
    const curSelection = _tree.find('li.selected');
    if(curSelection.length === 1) { 
      controller.add_new_question({
        offset:delta,
        section_id:curSelection.data('section'),
        question_id:curSelection.data('question'),
      });
    }
  }

  _add_question_clone.on('click', function() {
    const curSelection = _tree.find('li.question.selected');
    if(curSelection.length === 1) {
      controller.clone_question({
        parent_id: curSelection.data('question'),
      });
    }
  });

  //
  // Selection change handlers
  //

  function update_selection()
  {
    const selected_item = _tree.find('li.selected');

    if(selected_item.length === 0) {
      _selection_buttons.forEach( (b) => b.attr('disabled',true) );
      if(_tree.children('li').length === 0) {
        _add_section_below.attr('disabled',false);
      }
    }
    else if(selected_item.hasClass('section')) {
      _add_section_below.attr('disabled',false);
      _add_section_above.attr('disabled',false);
      _add_question_below.attr('disabled',false);
      _add_question_above.attr('disabled',true);
      _add_question_clone.attr('disabled',true);
      _delete.attr('disabled',false);
    }
    else { // question selected
      _add_section_below.attr('disabled',true);
      _add_section_above.attr('disabled',true);
      _add_question_below.attr('disabled',false);
      _add_question_above.attr('disabled',false);
      _add_question_clone.attr('disabled',false);
      _delete.attr('disabled',false);
    }

    update_up_down_buttons();
  }

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
      // handle case where selected item is an question
      const questions = _tree.find('li.question');
      const first_question = questions.first();
      const last_question  = questions.last();

      if(!selected_item.is(first_question)) { // not first question, can move up
        _up.attr('disabled',false);
      } else if( first_section.find('li.question').length == 0 ) {
        // the selected item is first, but there is at least one section above
        // that is empty, we can move the question up there
        _up.attr('disabled',false);
      } else {
        // the selected item is the first question in the first section
        // there is nowhere to move it up to
        _up.attr('disabled',true);
      }

      if(!selected_item.is(last_question)) { // not last question, can move down
        _down.attr('disabled',false);
      } else if( last_section.find('li.question').length == 0 ) {
        // the selected item is last, but there is at least one section below
        // that is empty, we can move the question down there
        _down.attr('disabled',false);
      } else {
        // the selected item is the last question in the last section
        // there is nowhere to move it down to
        _down.attr('disabled',true);
      }
    }
  }
  $(document).on('SurveyWasReordered',update_up_down_buttons);


  return {
    show(v=true) { if(v) { _mbar.show() } else { _mbar.hide() } },
    hide()       { _mbar.hide() },
    update_selection:update_selection,
  };
}

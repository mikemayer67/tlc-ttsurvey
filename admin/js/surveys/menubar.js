
/**
 * Controller used to manage the survey panel menubar
 * 
 * @typedef {Object} MenubarController
 * @property { (show:boolean) => void } show
 * @property { () => void } hide
 * @property { () => void } updates_selection Updates selection based on navigation tree
 */

/**
 * Initializes a MenubarController object
 * @param {object} ce Container of shared "global" variables
 * @param {SurveyViewController} controller TODO add JSDoc to controller
 * @returns {NavTreeController}
 */
export default function init(ce,controller)
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
  const _add_group          = _mbar.find('button.add.group');
  const _remove_group       = _mbar.find('button.remove.group');
  const _delete             = _mbar.find('button.delete');
  const _undo               = _mbar.find('button.undo');
  const _redo               = _mbar.find('button.redo');

  const _buttons = [
    _up, _down, _delete, _undo, _redo,
    _add_section_below, _add_section_above,
    _add_question_below, _add_question_above, _add_question_clone,
    _add_group, _remove_group,
  ];

  const _selection_buttons = [
    _up, _down, _delete,
    _add_section_below, _add_section_above,
    _add_question_below, _add_question_above, _add_question_clone,
    _add_group, _remove_group,
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
    switch(item.data('type')) {
      case 'section':  controller.delete_section(item);  break;
      case 'group':    controller.delete_group(item);    break;
      case 'question': controller.delete_question(item); break;
    }
  });

  // Up/Down Buttons

  _up.on(  'click', -1, request_move);
  _down.on('click',  1, request_move);

  /**
   * Event handler for when someone clicks on the up or down button
   * @param {MouseEvent} evt 
   * @returns 
   */
  function request_move(evt) {
    const delta = evt.data;

    // if everything is working, there will never be more than one <li> selected
    const item = _tree.find('li.selected');
    if(item.length !== 1) { return; }

    switch(item.data('type')) {
      case 'section':  request_move_section(item,delta);  break;
      case 'group':    request_move_group(item,delta);    break;
      case 'question': request_move_question(item,delta); break;
    }
  }

  /**
   * Handles request_move where the current selection is a section <li>
   *  Verifies that the move is valid.  If so it registers the move with
   *  the undo manager and immediately executes the move (via redo)
   * 
   * @param {HTMLLIElement} section_li 
   * @param {number} delta 
   * @returns {boolean} Success
   */
  function request_move_section(section_li,delta) 
  {
    if(delta === 0) { return false; }

    const allSections = _tree.children('li.section');
    const curIndex    = allSections.index(section_li);
    const newIndex    = curIndex + delta;

    // If the UI is working correctly, the following test should never
    // fail, but let's not take that chance.  Only move to a valid index.
    if(newIndex < 0 || newIndex >= allSections.length) { return false; }

    const sectionId = section_li.data('item-id');
    ce.undo_manager.add_and_exec( {
      action:'mb-move-section',
      redo() { controller.move_section(sectionId,newIndex) },
      undo() { controller.move_section(sectionId,curIndex) },
    } );

    return true;
  }

  /**
   * Finds all of the group <li> elements and all of the question <li>
   *   elements that are not in a group <li> within the specified section <li>
   * 
   * Or more simply put, all unnested <li> children of the section <ul>
   * 
   * @param {HTMLLIElement} section_li 
   * @returns {jQuery<HTMLLIElement>}
   */
  function find_section_items(section_li)
  {
    return section_li.find('li.question, li.group').not('li.group li.question');
  }

  /**
   * Handles request_move where the current selection is a group <li>
   *   First checks to see if the group can be relocated within its current section
   *   If so, it moves the group to the new index in the section
   *   If not, can it be moved to a prior(delta<0) or subsequent(delta>0) section
   *   If so, it moves the group to end/start of prior/subsequent section
   *   If not, returns false
   * 
   * If a move is made, it is done by registering the move with the undo manager
   *   and immediately calling the redo method and returning true
   * 
   * @param {HTMLLIElement} question_li 
   * @param {number} delta 
   * @returns {boolean} Success
   */
  function request_move_group(group_li,delta) 
  {
    if(delta === 0) { return false; }

    const group_id       = group_li.data('item-id');
    const cur_section_li = group_li.closest('li.section');
    const cur_section_id = cur_section_li.data('item-id');

    // find all peers of group
    //   groups and questions within the section but not questions that are in groups
    const cur_section_items = find_section_items(cur_section_li);
    const cur_index         = cur_section_items.index(group_li);

    // assume for a second that we're moving the group within the current section
    let new_index      = cur_index + delta;
    let new_section_id = cur_section_id;

    if(new_index < 0 || new_index >= cur_section_items.length) {
      // nope, we're attempting to move the group to a different section
      // see if we can move the group to prior (delta<0) or next (delta>0) section
      const all_sections      = _tree.children('li.section');
      const cur_section_index = all_sections.index(cur_section_li);
      const new_section_index = cur_section_index + delta;
      const new_section_li    = all_sections.eq(new_section_index);

      // if the UI is working correctly, the following test should never
      // fail, but let's not take that chance.  Only move to a valid section
      if( new_section_li.length !== 1 ) { return false; }

      new_section_id    = new_section_li.data('item-id');
      const new_section_items = find_section_items(new_section_li);

      if( delta > 0 ) { new_index = 0;                        } // start of next section
      else            { new_index = new_section_items.length; } // end of prior section
    }

    ce.undo_manager.add_and_exec( {
      action:'mb-move-group',
      redo() { controller.move_group(group_id,new_section_id,new_index) },
      undo() { controller.move_group(group_id,cur_section_id,cur_index) },
    } );

    return true;
  }


  /**
   * Handles request_move where the current selection is a question <li>
   *   Checks to see if the question is currently grouped or not and dispatches
   *   the request to the appropriate support function.
   * 
   * @param {HTMLLIElement} question_li 
   * @param {number} delta 
   * @returns {boolean} Success
   */
  function request_move_question(question_li,delta) 
  {
    const group_li = question_li.closest('li.group');
    if(group_li.length) { return request_move_grouped_question(question_li,group_li,delta);   }
    else                { return request_move_ungrouped_question(question_li,delta); }
  }

  /**
   * Handles request_move where the current selection is a grouped question <li>
   *   First checks to see if the question can be relocated within its current group
   *   If so, it moves the question to the new index in the group
   *   If not, move it to before/after the group within the section
   * 
   * If a move is made, it is done by registering the move with the undo manager
   *   and immediately calling the redo method and returning true
   * 
   * @param {HTMLLIElement} question_li 
   * @param {HTMLLIElement} group_li 
   * @param {number} delta 
   * @returns {boolean}
   */
  function request_move_grouped_question(question_li,group_li,delta)
  {
    if(delta===0) { return false; }

    const question_id = question_li.data('item-id');
    const group_id    = group_li.data('item-id');
    const group_items = group_li.find('li.question'); 
    const cur_index   = group_items.index(question_li);
    let   new_index   = cur_index + delta;
    let   new_type    = 'group';
    let   new_id      = group_id;
    if(new_index < 0 || new_index >= group_items.length) {
      // move the qustion to just before the group within the section
      const section_li    = group_li.closest('li.section');
      const section_items = find_section_items(section_li);
      const group_index   = section_items.index(group_li);

      new_index = (delta<0 ? group_index : group_index+1 );
      new_type  = 'section';
      new_id    = section_li.data('item-id');
    }

    ce.undo_manager.add_and_exec({
      action: 'mb-move-question',
      redo() { controller.move_question(question_id, new_type, new_id,   new_index); },
      undo() { controller.move_question(question_id, 'group',  group_id, cur_index); },
    });

    return true;
  }

  /**
   * Handles request_move where the current selection is an ungrouped question <li>
   *   First checks to see if the question can be relocated within its current section
   *   If so, it moves the question to the new index in the section
   *     BUT.. if the item currently at the new index is a group
   *       move the question to the first/last position of that group
   *   If not, can it be moved to a prior(delta<0) or subsequent(delta>0) section
   *   If so, it moves the group to end/start of prior/subsequent section
   *   If not, returns false
   * 
   * If a move is made, it is done by registering the move with the undo manager
   *   and immediately calling the redo method and returning true
   * 
   * @param {HTMLLIElement} question_li 
   * @param {number} delta 
   * @returns {boolean}
   */
  function request_move_ungrouped_question(question_li,delta)
  {
    if(delta === 0) { return false; }

    const question_id    = question_li.data('item-id');
    const cur_section_li = question_li.closest('li.section');
    const cur_section_id = cur_section_li.data('item-id');

    // find all peers of item
    //   groups and questions within the section but not questions that are in groups
    const cur_section_items = find_section_items(cur_section_li);
    const cur_index         = cur_section_items.index(question_li);

    // assume for a second that we're moving the question within the current section
    let new_index = cur_index + delta;
    let new_type  = 'section'
    let new_id    = cur_section_id;

    if(new_index < 0 || new_index >= cur_section_items.length) 
    {
      // nope, we're attempting to move the group to a different section
      // see if we can move the group to prior (delta<0) or next (delta>0) section
      const all_sections      = _tree.children('li.section');
      const cur_section_index = all_sections.index(cur_section_li);
      const new_section_index = cur_section_index + delta;
      const new_section_li    = all_sections.eq(new_section_index);

      // if the UI is working correctly, the following test should never
      // fail, but let's not take that chance.  Only move to a valid section
      if( new_section_li.length !== 1 ) { return false; }

      new_id = new_section_li.data('item-id');
      if( delta > 0 ) { // moving down, so move to first position of next section
        new_index = 0;
      } else {          // moving up, so move to tail position of prior section
        new_index = find_section_items(new_section_li).length;
      }
    }
    else if(cur_section_items.eq(new_index).data('type') === 'group')
    {
      // we're moving the question into a group
      new_type = 'group';
      const group_li = cur_section_items.eq(new_index);
      new_id = group_li.data('item-id');
      if (delta > 0) { // moving down, so move to first postion of the group
        new_index = 0;
      } else {          // moving up, so move to tail position of the group
        new_index = group_li.find('li.question').length;
      }
    }

    ce.undo_manager.add_and_exec({
      action: 'mb-move-question',
      redo() { controller.move_question(question_id, new_type,  new_id,         new_index); },
      undo() { controller.move_question(question_id, 'section', cur_section_id, cur_index); },
    })

  }

  // Add section/question buttons

  _add_section_above.on('click', () => request_new_section(-1) );
  _add_section_below.on('click', () => request_new_section( 1) );

  function request_new_section(delta) {
    const curSelection = _tree.find('li.section.selected'); 
    if(curSelection.length === 1) {
      controller.add_new_section({
        offset:delta,
        section_id:curSelection.data('item-id'),
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
      const ref_id = curSelection.data('item-id');
      const ref_type = curSelection.data('type');
      controller.add_new_question({offset:delta, ref_type, ref_id});
    }
  }

  _add_question_clone.on('click', function() {
    const curSelection = _tree.find('li.question.selected');
    if(curSelection.length === 1) {
      controller.clone_question({
        parent_id: curSelection.data('item-id'),
      });
    }
  });

  _add_group.on('click', function() {
    const curSelection   = _tree.find('li.selected');
    const selection_type = curSelection.data('type');
    const selection_id   = curSelection.data('item-id');
    controller.add_new_group(selection_type,selection_id);
  });

  _remove_group.on('click', function() {
    const curSelection = _tree.find('li.selected');
    const selection_type = curSelection.data('type');
    if(selection_type === 'group') {
      controller.remove_group(curSelection.data('item-id'));
    } else {
      const group = curSelection.closest('li.group');
      if(group) {
        controller.remove_group(group.data('item-id'));
      }
    }
  });

  //
  // Selection change handlers
  //

  /**
   * Update the menubar buttons based on the current selection
   * @returns {void}
   */
  function update_selection()
  {
    const selected_item = _tree.find('li.selected');

    _selection_buttons.forEach((b) => b.attr('disabled', true));

    // if tree is empty, enable 'add section'
    if (_tree.children('li').length === 0) {
      _add_section_below.attr('disabled', false);
    }

    const selected_type = selected_item.data('type') ?? 'none';

    switch(selected_type) {
    case 'section':
      _add_section_above.attr('disabled', false);
      _add_section_below.attr('disabled', false);
      _add_question_below.attr('disabled', false);
      _add_group.attr('disabled', false);
      _delete.attr('disabled', false);
      break;
    case 'group':
      _add_question_above.attr('disabled', false);
      _add_question_below.attr('disabled', false);
      _remove_group.attr('disabled',false)
      _delete.attr('disabled', false);
      break;
    case 'question':
      _add_question_above.attr('disabled', false);
      _add_question_below.attr('disabled', false);
      _add_question_clone.attr('disabled', false);
      if(!selected_item.closest('li.group').length) { 
        _add_group.attr('disabled', false);
      } else { 
        _remove_group.attr('disabled', false); 
      }
      _delete.attr('disabled', false);
      break;
    }

    update_up_down_buttons();
  }

  $(document).on('SurveyWasReordered',update_up_down_buttons);

  /**
   * Update the menubar up/down buttons based on the current selection
   * @returns {void}
   */
  function update_up_down_buttons()
  {
    const selected_item = _tree.find('li.selected');

    if(selected_item.length !== 1) { 
      _up.attr('disabled', true);
      _down.attr('disabled', true);
      return;
    }

    let show_up   = false;
    let show_down = false;

    const item_type = selected_item.data('type');

    if(item_type === 'section') {
      const sections = _tree.find('li.section');
      // can move up unless this is the first section
      // can move down unless this is the last section
      const all_sections = _tree.find('li.section');
      show_up   = !selected_item.is(all_sections.first());
      show_down = !selected_item.is(all_sections.last());
    } 
    else if(item_type === 'group')
    {
      // can move up if index of the selected group is greater than 1
      //   0 = first section
      //   1 = first item of first section (if first section is empty)
      //   1 = second session if first section is empty
      show_up = _tree.find('li').index(selected_item) > 1;

      // can move down if group is not last item in list of:
      //   all sections
      //   all groups
      //   all ungrouped questions
      const test_list = _tree.find('li.section, li.group, li.question').not('li.group li.question');
      show_down = !selected_item.is(test_list.last());
    }
    else if(item_type === 'question')
    {
      // can move up if index of the selected group is greater than 1
      //   0 = first section
      //   1 = first item of first section (if first section is empty)
      //   1 = second session if first section is empty
      show_up = _tree.find('li').index(selected_item) > 1;

      // can move down if question is grouped 
      //   OR not last item in list of:
      //     all sections
      //     all groups
      //     all ungrouped questions
      if(selected_item.closest('li.group').length) {
        show_down = true;
      } else {
        const test_list = _tree.find('li.section, li.group, li.question').not('li.group li.question');
        show_down = !selected_item.is(test_list.last());
      }
    }
    _up.attr('disabled',!show_up);
    _down.attr('disabled',!show_down);
  }

  return {
    show(v=true) { if(v) { _mbar.show() } else { _mbar.hide() } },
    hide()       { _mbar.hide() },
    update_selection,
  };
}

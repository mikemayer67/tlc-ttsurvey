import Sortable from '../../../js/sortable.esm.js';
import arborist from './arborist.js';

/**
 * Controller used to manage the content of the navigation tree.
 * 
 * @typedef {Object} NavTreeController
 * @property { () => void } reset
 * @property { (content:object) => void } update
 * @property { (section_id:number, key:string, value:number|string)  => void } update_section
 * @property { (group_id:number, key:string, value:number|string)  => void } update_group
 * @property { (question_id:number, new_type:string, old_type:string) => void } update_question_type
 * @property { (question_id:number, key:string, value:number|string)  => void } update_question
 * @property { () => void } disable
 * @property { () => void } enable
 * @property { (sectionId:number, toIndex:number) => boolean } move_section
 * @property { (
 *   itemType:"group"|"question",
 *   itemId:number,
 *   toType:"section"|"group",
 *   toId:number,
 *   toIndex:number
 *   ) => boolean 
 * } move_to_container
 * @property { (section_id:number) => void } select_section
 * @property { (group_id:number) => void } select_group
 * @property { (question_id:number) => void } select_question
 * @property { ( 
 *   section_id:number, 
 *   section_name:string, 
 *   where:{section_id:number, offset:number}
 *   ) => [jQuery<HTMLLIElement>,jQuery<HTMLULElement>] 
 * } add_section
 * @property { (question_id:number, question:Object) => jQuery<HTMLLIElement> } add_question
 * @property { (section_id) => void } remove_section
 * @property { (group_id) => void } remove_group
 * @property { (question_id) => void } remove_question
 * @property { () => {null | {item_type:"section"|"group"|"question", item_id:number}} } current_selection
 * @property { (null | {item_type:"section"|"group"|"question", item_id:number}) => void } restore_selection
 * @property { (item_type:"section"|"group"|"question", item_id:number, has_error:boolean) => void } toggle_error
 * @property { () => boolean } can_submit
 * @property { () => Set<number> } bullpen
 * @property { (old_id:number, new_id:number, old_data:object, new_data:object) => void} replace_question
 * @property { () => Object } survey_structure TODO: flesh out return type
 */

/**
 * @typedef {Object} WhereHints
 * @property { number } [ref_id] ID of the item relative to which position a new item
 * @property { "question" | "group" } [ref_type] type of the reference item
 * @property { number } [section_id] ID of the section into which insert the new item
 * @property { number } [group_id] ID of the group into which insert the new item
 * @property { 1 | -1 } [offset] Where to put the item relative to the ref item 
 * @property { boolean } [at_end] Insert item at the end of the section/group
 */

/**
 * Initializes a NavTreeController object
 * @param {object} ce Container of shared "global" variables
 * @param {SurveyViewController} controller TODO add JSDoc to controller
 * @returns {NavTreeController}
 */
export default function init(ce,controller)
{
  const _box      = $('#survey-tree');
  const _info     = $('#survey-tree .info');
  const _tree     = $('#survey-tree ul.sections');

  const _bullpen  = new Set();  // to hold archived questions

  const _arborist = arborist(_box);

  // Start the returned editor_tree object.
  //    We'll add more properties/methods below
  const self = {
  };

  let _keyboardNav = false;

  // sorter for ul.sections within a tree
  const _tree_sorter = new Sortable( _tree[0], {
    group: {
      name:'tree',
      pull: false,
      put: false,
    },
    animation: 150,
    disabled: false,
    onEnd: handle_drop_in_tree,
  });

  const _section_sorters = new Map(); // sorters for group and question elements within a section
  const _group_sorters   = new Map(); // sorters for question elements within a group

  /**
   * Clears out the tree in preparation for rebuilding it
   * - tree sorter is disabled
   * - all section and group sorters are released
   * - the "drag-n-drop" info box is hidden
   * - all DOM element in the tree are released
   * - the question bullpen is emptied
   * 
   * @returns {void}
   */
  self.reset = function()
  {
    _tree_sorter.option('disabled',true);
    _section_sorters.forEach((sorter) => sorter.destroy());
    _section_sorters.clear();
    _group_sorters.forEach((sorter) => sorter.destroy());
    _group_sorters.clear();
    _tree.empty();
    _info.hide();
    _bullpen.clear();
  }

  /**
   * Repopulates the tree based on new survey content
   * - the current tree content is cleared out (via reset)
   * - content sorters are attached to each ul.section and ul.group
   * - the question bullpen is repopluated
   * 
   * @param {Object} content All the survey content and structure
   * @returns {void}
   */
  self.update = function(content)
  {
    self.reset();

    if(!content)           { return; }
    if(!content.sections)  { return; }
    if(!content.groups)    { return; }
    if(!content.questions) { return; }

    _bullpen.clear();
    for( const question_id of Object.keys(content.questions) ) {
      _bullpen.add(Number(question_id));
    }

    Object.entries(content.sections)
    .sort( ([,a],[,b]) => a.section_id - b.section_id )
    .forEach( ([,section]) => {
      add_section_to_tree(section, content);
    });

    _arborist.handle_resize();
  }

  /**
   * Inserts the DOM elements for the specified survey section in the tree <ul>.
   * This includes all the questions and question groups that belong to that section.
   * 
   * Note that this function does not create the DOM elements, it calls
   *   other functions to create them and then it places them into the DOM.
   * 
   * @param {number} section ID of the section to be added to the tree
   * @param {object} content All the survey content and structure
   * @returns {none}
   */
  function add_section_to_tree(section, content)
  {
    const section_id = section.section_id;
    const [section_li,section_ul] = create_section_li(section_id, section.name);
    section_li.appendTo(_tree);

    const section_content = section.content ?? [];
    for(const item of section_content) {
      if(item.type === 'question') {
        const question = content.questions[item.id] ?? null;
        if(question) {
          const question_li = create_question_li(question.id,question);
          question_li.appendTo(section_ul);
          _bullpen.delete(question.id);
        }
      }
      else if(item.type === 'group') 
      {
        const group = content.groups[item.id] ?? null;
        if(group) {
          const [group_li, group_ul] = create_group_li(group.group_id,group.name);

          group_li.appendTo(section_ul);
          const group_content = group.content ?? [];
          for (const question_id of group_content) {
            const question = content.questions[question_id] ?? null;
            if (question) {
              const question_li = create_question_li(question.id, question);
              question_li.appendTo(group_ul);
              _bullpen.delete(question.id);
            }
          }
        }
      }
    }
  }

  /**
   * Creates an <li> element to for the specified section with the following content:
   *   - <span> content to be displayed in the navigation tree
   *     - <button> show/hide arrow controller
   *     - text is the name of the section
   *   - <ul> list to contain all associated questions and groups
   * 
   * Attaches callbacks to:
   *   - show/hide button: open/close the content items
   *   - name: make the section the active tree element for navigation/editing 
   * 
   * @param {number} section_id ID of the section to be added to the tree
   * @param {string} name Section name to display in the tree
   * @returns {[HTMLLIElement, HTMLULElement]} 
   */
  function create_section_li(section_id,name)
  {
    const btn  = $('<button>').addClass('toggle');
    const span = $('<span>').addClass('name');
    const div  = $('<div>').append(btn,span);

    const li = $('<li>')
      .addClass('section closed')
      .attr('data-item-id',section_id)
      .data('type','section')
      .html(div);

    _arborist.initialize(li,name);

    btn.on('click', function(e) {
      e.stopPropagation();
      const li = $(this).closest('li.section');
      li.toggleClass('closed');
    });

    span.on('click',function(e) {
      e.stopPropagation();
      set_selection($(this).closest('li.section'));
      start_keyboard_navigation(e);
    });

    const ul = $('<ul>').addClass('section-content').appendTo(li);

    _section_sorters.set(
      section_id, 
      new Sortable( ul[0], {
        group: {
          name: 'content',
          pull: true,
          put: true,
        },
        animation: 150,
        disabled: true,
        onEnd: handle_drop_in_container,
      })
    );

    return [li,ul];
  }

  /**
   * Updates a property associated with a section entry in the tree.
   * 
   * The only property that has an affect on the tree is the section name.
   * They key/value design exists so that the caller doesn't need to know
   *   which keys drive a change in the tree.
   * 
   * @param {number} section_id ID of the section to be updated
   * @param {string} key Property to be updated
   * @param {number|string} value New value for the property
   * @returns {void}
   */
  self.update_section = function(section_id,key,value)
  {
    if(key === 'name') {
      const leaf = _tree.find('.section[data-item-id='+section_id+']');
      _arborist.update_label(leaf, value);
    }
  }

  /**
   * Creates an <li> element for the specifed group with the following content
   *   - <span> content to be displayed in the navigation tree
   *     - <button> show/hide arrow controller
   *     - text is the name of the group
   *   - <ul> list to contain all associated questions
   * 
   * Attaches callbacks to:
   *   - show/hide button: open/close the content items
   *   - name: make the group the active tree element for navigation/editing 
   * 
   * @param {number} group_id ID of the group to be added to the tree
   * @param {string} name Group name to display in the tree
   * @returns {[HTMLLIElement, HTMLULElement]} 
   */
  function create_group_li(group_id,name)
  {
    const btn  = $('<button>').addClass('toggle');
    const span = $('<span>').addClass('name');
    const div  = $('<div>').append(btn,span);

    const li = $('<li>')
      .addClass('group')
      .attr('data-item-id',group_id)
      .data('type','group')
      .html(div);

    _arborist.initialize(li,name);

    btn.on('click', function(e) {
      e.stopPropagation();
      const li = $(this).closest('li.group');
      li.toggleClass('closed');
    });

    span.on('click',function(e) {
      e.stopPropagation();
      set_selection($(this).closest('li.group'));
      start_keyboard_navigation(e);
    });

    const ul = $('<ul>').addClass('group-content').attr('data-item-id',group_id).appendTo(li);

    _group_sorters.set(
      group_id,
      new Sortable( ul[0], {
        group: {
          name: 'content',
          pull: true,
          put(to,from,dragEl,evt) { return !$(dragEl).hasClass('group') },
        },
        animation: 150,
        disabled: true,
        onEnd: handle_drop_in_container,
      })
    );

    return [li,ul];
  }

  /**
   * Updates a property associated with a group entry in the tree.
   * 
   * The only property that has an affect on the tree is the group name.
   * They key/value design exists so that the caller doesn't need to know
   *   which keys drive a change in the tree.
   * 
   * @param {number} group_id ID of the group to be updated
   * @param {string} key Property to be updated
   * @param {number|string} value New value for the property
   * @returns {void}
   */
  self.update_group = function(group_id,key,value)
  {
    if(key === 'name') {
      const leaf = _tree.find('.group[data-item-id='+group_id+']');
      _arborist.update_label(leaf, value);
    }
  }


  /**
   * Creates an <li> element for the specified question
   *   It has no child content other than name text
   *   It does have a class added to indicate the type of question.  
   *      The type class is used to stylize how it appears in the navigation tree
   * 
   * Attaches a single callbacks to:
   *   - name: make the section the active tree element for navigation/editing 
   * 
   * @param {number} question_id ID of the question to be added to the tree
   * @param {Object} details All of the question specific information
   * @returns {[HTMLLIElement, HTMLULElement]} 
   */
  function create_question_li(question_id,details)
  {
    const leaf = $('<li>')
      .addClass('question')
      .attr('data-item-id',question_id)
      .data('type','question');

    let wording = details.wording;
    let type    = details.type || '';

    if( type.toLowerCase() === 'info') {
      if( details.infotag ) {
        wording = details.infotag;
        leaf.data('using-infotag',true);
      } else {
        wording = details.info;
        leaf.data('using-infotag',false);
      }
    }

    _arborist.initialize(leaf, wording, details.type || '');
    
    leaf.on('click',function(e) { 
      e.stopPropagation();
      set_selection($(this)); 
      start_keyboard_navigation(e);
    } );

    return leaf;
  }

  /**
   * Updates the type of the specified question

   * @param {number} question_id ID of the question to be updated
   * @param {string} new_type Must be a recognized question type
   * @param {string} old_type Must be a recognized question type
   * @returns {void}
   */
  self.update_question_type = function(question_id,new_type,old_type)
  {
    const leaf = _tree.find('.question[data-item-id='+question_id+']');
    _arborist.update_type(leaf,new_type,old_type);
  }

  /**
   * Updates a property associated with a question entry in the tree.
   * 
   * The following property keys affect the content of the navigation tree:
   *   wording: Sets name of question shown in the tree
   *   infotag: Sets the name of question if value is truthy
   *   info:    Sets the name of question  
   * 
   * They key/value design exists so that the caller doesn't need to know
   *   which keys drive a change in the tree.
   * 
   * @param {number} section_id ID of the section to be updated
   * @param {string} key Property to be updated
   * @param {number|string} value New value for the property
   * @returns {void}
   */
  self.update_question = function(question_id,key,value)
  {
    const leaf  = _tree.find('.question[data-item-id='+question_id+']');

    if(key === 'wording') {
      _arborist.update_label(leaf,value);
      return;
    }

    // wording is always the question label except for info questions
    //   so if this isn't an info question, return now
    if(!leaf.hasClass('info')) { return; }

    if(key === 'infotag') {
      if(value) {
        // infotag is being provided. use that to label the question
        _arborist.update_label(leaf,value);
        leaf.data('using-infotag',true);
      } 
      else {
        // infotag was cleared, use info itself to label the question
        // ... but we'll need to get that from the controller
        const info = controller.cur_question_data(question_id,'info');
        _arborist.update_label(leaf,info);
        leaf.data('using-infotag',false);
      }
      return;
    }

    // something other than infotag changed.
    //   if we're currently labeling the question with infotag, no need to continue.

    if( leaf.data('using-infotag') ) { return; }

    if(key === 'info') {
      _arborist.update_label(leaf,value || '');
    }
  }

  /**
   * Disables sorting and drag/drop of the navigation tree
   * @returns {void}
   */
  self.disable = function()
  {
    _info.hide();
    _tree_sorter.option('disabled',true);
    _section_sorters.forEach((sorter)=>sorter.option('disabled',true));
    _group_sorters.forEach((sorter)=>sorter.option('disabled',true));
  }

  /**
   * Enables sorting and drag/drop of the navigation tree
   * @returns {void}
   */
  self.enable = function()
  {
    _info.show();
    _tree_sorter.option('disabled',false);
    _section_sorters.forEach((sorter)=>sorter.option('disabled',false));
    _group_sorters.forEach((sorter)=>sorter.option('disabled',false));
  }

  //
  // User driven reordering of the editor tree
  //
  
  /**
   * Moves a section <li> to a new location in the tree <ul>
   *
   * @param {number} sectionId Unique identifier for the section being moved
   * @param {number} toIndex New position in the tree
   * @returns {boolean} true on success, false on failure
   * @fires SurveyWasReordered on success
   */
  self.move_section = function(sectionId,toIndex) 
  {
    const all_sections = _tree.children('li.section');
    if( toIndex >= all_sections.length) { return false; }

    const move_li = all_sections.filter('[data-item-id='+sectionId+']');
    if( move_li.length !== 1 ) { return false; }

    const tgt_li    = all_sections.eq(toIndex);
    const fromIndex = all_sections.index(move_li);

    if(toIndex < fromIndex) { move_li.insertBefore(tgt_li); }
    if(toIndex > fromIndex) { move_li.insertAfter(tgt_li); }

    set_selection(move_li);
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  /**
   * move_to_container handles requests to move an item (group or question)
   *   into a container (section or group).  The item will be a <li> element.
   *   The container will be a <ul> element.
   * This function does not care where the request came from:
   *   e.g. drag-n-drop undo/redo or move item buttons
   * This function simply updates the DOM per the request
   * @param {"group"|"question"} itemType Type of item being moved
   * @param {number} itemId ID of the item being moved
   * @param {"section"|"group"} toType Type of container into which item is being moved
   * @param {number} toId ID of the destination container
   * @param {number} toIndex Ordinal position with in the destination container 
   * @returns {boolean} true on success, false on failure
   * @fires SurveyWasReordered on success
   */
  self.move_to_container = function(itemType,itemId,toType,toId,toIndex)
  {
    // Find the <li> element of the item to be moved.
    const move_li = _tree.find('li.'+itemType+'[data-item-id='+itemId+']');
    if(move_li.length != 1) {
      // length should only ever be 1... but just in case it's not
      //   If it's 0, then something broke in the view controller
      //   If it's >1, then something broke in the underlying app logic
      return false;
    }

    // Find: the <li> for the current container of the item to be moved
    //       its type
    //       its ID
    const from_li = move_li.closest('ul').closest('li');
    const fromType = from_li.hasClass('section') ? 'section' : 'group'; 
    const fromId = from_li.data('item-id');

    // Find: the <li> for the destination container
    //       the <ul> that holds its content
    //       the content of that <ul>
    const to_li = _tree.find('li.'+toType+'[data-item-id='+toId+']');
    const to_ul = to_li.children('ul');
    const content = to_ul.children('li');

    // Find the <li> currently in the target location
    //   It is possible there is nothing currently there
    const tgt_li = content.eq(toIndex);

    if(toType === fromType && toId === fromId) {
      // moving item within the same container
      if(tgt_li.length !== 1 ) { 
        // something went wrong... because we are simply shuffling items
        //   within the same container, there must be something currently
        //   at the destination position for the item being moved
        return false; 
      } 
      // find current position of the item to be moved
      const fromIndex = content.index(move_li);
      // case 1 (toIndex < fromIndex)
      //   5 -> 2:  0 1 5 2 3 4 6 7 8
      //   5 -> 0:  5 0 1 2 3 4 6 7 8
      if(toIndex < fromIndex) { move_li.insertBefore(tgt_li); }
      // case 2 (toIndex > fromIndex)
      //   2 -> 5:  0 1 3 4 5 2 6 7 8
      //   2 -> 8:  0 1 3 4 5 6 7 8 2
      if(toIndex > fromIndex) { move_li.insertAfter(tgt_li); }
    }
    else if(tgt_li.length === 0) 
    { 
      // there is currently nothing at the destination index
      // only allowed if adding to end of the container
      if( toIndex > content.length ) { return false; } 
      move_li.appendTo(to_ul);
    }
    else {
      // insert before element currenty at destination index
      move_li.insertBefore(tgt_li);
    }

    set_selection(move_li);
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  /**
   * Event handler for dropping a section item into the tree sorter 
   * 
   * @param {Sortable.SortableEvent} evt Custom event generated by SortableJS
   * @returns {boolean} true on success, false on failure
   * @fires SurveyWasReordered on success
   */
  function handle_drop_in_tree(evt)
  {
    if(evt.oldIndex === evt.newIndex) { return false; }

    const sectionId = $(evt.item).data('item-id');
    ce.undo_manager.add( {
      action:'drop-in-tree',
      undo() { self.move_section(sectionId,evt.oldIndex); },
      redo() { self.move_section(sectionId,evt.newIndex); },
    });

    set_selection($(evt.item));
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  /**
   * Event handler for dropping a question or group item into a section or group sorter 
   * 
   * @param {Sortable.SortableEvent} evt Custom event generated by SortableJS
   * @returns {boolean} true on success, false on failure
   * @fires SurveyWasReordered on success
   */
  function handle_drop_in_container(evt)
  {
    if(evt.from === evt.to && evt.oldIndex === evt.newIndex) { return false; }

    const item_type = $(evt.item).data('type');
    const item_id   = $(evt.item).data('item-id');

    const to_li     = $(evt.to).closest('li');
    const to_type   = to_li.data('type');
    const to_id     = to_li.data('item-id');
    
    const from_li   = $(evt.from).closest('li');
    const from_type = from_li.data('type');
    const from_id   = from_li.data('item-id');

    ce.undo_manager.add({
      action: 'drop-in-section',
      undo() { self.move_to_container(item_type, item_id, from_type, from_id, evt.oldIndex); },
      redo() { self.move_to_container(item_type, item_id, to_type,   to_id,   evt.newIndex); },
    });

    set_selection($(evt.item));
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  //
  // User section/question selection handlers
  //
  
  /**
   * Makes the specified section the selected item
   * @param {number} section_id Section to make the active selection
   * @returns {void}
   */
  self.select_section = function(section_id)
  {
    const e = _tree.find('.section[data-item-id='+section_id+']');
    _tree.find('.selected').removeClass('selected');
    e.addClass('selected');
  }

  /**
   * Makes the specified group the selected item
   * @param {number} group_id Group to make the active selection
   * @returns {void}
   */
  self.select_group = function(group_id)
  {
    const e = _tree.find('.group[data-item-id='+group_id+']');
    _tree.find('.selected').removeClass('selected');
    e.addClass('selected');
  }

  /**
   * Makes the specified question the selected item
   * @param {number} question_id Question to make the active selection
   * @returns {void}
   */
  self.select_question = function(question_id)
  {
    const e = _tree.find('.question[data-item-id='+question_id+']');
    _tree.find('.selected').removeClass('selected');
    e.addClass('selected');
  }

  /**
   * Deselects the current selection
   * @returns {void}
   */
  function clear_selection()
  {
    _tree.find('.selected').removeClass('selected');
    controller.clear_selection();
  }

  /**
   * Makes the specified section, group, or question <li> element the 
   *    selected navigation tree element
   * @param {jQuery<HTMLLIElement>} $li 
   * @returns {void}
   */
  function set_selection($li)
  {
    if(!$li) {
      clear_selection;
      return;
    }
    _tree.find('.selected').removeClass('selected');
    $li.addClass('selected');
    if($li.hasClass('section')) {
      controller.select_section($li.data('item-id'));
    } else if($li.hasClass('group')) {
      controller.select_group($li.data('item-id'));
    } else {
      controller.select_question($li.data('item-id'));
    }

    $li[0].scrollIntoView({block:'nearest', behavior:'smooth'});
  }

  _box.on('click', function(e) {
    const clicked_li = $(e.target).closest('li');
    if(clicked_li.length === 0) {
      clear_selection();
    }
  });

  //
  // Insertions and Deletions
  //

  // TODO Revise/Add functions for adding groups

  /**
   * Adds a new section <li> element to the navigation tree DOM
   * @param {number} section_id ID of section to add
   * @param {string} section_name Name of section to add
   * @param {WhereHints} where 
   * @returns {[jQuery<HTMLLIElement>,jQuery<HTMLULElement>]}
   */
  self.add_section = function(section_id, section_name, where)
  {
    const [new_li,new_ul] = create_section_li(section_id,section_name);
    if(where.section_id) {
      const existing_li = _tree.find('li.section[data-item-id='+where.section_id+']');
      if(where.offset < 0) { new_li.insertBefore(existing_li); }
      else                 { new_li.insertAfter(existing_li); }
    } else {
      new_li.prependTo(_tree);
    }

    // if we got here, editing must be enabled, turn on sorting
    _section_sorters.get(section_id).option('disabled',false);

    set_selection(new_li);
    $(document).trigger('SurveyWasModified');

    return [new_li,new_ul];
  }

  /**
   * Adds a new group <li> element to the navigation tree DOM
   * @param {number} group_id ID of group to add
   * @param {string} group_name Name of group to add
   * @param {WhereHints} where 
   * @returns {[jQuery<HTMLLIElement>,jQuery<HTMLULElement>]}
   */
  self.add_group = function(group_id, group_name, where)
  {
    const [new_li,new_ul] = create_group_li(group_id,group_name);

    if(where.ref_id) {
      const ref_li = _tree.find('li.'+where.ref_type+'[data-item-id='+where.ref_id+']');
      if(where.offset < 0) { new_li.insertBefore(ref_li); }
      else                 { new_li.insertAfter(ref_li); }
    }
    else if(where.section_id) {
      const section_li = _tree.find('li.section[data-item-id='+where.section_id+']');
      const content_ul  = section_li.children('ul.section-content');
      if(where.at_end) { new_li.appendTo(content_ul); }
      else             { new_li.prependTo(content_ul); }
    }

    // if we got here, editing must be enabled, turn on sorting
    _group_sorters.get(group_id).option('disabled',false);

    set_selection(new_li);
    $(document).trigger('SurveyWasModified');

    return [new_li,new_ul];
  }

  /**
   * TODO: update to include groups
   * TODO: update the following param list
   * TODO: add add_question to the API prologue
   * Adds a new question <li> element to the navigation tree DOM
   * @param {number} question_id ID of question to add
   * @param {object} question Details about question to add
   * @param {WhereHints} where 
   * @returns {[jQuery<HTMLLIElement>,jQuery<HTMLULElement>]}
   */
  self.add_question = function(question_id, question, where)
  {
    const new_li = create_question_li(question_id,question);
    if(where.ref_id) {
      const ref_li = _tree.find('li.'+where.ref_type+'[data-item-id='+where.ref_id+']');
      if(where.offset < 0) { 
        new_li.insertBefore(ref_li); 
      } else if(where.ref_type === 'question') { 
        new_li.insertAfter(ref_li); 
      } else {
        const content_ul = ref_li.children('ul.group-content');
        new_li.prependTo(content_ul);
      }
    }
    else if(where.section_id) {
      const section_li = _tree.find('li.section[data-item-id='+where.section_id+']');
      const content_ul = section_li.children('ul.section-content');
      if(where.at_end) { new_li.appendTo(content_ul);  }
      else             { new_li.prependTo(content_ul); }
    } 
    else if(where.group_id) {
      const group_li = _tree.find('li.group[data-item-id='+where.group_id+']');
      const content_ul = group_li.children('ul.group-content');
      if(where.at_end) { new_li.appendTo(content_ul);  }
      else             { new_li.prependTo(content_ul); }
    }
    set_selection(new_li);
    $(document).trigger('SurveyWasModified');

    return new_li;
  }

  // TODO add function to remove groups

  /**
   * Removes a section <li> from the DOM
   * 
   * @param {number} section_id ID of section to remove
   * @fires SurveyWasModified
   */
  self.remove_section = function(section_id)
  {
    const section_li = _tree.find('li.section[data-item-id='+section_id+']');

    const question_lis = section_li.find('li.question');
    const question_ids = question_lis.map(
      function() { return $(this).data('item-id'); }
    );
    for(const question_id of question_ids) {
      self.remove_question(question_id);
    }

    const group_lis = section_li.find('li.group');
    const group_ids = group_lis.map(
      function() { return $(this).data('item-id'); }
    );
    for(const group_id of group_ids) {
      self.remove_group(group_id);
    }

    _section_sorters.get(section_id)?.destroy();
    _section_sorters.delete(section_id);

    section_li.remove();
    clear_selection();
    $(document).trigger('SurveyWasModified');
  }

  /**
   * Removes a group <li> from the DOM
   * 
   * @param {number} group_id ID of group to remove
   * @fires SurveyWasModified
   */
  self.remove_group = function(group_id)
  {
    const group_li = _tree.find('li.group[data-item-id='+group_id+']');

    const questions = group_li.find('li.question');
    questions.each( function () {
      const question_id = $(this).data('item-id');
      self.remove_question(question_id);
    });
    _group_sorters.get(group_id)?.destroy();
    _group_sorters.delete(group_id);

    clear_selection();
    group_li.remove();
    $(document).trigger('SurveyWasModified');
  }

  /**
   * Removes a question <li> from the DOM and returns question ID to the bullpen
   * 
   * @param {number} question_id ID of question to remove
   * @fires SurveyWasModified
   */
  self.remove_question = function(question_id)
  {
    _tree.find('li.question[data-item-id='+question_id+']').remove();
    clear_selection();
    _bullpen.add(Number(question_id));
    $(document).trigger('SurveyWasModified');
  }

  /**
   * Returns the type and ID of the current selection
   * @returns {null | {item_type:"section"|"group"|"question", item_id:number}} 
   */
  self.current_selection = function()
  {
    const curSelection = _tree.find('li.selected');
    if( curSelection ) {
      return {
        item_type: curSelection.data('type'),
        item_id: curSelection.data('item-id')
      };
    } else {
      return null;
    }
  }

  /**
   * Sets the current selection based on item type and ID
   * @param {null | {item_type:"section"|"group"|"question", item_id:number}} selection
   * @returns {void}
   */
  self.restore_selection = function(selection)
  {
    if(selection) {
      const item_type = selection.item_type;
      const item_id   = selection.item_id;
      set_selection(_tree.find('li.'+item_type+'[data-item-id='+item_id+']'));
    }
  }

  //
  // Keyboard up|down arrow navigation
  //

  /**
   * Enable keyboard navigation
   * @returns {void}
   */
  function start_keyboard_navigation()
  {
    if(!_keyboardNav) { 
      _keyboardNav = true;
      $(document).on('keydown', handle_keyboard_navigation);
    }
  }

  /**
   * Disable keyboard navigation
   * @returns {void}
   */
  function stop_keyboard_navigation()
  {
    if(_keyboardNav) {
      _keyboardNav = false;
      $(document).off('keydown', handle_keyboard_navigation);
    }
  }

  $(document).on('click', stop_keyboard_navigation);

  /**
   * Keypress handler for keyboard navigation
   * @param {KeyboardEvent} e 
   * @returns 
   */
  function handle_keyboard_navigation(e)
  {
    const cur_selection = _tree.find('.selected');
    if(cur_selection.length !== 1 ) { return;}
    var delta = 0;
    switch(e.keyCode) {
      case 38: delta = -1; break;
      case 40: delta =  1; break;
      default: delta =  0; break;
    }
    if( delta === 0 ) { return; }
    e.stopPropagation();
    e.preventDefault();

    const full_tree = _tree.find('li');
    const vis_tree = full_tree.filter( function() {
      if( $(this).hasClass('section') ) { return true; }
      if( $(this).parent().parent().hasClass('closed') ) { return false; }
      return true;
    })

    const cur_index = vis_tree.index(cur_selection);
    const new_index = cur_index + delta;

    if(new_index < 0 || new_index >= vis_tree.length) { return; }
    const new_selection = vis_tree.eq(new_index);
    set_selection(new_selection);
  }

  //
  // Error Markup
  //

  let observerMicrotaskQueued = false;
  const _observer = new MutationObserver((mutations) => {
    let dirty = false;
    for(const m of mutations) {
      const tgt = $(m.target);
      if( (m.type === 'childList'  && tgt.is('ul.group-content,ul.section-content')) ||
          (m.type === 'attributes' && tgt.is('li.question, li.group')) 
      ) {
        dirty = true;
        break;
      }
    }
    if(dirty && !observerMicrotaskQueued) {
      observerMicrotaskQueued = true;
      queueMicrotask(() => {
        observerMicrotaskQueued = false;
        _tree.find('li.section,li.group').each((i,e) => {
          const child_selected = $(e).find('.selected');
          $(e).toggleClass('child-selected',child_selected.length > 0);
          const child_error = $(e).find('.error,.needs-value,.needs-type');
          $(e).toggleClass('child-error',child_error.length > 0);
        });
      });
    }
  });
  _observer.observe( $(_tree)[0], {
    attributes: true,
    subtree: true,
    childList: true,
    attributeFilter: ['class'],
  });
  
  /**
   * Toggles the error class on the specified <li> element.
   * Will trigger the observer if this actually changes the state of the error class
   * @param {"section"|"group"|"question"} item_type 
   * @param {number} item_id 
   * @param {boolean} has_error 
   */
  self.toggle_error = function(item_type,item_id,has_error) {
    const item = _tree.find('li.'+item_type+'[data-item-id='+item_id+']');
    item.toggleClass('error',has_error)
  }

  /**
   * Returns whether or not the navigation tree is ok with submitting the survey updates.
   *   If there are any entries in the tree with any sort of error, returns false
   *   Otherwise (everything looks good), returns true
   * @returns {boolean}
   */
  self.can_submit = function() {
    return _tree.find('li.error, li.needs-value, li.needs-type').length === 0;
  }

  // 
  // Question Handling
  //

  /**
   * Returns the array of all question IDs that are not current in the tree
   * @returns 
   */
  self.bullpen = function() { 
    return _bullpen; 
  }

  /**
   * Swaps out one question in the navigation tree for another
   * @param {number} old_id ID of the question to be replaced
   * @param {number} new_id ID of the question replacing it
   * @param {object} old_data Details of the question being replaced
   * @param {object} new_data Details of the question replacing it
   * @returns 
   */
  self.replace_question = function(old_id, new_id, old_data, new_data) {
    const leaf = _tree.find('li.question[data-item-id='+old_id+']');
    if(leaf.length !== 1) { return; }

    leaf.data('question',new_id).attr('data-item-id',new_id);

    if(new_data.type === 'INFO') {
      _arborist.update_label(leaf,new_data.infotag || new_data.info);
    } else {
      _arborist.update_label(leaf,new_data.wording);
    }

    const old_type = old_data.type || '';
    const new_type = new_data.type || '';
    _arborist.update_type(leaf, new_type, old_type);

    _bullpen.delete(Number(new_id));
    if(old_type) { _bullpen.add(Number(old_id)); }
  }

  // 
  // Section/Question structure
  //

  /**
   * Returns the survey structure as defined by the navigation tree
   * @returns {object} TODO: flesh out the return type once groups have been added
   */
  self.survey_structure = function() 
  {
    const section_lis = _tree.find('li.section');
    const rval = section_lis.map( function () {
      const section_li = $(this);
      const section_id = section_li.data('item-id');

      const content_lis = section_li.find('li.group, li.question').not('li.group li.question');
      const section_content = content_lis.map( function() {
        const item_li = $(this);
        const item_id = item_li.data('item-id');
        const item_type = item_li.data('type');

        if( item_type === 'question') {
          return {question_id:item_id};
        } else {
          const question_lis = item_li.find('li.question');
          const group_content = question_lis.map( function() {
            return $(this).data('item-id');
          });
          return {group_id:item_id, content:group_content};
        }
      });
      return {section_id, content:section_content};
    });

    return rval;
  }

  //
  // Resize Handler
  //

  let roTimer = null;

  const ro = new ResizeObserver( function(entries,observer) {
    if(roTimer) { return; }
    roTimer = setTimeout( function() {
      roTimer = null;
      _arborist.handle_resize();
    }, 250 );
  });
  ro.observe(_tree[0]);

  //
  // Return
  //

  return self;
}

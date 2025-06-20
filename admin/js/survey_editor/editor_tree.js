import Sortable from '../../../js/sortable.esm.js';
import arborist from './arborist.js';

export default function editor_tree(ce,controller)
{
  const _box      = $('#survey-tree');
  const _info     = $('#survey-tree .info');
  const _tree     = $('#survey-tree ul.sections');

  const _arborist = arborist(_box);

  // Start the returned editor_tree object.
  //    We'll add more properties/methods below

  const self = {
  };

  let _keyboardNav = false;

  // sorter for ul.sections
  const _section_sorter = new Sortable( _tree[0],
    {
      group: 'sections',
      animation: 150,
      filter: '.question',
      disabled: true,
      onEnd: handle_drop_section,
    }
  );

  // sorters for each of the ul.questions
  let _question_sorters = {};

  // reset clears out the tree
  //   section sorter is disabled
  //   all question sorters are released
  //   the "drag-n-drop" info box is hidden
  self.reset = function()
  {
    _section_sorter.option('disabled',true);
    Object.values(_question_sorters).forEach((s) => s.destroy());
    _question_sorters = {};
    _tree.empty();
    _info.hide();
  }

  // update repopulates the tree based on new survey content
  //   the current tree content is cleared out (via reset)
  //   an question sorter is attached to each ul.questions
  self.update = function(content)
  {
    self.reset();

    if(!content) { return; }

    Object.keys(content.sections)
    .map(Number)
    .sort((a,b) => a-b)
    .forEach( sid => { // section id
      const section = content.sections[sid];
      const [li,ul] = create_section_li(sid,section.name);
      li.appendTo(_tree);

      Object.entries(content.questions)
      .filter( ([eid,question]) => question.section == sid )
      .sort( ([aid,a],[bid,b]) => a.sequence - b.sequence )
      .forEach( ([eid,question]) => {
        create_question_li(eid,question).appendTo(ul);
      });
    });

    _arborist.handle_resize();
  }

  function create_section_li(section_id,name)
  {
    const btn  = $('<button>').addClass('toggle');
    const span = $('<span>').addClass('name');
    const div  = $('<div>').append(btn,span);

    const li = $('<li>').addClass('section closed').attr('data-section',section_id).html(div);

    _arborist.initialize(li,name);

    btn.on('click', function(e) {
      e.stopPropagation();
      const li = $(this).parent().parent();
      li.toggleClass('closed');
    });

    span.on('click',function(e) {
      e.stopPropagation();
      // li.section is grandparent of span
      set_selection($(this).parent().parent());
      start_keyboard_navigation(e);
    });

    const ul = $('<ul>').addClass('questions').appendTo(li);
    _question_sorters[section_id] = new Sortable( ul[0],
      {
        group: { name:'questions', pull:true, put:true },
        animation: 150,
        disabled: true,
        onEnd: handle_drop_question,
      }
    );

    return [li,ul];
  }

  self.update_section = function(section_id,key,value)
  {
    if(key === 'name') {
      const leaf = _tree.find(`.section[data-section=${section_id}]`);
      _arborist.update_label(leaf, value);
    }
  }

  function create_question_li(question_id,details)
  {
    const leaf = $('<li>').addClass('question').attr('data-question',question_id);

    let wording = details.wording;
    let type  = details.type || '';

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

  self.update_question_type = function(question_id,new_type,old_type)
  {
    const leaf = _tree.find(`.question[data-question=${question_id}]`);
    _arborist.update_type(leaf,new_type,old_type);
  }

  self.update_question = function(question_id,key,value)
  {
    const leaf  = _tree.find(`.question[data-question=${question_id}]`);

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

  // disable_sorting pretty much does what it says
  //   it disables sorting of both ul.sections and ul.questions
  //   it hides the "drag-n-drop" info box
  self.disable = function()
  {
    _info.hide();
    _section_sorter.option('disabled',true);
    Object.values(_question_sorters).forEach( (s) => s.option('disabled',true) );
  }

  // enable_sorting pretty much does what it says
  //   it enables sorting of both ul.sections and ul.questions
  //   it shows the "drag-n-drop" info box
  self.enable = function()
  {
    _info.show();
    _section_sorter.option('disabled',false);
    Object.values(_question_sorters).forEach( (s) => s.option('disabled',false) );
  }

  //
  // User driven reordering of the editor tree
  //
  
  // move_section function handles requests to move a li.section DOM element
  //   to a new location under the ul.sections DOM element.
  // This function does not care where the request came from.  It could be
  //   the result of a SortableJS drag-n-drop.  It could be the result of
  //   the user clicking on the up|down arrows in the menubar.
  // This function simply works out the necessary jquery calls necessary
  //   to update the DOM.
  // It triggers a SurveyWasReordered custom event on success
  //   and returns true.  It returns false on failure.
  self.move_section = function(sectionId,toIndex) 
  {
    const all_sections = _tree.children('li.section');
    if( toIndex >= all_sections.length) { return false; }

    const tgt_li  = all_sections.eq(toIndex);

    const move_li = all_sections.filter('[data-section='+sectionId+']');
    if( move_li.length !== 1 ) { return false; }

    const fromIndex = all_sections.index(move_li);

    if(toIndex < fromIndex) { move_li.insertBefore(tgt_li); }
    if(toIndex > fromIndex) { move_li.insertAfter(tgt_li); }

    set_selection(move_li);
    $(document).trigger('SurveyWasReordered');

    return true;
  }

  // move_question function handles requests to move a li.question DOM element
  //   to a new location under any of the ul.questions DOM element.
  // This function does not care where the request came from.  It could be
  //   the result of a SortableJS drag-n-drop.  It could be the result of
  //   the user clicking on the up|down arrows in the menubar.
  // This function simply works out the necessary jquery calls necessary
  //   to update the DOM.
  // It triggers a SurveyWasReordered custom event on success
  //   and returns true.  It returns false on failure.
  self.move_question = function(questionId,toSectionId,toIndex)
  {
    // notation:
    //   sul = ul.sections <--- only one of these in the DOM (aka _tree)
    //   sli = li.section
    //   eul = ul.questions <--- only one of these per li.section
    //   eli = li.question

    const all_eli = _tree.find('li.question');
    const move_eli = all_eli.filter('[data-question='+questionId+']');
    if(move_eli.length != 1) {
      // length should only ever be 1... but just in case it's not
      //   If it's 0, then something broke in the view controller
      //   If it's >1, then something broke in the underlying app logic
      return false;
    }

    // move_eli parent      is ul.questions
    // move_eli grandparent is li.section
    const fromSectionId = move_eli.parent().parent().data('section');

    const dst_sli = _tree.find('li.section[data-section='+toSectionId+']');
    const dst_eli = dst_sli.find('li.question');

    const tgt_eli = dst_eli.eq(toIndex);  // could be empty

    if(toSectionId === fromSectionId) { // moving li.question within its current ul.questions
      // the logic is identical to moving sections around in the sections ul
      if(tgt_eli.length !=1 ) { return false; }  // empty not allowed in this case

      const fromIndex = dst_eli.index(move_eli);
      if(toIndex < fromIndex) { move_eli.insertBefore(tgt_eli); }
      if(toIndex > fromIndex) { move_eli.insertAfter(tgt_eli); }
    }
    else { // moving li.question to a new ul.questions
      if(tgt_eli.length === 0) {
        // allow inserting at dst_eli.length (i.e., append to end), but not beyond it
        if( toIndex > dst_eli.length ) { return false; } 

        const dst_eul = dst_sli.children('ul.questions').first(); // should only be one and only one
        move_eli.appendTo(dst_eul);
      }
      else {
        move_eli.insertBefore(tgt_eli);
      }
    }

    set_selection(move_eli);
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  // Handle SortableJS onEnd from the ul.sections sorter.
  //   Unpacks the onEnd custom event in order to add the move section action
  //   to the undo manager.
  // It also triggers a SurveyWasReordered custom event
  function handle_drop_section(e)
  {
    if(e.oldIndex === e.newIndex) { return false; }

    const sectionId = $(e.item).data('section');
    ce.undo_manager.add( {
      undo() { self.move_section(sectionId,e.oldIndex); },
      redo() { self.move_section(sectionId,e.newIndex); },
    });

    set_selection($(e.item));
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  // Handle SortableJS onEnd from any of the ul.questions sorters.
  //   Unpacks the onEnd custom event in order to add the move question action
  //   to the undo manager.
  // It also triggers a SurveyWasReordered custom event
  function handle_drop_question(e)
  {
    if(e.from === e.to && e.oldIndex === e.newIndex) { return false; }

    const questionId   = $(e.item).data('question');
    const from_section = $(e.from).parent().data('section');
    const to_section   = $(e.to).parent().data('section');
    ce.undo_manager.add( {
      undo() { self.move_question(questionId,from_section,e.oldIndex); },
      redo() { self.move_question(questionId,to_section,e.newIndex); },
    });

    set_selection($(e.item));
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  //
  // User section/question selection handlers
  //
  
  self.select_section = function(section_id)
  {
    const e = _tree.find(`.section[data-section=${section_id}]`);
    _tree.find('.selected').removeClass('selected');
    e.addClass('selected');
  }

  self.select_question = function(question_id)
  {
    const e = _tree.find(`.question[data-question=${question_id}]`);
    _tree.find('.selected').removeClass('selected');
    e.addClass('selected');
  }

  // clears all class attributes associated with section/question selection
  function clear_selection()
  {
    _tree.find('.selected').removeClass('selected');
    controller.clear_selection();
  }

  function set_selection(e)
  {
    if(!e) {
      clear_selection;
      return;
    }
    _tree.find('.selected').removeClass('selected');
    e.addClass('selected');
    if(e.hasClass('section')) {
      controller.select_section(e.data('section'));
    } else {
      controller.select_question(e.data('question'));
    }
  }

  // clicking anywhere in the editor tree box other than on one of the sections
  //   or questions clears the current selection
  _box.on('click', function(e) {
    const clicked_li = $(e.target).closest('li');
    if(clicked_li.length === 0) {
      clear_selection();
    }
  });

  //
  // Insertions and Deletions
  //

  self.add_section = function(section_id, section, where)
  {
    const [new_li,new_ul] = create_section_li(section_id,section.name);
    if(where.section_id) {
      const existing_li = _tree.find(`li.section[data-section=${where.section_id}]`);
      if(where.offset < 0) { new_li.insertBefore(existing_li); }
      else                 { new_li.insertAfter(existing_li); }
    } else {
      new_li.prependTo(_tree);
    }

    // if we got here, editing must be enabled, turn on sorting in new ul.questions
    _question_sorters[section_id].option('disabled',false);

    set_selection(new_li);
    $(document).trigger('SurveyWasModified');

    return [new_li,new_ul];
  }

  self.add_question = function(question_id, question, where)
  {
    const new_li = create_question_li(question_id,question);
    if(where.section_id) {
      const section_li = _tree.find(`li.section[data-section=${where.section_id}]`);
      const questions_ul = section_li.children('ul.questions');
      if(where.at_end) { new_li.appendTo(questions_ul);  }
      else             { new_li.prependTo(questions_ul); }
    } else {
      const existing_li = _tree.find(`li.question[data-question=${where.question_id}]`);
      if(where.offset < 0) { new_li.insertBefore(existing_li); }
      else                 { new_li.insertAfter(existing_li); }
    }

    set_selection(new_li);
    $(document).trigger('SurveyWasModified');

    return new_li;
  }

  self.remove_section = function(section_id)
  {
    _question_sorters[section_id].destroy();
    delete _question_sorters[section_id];

    _tree.find(`li.section[data-section=${section_id}]`).remove();
    clear_selection();
    $(document).trigger('SurveyWasModified');
  }

  self.remove_question = function(question_id)
  {
    _tree.find(`li.question[data-question=${question_id}]`).remove();
    clear_selection();
    $(document).trigger('SurveyWasModified');
  }

  self.cache_selection = function()
  {
    const curSelection = _tree.find('li.selected');
    return {
      section_id: curSelection.data('section'),
      question_id: curSelection.data('question'),
    };
  }

  self.restore_selection = function(cache)
  {
    if(cache.section_id) {
      set_selection(_tree.find(`li.section[data-section=${cache.section_id}]`));
    }
  }

  //
  // Keyboard up|down arrow navigation
  //

  function start_keyboard_navigation(e)
  {
    if(!_keyboardNav) { 
      _keyboardNav = true;
      $(document).on('keydown', handle_keyboard_navigation);
    }
  }

  function stop_keyboard_navigation(e)
  {
    if(_keyboardNav) {
      _keyboardNav = false;
      $(document).off('keydown', handle_keyboard_navigation);
    }
  }

  $(document).on('click', stop_keyboard_navigation);

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

  const _observer = new MutationObserver((mutations) => {
    for(const m of mutations) {
      const tgt = $(m.target);
      switch(m.type) {
        case 'childList': {
          if(!tgt.is('ul.questions')) { continue; }
          break;
        }
        case 'attributes': {
          if(!tgt.is('li.question')) { continue; }
          if(m.attributeName !== 'class' ) { continue; }
          break;
        }
        default: {
          continue;
          break;
        }
      }
      const section_li = tgt.closest('li.section');
      if( section_li.length !== 1 ) { continue }

      const item_ok = (
        section_li
        .find('li.question')
        .filter('.error,.needs-value,.needs-type')
        .length === 0
      );
      const children_ok = section_li.find('li.question .error').length === 0;
      const all_ok      = item_ok && children_ok;

      const child_selected = section_li.find('li.question.selected').length > 0;

      section_li.toggleClass('child-error', !all_ok);
      section_li.toggleClass('child-selected', all_ok && child_selected);
    }
  });
  _observer.observe( $(_tree)[0], {
    attributes: true,
    subtree: true,
    childList: true,
    attributeFilter: ['class'],
  });
  
  self.set_error = function(scope,id,key,error) {
    const item = 
      scope === 'section'
      ? _tree.find(`li.section[data-section=${id}]`)
      : _tree.find(`li.question[data-question=${id}]`) ;

    if(item.length === 0) { return; }

    item.toggleClass('error',Boolean(error));
  }

  self.can_submit = function() {
    return _tree.find('li.error, li.needs-value, li.needs-type').length === 0;
  }

  // 
  // Question Handling
  //

  self.all_questions = function() {
    return new Set( _tree.find('li.question').map((_,el) => Number($(el).data('question'))));
  }

  self.replace_question = function(old_id, new_id, old_data, new_data) {
    const leaf = _tree.find(`li.question[data-question=${old_id}]`);
    if(leaf.length !== 1) { return; }

    leaf.data('question',new_id).attr('data-question',new_id);

    _arborist.update_label(leaf,new_data.wording);

    const old_type = old_data.type || '';
    const new_type = new_data.type || '';
    _arborist.update_type(leaf, new_type, old_type);
  }

  // 
  // Section/Question structure
  //

  self.survey_structure = function() {
    const rval = [];
    const sections = _tree.find('li.section');
    sections.each( function(index) {
      const section = $(this);
      const section_id = section.data('section');
      const question_ids = section.find('li.question').map( function() {
        return $(this).data('question');
      }).get();
      rval.push( {section_id:section_id, question_ids:question_ids} );
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

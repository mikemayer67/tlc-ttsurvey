import Sortable from '../../js/sortable.esm.js';

export default function editor_tree(ce)
{
  const _box  = $('#survey-tree');
  const _info = $('#survey-tree .info');
  const _tree = $('#survey-tree ul.sections');

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
  function reset()
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
  function update(content)
  {
    reset();

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
  }

  function create_section_li(sid,name)
  {
    const btn = $('<button>').addClass('toggle');

    const span = $('<span>').addClass('name');
    if(name) {
      span.text(name);
    } else {
      span.text('[needs name]');
    }

    const div = $('<div>').append(btn,span);

    const li = $('<li>')
    .addClass('section closed')
    .attr('data-section',sid)
    .html(div)

    if(!name) {
      span.addClass('incomplete').addClass('missing');
    }

    btn.on('click', function(e) {
      const li = $(this).parent().parent();
      if( li.hasClass('closed') ) { li.removeClass('closed'); }
      else                        { li.addClass('closed');    }
    });
    span.on('click',function(e) {
      // li.section is grandparent of span
      set_selection($(this).parent().parent());
    });

    const ul = $('<ul>').addClass('questions').appendTo(li);
    _question_sorters[sid] = new Sortable( ul[0],
      {
        group: { name:'questions', pull:true, put:true },
        animation: 150,
        disabled: true,
        onEnd: handle_drop_question,
      }
    );

    return [li,ul];
  }

  function create_question_li(eid,details)
  {
    const li = $('<li>').addClass('question').attr('data-question',eid);
    li.on('click',function() { set_selection($(this)); } );

    if(details.wording) {
      li.text(details.wording);
    } else {
      li.text('[needs wording]');
      li.addClass('incomplete');
    }

    if(details.type) {
      li.addClass(details.type.toLowerCase());
    } else {
      li.addClass('missing');
    }

    if(details.multiple) {
      li.addClass('multi');
    }

    return li;
  }

  // disable_sorting pretty much does what it says
  //   it disables sorting of both ul.sections and ul.questions
  //   it hides the "drag-n-drop" info box
  function disable_sorting()
  {
    _info.hide();
    _section_sorter.option('disabled',true);
    Object.values(_question_sorters).forEach( (s) => s.option('disabled',true) );
  }

  // enable_sorting pretty much does what it says
  //   it enables sorting of both ul.sections and ul.questions
  //   it shows the "drag-n-drop" info box
  function enable_sorting()
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
  function move_section(sectionId,toIndex) 
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

  // Watch for any RequestMoveSection events and unpackage it to
  //   make the desired call to move_section.
  $(document).on('RequestMoveSection', function(e,kwargs) {
    move_section(kwargs.sectionId, kwargs.toIndex);
  });

  // move_question function handles requests to move a li.question DOM element
  //   to a new location under any of the ul.questions DOM element.
  // This function does not care where the request came from.  It could be
  //   the result of a SortableJS drag-n-drop.  It could be the result of
  //   the user clicking on the up|down arrows in the menubar.
  // This function simply works out the necessary jquery calls necessary
  //   to update the DOM.
  // It triggers a SurveyWasReordered custom event on success
  //   and returns true.  It returns false on failure.
  function move_question(questionId,toSectionId,toIndex)
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

  // Watch for any RequestMoveQuestion events and unpackage it to
  //   make the desired call to move_question.
  $(document).on('RequestMoveQuestion', function(e,kwargs) {
    move_question(kwargs.questionId, kwargs.toSectionId, kwargs.toIndex);
  });


  // Handle SortableJS onEnd from the ul.sections sorter.
  //   Unpacks the onEnd custom event in order to add the move section action
  //   to the undo manager.
  // It also triggers a SurveyWasReordered custom event
  function handle_drop_section(e)
  {
    if(e.oldIndex === e.newIndex) { return false; }

    const sectionId = $(e.item).data('section');
    ce.undo_manager.add( {
      undo() { move_section(sectionId,e.oldIndex); },
      redo() { move_section(sectionId,e.newIndex); },
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
      undo() { move_question(questionId,from_section,e.oldIndex); },
      redo() { move_question(questionId,to_section,e.newIndex); },
    });

    set_selection($(e.item));
    $(document).trigger('SurveyWasReordered');
    return true;
  }

  //
  // User section/question selection handlers
  //
  
  // if the selected item is an li.question, adds the selected-child class
  //   to the li.section that contains the selected li.question
  function update_parent_section()
  {
    _tree.find('.selected-child').removeClass('selected-child');

    const selected_question = _tree.find('li.question.selected');
    if( selected_question.length === 1 ) {
      selected_question.parent().parent().addClass('selected-child');
    }
  }

  // clears all class attributes associated with section/question selection
  function clear_selection()
  {
    _tree.find('.selected').removeClass('selected');
    _tree.find('.selected-child').removeClass('selected-child');
    $(document).trigger('SelectionCleared');
  }

  function set_selection(e)
  {
    if(!e) {
      clear_selection;
      return;
    }
    _tree.find('.selected').removeClass('selected');
    _tree.find('.selected-child').removeClass('selected-child');
    e.addClass('selected');
    if(e.hasClass('section')) {
      $(document).trigger('SectionSelected',[e.data('section')]);
    } else {
      e.parent().parent().addClass('selected-child'); 
      $(document).trigger('QuestionSelected',[e.data('question')]);
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

  function add_section(section_id, section, where)
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

  function add_question(question_id, question, where)
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

  function remove_section(section_id)
  {
    _question_sorters[section_id].destroy();
    delete _question_sorters[section_id];

    _tree.find(`li.section[data-section=${section_id}]`).remove();
    clear_selection();
    $(document).trigger('SurveyWasModified');
  }

  function remove_question(question_id)
  {
    _tree.find(`li.question[data-question=${question_id}]`).remove();
    clear_selection();
    $(document).trigger('SurveyWasModified');
  }

  function cache_selection()
  {
    const curSelection = _tree.find('li.selected');
    return {
      section_id: curSelection.data('section'),
      question_id: curSelection.data('question'),
    };
  }

  function restore_selection(cache)
  {
    if(cache.section_id) {
      set_selection(_tree.find(`li.section[data-section=${cache.section_id}]`));
    }
  }

  //
  // Return
  //

  return {
    reset:  reset,                        // clears the tree and disables user sorting
    update: update,                       // updates content of the survey tree
    enable:  enable_sorting,              // enables sorting
    disable: disable_sorting,             // disables sorting
    add_section: add_section,             // (new_section_id, new_section object, where)
    add_question: add_question,           // (new_question_id, new_question object, where)
    remove_section: remove_section,       // (section_id)
    remove_question: remove_question,     // (question_id)
    cache_selection: cache_selection,     // returns object to pass to restore_selection
    restore_selection: restore_selection, // (object returned by cache_selection)
  };
}

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
      filter: '.element',
      disabled: true,
      onEnd: handle_drop_section,
    }
  );

  // sorters for each of the ul.elements
  let _element_sorters = {};

  // reset clears out the tree
  //   section sorter is disabled
  //   all element sorters are released
  //   the "drag-n-drop" info box is hidden
  function reset()
  {
    _section_sorter.option('disabled',true);
    Object.values(_element_sorters).forEach((s) => s.destroy());
    _element_sorters = {};
    _tree.empty();
    _info.hide();
  }

  // update repopulates the tree based on new survey content
  //   the current tree content is cleared out (via reset)
  //   an element sorter is attached to each ul.elements
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

      Object.entries(content.elements)
      .filter( ([eid,element]) => element.section == sid )
      .sort( ([aid,a],[bid,b]) => a.sequence - b.sequence )
      .forEach( ([eid,element]) => {
        const eli = $('<li>')
        .addClass('element')
        .addClass(element.type.toLowerCase())
        .attr('data-element',eid)
        .text(element.label);
        if(element.multiple) { 
          eli.addClass('multi'); 
        }
        eli.appendTo(ul);
        eli.on('click',element_selected);
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
    span.on('click',section_selected);

    const ul = $('<ul>').addClass('elements').appendTo(li);
    _element_sorters[sid] = new Sortable( ul[0],
      {
        group: { name:'elements', pull:true, put:true },
        animation: 150,
        disabled: true,
        onEnd: handle_drop_element,
      }
    );

    return [li,ul];
  }

  // disable_sorting pretty much does what it says
  //   it disables sorting of both ul.sections and ul.elements
  //   it hides the "drag-n-drop" info box
  function disable_sorting()
  {
    _info.hide();
    _section_sorter.option('disabled',true);
    Object.values(_element_sorters).forEach( (s) => s.option('disabled',true) );
  }

  // enable_sorting pretty much does what it says
  //   it enables sorting of both ul.sections and ul.elements
  //   it shows the "drag-n-drop" info box
  function enable_sorting()
  {
    _info.show();
    _section_sorter.option('disabled',false);
    Object.values(_element_sorters).forEach( (s) => s.option('disabled',false) );
  }


  // handles clicks on any of the li.section in the editor tree
  function section_selected(e)
  {
    clear_selection();

    const li = $(this).parent().parent();
    li.addClass('selected');
    const sid = li.data('section');

    $(document).trigger('UserSelectedSection',{sid:sid});
  }

  // handles clicks on any of the li.element in the editor tree
  function element_selected(e)
  {
    clear_selection();

    $(this).addClass('selected');
    const eid = $(this).data('element');
    $(this).parent().parent().addClass('selected-child');

    $(document).trigger('UserSelectedElement',{eid:eid});
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
  // It triggers a SurveyContentWasReordered custom event on success
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

    $(document).trigger('SurveyContentWasReordered');

    return true;
  }

  // Watch for any RequestMoveSection events and unpackage it to
  //   make the desired call to move_section.
  $(document).on('RequestMoveSection', function(e,kwargs) {
    move_section(kwargs.sectionId, kwargs.toIndex);
  });

  // move_element function handles requests to move a li.element DOM element
  //   to a new location under any of the ul.elements DOM element.
  // This function does not care where the request came from.  It could be
  //   the result of a SortableJS drag-n-drop.  It could be the result of
  //   the user clicking on the up|down arrows in the menubar.
  // This function simply works out the necessary jquery calls necessary
  //   to update the DOM.
  // It triggers a SurveyContentWasReordered custom event on success
  //   and returns true.  It returns false on failure.
  function move_element(elementId,toSectionId,toIndex)
  {
    // notation:
    //   sul = ul.sections <--- only one of these in the DOM (aka _tree)
    //   sli = li.section
    //   eul = ul.elements <--- only one of these per li.section
    //   eli = li.element

    const all_eli = _tree.find('li.element');
    const move_eli = all_eli.filter('[data-element='+elementId+']');
    if(move_eli.length != 1) {
      // length should only ever be 1... but just in case it's not
      //   If it's 0, then something broke in the view controller
      //   If it's >1, then something broke in the underlying app logic
      return false;
    }

    // move_eli parent      is ul.elements
    // move_eli grandparent is li.section
    const fromSectionId = move_eli.parent().parent().data('section');

    const dst_sli = _tree.find('li.section[data-section='+toSectionId+']');
    const dst_eli = dst_sli.find('li.element');

    const tgt_eli = dst_eli.eq(toIndex);  // could be empty

    if(toSectionId === fromSectionId) { // moving li.element within its current ul.eleemnts
      // the logic is identical to moving sections around in the sections ul
      if(tgt_eli.length !=1 ) { return false; }  // empty not allowed in this case

      const fromIndex = dst_eli.index(move_eli);
      if(toIndex < fromIndex) { move_eli.insertBefore(tgt_eli); }
      if(toIndex > fromIndex) { move_eli.insertAfter(tgt_eli); }
    }
    else { // moving li.element to a new ul.elements
      if(tgt_eli.length === 0) {
        // allow inserting at dst_eli.length (i.e., append to end), but not beyond it
        if( toIndex > dst_eli.length ) { return false; } 

        const dst_eul = dst_sli.children('ul.elements').first(); // should only be one and only one
        move_eli.appendTo(dst_eul);
      }
      else {
        move_eli.insertBefore(tgt_eli);
      }
    }

    update_parent_section();

    $(document).trigger('SurveyContentWasReordered');
    return true;
  }

  // Watch for any RequestMoveElement events and unpackage it to
  //   make the desired call to move_element.
  $(document).on('RequestMoveElement', function(e,kwargs) {
    move_element(kwargs.elementId, kwargs.toSectionId, kwargs.toIndex);
  });


  // Handle SortableJS onEnd from the ul.sections sorter.
  //   Unpacks the onEnd custom event in order to add the move section action
  //   to the undo manager.
  // It also triggers a SurveyContentWasReordered custom event
  function handle_drop_section(e)
  {
    if(e.oldIndex === e.newIndex) { return false; }

    const sectionId = $(e.item).data('section');
    ce.undo_manager.add( {
      undo() { move_section(sectionId,e.oldIndex); },
      redo() { move_section(sectionId,e.newIndex); },
    });

    $(document).trigger('SurveyContentWasReordered');
    return true;
  }

  // Handle SortableJS onEnd from any of the ul.elements sorters.
  //   Unpacks the onEnd custom event in order to add the move element action
  //   to the undo manager.
  // It also triggers a SurveyContentWasReordered custom event
  function handle_drop_element(e)
  {
    if(e.from === e.to && e.oldIndex === e.newIndex) { return false; }

    const elementId    = $(e.item).data('element');
    const from_section = $(e.from).parent().data('section');
    const to_section   = $(e.to).parent().data('section');
    ce.undo_manager.add( {
      undo() { move_element(elementId,from_section,e.oldIndex); },
      redo() { move_element(elementId,to_section,e.newIndex); },
    });

    update_parent_section();
    $(document).trigger('SurveyContentWasReordered');
    return true;
  }

  //
  // User section/element selection handlers
  //
  
  // if the selected item is an li.element, adds the selected-child class
  //   to the li.section that contains the selected li.element
  function update_parent_section()
  {
    _tree.find('.selected-child').removeClass('selected-child');

    const selected_element = _tree.find('li.element.selected');
    if( selected_element.length === 1 ) {
      selected_element.parent().parent().addClass('selected-child');
    }
  }

  // clears all class attributes associated with section/element selection
  function clear_selection()
  {
    _tree.find('.selected').removeClass('selected');
    _tree.find('.selected-child').removeClass('selected-child');
  }

  // clicking anywhere in the editor tree box other than on one of the sections
  //   or elements clears the current selection
  _box.on('click', function(e) {
    const clicked_li = $(e.target).closest('li');
    if(clicked_li.length === 0) {
      clear_selection();
      $(document).trigger('UserClearedSelection');
    }
  });

  //
  // Insertions and Deletions
  //

  $(document).on('AddNewSection', function(e,data) {
    const [li,ul] = create_section_li(data.section_id)
    if(data.direction<0) { li.insertBefore(data.relativeTo); }
    else                 { li.insertAfter(data.relativeTo); }
    // if we got here, editing must be enabled.
    _element_sorters[data.section_id].option('disabled',false);

    $(document).trigger('SurveyContentWasModified');
  });

  //
  // Return
  //

  return {
    reset:  reset,               // clears the tree and disables user sorting
    update: update,              // updates content of the survey tree
    enable:  enable_sorting,     // enables sorting
    disable: disable_sorting,    // disables sorting
  };
}

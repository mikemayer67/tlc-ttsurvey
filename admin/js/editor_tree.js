import Sortable from '../../js/sortable.esm.js';

export default function editor_tree(ce,menubar)
{
  const _box  = $('#survey-tree');
  const _info = $('#survey-tree .info');
  const _tree = $('#survey-tree ul.sections');

  let _section_sorter = null;
  let _element_sorters = [];

  function reset()
  {
    _section_sorter.option('disabled',true);
    _element_sorters.forEach( (s) => s.destroy() );
    _element_sorters = [];
    _tree.empty();
    _info.hide();
  }

  function update_content(content,editable)
  {
    Object.keys(content.sections)
    .map(Number)
    .sort((a,b) => a-b)
    .forEach( sid => { // section id
      const section = content.sections[sid];

      const btn = $('<button>').addClass('toggle');
      const span = $('<span>').text(section.name).addClass('name');
      const div = $('<div>').append(btn,span);

      const li = $('<li>')
        .addClass('section closed')
        .attr('data-section',sid)
        .html(div)
        .appendTo(_tree);

      const ul = $('<ul>').addClass('elements').appendTo(li);

      btn.on('click', function(e) {
        const li = $(this).parent().parent();
        if( li.hasClass('closed') ) { li.removeClass('closed'); }
        else                        { li.addClass('closed');    }
      });
      span.on('click', new_section_selected);

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
        eli.on('click',new_element_selected);
      });
    });

    // Tree sorting
    if( editable ) {
      setup_sorting();
      enable_sorting();
    }
  }

  function new_section_selected(e)
  {
    clear_selection();

    const li = $(this).parent().parent();
    li.addClass('selected');
    const sid = li.data('section');

    menubar.new_section_selected(sid);
  }

  function new_element_selected(e)
  {
    clear_selection();

    $(this).addClass('selected');
    const eid = $(this).data('element');
    $(this).parent().parent().addClass('selected-child');

    menubar.new_element_selected(eid);
  }
  
  _section_sorter = new Sortable( _tree[0],
    {
      group: 'sections',
      animation: 150,
      filter: '.element',
      onEnd: handle_drop_section,
    }
  );

  function move_section(sectionId,toIndex) 
  {
    const all_sections = _tree.children('li.section');
    if( toIndex >= all_sections.length) { return false; }

    const tgt_li  = all_sections.eq(toIndex);

    const move_li = all_sections.filter('[data-section='+sectionId+']');
    if( move_li.length !== 1 ) { return false; }

    const fromIndex = all_sections.index(move_li);

    // test case 1: move up the list (toIndex < fromIndex)
    //    Before: 0 1 2 3 4 5 6 7 8 9
    //    move("5",2) {tgt_li="2" / move_li="5" / fromIndex=5}
    //      "5".insertBefore("2")
    //    result: 0 1 5 2 3 4 6 7 8 9
    //
    // test case 2: move down the list (toIndex > fromIndex)
    //    Before: 0 1 2 3 4 5 6 7 8 9
    //    move("4",9) { tgt_li="9" / move_li="4" / fromIndex=4}
    //      "4".insertAfter("9")
    //    result: 0 1 2 3 5 6 7 8 9 4
    //
    // test case 3: no move needed (toIndex === fromIndex)
    //    do nothing other than raturn true (the section is at the requested position)

    if(toIndex < fromIndex) { move_li.insertBefore(tgt_li); }
    if(toIndex > fromIndex) { move_li.insertAfter(tgt_li); }

    $(document).trigger('SurveyContentReordered');

    return true;
  }

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

    update_selected_child();

    $(document).trigger('SurveyContentReordered');
    return true;
  }

  menubar.set_move_section_hook(move_section);
  menubar.set_move_element_hook(move_element);

  function setup_sorting()
  {
    _section_sorter.option('disabled',false);
    _tree.find('ul.elements').each( function() {
      const sorter = new Sortable( this,
        {
          group: { name:'elements', pull:true, pust:true },
          animation: 150,
          onEnd: handle_drop_element,
        }
      );
      _element_sorters.push(sorter);
    });
  }

  function disable_sorting()
  {
    _info.hide();
    _section_sorter.option('disabled',true);
    _element_sorters.forEach( (s) => s.option('disabled',true) );
  }

  function enable_sorting()
  {
    _info.show();
    _section_sorter.option('disabled',false);
    _element_sorters.forEach( (s) => s.option('disabled',false) );
  }

  function handle_drop_section(e)
  {
    if(e.oldIndex === e.newIndex) { return false; }

    const sectionId = $(e.item).data('section');
    ce.undo_manager.add( {
      undo() { move_section(sectionId,e.oldIndex); },
      redo() { move_section(sectionId,e.newIndex); },
    });

    $(document).trigger('SurveyContentReordered');
    return true;
  }

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

    update_selected_child();
    $(document).trigger('SurveyContentReordered');
    return true;
  }

  function update_selected_child()
  {
    _tree.find('.selected-child').removeClass('selected-child');
    const selected_element = _tree.find('li.element.selected');
    selected_element.parent().parent().addClass('selected-child');
  }

  function clear_selection()
  {
    _tree.find('.selected').removeClass('selected');
    _tree.find('.selected-child').removeClass('selected-child');
    menubar.clear_selection();
  }

  _box.on('click', function(e) {
    const clicked_li = $(e.target).closest('li');
    if(clicked_li.length === 0) {
      clear_selection();
    }
  });

  return {
    reset: reset,
    update_content: update_content,
  };
}

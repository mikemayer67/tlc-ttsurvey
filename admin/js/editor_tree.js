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
    _tree.find('.drop-target').removeClass('drop-target');

    if(e.oldIndex === e.newIndex) { return false; }

    const action = {
      element: e.item,
      oldIndex: e.oldIndex,
      newIndex: e.newIndex,
      undo() {
        const tree = _tree[0];
        const peers = tree.children;
        var tgt_index = this.oldIndex;
        if( tgt_index > this.newIndex ) { tgt_index +=1; }
        if( tgt_index >= peers.length ) {
          tree.appendChild(this.element);
        } else {
          tree.insertBefore(this.element, peers[tgt_index]);
        }
      },
      redo() {
        const tree = _tree[0];
        const peers = tree.children;
        var tgt_index = this.newIndex;
        if( tgt_index > this.oldIndex ) { tgt_index +=1; }
        if( tgt_index >= peers.length ) {
          tree.appendChild(this.element);
        } else {
          tree.insertBefore(this.element, peers[tgt_index]);
        }
      },
    };

    ce.undo_manager.add(action);

    return true;
  }

  function handle_drop_element(e)
  {
    _tree.find('.drop-target').removeClass('drop-target');

    if(e.from === e.to && e.oldIndex === e.newIndex) { return false; }

    const action = {
      element: e.item,
      from: e.from,
      to: e.to,
      oldIndex: e.oldIndex,
      newIndex: e.newIndex,
      undo() {
        var tgt_index = this.oldIndex;
        if( tgt_index > this.newIndex ) { tgt_index +=1; }
        const peers = this.from.children;
        if( tgt_index >= peers.length ) {
          this.from.appendChild(this.element);
        } else {
          this.from.insertBefore(this.element, peers[tgt_index]);
        }
        update_selected_child(this.element);
      },
      redo() {
        var tgt_index = this.newIndex;
        if( tgt_index > this.oldIndex ) { tgt_index +=1; }
        const peers = this.to.children;
        if( tgt_index >= peers.length ) {
          this.to.appendChild(this.element);
        } else {
          this.to.insertBefore(this.element, peers[tgt_index]);
        }
        update_selected_child(this.element);
      },
    };

    update_selected_child(e.item);
    ce.undo_manager.add(action);

    return true;
  }

  function update_selected_child(element_li)
  {
    _tree.find('.selected-child').removeClass('selected-child');
    $(element_li).parent().parent().addClass('selected-child');
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

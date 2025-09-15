import Sortable from '../../../../js/sortable.esm.js';

export default function init(ce,controller,editor)
{
  const _options    = editor.box.children('.options');
  const _selected   = _options.find('.selected');
  const _pool       = _options.find('.pool');
  const _add_chip   = _pool.find('button.add');

  let _question_id = null;

  const _selected_sorter = new Sortable( _selected[0], 
    {
      group: {
        name:'selected', 
        put:['pool','selected'],
      },
      animation: 150,
      draggable: '.chip',
      onAdd: handle_selected_drag_add,
      onEnd: handle_selected_drag_end,
    },
  );

  const _pool_sorter = new Sortable( _pool[0], 
    {
      group: {
        name:'pool', 
        put:false,
      },
      animation: 150,
      draggable: '.chip',
      sort: false,
    },
  );

  function handle_selected_drag_end(e)
  {
    if( e.oldIndex === e.newIndex ) { return; }

    const chip = $(e.item);
    const chip_id = chip.data('id');
    const chip_sel = '.chip[id=' + chip_id + ']';

    const cur_undo = ce.undo_manager.head();
    const reverts_prior = (
      ( cur_undo?.action === 'chip-drag' ) &&
      ( cur_undo?.question_id === _question_id ) && 
      ( cur_undo?.old_index === e.newIndex )
    );

    if( reverts_prior && cur_undo.reverts_prior ) {
      // remove 'manual undo' from the undo stack
      ce.undo_manager.pop(cur_undo);
    }
    else
    {
      // add new undo action onto the stack
      ce.undo_manager.add({
        action:'chip-drag',
        question_id:_question_id,
        chip_id:chip_id,
        old_index:e.oldIndex,
        new_index:e.newIndex,
        apply(to_index,from_index) {
          const chips = _selected.children('.chip');
          const chip  = $(chips[from_index]);
          const tgt   = $(chips[to_index]);
          if(to_index > from_index) { chip.insertAfter(tgt);  }
          else                      { chip.insertBefore(tgt); }
          update_options();
        },
        redo() { 
          controller.select_question(this.question_id);
          setTimeout( () => { this.apply(this.new_index,this.old_index); }, 100 );
        },
        undo() { 
          controller.select_question(this.question_id);
          setTimeout( () => { this.apply(this.old_index,this.new_index); }, 100 );
        },
      });
    }

    update_options();
  }

  function handle_selected_drag_add(e)
  {
    const chip = $(e.item);
    const chip_id = chip.data('id');
    const chip_sel = '.chip[id=' + chip_id + ']';
    ce.undo_manager.add({
      action:'chip-from-pool',
      index:e.newIndex,
      redo() {
        const chip = _pool.find(chip_sel);
        insert_selected_chip_at(chip,this.index);
        update_options();
      },
      undo() {
        const chip = _selected.find(chip_sel);
        insert_chip_alphabetically_in_pool(chip);
        update_options();
      },
    });
    update_options();
  }

  let _all_options = {};

  function show(data)
  {
    _question_id = data.id;

    _all_options = controller.all_options();

    _selected.empty();
    _pool.find('.chip').remove();

    for( const [id,label] of Object.entries(_all_options) ) {
      const chip = create_chip(id,label);
      const selected = data.options.includes(Number(id));
      if( selected ) { _selected.append(chip); }
      else           { _pool.append(chip);     }
    }

    validate_options()

    _options.show();
    _pool.hide();
  }

  function create_chip(id,label)
  {
    const chip = $("<div>").addClass('chip').data('id',id).attr('id',id);
    const span = $("<span>").text(label);
    const close = $("<button class='option' type='button'>x</button>");
    return chip.append(span).append(close);
  };

  function update_options()
  {
    validate_options()

    const chips = _selected.children('.chip');
    const new_indices = chips.map( (i,e) => { return Number($(e).data('id')); });
    controller.update_question_data(_question_id, 'options', new_indices);
  }


  function validate_options()
  {
    const has_options = _selected.children(':first').length > 0;

    _selected.toggleClass('error',!has_options);

    const span = _options.find('span.error');
    if(has_options) {
      span.text('');
      editor.clear_error('options');
    } else {
      editor.set_error('options','needs-selected-option');
      span.text('missing');
    }
  }

  _options.on('mousedown', '.selected,.pool', function(e) {
    // We want to yield to SortableJS if the mouse down occured in a chip element.
    if( $(e.target).closest('.chip').length > 0) { return; }
    if( $(e.target).closest('.add').length > 0)  { return; }
    _pool.toggle();
  });

  _options.on('click','.selected .chip button', function(e) {
    // remove a chip from the selected list and return to the pool
    const chip = $(this).closest('.chip');
    const chip_sel = '.chip[id=' + chip.data('id') + ']';
    ce.undo_manager.add_and_exec({
      action:'chip-to-pool',
      old_index:chip.index(),
      redo() {
        const chip = _options.find(chip_sel);
        insert_chip_alphabetically_in_pool(chip);
        update_options();
      },
      undo() {
        const chip = _options.find(chip_sel);
        insert_selected_chip_at(chip,this.old_index);
        update_options();
      },
    });
  });

  _options.on('dblclick','.chip', function(e) {
    // present dialog box to edit the option label
    const chip      = $(this).closest('.chip');
    const span      = chip.children('span');
    const old_value = span.text();
    const raw_value = prompt('Edit option',old_value);
    if( raw_value === null ) { return; }

    const new_value = raw_value.trim();
    if( ! new_value )             { return; }
    if( new_value === old_value ) { return; }

    const chip_sel = '.chip[id=' + chip.data('id') + ']';
    ce.undo_manager.add_and_exec({
      action:'chip-edit',
      question_id:_question_id,
      apply(value) {
        const chip = _options.find(chip_sel);
        const span = chip.children('span');
        span.text(value);
        // controller.update_option(chip_id,value);
        // $(document).trigger('SurveyWasModified');
      },
      redo() {
        controller.select_question(this.question_id);
        setTimeout( () => { this.apply(new_value); }, 100 );
      },
      undo() {
        controller.select_question(this.question_id);
        setTimeout( () => { this.apply(old_value); }, 100 );
      }
    });
  });

  _add_chip.on('click',function(e) {
    const new_option = prompt('New option').trim();
    if(new_option) {
      const new_id = controller.add_option(new_option);
      const chip = create_chip(new_id, new_option);
      insert_chip_at_end_of_pool(chip);
    }
  });

  function insert_selected_chip_at(chip,index)
  {
    if( index >= _selected.children().length ) {
      _selected.append(chip)
    } else {
      chip.insertBefore(_selected.children().eq(index));
    }
  }

  function insert_selected_chip_before(chip,id)
  {
    if(id) {
      ref_chip = _selected.find('.chip[id='+id+']');
      if(ref_chip) {
        chip.insertBefore(ref_chip);
        return;
      }
    }
    _select.append(chip);
  }

  function insert_chip_at_end_of_pool(chip) 
  {
    chip.insertBefore(_add_chip);
  }

  function insert_chip_alphabetically_in_pool(chip)
  {
    chip.insertBefore(_add_chip);
    const chips = _pool.children('.chip').get();

    chips.sort((a,b) => {
      const label_a = $(a).find('span').text().toLowerCase();
      const label_b = $(b).find('span').text().toLowerCase();
      return label_a.localeCompare(label_b);
    });

    $(chips).insertBefore(_add_chip);
  }

  return {
    show:show,
  };
}


import Sortable from '../../../../js/sortable.esm.js';
import OptionEditor from './option_editor.js';

/// <reference path="../data-types.js" />

/**
 * OptionController used to manage the pool of available select question options
 * @typedef {Object} OptionController
 * @property {(data:QuestionInfo) => void } show
 */

/**
 * Local typedefs
 * @typedef {jQuery<HTMLDivElement} ChipElement
 */

/**
 * Initializes a OptionController
 * @param {RuntimeContext} ce 
 * @param {SurveyViewController} controller 
 * @param {QuestionEditor} editor 
 * @returns 
 */
export default function init(ce,controller,editor)
{
  const $_options       = editor.box.children('.options');
  const $_selected      = $_options.find('.selected');
  const $_pool          = $_options.find('.pool');
  const $_add_chip      = $_pool.find('button.add');

  const _option_editor = OptionEditor(ce,controller);

  let _question_id = null;

  const self = {};

  const _selected_sorter = new Sortable( $_selected[0], 
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

  const _pool_sorter = new Sortable( $_pool[0], 
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

  /**
   * Callback when an option chip drag operation has ended
   * @param {Event} e
   */
  function handle_selected_drag_end(e)
  {
    if( e.oldIndex === e.newIndex ) { return; }

    const chip = $(e.item);
    const option_id = chip.data('id');

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
        option_id:option_id,
        old_index:e.oldIndex,
        new_index:e.newIndex,
        apply(to_index,from_index) {
          const $chips = $_selected.children('.chip');
          const $chip  = $chips.eq(from_index);
          const $tgt   = $chips.eq(to_index);
          animate_chip_move($chip, () => {
            if(to_index > from_index) { $chip.insertAfter($tgt);  }
            else                      { $chip.insertBefore($tgt); }
            update_options();
          });
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

  /**
   * Callback when an option chip drag operation has ended with
   *   the chip being added to the list of question options from
   *   the pool of available options
   * @param {Event} e
   */
  function handle_selected_drag_add(e)
  {
    const $chip = $(e.item);
    const option_id = $chip.data('id');
    const chip_sel = '.chip[id=' + option_id + ']';
    ce.undo_manager.add({
      action:'chip-from-pool',
      question_id:_question_id,
      index:e.newIndex,
      redo() {
        controller.select_question(this.question_id);
        setTimeout( () => {
          const $chip = $_pool.find(chip_sel);
          animate_chip_move($chip, () => {
            insert_selected_chip_at($chip,this.index);
            update_options();
          });
        }, 100);
      },
      undo() {
        controller.select_question(this.question_id);
        setTimeout( () => {
          const $chip = $_selected.find(chip_sel);
          animate_chip_move($chip, () => {
            insert_chip_alphabetically_in_pool($chip);
            update_options();
          });
        }, 100);
      },
    });
    update_options();
  }

  let _all_options = {};

  /**
   * Adds the option fields to the question editor via the survey view controller
   * @param {QuestionInfo} data 
   */
  function show(data)
  {
    _question_id = data.id;

    _all_options = controller.all_options();

    $_selected.empty();
    $_pool.find('.chip').remove();

    for( const [id,opt] of Object.entries(_all_options) ) {
      let is_common = true;
      if(opt.specific_to !== null) {
        if(opt.specific_to !== _question_id) { continue; }
        is_common = false;
      }
      const $chip = create_chip(id,opt.text,is_common);
      const selected = data.options.includes(Number(id));
      if( selected ) { $_selected.append($chip); }
      else           { insert_chip_alphabetically_in_pool($chip); }
    }

    validate_options()

    $_options.show();
    $_pool.hide();
  }

  /**
   * constructs a new chip as an unattached DOM element
   * @param {number} id the associated option ID
   * @param {string} label 
   * @param {boolean} is_common 
   * @returns {ChipElement}
   */
  function create_chip(id,label,is_common)
  {
    const chip = $("<div>").addClass('chip').data('id',id).attr('id',id);
    if(is_common) { chip.addClass('common'); }
    else          { chip.addClass('specific'); }
    const span = $("<span>").text(label);
    const close = $("<button class='option' type='button'></button>");
    return chip.append(span).append(close);
  };

  /**
   * Should be invoked whenever an event in the browser results in
   *   a change to the mapping of options to questions or the pool.
   * This function coordinates that change with the survey view controller
   */
  function update_options()
  {
    validate_options()

    const chips = $_selected.children('.chip');
    const new_indices = chips.map( (i,e) => { return Number($(e).data('id')); }).get();
    controller.update_question_data(_question_id, 'options', new_indices);
    $(document).trigger('SurveyWasModified');
  }


  /**
   * Should be invoked whenever the option elements in the question editor
   *   change, either because of new content or user interaction with them
   *   via the browser.
   * This function verifies that there is at least one option currently
   *   selected.  If not, it updates the selected option field to show
   *   the error and notifies the question editor of the error.  Otherwise,
   *   it clears the error info from the selected option field and
   *   notifies the question editor that there are no option errors.
   */
  function validate_options()
  {
    const has_options = $_selected.children(':first').length > 0;

    $_selected.toggleClass('error',!has_options);

    const span = $_options.find('span.error');
    if(has_options) {
      span.text('');
      editor.clear_error('options');
    } else {
      editor.set_error('options','needs-selected-option');
      span.text('missing');
    }
  }

  /**
   * MouseDown event handler in the selected option field or option pool
   *   If event occured within a chip or the add button, do nothing (yet)
   *   Otherwise toggle visibility of the option pool
   */
  $_options.on('mousedown', '.selected,.pool', function(e) {
    // We want to yield to SortableJS if the mouse down occured in a chip element.
    if( $(e.target).closest('.chip').length > 0) { return; }
    if( $(e.target).closest('.add').length > 0)  { return; }
    $_pool.toggle();
  });

  /**
   * Click event handler on the chip 'dismiss' button (little x in the option chip).
   *   Returns the option chip from the selected option field to the option pool
   * Note this is actually the same button as the 'add' button in the
   *   next event handler.  The selector differentiates which handler
   *   is invoked.
   */
  $_options.on('click','.selected .chip button', function(e) {
    // remove a chip from the selected list and return to the pool
    const $chip = $(this).closest('.chip');
    const chip_sel = '.chip[id=' + $chip.data('id') + ']';
    ce.undo_manager.add_and_exec({
      action:'chip-to-pool',
      question_id:_question_id,
      old_index:$chip.index(),
      redo() {
        controller.select_question(this.question_id);
        setTimeout( () => {
          const $chip = $_options.find(chip_sel);
          animate_chip_move($chip, () => {
            insert_chip_alphabetically_in_pool($chip);
            update_options();
          });
        }, 100);
      },
      undo() {
        controller.select_question(this.question_id);
        setTimeout( () => {
          const $chip = $_options.find(chip_sel);
          animate_chip_move($chip, () => {
            insert_selected_chip_at($chip,this.old_index);
            update_options();
          });
        }, 100);
      },
    });
  });

  /**
   * Click event hanlder on the chip 'add to list' button (little + in the 
   *   option chip)). Adds the option chip from the option pool to the selected
   *   option field.  
   * Note this is actually the same button as the 'dismiss' button in the
   *   prior event handler.  The selector differentiates which handler
   *   is invoked.
   */
  $_options.on('click','.pool .chip button', function(e) {
    // remove a chip from the pool and add it to the end of the selected list
    const $chip = $(this).closest('.chip');
    const chip_sel = '.chip[id=' + $chip.data('id') + ']';
    ce.undo_manager.add_and_exec({
      action:'chip-to-selected',
      question_id:_question_id,
      redo() {
        controller.select_question(this.question_id);
        setTimeout( () => {
          const $chip = $_options.find(chip_sel);
          animate_chip_move($chip, () => {
            $_selected.append($chip);
            update_options();
          });
        }, 100);
      },
      undo() {
        controller.select_question(this.question_id);
        setTimeout( () => {
          const $chip = $_options.find(chip_sel);
          animate_chip_move($chip, () => {
            insert_chip_alphabetically_in_pool($chip);
            update_options();
          });
        }, 100);
      },
    });
  });

  /**
   * Double click event handler on an option chip
   * Presents the option editor and coordinates the response from it
   */
  $_options.on('dblclick','.chip', function(e) {
    // present dialog box to edit the option label
    const $chip      = $(this).closest('.chip');
    const $span      = $chip.children('span');
    const option_id  = $chip.data('id');
    const old_value  = $span.text();
    const was_common = $chip.hasClass('common');

    const used_by = controller.option_used_by(option_id);
    const other_users = used_by.filter(id => id !== Number(_question_id));
    const must_be_common = other_users.length>0;

    const chip_sel = '.chip[id=' + $chip.data('id') + ']';

    _option_editor.edit_option(old_value, was_common, must_be_common, 
      function(value,is_common) {
        if( value === old_value && is_common === was_common ) { return; }

        ce.undo_manager.add_and_exec({
          action:'chip-edit', 
          question_id:_question_id,
          apply(value,is_common) {
            const $chip = $_options.find(chip_sel);
            const $span = $chip.children('span');
            $span.text(value);
            $chip.toggleClass('common',is_common);
            $chip.toggleClass('specific',!is_common);
            controller.update_option(option_id,value,is_common?null:_question_id);
            $(document).trigger('SurveyWasModified');
          },
          redo() {
            controller.select_question(this.question_id);
            setTimeout( () => { this.apply(value,is_common); }, 100 );
          },
          undo() {
            controller.select_question(this.question_id);
            setTimeout( () => { this.apply(old_value,was_common); }, 100 );
          }
        }
      );
    });
  });

  /**
   * Click event handler on the add option (+) button in the option pool
   * Coordinates the creation of a new chip
   */
  $_add_chip.on('click',function(e) {
    _option_editor.new_option( function(value,is_common) {
      const new_id = controller.add_option(value, is_common?null:_question_id);
      const $chip = create_chip(new_id, value, is_common);
      const chip_sel = '.chip[id=' + new_id + ']';
      $_selected.append($chip);
      update_options();

      ce.undo_manager.add({
        action:'new-chip',
        question_id:_question_id,
        redo() {
          controller.select_question(this.question_id);
          setTimeout( () => {
            const $chip = $_options.find(chip_sel);
            animate_chip_move($chip, () => {
              $_selected.append($chip);
              update_options();
            });
          }, 100);
        },
        undo() {
          controller.select_question(this.question_id);
          setTimeout( () => {
            const $chip = $_options.find(chip_sel);
            animate_chip_move($chip, () => {
              insert_chip_alphabetically_in_pool($chip);
              update_options();
            });
          }, 100);
        },
      });
    });
  });

  /**
   * Adds the chip element to the selected options field in the DOM
   *   at the specified position
   * @param {ChipElement} $chip 
   * @param {number} index 
   */
  function insert_selected_chip_at($chip,index)
  {
    if( index >= $_selected.children().length ) {
      $_selected.append($chip)
    } else {
      $chip.insertBefore($_selected.children().eq(index));
    }
  }

  /**
   * Adds the chip element alphabetically within the option pool
   * @param {ChipElement} $chip 
   */
  function insert_chip_alphabetically_in_pool($chip)
  {
    $chip.insertBefore($_add_chip);
    const chips = $_pool.children('.chip').get();

    chips.sort((a,b) => {
      const label_a = $(a).find('span').text().toLowerCase();
      const label_b = $(b).find('span').text().toLowerCase();
      return label_a.localeCompare(label_b);
    });

    $(chips).insertBefore($_add_chip);
  }

  /**
   * Moves a chip in the DOM while animating its visual movement.
   * @param {jQuery} $chip
   * @param {Function} move_fn Performs the DOM move
   */
  function animate_chip_move($chip, move_fn) {
    const chip = $chip[0];
    const first = chip.getBoundingClientRect();
    const old_z = chip.style.zIndex;
    chip.style.zIndex = 1000;

    move_fn();

    const last = chip.getBoundingClientRect();
    const dx = first.left - last.left;
    const dy = first.top - last.top;

    if (dx === 0 && dy === 0) return;

    const animation = chip.animate([
      { transform: `translate(${dx}px, ${dy}px)` },
      { transform: 'translate(0, 0)' }
    ], {
      duration: 500,
      easing: 'cubic-bezier(0.45, 0, 0.55, 1)'
    });
    animation.onfinish = () => { chip.style.xIndex = old_z; };
  }

  self.show = show;
  self.create_chip = create_chip;
  return self;
}


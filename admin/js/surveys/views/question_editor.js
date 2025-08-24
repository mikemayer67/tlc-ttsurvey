import Sortable from '../../../../js/sortable.esm.js';
import { deepCopy, update_character_count, validate_markdown } from '../../utils.js';


function input_error(key,value) 
{
  const len = String(value).trim().length;
  let invalid_char_regex = null;
  let required = false;
  let markdown = false;

  switch(key) {
    case 'wording':
    case 'infotag':
      invalid_char_regex = /([^\p{L}\p{N}\s.,!?;:'"()\-–—_@#%&*/\\\[\]{}<>|=+~`^$])/u;
      required = true;
      break;

    case 'info':
      required = true;
      markdown = true;
      invalid_char_regex = /([^\p{L}\p{N}\s.,!?;:'"()\-–—_@#%&*/\\\[\]{}<>|=+~`^$])/u;
      break;

    case 'description':
      markdown = true;
      break;

    default:
      break;
  }

  if(required) {
    if(len==0) { return 'missing'; }
  }

  if(invalid_char_regex) {
    const invalid_char = value.match(invalid_char_regex);
    if(invalid_char) { return `invalid char (${invalid_char[0]})`; }
  }

  if(markdown) {
    const error = validate_markdown(value);
    if(error) { return error; }
  }

  return '';
}

export default function init(ce,controller)
{
  const _box = $('#editor-frame div.grid.question.editor');

  const _inputs             = _box.find('input');
  const _textareas          = _box.find('textarea');

  const _archive            = _box.children('.archive');
  const _archive_select     = _archive.filter('.value').find('select');

  const _type               = _box.children('.type');
  const _type_value         = _type.filter('.value').find('div.text');
  const _type_select        = _type.filter('.value').find('select');

  const _infotag             = _box.children('.infotag');
  const _infotag_value       = _infotag.find('input');

  const _wording            = _box.children('.wording');
  const _wording_value      = _wording.find('input');

  const _qualifier          = _box.children('.qualifier');
  const _qualifier_value    = _qualifier.find('input');

  const _description        = _box.children('.description');
  const _description_value  = _description.find('textarea');

  const _info               = _box.children('.info');
  const _info_value         = _info.find('textarea');
  const _popup              = _box.children('.popup');
  const _popup_value        = _popup.find('textarea');

  const _options            = _box.children('.options');
  const _primary_selected   = _options.find('.primary.selected');
  const _secondary_selected = _options.find('.secondary.selected');
  const _option_pool        = _box.find('.option.pool');
  const _other              = _options.filter('.other');
  const _other_value        = _other.find('input');

  const _hints              = _box.find('div.hint');

  let _cur_id = null;  // This is the current question ID in the editor
  let _errors = {};
 
  const _show_handlers = {
    INFO: show_info,
    BOOL: show_bool,
    FREETEXT: show_freetext,
    SELECT_ONE: show_select_one,
    SELECT_MULTI: show_select_multi,
  };

  const _primary_selected_sorter = new Sortable( _primary_selected[0], 
    {
      group: {
        name:'primary_selected', 
        put:['option_pool','primary_selected','secondary_selected'],
      },
      animation: 150,
      draggable: '.chip',
      onEnd: handle_option_drag,
    },
  );
  const _option_pool_sorter = new Sortable( _option_pool[1], 
    {
      group: {
        name:'option_pool', 
        put:false,
      },
      animation: 150,
      draggable: '.chip',
      sort: false,
      onEnd: handle_option_drag,
    },
  );
  const _secondary_selected_sorter = new Sortable( _secondary_selected[0], 
    {
      group: {
        name:'secondary_selected', 
        put:['option_pool','primary_selected'],
      },
      animation: 150,
      draggable: '.chip',
      onEnd: handle_option_drag,
    },
  );

  _textareas.on('input change', update_character_count)

  _textareas.filter('.auto-resize').on('input change', function(e) {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
  });

  _box.find('input,textarea')
    .on('input',handle_input)
    .on('blur',handle_input_change)
    .on('change',handle_input_change);

  function handle_input(e) 
  {
    const input = $(this);
    const timer_id = input.data('timer');
    clearTimeout(timer_id);
    input.data('timer', setTimeout( function() {
      input.removeData('timer');
      validate_and_handle_update(input);
    }, 250 ));
  }

  function handle_input_change(e)
  {
    // this is for changes to <input> fields
    const input = $(this);
    const timer_id = input.data('timer') ?? undefined;
    if(timer_id) { // no timer => no changes
      input.removeData('timer');
      validate_and_handle_update(input);
    }
  }

  function handle_change(e)
  {
    // this is for changes to non-<input> fields
    const key      = $(this).data('key');
    const value    = $(this).val();
    handle_update(key,value);
  }

  function handle_update(key,value)
  {
    create_or_update_undo(key,value);
    controller.update_question_data(_cur_id,key,value);
    $(document).trigger('SurveyWasModified');
  }

  function validate_and_handle_update(input)
  {
    const key   = input.data('key');
    const value = input.val();

    validate_input(key,value);
    handle_update(key,value);
  }

  function validate_input(key,value)
  {
    const error = input_error(key,value);

    const span = _box.children('.value.'+key).find('span.error');
    const input = _box.find('.question.'+key);
    if(error) {
      span.text(error);
      _errors[key] = error;
      input.addClass('error');
    } else {
      span.text('');
      delete _errors[key];
      input.removeClass('error');
    }

    const has_error = Object.keys(_errors).length > 0;
    controller.toggle_question_error(_cur_id,has_error);
  }

  function show(id,data)
  {
    _cur_id = id;
    _errors = {};

    // As the list of fields to show depend on question type, we start by hiding
    //   all of the fields and then turning back on those that are needed based on
    //   question type.
    _box.children().hide();
    _type.show();
    _hints.removeClass('locked');
    _type_select.off('change');
    _archive_select.off('change');

    // Now we can customize what is shown based on the question type
    //   We'll handle the case where the type is not specified first
    //   We'll then handle all the other case where the type is specified

    if(data.type) {
      _type_value.text( typeLabels[data.type] ).show();
      _type_select.hide();
      _show_handlers[data.type]?.(data);
    } else {
      show_new(id,data);
    }
  }

  function show_info(data)
  {
    _infotag.show();
    _info.show();

    const infotag = data.infotag || '';
    const info    = data.info    || '';

    _infotag_value.val(infotag).trigger('change');
    _info_value.val(data.info).trigger('change');

    validate_input('infotag', infotag);
    validate_input('info', info);
  }

  function show_bool(data)
  {
    _wording.show();
    _qualifier.show();
    _description.show();
    _popup.show();

    const wording     = data.wording || '';
    const qualifier   = data.qualifier || '';
    const description = data.description || '';
    const popup       = data.popup || '';
    
    _wording_value.val(wording);
    _qualifier_value.val(qualifier);
    _description_value.val(description);
    _popup_value.val(popup);

    validate_input('wording'    , wording);
    validate_input('qualifier'  , qualifier);
    validate_input('description', description);
    validate_input('popup'      , popup);
  }

  function show_freetext(data)
  {
    _wording.show();
    _description.show();
    _popup.show();

    const wording     = data.wording || '';
    const description = data.description || '';
    const popup       = data.popup || '';

    _wording_value.val(wording);
    _description_value.val(description);
    _popup_value.val(popup);

    validate_input('wording'    , wording);
    validate_input('description', description);
    validate_input('popup'      , popup);
  }

  function show_select_one(data)
  {
    _wording.show();
    _qualifier.show();
    _description.show();
    _other.show();
    _popup.show();

    const wording     = data.wording || '';
    const qualifier   = data.qualifier || '';
    const description = data.description || '';
    const other       = data.other || '';
    const popup       = data.popup || '';

    _wording_value.val(wording);
    _qualifier_value.val(qualifier);
    _description_value.val(description);
    _other_value.val(other);
    _popup_value.val(popup);

    validate_input('wording'    , wording);
    validate_input('qualifier'  , qualifier);
    validate_input('description', description);
    validate_input('other'      , other);
    validate_input('popup'      , popup);

    show_options(data.options);
  }

  function show_select_multi(data)
  {
    // currently no difference between select_one and select_multi
    show_select_one(data);
  }

  function show_new(id,data)
  {
    const bullpen = controller.unused_questions();
    if(Object.keys(bullpen).length) {
      _archive_select.find('option:not(:first)').remove();
      Object.entries(bullpen).forEach( ([id,data]) => {
        if(data.type) {
          let wording = data.type === "INFO"
          ? data.infotag || data.info || ''
          : data.wording || '';

          if(wording.length > 64) {
            wording = wording.slice(0,64) + '...';
          }
          _archive_select.append(new Option( wording, id ));
        }
      });
      _archive_select.val('').on('change',id,handle_archive);
      _archive.show();
    }

    _type_value.hide();
    _type_select.val('').on('change',[id,data],handle_type).show();
  }

  function show_options(data)
  {
    populate_options(data);
    _options.show();
    _option_pool.hide();
  }

  function populate_options(data)
  {
    _primary_selected.empty();
    _secondary_selected.empty();
    const all_options = controller.all_options();
    data.forEach( ([id,secondary]) => {
      const label = all_options[id];
      const chip = create_chip(id,label);
      if(secondary) { _secondary_selected.append(chip); } 
      else          { _primary_selected.append(chip); }
    });
    validate_options();
  }

  function update_options(options)
  {
    controller.update_question_data(_cur_id,'options',options); 
    populate_options(options);
    update_option_pools();
    $(document).trigger('SurveyWasModified');
  }

  function validate_options()
  {
    const num_primary = _primary_selected.children().length;

    const span = _options.find('.primary.error');
    if(num_primary>0) {
      delete _errors.primary;
      span.text('');
    } else {
      _errors.primary = 'needs-primary-option';
      span.text('missing');
    }
    
    const has_error = Object.keys(_errors).length > 0;
    controller.toggle_question_error(_cur_id,has_error);
  }

  function create_chip(id,label) {
    const chip = $("<div>").addClass('chip').data('id',id).attr('id',id);
    const span = $("<span>").text(label);
    const close = $("<button class='option' type='button'>x</button>");
    return chip.append(span).append(close);
  }

  //
  // option pool
  //

  _options.on('mousedown', '.selected,.pool', toggle_option_pool);
  _options.on('click','.selected .chip button',handle_close_chip);
  _options.on('dblclick','.chip',handle_edit_chip);

  function toggle_option_pool(e) {
    // We want to yield to SortableJS if the mouse down occured in a chip element.
    if( $(e.target).closest('.chip').length > 0) { return; }
    if( $(e.target).closest('.add.option').length > 0) { return; }

    if(_option_pool.is(':visible')) {
      _option_pool.hide();
    } else {
      populate_option_pool(_option_pool);
      _option_pool.show();
    }
  }

  function populate_option_pool(pool) {
    const all_options = controller.all_options();

    let in_use = selected_options();
    in_use = in_use.map((x) => Number(x[0]));
    in_use = new Set(in_use);

    const add_button = pool.find('button.add.option');
    pool.find('.chip').remove();
    Object.entries(all_options).forEach( ([id,label]) => {
      if( ! in_use.has(Number(id)) ) {
        create_chip(id,label).insertBefore(add_button);
      }
    });
  }

  function update_option_pools() {
    if(_option_pool.is(':visible'))   { populate_option_pool(_option_pool);   }
  }

  function selected_options() {
    const rval = [];
    _primary_selected.children('.chip').each( function(i) { 
      const id = $(this).data('id');
      rval.push([ id, 0]);
    });
    _secondary_selected.children('.chip').each( function(i) { 
      const id = $(this).data('id');
      rval.push([ id, 1]);
    });
    return rval;
  }

  function handle_close_chip(e) {
    const chip = $(this).closest('.chip');
    chip.remove(); 
    handle_option_change();
  }

  function handle_option_change() {
    const old_options = controller.cur_question_data(_cur_id,'options');
    const new_options = selected_options();
    ce.undo_manager.add({
      action:'option-change',
      question_id:_cur_id,
      undo() { 
        controller.select_question(this.question_id);
        setTimeout( function() { update_options(old_options) }, 100);
      },
      redo() { 
        controller.select_question(this.question_id);
        setTimeout( function() { update_options(new_options) }, 100);
      },
    });
    
    controller.update_question_data(_cur_id,'options',new_options); 
    update_option_pools();
    validate_options();
    $(document).trigger('SurveyWasModified');
  }

  function handle_edit_chip(e) {
    const chip = $(this).closest('.chip');
    const span = chip.children('span');
    const old_value = span.text();
    const raw_value = prompt('Edit option',old_value);
    if( raw_value === null ) { return; }

    const chip_id = chip.data('id');

    const new_value = raw_value.trim();
    if(new_value && (old_value !== new_value)) {
      function apply_edit_chip(value) {
        const all_chips = _options.find('div.chip');
        const chips = all_chips.filter('[id='+chip_id+']');
        const spans = chips.children('span');
        spans.text(value);
        controller.update_option(chip_id,value);
        update_option_pools();
        $(document).trigger('SurveyWasModified');
      }
      ce.undo_manager.add_and_exec( {
        action:'chip_edit',
        question_id:_cur_id,
        redo() { 
          controller.select_question(this.question_id); 
          setTimeout( function() {apply_edit_chip(new_value)}, 100)
        },
        undo() { 
          controller.select_question(this.question_id);
          setTimeout( function() {apply_edit_chip(old_value)}, 100);
        },
      });
    }
  }

  function handle_option_drag(e) {
    console.log('handle option drag');
    handle_option_change();
  }

  _option_pool.find('button.add.option').on('click', function(e) {
    const new_option = prompt('New option').trim();
    if(new_option) {
      const new_id = controller.add_option(new_option);
      update_option_pools();
    }
  });

  //
  // New question handlers
  //

  function handle_archive(e)
  {
    const item = $(this);
    const new_id = item.val();
    const old_id = e.data;
    ce.undo_manager.add_and_exec({
      action:'unarchive-question',
      redo() {
        const data = controller.replace_question(old_id,new_id);
        show(new_id,data);
      },
      undo() {
        const data = controller.replace_question(new_id,old_id);
        show(old_id,data);
      },
    });
  }

  function handle_type(e)
  {
    const item = $(this);
    const type = item.val();
    const [id,data] = e.data;
    ce.undo_manager.add_and_exec({
      action:'set-question-type',
      redo() {
        controller.update_question_type(id,type);
        show(id,data);
      },
      undo() {
        controller.update_question_type(id,null,type);
        show(id,data);
      },
    });
  }
 
  //---------------------------------------------------------------------------------------
  // The following function handles the creation or updating of undo/redo actions
  //   associated with changes to section data.  As we don't want to create an action
  //   for every keystroke in an <input> fieled, these changes are accumulated in a
  //   single undo action.
  //
  // The following conditions must apply in order to update the action rather than
  //   creating a new action:
  //   - The redo stack must be empty
  //   - The undo action in question must be on the top of the undo stack
  //   - The undo action must be for the same input field (*)
  //   - The undo action must never have been on the redo stack
  //   These rules ensure that only changes that happen without any intevening 
  //   undoable actions are accumulated.
  //
  // The first two of these rules are handled automatically by using the undo manager's
  //    head() method.  This will return null if there is anything on the redo stack.
  //
  // * In an earlier implementation of the undo actions for section input changes, an
  //   attempt was made to accumulate all changes for a given section into a single
  //   action regardless of the particular input field.   This became problematic
  //   over time and was abandoned.
  //---------------------------------------------------------------------------------------

  function create_or_update_undo(key,value)
  {
    const cur_undo = ce.undo_manager.head();

    const can_accumulate = (
      ( cur_undo?.action === 'question-input-change' ) &&
      ( cur_undo?.question_id === _cur_id ) &&
      ( cur_undo?.key === key ) &&
      ( cur_undo?.redone !== true )
    );

    if( can_accumulate ) {
      // Modify the current undo action rather than creating a new one.
      cur_undo.new_value = value

      // But, if the new value is the same as the original value, the user has manually
      //   performed the undo. Pop the action off the undo stack.
      if(cur_undo.new_value === cur_undo.old_value) {
        ce.undo_manager.pop(cur_undo);
      }
    }
    else {
      // Accumulation not allowed, create a new undo action

      function apply_action(question_id, value)
      {
        validate_input(key,value);
        _box.find('.question.'+key).val(value);
//        controller.update_question_error(question_id,key,value,error);
        controller.update_question_data(question_id,key,value);
        $(document).trigger('SurveyWasModified');
      }

      ce.undo_manager.add({
        action: 'question-input-change',
        question_id: _cur_id,
        key: key,
        old_value: controller.cur_question_data(_cur_id,key),
        new_value: value,
        redone: false,
        undo() {
          controller.select_question(this.question_id);
          setTimeout(() => { apply_action(this.question_id, this.old_value); }, 100);
        },
        redo() {
          this.redone = true;
          controller.select_question(this.question_id);
          setTimeout(() => { apply_action(this.question_id, this.new_value); }, 100);
        }
      });
    }
  }

  return {
    show:show,
  };

}

import { validate_markdown } from '../../utils.js';

// The architecture of the group_editor mirrors that of the section_editor even though
//   groups only hava a single attribute (name).
// This was done for consistency with the section and question editors and to
//   make extension easier if any other group attributes are added.

function input_error(key,value) 
{
  const len = String(value).trim().length;
  var invalid_char_regex = null;
  let required = false;
  let markdown = false;

  switch(key) {
    case 'name':
      required = true;
      invalid_char_regex = /([^\p{L}\p{N}\s.,!?;:'"()\-–—_@#%&*/\\\[\]{}<>|=+~`^$])/u;
      break;

    default:
      break;
  }

  if(required) {
    if(len==0) { return 'missing'; } 
  }
  
  if(invalid_char_regex) {
    const invalid_char = value.match(invalid_char_regex);
    if(invalid_char) { return `invalid char (${invalid_char})`; }
  }

  if(markdown) {
    const error = validate_markdown(value);
    if(error) { return error; }
  }

  return '';
}

export default function init(ce,controller)
{
  const _frame             = $('#editor-frame');
  const _box               = _frame.find('div.grid.group.editor');

  const _name              = _box.children('.name');
  const _name_value        = _name.find('input');

  const _hints             = _box.find('div.hint');

  let _cur_id = null;  // This is the current group ID displayed in the editor
  let _errors = {};

  _box.find('input,textarea').on('input',handle_input).on('blur',handle_input_change);
  _box.find('select').on('change', handle_change);

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
    if(timer_id) {
      input.removeData('timer');
      validate_and_handle_update(input);
    }
  }

  function handle_change(e)
  {
    // this is for changes to non-<input> fields
    const key   = $(this).data('key');
    const type  = $(this).data('type');
    let   value = $(this).val();

    if( type === 'int' ) { value = parseInt(value,10); }
    handle_update(key,value);
  }

  function handle_update(key,value)
  {
    create_or_update_undo(key,value);
    controller.update_group_data(_cur_id,key,value);
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
    const input = _box.find('.group.'+key);
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
    controller.toggle_content_error('group',_cur_id,has_error);
  }


  function show(id,data,options)
  {
    _cur_id = id;
    _errors = {};

    _frame.find('div.content-header').text('Group Editor');

    const name = data.name || '';
    _name_value.val(name);
    validate_input('name', name);
    _hints.removeClass('locked');
  }

  //---------------------------------------------------------------------------------------
  // The following function handles the creation or updating of undo/redo actions
  //   associated with changes to group data.  As we don't want to create an action
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
  //---------------------------------------------------------------------------------------

  function create_or_update_undo(key,value)
  {
    const cur_undo = ce.undo_manager.head();

    const can_accumulate = (
      ( cur_undo?.action === 'group-input-change' ) &&
      ( cur_undo?.group_id === _cur_id ) &&
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

      function apply_action(group_id, value)
      {
        controller.select_group(group_id);
        validate_input(key,value);
        _box.find('.group.'+key).val(value);
        controller.update_group_data(group_id,key,value);
        $(document).trigger('SurveyWasModified');
      }

      ce.undo_manager.add({
        action: 'group-input-change',
        group_id: _cur_id,
        key: key,
        old_value: controller.cur_group_data(_cur_id,key),
        new_value: value,
        redone: false,
        undo() {
          apply_action(this.group_id, this.old_value);
        },
        redo() {
          this.redone = true;
          apply_action(this.group_id, this.new_value);
        }
      });
    }
  }

  return { show };
}


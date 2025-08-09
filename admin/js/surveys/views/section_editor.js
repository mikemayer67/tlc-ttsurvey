import { deepCopy, update_character_count } from '../../utils.js';


function input_error(key,value) 
{
  const len = String(value).trim().length;
  var invalid_char_regex = null;

  switch(key) {
    case 'name':
      if(len==0) { return 'missing';   } 
      if(len<4)  { return 'too short'; }
      invalid_char_regex = new RegExp("[^\\w\\s.,&-]");
      break;

    case 'feedback':
      if(len>0 && len<4) { return 'too short'; }
      invalid_char_regex = new RegExp("[^\\w\\s.,;:&-?]");
      break;

    default:
      return '';
  }

  const invalid_char = value.match(invalid_char_regex);
  if(invalid_char) { return `invalid char (${invalid_char})`; }

  return '';
}

export default function init(ce,controller)
{
  const _box = $('#editor-frame div.grid.section.editor');

  const _name              = _box.children('.name');
  const _name_value        = _name.find('input');

  const _labeled           = _box.children('.labeled');
  const _labeled_value     = _labeled.find('select');

  const _description       = _box.children('.description');
  const _description_value = _description.find('textarea');

  const _feedback          = _box.children('.feedback');
  const _feedback_value    = _feedback.find('input');

  const _hints             = _box.find('div.hint');
  const _fields            = _box.find('input,textarea,select');

  let _cur_id = null;  // This is the current section ID displayed in the editor
  let _errors = {};

  _description_value.on('input change', update_character_count);
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
    const key      = $(this).data('key');
    const value    = $(this).val();
    handle_update(key,value);
  }

  function handle_update(key,value)
  {
    create_or_update_undo(key,value);
    controller.update_section_data(_cur_id,key,value);
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
    if(error) { 
      span.text(error);
      _errors[key] = error;
    } else {
      span.text('');
      delete _errors[key];
    }

    const has_error = Object.keys(_errors).length > 0;
    controller.toggle_section_error(_cur_id,has_error);
  }


  function show(id,data,options)
  {
    _cur_id = id;
    _errors = {};

    const name        = data.name || '';
    const labeled     = data.labeled ? 1 : 0;
    const description = data.description || '';
    const feedback    = data.feedback || '';

    _name_value.val(name);
    _labeled_value.val(labeled);
    _description_value.val(description).trigger('change');
    _feedback_value.val(feedback);

    validate_input('name',       name);
    validate_input('description',description);
    validate_input('feedback',   feedback);

    _hints.removeClass('locked');
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
      ( cur_undo?.action === 'section-input-change' ) &&
      ( cur_undo?.section_id === _cur_id ) &&
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

      function apply_action(section_id, value)
      {
        controller.select_section(section_id);
        validate_input(key,value);
        controller.update_section_data(section_id,key,value);
        $(document).trigger('SurveyWasModified');
      }

      ce.undo_manager.add({
        action: 'section-input-change',
        section_id: _cur_id,
        key: key,
        old_value: controller.cur_section_data(_cur_id,key),
        new_value: value,
        redone: false,
        undo() {
          apply_action(this.section_id, this.old_value);
        },
        redo() {
          this.redone = true;
          apply_action(this.section_id, this.new_value);
        }
      });
    }
  }

  return {
    show:show,
  };
}


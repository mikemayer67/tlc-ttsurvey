import { deepCopy, update_character_count } from '../utils.js';


function validate_input(key,value) 
{
  const len = String(value).trim().length;
  var invalid_char_regex = null;

  switch(key) {
    case 'name':
      if(len==0) { return 'missing';   } 
      if(len<4)  { return 'too short'; }
      invalid_char_regex = new RegExp("[^\\w\\s&-]");
      break;
    case 'feedback':
      if(len>0 && len<4) { return 'too short'; }
      invalid_char_regex = new RegExp("[^\\w\\s.,;:&-?]");
      break;
  }

  if(invalid_char_regex) {
    const invalid_char = value.match(invalid_char_regex);
    if(invalid_char) { return `invalid char (${invalid_char})`; }
  }

  return '';
}

export default function section_editor(ce,controller)
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

  let _cur_id    = null;
  let _cur_undo  = null;

  _description_value.on('input change', update_character_count);
  _box.find('input,textarea').on('input',handle_input).on('blur',handle_input_blur);
  _box.find('select').on('change', handle_change);

  function handle_input(e) 
  {
    const input = $(this);
    const timer_id = input.data('timer');
    clearTimeout(timer_id);
    input.data('timer', setTimeout( function() {
      input.removeData('timer');
      validate_and_handle_update(input);
    }, 500 ));
  }

  function handle_input_blur(e)
  {
    const input = $(this);
    const timer_id = input.data('timer') ?? undefined;
    if(timer_id) {
      input.removeData('timer');
      validate_and_handle_update(input);
    }
  }

  function validate_and_handle_update(input)
  {
    const key   = input.data('key');
    const value = input.val();
    const error = validate_input(key,value);
    _box.children('.value.'+key).find('span.error').text(error?? '');
    controller.update_section_error(_cur_id,key,value,error);
    handle_update(key,value);
  }

  function handle_change(e)
  {
    const key      = $(this).data('key');
    const value    = $(this).val();
    handle_update(key,value);
  }

  function handle_update(key,value)
  {
    _cur_undo.update(key,value);
    controller.update_section_data(_cur_id,key,value);
    $(document).trigger('SurveyWasModified');
  }

  function show(id,data)
  {
    // Determine if we can continue with the current undo action.
    //   Can do so only if it is the newest action on the undo stack.
    //   Otherwise, create a new undo action
    if( ! _cur_undo?.can_continue(id) ) { create_undo(id,data,controller); }

    _cur_id = id;

    _name_value.val(data.name || '');
    _labeled_value.val(data.labeled ? 1 : 0);
    _description_value.val(data.description || '').trigger('change');
    _feedback_value.val(data.feedback || '')

    _hints.removeClass('locked');
  }

  function create_undo(id,data,controller)
  {
    // This undo action accumulates changes to the section editor so that triggering
    //   undo or redo will treat the entire sequence of edits as a single atomic action.
    //  - When the undo manager triggers an undo, it moves the action to the redo stack.
    //      If additional edits occur after an undo, we want to start a new undo action
    //      rather than accumulating them on the current action (which has been undone)
    //      We do not want to modify the current action as this would break its future
    //      redo capability.
    //  - When the redo manager triggers a redo, it moves the action back to the undo stack.
    //      If additional edits occur after a redo, we want to continue the accumulation
    //      of edits. Simply reconnect our current undo action pointer to the action
    //      that was just moved back to the undo stack.  The following scenario illustrates
    //      a series of edits and undo/redo actions that comes back to the original state:
    //        [orig] > (edits) > undo > redo > (edits) > undo > [orig]
    //      An alternative design which was rejected was to start a new undo action
    //        after a redo to capture the next set of edits.  This would require multiple
    //        undos simply because the user did an undo/redo sequence in the middle of
    //        their edits:
    //          [orig] > (edits) > undo > redo > (edits) > undo > undo > [orig].
    //        While this is a plausible implementation, I opted for the first approach.
    //  - Finally, this undo action will not be added to the undo manager until the
    //      first edit actually occurs. There is no need to require that the user
    //      hit the undo button multiple times if there is nothing to undo. The
    //      first edit is identified by the lack of a new_values property.
    
    function apply_action(section_id, values)
    {
      if(section_id !== _cur_id) {
        controller.select_section(section_id);
      }
      Object.entries(values).forEach(([key,value]) => {
        const input = _box.find('.section.'+key).val(value);
        const error = validate_input(key,value);
        _box.children('.value.'+key).find('span.error').text(error?? '');
        controller.update_section_error(_cur_id,key,value,error);
        controller.update_section_data(_cur_id,key,value);
        $(document).trigger('SurveyWasModified');
      });
    }

    _cur_undo = {
      section_id:id,
      orig_values: {
        name:data.name || '',
        labeled:data.labeled ?? 0,
        description:data.description || '',
        feedback:data.feedback || '',
      },
      can_continue(id) {
        return ce.undo_manager.isCurrent(this) && (id === this.section_id);
      },
      undo() {
        apply_action(this.section_id, this.orig_values);
        create_undo(id,data,controller);
      },
      redo() { 
        apply_action(this.section_id, this.new_values);
        _cur_undo = this;
      },
      update(key,value) {
        if(!('new_values' in this)) {
          this.new_values = deepCopy(this.orig_values);
          ce.undo_manager.add(this);
        }
        this.new_values[key] = value;
      }
    };
  }

  return {
    show:show,
  };
}



import { deepCopy, update_character_count, validate_markdown } from '../../utils.js';
import ui_config         from './ui_config.js';
import option_controller from './options.js'

function input_error(key,value) 
{
  const len = String(value).trim().length;
  let invalid_char_regex = null;
  let required = false;
  let markdown = false;

  const common_invalid_regex = /([^\p{L}\p{N}\s.,!?;:'"()\-–—_@#%&*/\\\[\]{}<>|=+~`^$])/u;

  switch(key) {
    case 'wording':
      invalid_char_regex = common_invalid_regex;
      required = true;
      break;

    case 'info':
      required = true;
      markdown = true;
      invalid_char_regex = common_invalid_regex;
      break;

    case 'infotag':
    case 'other':
      invalid_char_regex = common_invalid_regex;
      break;

    case 'intro':
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

  const _inputs          = _box.find('input');
  const _textareas       = _box.find('textarea');

  const _archive         = _box.children('.archive');
  const _archive_select  = _archive.filter('.value').find('select');

  const _type            = _box.children('.type');
  const _type_value      = _type.filter('.value').find('div.text');
  const _type_select     = _type.filter('.value').find('select');

  const _infotag         = _box.children('.infotag');
  const _infotag_value   = _infotag.find('input');

  const _wording         = _box.children('.wording');
  const _wording_value   = _wording.find('input');

  const _layout          = _box.children('.layout');
  const _layout_value    = _layout.find('select');

  const _qualifier       = _box.children('.qualifier');
  const _qualifier_value = _qualifier.find('input');

  const _intro           = _box.children('.intro');
  const _intro_value     = _intro.find('textarea');

  const _grouped         = _box.children('.grouped');
  const _grouped_value   = _grouped.find('select');

  const _info            = _box.children('.info');
  const _info_value      = _info.find('textarea');
  const _popup           = _box.children('.popup');
  const _popup_value     = _popup.find('textarea');

  const _other           = _box.children('.other');
  const _other_flag      = _other.find('input.other_flag');
  const _other_value     = _other.find('input.other');

  const _hints           = _box.find('div.hint');

  let _cur_id = null;  // This is the current question ID in the editor

  let _errors = {};
  function set_error(key,msg)
  {
    _errors[key] = msg;
    controller.toggle_question_error(_cur_id,true);
  }
  function clear_error(key) {
    delete _errors[key];
    const has_error = Object.keys(_errors).length > 0;
    controller.toggle_question_error(_cur_id,has_error);
  }
  function reset_errors() {
    _errors = {}
    controller.toggle_question_error(_cur_id,false);
  }

  const self = {
    box: _box,
    set_error:   set_error,
    clear_error: clear_error,
  };

  const _options = option_controller(ce,controller,self);


  const _show_handlers = {
    INFO: show_info,
    BOOL: show_bool,
    FREETEXT: show_freetext,
    SELECT_ONE: show_select_one,
    SELECT_MULTI: show_select_multi,
  };

  _textareas.on('input change', update_character_count);

  _textareas.filter('.auto-resize').on('input change', function(e) {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
  });

  _box.find('input,textarea').not('[type=checkbox]')
    .on('input',handle_input)
    .on('blur',handle_input_change)
    .on('change',handle_input_change);

  _box.find('input[type=checkbox]')
    .on('blur',handle_checkbox)
    .on('change',handle_checkbox);

  _layout_value.on('change', handle_select_change);
  _grouped_value.on('change', handle_select_change);

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
      input.addClass('error');
      set_error(key,error);
    } else {
      span.text('');
      input.removeClass('error');
      clear_error(key);
    }
  }

  function handle_checkbox(e)
  { 
    const input   = $(this);
    const key     = input.data('key');
    const checked = input.prop('checked');

    create_checkbox_undo(key,checked);
    controller.update_question_data(_cur_id,key,checked);
    $(document).trigger('SurveyWasModified');
  }

  function handle_select_change(e)
  {
    const tgt           = $(e.currentTarget);
    const key           = tgt.data('key');
    const new_value     = $(this).val();
    const old_value     = controller.cur_question_data(_cur_id,key);

    create_select_undo(new_value,old_value,tgt);
    ce.controller.update_question_data(_cur_id, key, new_value);
    $(document).trigger('SurveyWasModified');
  }

  function show(id,data)
  {
    _cur_id = id;

    reset_errors();

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

    if(data.type) 
    {
      _type_value.text( typeLabels[data.type] ).show();
      _type_select.hide();

      _grouped_value.empty();
      ["NO","YES"].forEach( (value) => {
        const label = ui_config.grouped.label[value];
        const opt   = $('<option></option>').attr('value',value).text(label);
        _grouped_value.append(opt);
      });

      _show_handlers[data.type]?.(data);
    } 
    else 
    {
      show_new(id,data);
    }
  }

  function show_info(data)
  {
    _infotag.show();
    _info.show();
    _grouped.show();

    const infotag = data.infotag || '';
    const info    = data.info    || '';
    const grouped = data.grouped || 'NO';

    _grouped_value.empty();
    ["NO","YES","BOXED"].forEach( (value) => {
      const label = ui_config.grouped.info_label[value];
      const opt   = $('<option></option>').attr('value',value).text(label);
      _grouped_value.append(opt);
    });

    _infotag_value.val(infotag).trigger('change');
    _info_value.val(data.info).trigger('change');
    _grouped_value.val(grouped);

    validate_input('infotag', infotag);
    validate_input('info', info);
  }

  function show_bool(data)
  {
    _wording.show();
    _layout.show();
    _qualifier.show();
    _intro.show();
    const grouped = data.grouped || 0;
    _grouped.show();
    _popup.show();

    _layout_value.empty();
    ['LEFT','RIGHT'].forEach( (key) => {
      const label = ui_config.layout.bool_label[key];
      const opt   = $('<option></option>').attr('value',key).text(label);
      _layout_value.append(opt);
    });

    const wording   = data.wording || '';
    const layout    = data.layout || ui_config.layout.bool_default;
    const qualifier = data.qualifier || '';
    const intro     = data.intro || '';
    const popup     = data.popup || '';

    _wording_value.val(wording);
    _layout_value.val(layout);
    _qualifier_value.val(qualifier);
    _intro_value.val(intro);
    _grouped_value.val(grouped);
    _popup_value.val(popup);

    validate_input('wording'  , wording);
    validate_input('qualifier', qualifier);
    validate_input('intro'    , intro);
    validate_input('popup'    , popup);
  }

  function show_freetext(data)
  {
    _wording.show();
    _intro.show();
    _grouped.show();
    _popup.show();

    const wording = data.wording || '';
    const intro   = data.intro || '';
    const grouped = data.grouped || 0;
    const popup   = data.popup || '';

    _wording_value.val(wording);
    _intro_value.val(intro);
    _grouped_value.val(grouped);
    _popup_value.val(popup);

    validate_input('wording', wording);
    validate_input('intro'  , intro);
    validate_input('popup'  , popup);
  }

  function show_select_one(data)
  {
    _wording.show();
    _layout.show();
    _qualifier.show();
    _intro.show();
    _grouped.show();
    _other.show();
    _popup.show();

    _layout_value.empty();
    ['ROW','LCOL','RCOL'].forEach( (key) => {
      const label = ui_config.layout.select_label[key];
      const opt   = $('<option></option>').attr('value',key).text(label);
      _layout_value.append(opt);
    });

    const wording    = data.wording || '';
    const layout     = data.layout || ui_config.layout.select_default;
    const qualifier  = data.qualifier || '';
    const intro      = data.intro || '';
    const grouped = data.grouped || 0;
    const other_flag = data.other_flag || false;
    const other      = data.other || '';
    const popup      = data.popup || '';

    _wording_value.val(wording);
    _layout_value.val(layout);
    _qualifier_value.val(qualifier);
    _intro_value.val(intro);
    _grouped_value.val(grouped);
    _other_flag.prop('checked',other_flag);
    _other_value.val(other);
    _popup_value.val(popup);

    validate_input('wording'    , wording);
    validate_input('qualifier'  , qualifier);
    validate_input('intro'      , intro);
    validate_input('popup'      , popup);
    if(other_flag) {
      validate_input('other'    , other);
    }

    _options.show(data);
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
  //   associated with changes to question data.  As we don't want to create an action
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
  // * In an earlier implementation of the undo actions for question input changes, an
  //   attempt was made to accumulate all changes for a given question into a single
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
          setTimeout(() => { 
            apply_action(this.question_id, this.old_value); 
          }, 100);
        },
        redo() {
          this.redone = true;
          controller.select_question(this.question_id);
          setTimeout(() => { 
            apply_action(this.question_id, this.new_value); 
          }, 100);
        }
      });
    }
  }

  //---------------------------------------------------------------------------------------
  // The following functions handles the creation or updating of undo/redo actions
  //   associated with changes to question checkboxees and/or select inputs.
  //
  // The issue here deals with the scenario where the user repeatedly toggles between
  //   two possible values more than two times in a row, e.g. on, off, on, off, on, off, on.
  //   Do we really want the undo manager to back through all of these? That will
  //   get really frustrating for the user as they traverse the undo stack. Instead,
  //   we want to collapse this, e.g.:
  //     on  ->  off
  //     on, off -> on, off
  //     on, off, on -> off
  //     on, off, on, off -> on, off
  //     on, off, on, off, on -> off
  //     on, off, on, off, on, off -> on, off
  //
  // Add a new undo action if any of the following is true:
  //   - the top of the undo stack has a different action type
  //   - the top of the undo stack is not for the current question
  //   - the top of the undo stack does not undo the prior action
  // Otherwise, simply pop the last action off the undo stack (and clear redo stack)
  //
  //---------------------------------------------------------------------------------------

  function create_checkbox_undo(key,value)
  {
    const cur_undo = ce.undo_manager.head();

    const reverts_prior = (
      ( cur_undo?.action === 'toggle-checkbox' ) &&
      ( cur_undo?.question_id === _cur_id ) && 
      ( cur_undo?.key === key )
    );

    if( reverts_prior && (cur_undo?.reverts_prior) ) {
      ce.undo_manager.pop(cur_undo);
      return;
    }

    function apply_action(question_id, value)
    {
      _box.find('.question.'+key).prop('checked',value);
      controller.update_question_data(question_id,key,value);
      $(document).trigger('SurveyWasModified');
    }

    ce.undo_manager.add({
      action: 'toggle-checkbox',
      question_id: _cur_id,
      key: key,
      new_value: value,
      reverts_prior: reverts_prior,
      undo() {
        controller.select_question(this.question_id);
        setTimeout(() => { 
          apply_action(this.question_id, !this.new_value); 
        }, 100);
      },
      redo() {
        controller.select_question(this.question_id);
        setTimeout(() => { 
          apply_action(this.question_id, this.new_value); 
        }, 100);
      }
    });
  }

  function create_select_undo(new_value, old_value, select_ce)
  {
    const key = select_ce.data('key');

    const cur_undo   = ce.undo_manager.head();
    const action_key = 'change-' + key;

    const reverts_prior = ( cur_undo &&
      ( cur_undo.action === action_key ) &&
      ( cur_undo.question_id === _cur_id   ) && 
      ( cur_undo.old_value === new_value )
    );

    if( reverts_prior && (cur_undo?.reverts_prior) ) {
      ce.undo_manager.pop(cur_undo);
      return;
    }

    ce.undo_manager.add({
      action: action_key,
      question_id: _cur_id,
      new_value: new_value,
      old_value: old_value,
      reverts_prior: reverts_prior,
      undo() {
        setTimeout(() => { 
          controller.select_question(this.question_id);
          select_ce.val(this.old_value);
          controller.update_question_data(this.question_id,key,this.old_value);
          $(document).trigger('SurveyWasModified');
        }, 100);
      },
      redo() {
        controller.select_question(this.question_id);
        setTimeout(() => { 
          controller.select_question(this.question_id);
          select_ce.val(this.new_value);
          controller.update_question_data(this.question_id,key,this.new_value);

          $(document).trigger('SurveyWasModified');
        }, 100);
      }
    });
  }

  // Finally, return the question editor public interface 
  //   (which is rather small considering all tht happens internally)

  return {
    show:show,
  };

}

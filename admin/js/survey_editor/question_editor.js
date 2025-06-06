import { deepCopy, update_character_count } from '../utils.js';


function validate_input(key,value) 
{
  const len = String(value).trim().length;
  var invalid_char_regex = null;

  alert('flesh out validate_input in question_editor.js');

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

  return undefined;
}




export default function question_editor(ce,controller)
{
  const _box = $('#editor-frame div.grid.question.editor');

  const _type              = _box.children('.type');
  const _type_value        = _type.filter('.value').find('div.text');
  const _type_select       = _type.filter('.value').find('select');

  const _wording           = _box.children('.wording');
  const _wording_value     = _wording.find('input');

  const _qualifier         = _box.children('.qualifier');
  const _qualifier_value   = _qualifier.find('input');

  const _description       = _box.children('.description');
  const _description_value   = _description.find('textarea');

  const _info              = _box.children('.info');
  const _info_label        = _info.filter('.label');
  const _info_value        = _info.find('textarea');
  const _info_hint         = _info.find('.hint > div');
  const _info_hint_info    = _info_hint.filter('.info-block');
  const _info_hint_other   = _info_hint.filter('.other-type');

  const _options           = _box.children('.options');
  const _primary           = _options.filter('.primary');
  const _secondary         = _options.filter('.secondary');
  const _other             = _options.filter('.other');

  const _hints             = _box.find('div.hint');

  let _cur_id   = null;
  let _cur_undo = null;

  function show(id,data)
  {
    // As the list of fields to show depend on question type, we start by hiding
    //   all of the fields and then turning back on those that are needed based on
    //   question type.
    _box.children().hide();
    _type.show();
    _hints.removeClass('locked');
    _type_select.off('change');

    // The info field actually has a different interpretation based on if it's
    //   a real question or an information block.  We'll assume it's a real
    //   question for now and modify it for an information block if necessary
    _info_label.text('Popup Hint:');
    _info_hint.hide();

    // Now we can customize what is shown based on the question type
    //   We'll handle the case where the type is not specified first
    //   We'll then handle all the other case where the type is specified

    if(!data.type) {
      _type_value.hide();
      _type_select.val('').on('change',[id,data],handle_type).show();
      return;
    }

    _type_value.text( typeLabels[data.type] ).show();
    _type_select.hide();
  }

  function handle_type(e)
  {
    const item = $(this);
    const type = item.val();
    const [id,data] = e.data;
    ce.undo_manager.add_and_exec({
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

  return {
    show:show,
  };
}



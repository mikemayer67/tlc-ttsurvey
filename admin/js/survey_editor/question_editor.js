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

  return '';
}




export default function question_editor(ce,controller)
{
  const _box = $('#editor-frame div.grid.question.editor');

  const _archive            = _box.children('.archive');
  const _archive_select     = _archive.filter('.value').find('select');

  const _type               = _box.children('.type');
  const _type_value         = _type.filter('.value').find('div.text');
  const _type_select        = _type.filter('.value').find('select');

  const _wording            = _box.children('.wording');
  const _wording_value      = _wording.find('input');

  const _qualifier          = _box.children('.qualifier');
  const _qualifier_value    = _qualifier.find('input');

  const _description        = _box.children('.description');
  const _description_value  = _description.find('textarea');

  const _info               = _box.children('.info');
  const _info_label         = _info.filter('.label').find('span');
  const _info_value         = _info.find('textarea');
  const _info_maxlen        = _info.find('.char-count span.max');
  const _info_hint          = _info.find('.hint > div');
  const _info_hint_info     = _info_hint.filter('.info-block');
  const _info_hint_other    = _info_hint.filter('.other-type');

  const _options            = _box.children('.options');
  const _primary            = _options.filter('.primary');
  const _primary_selected   = _primary.find('.selected');
  const _primary_pool       = _primary.find('.pool');
  const _secondary          = _options.filter('.secondary');
  const _secondary_selected = _secondary.find('.selected');
  const _secondary_pool     = _secondary.find('.pool');
  const _other              = _options.filter('.other');

  const _hints              = _box.find('div.hint');

  let _cur_id   = null;
  let _cur_undo = null;

  _primary.find(  '.selected,.pool').on('mousedown', toggle_option_pool);
  _secondary.find('.selected,.pool').on('mousedown', toggle_option_pool);

  function show(id,data)
  {
    // As the list of fields to show depend on question type, we start by hiding
    //   all of the fields and then turning back on those that are needed based on
    //   question type.
    _box.children().hide();
    _type.show();
    _hints.removeClass('locked');
    _type_select.off('change');
    _archive_select.off('change');

    // The info field actually has a different interpretation based on if it's
    //   a real question or an information block.  We'll assume it's a real
    //   question for now and modify it for an information block if necessary
    _info_label.text('Popup Hint:');
    _info_hint.hide();
    const maxlen_other = _info_value.data('maxlen-other');
    _info_maxlen.text(maxlen_other);
    _info_value.attr('maxlength',maxlen_other);

    // Now we can customize what is shown based on the question type
    //   We'll handle the case where the type is not specified first
    //   We'll then handle all the other case where the type is specified

    if(!data.type) {
      show_new(id,data);
      return;
    }

    _type_value.text( typeLabels[data.type] ).show();
    _type_select.hide();

    switch(data.type) {
      case 'INFO': {
        const maxlen_info = _info_value.data('maxlen-info');
        _info.show();
        _info_label.text('Info Text:');
        _info_value.text(data.info || '');
        _info_maxlen.text(maxlen_info);
        _info_value.attr('maxlength',maxlen_info);
        _info_hint_info.show();
        break;
      }
      case 'BOOL': {
        _wording.show();
        _wording_value.val(data.wording || '');
        _qualifier.show();
        _qualifier_value.val(data.qualifier || '');
        _description.show();
        _description_value.val(data.description || '');
        _info.show();
        _info_value.val(data.info || '');
        _info_hint_other.show();
        break;
      }
      case 'FREETEXT': {
        _wording.show();
        _wording_value.val(data.wording || '');
        _description.show();
        _description_value.val(data.description || '');
        _info.show();
        _info_value.val(data.info || '');
        _info_hint_other.show();
        break;
      }
      case 'SELECT_ONE':
      case 'SELECT_MULTI': {
        _wording.show();
        _wording_value.val(data.wording || '');
        _description.show();
        _description_value.val(data.description || '');
        _qualifier.show();
        _qualifier_value.val(data.qualifier || '');
        _info.show();
        _info_value.val(data.info || '');
        _info_hint_other.show();
        _options.show();
        _primary_pool.hide();
        _secondary_pool.hide();

        break;
      }
    }
  }

  function show_new(id,data)
  {
    const bullpen = controller.unused_questions();
    if(Object.keys(bullpen).length) {
      _archive_select.find('option:not(:first)').remove();
      Object.entries(bullpen).forEach( ([id,data]) => {
        let wording = data.wording ?? '';
        if(wording.length > 125) {
          wording = wording.slice(0,125) + '...';
        }
        _archive_select.append(new Option( wording, id ));
      });
      _archive_select.val('').on('change',id,handle_archive);
      _archive.show();
    }

    _type_value.hide();
    _type_select.val('').on('change',[id,data],handle_type).show();
  }

  //
  // option pool
  //

  function toggle_option_pool(e) {
    const is_primary = $(this).hasClass('primary');
    
    if(is_primary) { _secondary_pool.hide(); } else { _primary_pool.hide(); }

    const pool = is_primary ? _primary_pool : _secondary_pool;
    if(pool.is(':visible')) {
      pool.hide();
    } else {
      const all_options = controller.all_options();
      pool.find('.chip').remove();
      Object.entries(all_options).forEach( ([id,label]) => {
        const chip = $("<div>");
        chip.addClass('chip');
        chip.data(id);
        const span = $("<span>").text(label);
        const close = $("<button class='option' type='button'>x</button>");
        chip.append(span).append(close).appendTo(pool);
      });
      pool.show();
    }
  }

  function handle_archive(e)
  {
    const item = $(this);
    const new_id = item.val();
    const old_id = e.data;
    ce.undo_manager.add_and_exec({
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



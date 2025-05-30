export default function editors(ce)
{
  const _frame = $('#editor-frame');
  const _section_editor  = _frame.find('div.grid.section.editor');
  const _section_viewer  = _frame.find('div.grid.section.viewer');
  const _question_editor = _frame.find('div.grid.question.editor');
  const _question_viewer = _frame.find('div.grid.question.viewer');

  let _editable = false;
  let _cur_section = null;
  let _cur_question = null;
  let _undo_action = null;

  function reset(editable)
  {
    hide();
    _editable = editable;
    if(editable) { _frame.addClass('editable').removeClass('locked') }
    else         { _frame.addClass('locked').removeClass('editable') }
  }

  function show_section(section_id,section) {
    _frame.removeClass('question').addClass('section');
    if(_editable) { show_section_editor(section_id,section); }
    else          { show_section_viewer(section_id,section); } 
  }

  function show_question(question_id,question,options) {
    _frame.removeClass('section').addClass('question');
    if(_editable) { show_question_editor(question_id,question,options); }
    else          { show_question_viewer(question_id,question,options); } 
  }


  const _se_name              = _section_editor.children('.name');
  const _se_name_value        = _se_name.find('input');
  const _se_show_name         = _section_editor.children('.show-name');
  const _se_show_name_value   = _se_show_name.find('select');
  const _se_description       = _section_editor.children('.description');
  const _se_description_value = _se_description.find('textarea');
  const _se_feedback          = _section_editor.children('.feedback');
  const _se_feedback_value    = _se_feedback.find('input');

  function show_section_editor(section_id,section)
  {
    _se_name_value.val(section.name || '');
    _se_show_name_value.val(section.show_name ? 'YES' : 'NO');
    _se_description_value.val(section.description || '');
    _se_feedback_value.val(section.feedback || '')
    _section_editor.find('div.hint').removeClass('locked');

    _se_description_value.on('input change',update_character_count);
  }

  const _sv_name              = _section_viewer.children('.name');
  const _sv_name_value        = _sv_name.find('div.text');
  const _sv_show_name         = _section_viewer.children('.show-name');
  const _sv_show_name_value   = _sv_show_name.find('div.text');
  const _sv_description       = _section_viewer.children('.description');
  const _sv_description_value = _sv_description.find('div.text');
  const _sv_feedback          = _section_viewer.children('.feedback');
  const _sv_feedback_value    = _sv_feedback.find('div.text');

  function show_section_viewer(section_id,section)
  {
    _sv_name_value.html(section.name || '');
    _sv_show_name_value.html(section.show_name ? "YES" : "NO");
    _sv_description_value.html( section.description || '' );
    _sv_feedback_value.html( section.feedback || '' );
    _section_viewer.find('div.hint').removeClass('locked');
  }

  function show_question_editor(question_id,question,options)
  {
  }

  const _qv_type              = _question_viewer.children('.type');
  const _qv_type_value        = _qv_type.find('div.text');
  const _qv_wording           = _question_viewer.children('.wording');
  const _qv_wording_value     = _qv_wording.find('div.text');
  const _qv_qualifier         = _question_viewer.children('.qualifier');
  const _qv_qualifier_value   = _qv_qualifier.find('div.text');
  const _qv_description       = _question_viewer.children('.description');
  const _qv_description_value = _qv_description.find('div.text');
  const _qv_info              = _question_viewer.children('.info');
  const _qv_info_label        = _qv_info.filter('label');
  const _qv_info_value        = _qv_info.find('div.text');
  const _qv_info_hint         = _qv_info.find('.hint > div');
  const _qv_info_hint_info    = _qv_info_hint.filter('.info-block');
  const _qv_info_hint_other   = _qv_info_hint.filter('.other-type');
  const _qv_options           = _question_viewer.children('.options');
  const _qv_primary           = _qv_options.filter('.primary');
  const _qv_secondary         = _qv_options.filter('.secondary');
  const _qv_other             = _qv_options.filter('.other');
  const _qv_primary_value     = _qv_primary.find('div.text');
  const _qv_secondary_value   = _qv_secondary.find('div.text');
  const _qv_other_value       = _qv_other.find('div.text');
  
  function show_question_viewer(question_id,question,options)
  {
    _question_viewer.children().hide();
    _qv_type.show();
    _qv_info_label.html('Popup Hint:');
    _qv_info_hint.hide();
    _question_viewer.find('div.hint').removeClass('locked');
    switch(question.type) {
      case 'INFO':
        _qv_type_value.html('Info Block');
        _qv_info.show();
        _qv_info_label.html('Info Text:');
        _qv_info_value.html(question.info || '');
        _qv_info_hint_info.show();
        break;
      case 'BOOL':
        _qv_type_value.html('Simple Checkbox');
        _qv_wording.show();
        _qv_wording_value.html(question.wording || '');
        _qv_qualifier.show();
        _qv_qualifier_value.html(question.qualifier || '');
        _qv_description.show();
        _qv_description_value.html(question.description || '');
        _qv_info.show();
        _qv_info_value.html(question.info || '');
        _qv_info_hint_other.show();
        break;
      case 'FREETEXT':
        _qv_type_value.html('Free text');
        _qv_wording.show();
        _qv_wording_value.html(question.wording || '');
        _qv_description.show();
        _qv_description_value.html(question.description || '');
        _qv_info.show();
        _qv_info_value.html(question.info || '');
        _qv_info_hint_other.show();
        break;
      case 'OPTIONS':
        if(question.multiple) {
          _qv_type_value.html('Multiple Selections');
        } else {
          _qv_type_value.html('Single Select');
        }
        _qv_wording.show();
        _qv_wording_value.html(question.wording || '');
        _qv_qualifier.show();
        _qv_qualifier_value.html(question.qualifier || '');
        _qv_description.show();
        _qv_description_value.html(question.description || '');
        _qv_info.show();
        _qv_info_value.html(question.info || '');
        _qv_info_hint_other.show();
        _qv_options.show();

        const primary = question.options.filter(([id,secondary]) => !secondary);
        const secondary = question.options.filter(([id,secondary]) => secondary);
        _qv_primary_value.find('ul').remove();
        if(primary.length) {
          const ul = $('<ul>').addClass('options').prependTo(_qv_primary_value);
          primary.map(([id,]) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        _qv_secondary_value.find('ul').remove();
        if(secondary.length) {
          const ul = $('<ul>').addClass('options').prependTo(_qv_secondary_value);
          secondary.map(([id,]) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        _qv_other_value.html(question.other || '');


        break;
    }
  }

  function hide() 
  { 
    _frame.removeClass('section question'); 
  }

  // hint handlers

  function update_character_count(e)
  {
    const cur_length = $(this).val().length;
    const max_length = $(this).attr('maxlength');
    const cc = $(this).parent().children('.char-count');

    cc.children('.cur').text(cur_length);

    if(cur_length > 0.9*max_length) {
      cc.addClass('danger').removeClass('warning');
    } else if(cur_length > 0.75*max_length) {
      cc.addClass('warning').removeClass('danger');
    } else {
      cc.removeClass('warning danger');
    }
  }

  _frame.find('.viewer, .editor').find('div.label span')
  .on('mouseenter', function(e) {
    const timeout_id = $(this).data('timeout');
    if(timeout_id) {
      clearTimeout(timeout_id);
    }
    const hint = $(this).parent().next().children('div.hint');
    $(this).data(
      'timeout',
      setTimeout(() => { hint.addClass('hover') }, 250),
    );
  })
  .on('mouseleave', function(e) {
    const timeout_id = $(this).data('timeout');
    if(timeout_id) {
      clearTimeout(timeout_id);
      $(this).data('timeout',0);
    }
    $(this).parent().next().children('div.hint').removeClass('hover');
  })
  .on('click', function(e) {
    $(this).parent().next().children('div.hint').toggleClass('locked');
  })
  ;
  

  return {
    reset:reset,
    show_section:show_section,
    show_question:show_question,
    hide: hide,
  };
}

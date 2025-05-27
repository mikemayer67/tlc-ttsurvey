export default function editors(ce)
{
  const _frame = $('#editor-frame');
  const _section_editor  = _frame.find('div.grid.section.editor');
  const _section_viewer  = _frame.find('div.grid.section.viewer');
  const _question_editor = _frame.find('div.grid.question.editor');
  const _question_viewer = _frame.find('div.grid.question.viewer');

  let _editable = false;

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


  function show_section_editor(section_id,section)
  {
  }

  const _sv_name              = _section_viewer.children('.name');
  const _sv_name_value        = _sv_name.find('span.value');
  const _sv_name_hide         = _sv_name.find('span.note');
  const _sv_description       = _section_viewer.children('.description');
  const _sv_description_value = _sv_description.filter('.value');
  const _sv_feedback          = _section_viewer.children('.feedback');
  const _sv_feedback_value    = _sv_feedback.filter('.value');

  function show_section_viewer(section_id,section)
  {
    _sv_name_value.html(section.name);
    if(section.show_name) { _sv_name_hide.hide() }
    else                  { _sv_name_hide.show() }

    _sv_description_value.html( section.description || '' );
    _sv_feedback_value.html( section.feedback ? 'YES' : 'NO');
  }

  function show_question_editor(question_id,question,options)
  {
  }

  const _qv_type              = _question_viewer.children('.type');
  const _qv_type_value        = _qv_type.find('span.value');
  const _qv_type_note         = _qv_type.find('span.note');
  const _qv_wording           = _question_viewer.children('.wording');
  const _qv_wording_value     = _qv_wording.filter('span.value');
  const _qv_qualifier         = _question_viewer.children('.qualifier');
  const _qv_qualifier_value   = _qv_qualifier.filter('span.value');
  const _qv_description       = _question_viewer.children('.description');
  const _qv_description_value = _qv_description.filter('span.value');
  const _qv_info              = _question_viewer.children('.info');
  const _qv_info_label        = _qv_info.filter('span.label');
  const _qv_info_value        = _qv_info.filter('span.value');
  const _qv_options           = _question_viewer.children('.options');
  const _qv_primary           = _qv_options.filter('.primary');
  const _qv_secondary         = _qv_options.filter('.secondary');
  const _qv_other             = _qv_options.filter('.other');
  const _qv_primary_value     = _qv_primary.filter('.value');
  const _qv_secondary_value   = _qv_secondary.filter('.value');
  const _qv_other_value       = _qv_other.filter('.value');
  
  function show_question_viewer(question_id,question,options)
  {
    _question_viewer.children().hide();
    _qv_type.show();
    _qv_type_note.html('');
    _qv_info_label.html('Popup Hint:');
    switch(question.type) {
      case 'INFO':
        _qv_type_value.html('Info Block');
        _qv_type_note.html('(survey text, no response)');
        _qv_info.show();
        _qv_info_label.html('Info Text:');
        _qv_info_value.html(question.info || '');
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
        break;
      case 'FREETEXT':
        _qv_type_value.html('Free text');
        _qv_type_note.html('(reponse textbox)');
        _qv_wording.show();
        _qv_wording_value.html(question.wording || '');
        _qv_description.show();
        _qv_description_value.html(question.description || '');
        _qv_info.show();
        _qv_info_value.html(question.info || '');
        break;
      case 'OPTIONS':
        if(question.multiple) {
          _qv_type_value.html('Multiple Select Options');
        } else {
          _qv_type_value.html('Single Select Option');
        }
        _qv_wording.show();
        _qv_wording_value.html(question.wording || '');
        _qv_qualifier.show();
        _qv_qualifier_value.html(question.qualifier || '');
        _qv_description.show();
        _qv_description_value.html(question.description || '');
        _qv_info.show();
        _qv_info_value.html(question.info || '');
        _qv_options.show();

        const primary = question.options.filter(([id,secondary]) => !secondary);
        const secondary = question.options.filter(([id,secondary]) => secondary);
        _qv_primary_value.html('');
        if(primary.length) {
          const ul = $('<ul>').addClass('options').appendTo(_qv_primary_value);
          primary.map(([id,]) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        _qv_secondary_value.html('');
        if(secondary.length) {
          const ul = $('<ul>').addClass('options').appendTo(_qv_secondary_value);
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

  return {
    reset:reset,
    show_section:show_section,
    show_question:show_question,
    hide: hide,
  };
}

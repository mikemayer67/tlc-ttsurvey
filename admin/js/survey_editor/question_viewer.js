export default function question_viewer(ce)
{
  const _box = $('#editor-frame div.grid.question.viewer');

  const _type              = _box.children('.type');
  const _type_value        = _type.find('div.text');

  const _wording           = _box.children('.wording');
  const _wording_value     = _wording.find('div.text');

  const _qualifier         = _box.children('.qualifier');
  const _qualifier_value   = _qualifier.find('div.text');

  const _description       = _box.children('.description');
  const _description_value = _description.find('div.text');

  const _info              = _box.children('.info');
  const _info_label        = _info.filter('.label').find('span');
  const _info_value        = _info.find('div.text');
  const _info_hint         = _info.find('.hint > div');
  const _info_hint_info    = _info_hint.filter('.info-block');
  const _info_hint_other   = _info_hint.filter('.other-type');

  const _options           = _box.children('.options');
  const _primary           = _options.filter('.primary');
  const _secondary         = _options.filter('.secondary');
  const _other             = _options.filter('.other');
  const _primary_value     = _primary.find('div.text');
  const _secondary_value   = _secondary.find('div.text');
  const _other_value       = _other.find('div.text');

  const _hints             = _box.find('div.hint');

  function show(id,data,options)
  {
    // As the list of fields to show depend on question type, we start by hiding
    //   all of the fields and then turning back on those that are needed based on
    //   question type.
    _box.children().hide();
    _type.show();
    _hints.removeClass('locked');

    // The info field actually has a different interpretation based on if it's
    //   a real question or an information block.  We'll assume it's a real
    //   question for now and modify it for an information block if necessary
    _info_label.text('Popup Hint:');
    _info_hint.hide();

    // Now we can customize what is shown based on the question type
    _type_value.text( typeLabels[data.type] );

    switch(data.type) {
      case 'INFO': {
        _info.show();
        _info_label.text('Info Text:');
        _info_value.text(data.info || '');
        _info_hint_info.show();
        break;
      }
      case 'BOOL': {
        _wording.show();
        _wording_value.text(data.wording || '');
        _qualifier.show();
        _qualifier_value.text(data.qualifier || '');
        _description.show();
        _description_value.text(data.description || '');
        _info.show();
        _info_value.text(data.info || '');
        _info_hint_other.show();
        break;
      }
      case 'FREETEXT': {
        _wording.show();
        _wording_value.text(data.wording || '');
        _description.show();
        _description_value.text(data.description || '');
        _info.show();
        _info_value.text(data.info || '');
        _info_hint_other.show();
        break;
      }
      case 'SELECT_ONE':
      case 'SELECT_MULTI': {
        _wording.show();
        _wording_value.text(data.wording || '');
        _qualifier.show();
        _qualifier_value.text(data.qualifier || '');
        _description.show();
        _description_value.text(data.description || '');
        _info.show();
        _info_value.text(data.info || '');
        _info_hint_other.show();
        _options.show();

        _primary_value.find('ul').remove();
        _secondary_value.find('ul').remove();

        const primary   = data.options.filter(([id,secondary]) => !secondary);
        const secondary = data.options.filter(([id,secondary]) =>  secondary);

        if(primary.length) {
          const ul = $('<ul>').addClass('options').prependTo(_primary_value);
          primary.map(([id,]) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        if(secondary.length) {
          const ul = $('<ul>').addClass('options').prependTo(_secondary_value);
          secondary.map(([id,]) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        _other_value.text(data.other || '');

        break;
      }
    }
  }

  return {
    show:show,
  };
}


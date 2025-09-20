import ui_config from './ui_config.js';

export default function init(ce)
{
  const _box = $('#editor-frame div.grid.question.viewer');

  const _type              = _box.children('.type');
  const _type_value        = _type.find('div.text');

  const _wording           = _box.children('.wording');
  const _wording_value     = _wording.find('div.text');

  const _layout            = _box.children('.layout');
  const _layout_value      = _layout.find('div.text');

  const _qualifier         = _box.children('.qualifier');
  const _qualifier_value   = _qualifier.find('div.text');

  const _intro             = _box.children('.intro');
  const _intro_value       = _intro.find('div.text');

  const _grouped           = _box.children('.grouped');
  const _grouped_value     = _grouped.find('div.text');

  const _info              = _box.children('.info');
  const _info_value        = _info.find('div.text');
  const _popup             = _box.children('.popup');
  const _popup_value       = _popup.find('div.text');

  const _options           = _box.children('.options');
  const _options_value     = _options.find('div.text');
  const _other             = _box.children('.other');
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

    // Now we can customize what is shown based on the question type
    _type_value.text( typeLabels[data.type] );

    switch(data.type) {
      case 'INFO': {
        _info.show();
        _info_value.text(data.info || '');
        _grouped.show();
        _grouped_value.text( ui_config.grouped.info_label[data.grouped] );
        break;
      }
      case 'BOOL': {
        _wording.show();
        _wording_value.text(data.wording || '');
        _layout.show();
        _layout_value.text(ui_config.layout.bool_label[data.layout]);
        _qualifier.show();
        _qualifier_value.text(data.qualifier || '');
        _intro.show();
        _intro_value.text(data.intro || '');
        _grouped.show();
        _grouped_value.text( ui_config.grouped.label[data.grouped] );
        _popup.show();
        _popup_value.text(data.popup || '');
        break;
      }
      case 'FREETEXT': {
        _wording.show();
        _wording_value.text(data.wording || '');
        _intro.show();
        _intro_value.text(data.intro || '');
        _grouped.show();
        _grouped_value.text( ui_config.grouped.label[data.grouped] );
        _popup.show();
        _popup_value.text(data.popup || '');
        break;
      }
      case 'SELECT_ONE':
      case 'SELECT_MULTI': {
        _wording.show();
        _wording_value.text(data.wording || '');
        _layout.show();
        _layout_value.text(ui_config.layout.select_label[data.layout]);
        _qualifier.show();
        _qualifier_value.text(data.qualifier || '');
        _intro.show();
        _intro_value.text(data.intro || '');
        _grouped.show();
        _grouped_value.text( ui_config.grouped.label[data.grouped] );
        _popup.show();
        _popup_value.text(data.popup || '');
        _options.show();
        _other.show();


        _options_value.find('ul').remove();

        if(data.options.length) {
          const ul = $('<ul>').addClass('options').prependTo(_options_value);
          data.options.map((id) => options[id])
          .forEach((opt) => $('<li>').text(opt).appendTo(ul));
        }

        if(data.other_flag) {
          if(data.other) {
            _other_value.text('Enabled  [label: "' + data.other + '"]');
          } else {
            _other_value.text('Enabled  [default label]');
          }
        } else {
          _other_value.text('Disabled');
        }
        

        break;
      }
    }
  }

  return {
    show:show,
  };
}



const _bool_labels = {
  LEFT:  'Checkbox before label',
  RIGHT: 'Checkbox after label',
};
const _select_labels = {
  ROW:   'Row (with wrap)',
  LCOL:  'Left-aligned column',
  RCOL:  'Right-aligned column',
};

const _bool_default   = 'LEFT';
const _select_default = 'ROW';

function _label_func(labels, defaultKey) {
  return (key) => labels[key] ?? labels[defaultKey];
}

export default 
{
  bool_label:     _label_func(_bool_labels,   _bool_default),
  select_label:   _label_func(_select_labels, _select_default),
  bool_default:   _bool_default,
  select_default: _select_default,
};

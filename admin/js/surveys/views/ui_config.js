export default 
{
  layout : {
    bool_label: {
      LEFT:  'Checkbox before label',
      RIGHT: 'Checkbox after label',
    },
    select_label: {
      ROW:   'Row (with wrap)',
      LCOL:  'Left-aligned column',
      RCOL:  'Right-aligned column',
    },
    bool_default:   'LEFT',
    select_default: 'ROW',
  },
  grouped: {
    info_label(v)   { return v ? 'Display in question box' : 'Not displayed in question box'; },
    common_label(v) { return v ? 'Group with previous' : 'Start new question box'; },
    default_value: 0,
  }
};

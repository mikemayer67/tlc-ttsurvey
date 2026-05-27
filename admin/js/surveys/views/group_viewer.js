export default function init(ce)
{
  const _frame       = $('#editor-frame');
  const _box         = _frame.find('div.grid.group.viewer');
  const _name        = _box.children('.name').find('div.text');

  function show(id,data)
  {
    _frame.find('div.content-header').text('Group Details');
    _name.html(data.name || '');
  }

  return { show };
}


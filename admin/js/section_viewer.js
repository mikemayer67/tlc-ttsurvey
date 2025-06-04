export default function section_viewer(ce)
{
  const _box         = $('#editor-frame div.grid.section.viewer');
  const _name        = _box.children('.name').find('div.text');
  const _labeled     = _box.children('.labeled').find('div.text');
  const _description = _box.children('.description').find('div.text');
  const _feedback    = _box.children('.feedback').find('div.text');
  const _hints       = _box.find('div.hint');

  function show(id,data)
  {
    _name.html(data.name || '');
    _labeled.html(data.labeled ? "YES" : "NO");
    _description.html( data.description || '' );
    _feedback.html( data.feedback || '' );
    _hints.removeClass('locked');
  }

  return {
    show:show,
  };
}


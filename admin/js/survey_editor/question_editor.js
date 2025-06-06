export default function question_editor(ce,controller)
{
  const _box = $('#editor-frame div.grid.question.editor');

  const _hints             = _box.find('div.hint');

  function show(id,data)
  {
  }

  return {
    show:show,
  };
}



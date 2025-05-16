export default function active_controller(ce)
{
  function select_survey()
  { 
    ce.survey_editor.disable();
    ce.survey_editor.update_content(ce.cur_survey.id);
  }

  return {
    state:'active',
    select_survey: select_survey,
  }
}

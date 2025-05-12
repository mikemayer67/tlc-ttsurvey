export default function closed_controller(ce) 
{
  function select_survey()
  { 
    const content = ce.survey_data.content(ce.cur_survey.id);
    ce.survey_editor.disable();
    ce.survey_editor.update_content(content);
  }

  return {
    state:'closed',
    select_survey: select_survey,
  }
};

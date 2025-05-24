export default function locked_controller(ce)
{
  function select_survey()
  { 
    const content = ce.survey_data.content(ce.cur_survey.id);

    // change the ContentDataLoaded handler
    $(document)
    .off('ContentDataLoaded')
    .on('ContentDataLoaded', function(e,id,data) { 
      ce.survey_editor.update(data); 
    });

    ce.survey_editor.disable();
    ce.survey_editor.update(content);
  }

  return {
    state:'locked',
    select_survey: select_survey,
  }
}

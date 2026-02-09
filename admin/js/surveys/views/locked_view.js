export default function init(ce)
{
  function select_survey()
  { 
    const content = ce.survey_data.content(ce.cur_survey.id);

    // change the ContentDataLoaded handler
    $(document)
    .off('ContentDataLoaded')
    .on('ContentDataLoaded', function(e,id,data) { 
      ce.controller.update_content(data); 
    });

    ce.controller.disable_edits();
    ce.controller.update_content(content);

    ce.submit.hide();
    ce.revert.hide();
    ce.preview.hide();
    ce.preview_js.hide();
    ce.download_pdf.css('margin-right','auto').show();
  }

  return {
    state:'locked',
    select_survey: select_survey,
  }
}

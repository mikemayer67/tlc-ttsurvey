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
    ce.preview_js.val("View Survey")
    ce.preview_nojs.hide();
    ce.download_pdf.show();
  }

  function handle_preview(e)
  {
    alert("Handle Locked Preview");
  }

  return {
    state:'locked',
    select_survey: select_survey, handle_preview
  }
}

let _previewWindow = null;
const _previewTabName = 'ttt_preview';

export function preview_handler(ce,e)
{
    const tgt = $(e.target);

    // create or reuse the preview tab
    var can_reuse = !!_previewWindow && !_previewWindow.closed;
    if( can_reuse ) {
      try       { _previewWindow.location.replace('about:blank'); } 
      catch (e) { can_reuse = false;                              }
    }

    if( !can_reuse ) {
      if(!(_previewWindow = window.open('',_previewTabName))) {
        alert("Failed to open a new tab to display the preview.  Do you have popup blockers installed?");
        return;
      }
    }

    // bring it to front if browser allows that, otherwise raise an alert to user
    _previewWindow.focus();
    setTimeout(() => {
      if(document.hasFocus()) { alert("Preview opened in another tab."); }
    }, 800);

    // create temporary form to request preview
    const nonce = ce.form.find('[name=preview-nonce]').val();

    const content = ce.controller.content();
    const json_content = JSON.stringify(content);
    const title = $('#survey-name').val() || ce.cur_survey.title;

    const form = $('<form>', {
      method:'POST',
      action:(ce.ajaxuri + '?preview'),
      target:_previewTabName,
    })
    .append( 
      $('<input>',{ type:'hidden', name:'nonce', value:nonce }),
      $('<input>',{ type:'hidden', name:'title', value:title}),
      $('<input>',{ type:'hidden', name:'content', value: json_content }),
      $('<input>',{ type:'hidden', name:'preview_js', value:tgt.hasClass('js')}),
    )
    .appendTo('body');

    form.submit();

    setTimeout(() => form.remove(), 0);

}
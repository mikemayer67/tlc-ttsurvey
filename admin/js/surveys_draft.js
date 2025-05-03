const self = {};

function has_changes()
{
  return true;
}

async function block_survey_select()
{
  if( !has_changes() ) { return Promise.resolve(false); }

  return new Promise((resolve) =>  {
    var tsm = $('#tab-switch-modal');
    tsm.find('.tsm-type').html('surveys');
    tsm.find('button.cancel').off('click').on('click',function() { 
      $(this).off('click');
      tsm.hide();
      resolve(true);
    });
    tsm.find('button.confirm').off('click').on('click',function() { 
      $(this).off('click');
      tsm.hide();
      resolve(false);
    }).html("Switch Surveys");
    tsm.show();
  });
}

function draft_handler(ce) {
  self.ce = ce;
  
  return {
    state:'draft',
    block_survey_select: block_survey_select,
  }
};

export default draft_handler;

let self = {
  surveys: {},
};

function init_select_list() {
  // self function requires that the php code creates the javascript object ttt_all_surveys in a <script> element

  var survey = ttt_all_surveys['active'];
  if(!(survey===null || Array.isArray(survey))) {
    survey.status = 'active';
    self.select.append($('<option>',{ 'text':'---Active---', 'disabled':true }));
    self.surveys[survey.id] = survey;
    self.select.append($('<option>',{
      'value':survey.id,
      'text':survey.title,
      'class':'active',
      'status':'active',
    }));
  }

  self.select.append($('<option>',{ 'text':'---New---', 'class':'new', 'disabled':true }));
  self.select.append($('<option>',{ 
    'value':'new', 
    'text':'Open New Survey...', 
    'class':'new', 
    'status':'new'
  }));

  if( ttt_all_surveys['draft'].length>0 ) {
    self.select.append($('<option>',{ 'text':'---Draft---', 'disabled':true }));
    for( var survey of ttt_all_surveys['draft']) {
      survey.status = 'draft';
      self.surveys[survey.id] = survey;
      self.select.append($('<option>',{
        'value':survey.id,
        'text':survey.title,
        'class':'draft',
        'status':'draft',
      }));
    }
  }

  if( ttt_all_surveys['closed'].length>0 ) {
    self.select.append($('<option>',{ 'text':'---Closed---', 'disabled':true }));
    for( var survey of ttt_all_surveys['closed']) {
      survey.status = 'closed';
      self.surveys[survey.id] = survey;
      self.select.append($('<option>',{
        'value':survey.id,
        'text':survey.title,
        'class':'closed',
        'status':'closed',
      }));
    }
  }

  // create a pseudo-survey for handling the New Survey select
  self.surveys["new"] = {'id':'new', 'status':'new'};
}

function select_initial_survey() {
  var options = self.select.find('option[value]').filter(':not(.new)');
  var id      = options.length ? options.first().val() : 'new';
  select_survey(id);
}

function select_survey(id) {
  self.select.val(id);
  self.cur_survey = self.surveys[id];
}

async function handle_survey_select(e) {
  const blocked = await self.ce.dispatch('block_survey_select');
  if( blocked )
  {
    self.select.val(self.cur_survey.id);
  } else {
    select_survey(self.select.val());
  }
}

function survey_controls(ce) {
  self.ce = ce;

  self.select  = $('#survey-select');
  self.status  = ce.form.find('span.survey-status');
  self.actions = ce.form.find('a.action');

  self.select.on('change',handle_survey_select);

  init_select_list();
  select_initial_survey();

  return {
    cur_survey() { return self.cur_survey; },
    cur_status() { return self.cur_survey.status; },

    clonable_surveys() {
      return self.select.find('option[value]').filter(':not(.new)').clone();
    },
  };
}

export default survey_controls;

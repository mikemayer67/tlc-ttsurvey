export default function survey_data()
{
  // full survey data (indexed by id)
  const _surveys = {};

  // survey cross reference by status (id's only)
  const _active_survey = null;  // survey ids
  const _draft_surveys = [];
  const _closed_surveys = [];

  // initialize the survey data from the ttt_all_surveys data
  //   ... provided by PHP via a <script> element.

  for( var survey of ttt_all_surveys ) {
    _surveys[survey.id] = survey;
    switch( survey.status ) {
      case "active":
        _active_survey = survey.id;
      break;

      case "draft":
        _draft_surveys.push(survey.id);
      break;

      case "closed":
        _closed_surveys.push(survey.id);
      break;
    }
  }
  // create a pseudo-survey for handling the New Survey select
  _surveys["new"] = {'id':'new', 'status':'new'};

  // accessors

  function get_active_survey() { 
    return get_survey_by_id(_active_survey);
  }

  function get_draft_surveys() { 
    return _draft_surveys
    .filter(id => id in _surveys)
    .map(id => _surveys[id]);
  }

  function get_closed_surveys() { 
    return _closed_surveys
    .filter(id => id in _surveys)
    .map(id => _surveys[id]);
  }

  function get_survey_by_id(id) {
    return _surveys[id] ?? null;
  }

  function add_survey(survey) {
    const id = survey.id;
    switch(survey.status) {
      case 'draft':
        _draft_surveys.push(id);
      break;

      case 'closed':
        _closed_surveys.push(id);
      break;

      case 'active':
        alert('Cannot add a second active survey');
        return null;
      break;
    }
    _surveys[id] = survey;
  }

  return {
    active: get_active_survey,
    drafts: get_draft_surveys,
    closed: get_closed_surveys,
    lookup: get_survey_by_id,
    add:    add_survey,
  };
};

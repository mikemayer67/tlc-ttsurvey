export default function init(ce)
{
  // full survey data (indexed by id)
  const _surveys = {};

  // survey cross reference by status (id's only)
  let _active_survey = null;  // survey ids
  const _draft_surveys = [];
  const _closed_surveys = [];

  // initialize the survey data from the ttt_all_surveys data
  //   ... provided by PHP via a <script> element.

  for( var survey of ttt_all_surveys ) {
    // Note that while PHP/MySQL use the key 'survey_id' to identify the survey index,
    //   within the javascript survey objects, the property is named simply 'id'
    survey.id = survey.survey_id;
    delete survey.survey_id;

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

  // content methods

  function get_content(id) {
    // This function may need to retrieve the data from the server.
    //   In this case, this function issues the AJAX call and then return null.
    //   The AJAX response handler will trigger a ContentDataLoaded event
    //     when the data has been retrieved, passing the content data as the
    //     (only) function parameter.
    // If the data does not need to be retrieved from the server, it returns
    //   it immediately.
    
    if( ! (id in _surveys) ) { 
      alert("Somthing got out of sync.  Reloading page.");
      location.reload();
    }

    if('content' in _surveys[id]) { return _surveys[id].content; }

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: {
        ajax:'admin/get_survey_content',
        nonce: ce.nonce,
        survey_id:id,
      }
    })
    .done( function(data,status,jqXHR) {
      if (data.success) {
        const content = data.content;
        for( const [key,value] of Object.entries(content) ) {
          if( Array.isArray(value) && value.length===0 ) {
            content[key] = {};
          }
        }
        _surveys[id].content = content;
        $(document).trigger('ContentDataLoaded',[id,data.content]);
      }
      else if( 'bad_nonce' in data ) {
        alert("Somthing got out of sync.  Reloading page.");
        location.reload();
      } 
      else {
        alert("Data got out of sync with database: " + data.error);
        location.reload();
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    }) ;
    return null;
  }

  function set_content(id,content) 
  {
    // hopefully never hit this, but just in case...
    if( ! id in _surveys ) { 
      alert("Somthing got out of sync.  Reloading page.");
      location.reload();
    }
    _surveys[id].content = content;
  }

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

  // modifiers
  
  function update_survey_state(id,state) 
  {
    $.ajax( {
      type: 'POST',
      url: ce.ajaxurl,
      dataType: 'json',
      data: {
        ajax: 'admin/update_survey_state',
        nonce: ce.nonce,
        survey_id: id,
        new_state: state,
      }
    })
    .done( function(data,status,jqXHR) {
      if (data.success) {
        alert(data.message + "\n\nUpdating Admin Dashboard");
        const url = new URL(location.href);
        url.searchParams.set('tab','surveys');
        url.searchParams.set('survey',id);
        location.replace(url.toString());
      }
      else if( 'bad_nonce' in data ) {
        alert("Somthing got out of sync.  Reloading page.");
        location.reload();
      } 
      else {
        alert("Data got out of sync with database: " + data.error);
        location.reload();
      }
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    }) ;
  }

  return {
    active:    get_active_survey,
    drafts:    get_draft_surveys,
    closed:    get_closed_surveys,
    lookup:    get_survey_by_id,
    add:       add_survey,

    activate(id) { update_survey_state(id,'active'); },
    close(id)    { update_survey_state(id,'closed'); },
    recall(id)   { update_survey_state(id,'draft');  },

    content(id,data) { 
      if(data) { set_content(id,data);   }
      else     { return get_content(id); }
    },
  };
};

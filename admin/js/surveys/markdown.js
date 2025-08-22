////////////////////////////////////////////////////////////////////////////////
//
// The markdown function sets up an object that supports markdown validation.
//
// It handles the AJAX calls to the server to validate any field that supports markdown
//   to look for any disallowed html tags that could lead to HTML insertion attacks
//   (either malicious or accidental).
//
// To avoid sending exessive validation requests, a fixed delay (nominally 5 seconds)
//   is applied between the last received update to the field and when the AJAX 
//   request is made.
//
// Validation findings are tracked for each section/question entry that allows markdown
//   and goes through three phases:
//   1) Queueing of validation requests
//   2) Pending validation responses
//   3) Caching of findings
//
// At each phase, the specific entry section/quesiton entry is identified by a key
//   with one of two forms:
//     section.[id].[key]
//     question.[id].[key]
//
// Queueing kicks off when a change is made in any of the entries that allow markdown.
//   Once queued, a timeout is started.  If additional changes occur before the timeout
//   fires, the timeout is restarted.  When the timout fires, the AJAX request is 
//   sent to the server for validation along with a unique request ID.
//
// Oncce the request has been sent, it enters a pending state where the request ID
//   is mapped to the entry key.  If a subsequent request for the same key is sent
//   before the response is received, the pending request map is updated with the 
//   new request ID.  When a reqponse is recieved, its request ID is checked against
//   the pending request map.  If they don't match, the reponse is ignored as OBE
//   due to subsequent request.
//
// Once a response has been received (with latest request_id for the corresponding key),
//   the findings are cached (if there are any) or cleared (if the markdown passes
//   validation) and a MarkdownValidationReceived event is posted to allow other
//   controllers to respond to the validation response.
//
//
// The markdown validation object supports a number of maintenance and getter methods:
//   - The reset method clears all queued, pending, and cached requests/findings.
//   - The is_empty method returns true if there are no queued, pending, or cached requests.
//   - The findings method return the list of findings for a given key (or undefined)
//
////////////////////////////////////////////////////////////////////////////////

export default function markdown(ce,controller)
{
  const self = {};

  let _queued   = {};  // waiting on timeout before sending to server
  let _pending  = {};  // waiting on response from server
  let _findings = {};  // findings returned from the server

  let _request_index = 0;

  function gen_key(context, id, key)
  {
    return `${context}.${id}.${key}`;
  }

  self.reset = function() 
  {
    // kill all queued timers to avoid sending the AJAx requests
    Object.values(_queued).forEach( (entry) => { clearTimeout(entry.timer) } );

    _queued = {};
    _pending = {};
    _findings = {};
  }

  self.queue_validation = function(context, item_id, input_key, markdown_text)
  {
    const key = gen_key(context, item_id, input_key);

    // if we're still accumulating changes, reset the time and update the queued text
    if( key in _queued ) {
      clearTimeout(_queued[key].timer);
    }
    _queued[key] = {
      context: context,
      item_id: item_id,
      input_key: input_key,
      markdown: markdown_text,
      timer: setTimeout( () => { send_validation_request(key) }, 5000 ),
    };

    $(document).trigger('MarkdownValidationUpdated');
  }

  self.flush_queue = function()
  {
    const n = Object.keys(_queued).length;
    Object.entries(_queued).forEach( ([key,entry]) => { 
      send_validation_request(key) 
    });
  }

  self.findings = function(context, item_id, input_key) 
  {
    const key = gen_key(context, item_id, input_key);
    return _findings[key];
  }

  self.can_submit = function()
  {
    if( Object.keys(_queued).length ) { return false; }
    if( Object.keys(_pending).length ) { return false; }
    if( Object.keys(_findings).length ) { return false; }
    return true;
  }

  function send_validation_request(key) 
  {
    const entry = _queued[key];
    if(!entry) { 
      return; 
    }
    delete _queued[key];

    clearTimeout(entry.timer);
    delete entry.timer;

    _request_index += 1;
    entry.request_index = _request_index;

    _pending[key] = entry;

    $.ajax( {
      type: 'POST',
      url: ce.ajaxuri,
      dataType: 'json',
      data: {
        ajax:'admin/validate_markdown',
        nonce: ce.nonce,
        markdown: entry.markdown,
      }
    })
    .done( function(data,status,jqXHR) {
      // before processing this response, check that this is for the current
      //   request for the associated key
      if(entry.request_index === _pending[key].request_index) {
        delete _pending[key];
        if( data.success ) {
          if( key in _findings ) {
            delete _findings[key];
            $(document).trigger('MarkdownValidationUpdated');
          }
        } else if( 'findings' in data ) {
          _findings[key] = data.findings;
          $(document).trigger('MarkdownValidationUpdated');
        } else {
          internal_error( {status:-1, statusMessage:"Missing markdown findings"} );
        }
      } 
    })
    .fail( function(jqXHR,textStatus,errorThrown) {
      internal_error(jqXHR);
    });
  }

  return self;
}

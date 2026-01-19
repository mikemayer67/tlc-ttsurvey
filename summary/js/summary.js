var ce = {};

function handle_exit_summary(e) {
  url = new URL(window.location.href);
  url.search='';
  window.open(url,'ttt_survey');
}

let list_adjust_timer = null;
function queue_adjust_lists(e)
{
  if(list_adjust_timer) { clearTimeout(list_adjust_timer); }
  list_adjust_timer = setTimeout( () => { 
    list_adjust_timer = null;
    adjust_lists(e);
  }, 50);
}

function adjust_lists(e)
{
  ce.resizable_lists.each( (i,l) => adjust_list(l) );
}

function adjust_list(l)
{
  const container_width = l.getBoundingClientRect().width;
  if(container_width <= 0) return;

  const items = $(l).children();
  const widths = items.map( (i,item) => $(item).data('natural-width') );

  const min_width = Math.min(...widths);
  const max_width = Math.max(...widths);
  const min_cols = Math.floor(container_width/max_width);
  const max_cols = Math.floor(container_width/min_width);

  for(let ncol=max_cols; ncol>=min_cols; ncol--) {
    const new_widths = adjust_column_widths(ncol,container_width,widths);
    if(new_widths) {
      items.each( (i,item) => { 
        item.style.width = new_widths[i%ncol] + "px";
      });
      return;
    }
  }

  items.each( (i,item) => { item.style.width = $(item).data('natural-width'); });
}

function download_csv(e)
{
  e.preventDefault();
  // not really an ajax call, but channels through the same mechanism
  const url = new URL(ce.ajaxuri, window.location.href);
  url.searchParams.set('download','summary');
  url.searchParams.set('f','csv');
  url.searchParams.set('sid', ce.survey_id);
  url.searchParams.set('ttt',ce.nonce);
  window.open(url.toString(), 'ttt_summary_download');
}

function download_pdf(e)
{
  alert('Not yet implemented');
}

function adjust_column_widths(ncol,container_width,widths)
{
  const n = widths.length;
  const new_widths = new Array(ncol).fill(0);

  for(let i=0; i<n; ++i) {
    const col = i%ncol;
    if(widths[i] > new_widths[col]) { new_widths[col] = widths[i]; }
  }

  let total_width = 0;
  for(let i=0; i<ncol; ++i) {
    total_width += new_widths[i];
  }
  
  return total_width <= container_width ? new_widths : undefined;
}

$(document).ready( function() {
  ce.resizable_lists = $('.resizable-list');
  ce.inputs = $('#ttt-navbar input[type=hidden]');
  ce.nonce = ce.inputs.filter('[name=nonce]').val();
  ce.ajaxuri = ce.inputs.filter('[name=ajaxurl]').val();
  ce.survey_id = ce.inputs.filter('[name=survey_id]').val();

  $('.js-only').removeClass('js-only');
  $('#download-csv').on('click',download_csv);
  $('#download-pdf').on('click',download_pdf);

  ce.resizable_lists.children().each( function(i,item) {
    $(item).data('natural-width', item.getBoundingClientRect().width);
  });

  $('.left-box').on('click',handle_exit_summary);

  $(window).on('load',adjust_lists);
  $(window).on('resize',queue_adjust_lists);
});

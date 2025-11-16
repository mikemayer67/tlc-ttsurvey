var ce = {};

function measure_element(el)
{
  const rect = el.getBoundingClientRect();
  const cs   = getComputedStyle(el);

  return {
    el,
    height: rect.height,
    marginTop: parseFloat(cs.marginTop) || 0,
    marginBottom: parseFloat(cs.marginBottom) || 0,
    isSectionStart: false,
    keepWithNext: false,
    groupHeight: rect.height,
  };
}

function paginate()
{
  // measure existing elements

  const header_metrics = measure_element( ce.header[0] );
  const block_metrics  = ce.divs.map( function() { return measure_element(this); }  ).get();
 
  // determine section starts

  var p = null; // prior "m" element in forEach below
  block_metrics.forEach( function(m) {
    if( m.el.classList.contains('section') ) {
      if( m.el.classList.contains('label') ) { 
        m.isSectionStart = true; 
      }
      if( m.el.classList.contains('intro') && p && p.isSectionStart) {
        p.keepWithNext = true;
        p.groupHeight = p.height + m.height + Math.max(p.marginBottom,m.marginTop);
      }
    }
    p = m;
  });

  // prepare to locate page breaks
  const page_height = 96 * parseFloat(
    getComputedStyle(document.documentElement).getPropertyValue('--page-height')
  );
  const page_margin = 96 * parseFloat(
    getComputedStyle(document.documentElement).getPropertyValue('--page-margin')
  );

  const printable_height  = page_height - 2 * page_margin;
  const max_section_start = 0.65 * printable_height;        // 65% is arbirary, adjust if desired

  const header_bottom = header_metrics.height + header_metrics.marginTop;
  const min_first_top = header_bottom + header_metrics.marginBottom;

  // prepare to iterate over all content blocks 
  
  var prior_bottom = header_bottom;
  var new_top      = min_first_top;
  var new_bottom   = null;

  const break_before = [];
  block_metrics.forEach( function(m,i) {
    new_top    = Math.max( new_top, prior_bottom + m.marginTop );
    new_bottom = new_top + m.height;

    const needs_break = (
      new_bottom > printable_height
      || (m.isSectionStart && new_top > max_section_start)
      || (m.isSectionStart && new_top + m.groupHeight > printable_height)
    );

    if( needs_break ) {
      break_before.push(m.el);
      new_top = Math.max(min_first_top, header_bottom + m.marginTop);
      prior_bottom = new_top + m.height;
    }
    else {
      prior_bottom = new_bottom;
    }

    new_top = prior_bottom + m.marginBottom;
  });

  // insert clone of breaking header element before the page break elements

  break_before.forEach( (el,pi) => {
    const hc = ce.header.clone().addClass('page-break');
    const pn = $('<div class="page-number">').text('Page ' + (2+pi));
    hc.insertBefore(el);
    pn.insertAfter(hc);
  });
}


$(document).ready( function() {

  ce.content = $('#content');
  ce.header  = $('div.ttt-header');
  ce.divs    = ce.content.children().not(ce.header);

  paginate();
});

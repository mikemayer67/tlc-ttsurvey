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
  //   - reserve  bottom 1/4" for page number
  const printable_height  = ce.print_height - 24;
  const max_section_start = 0.65 * printable_height;        // 65% is arbirary, adjust if desired

  const header_bottom = header_metrics.height + header_metrics.marginTop;
  const min_first_top = header_bottom + header_metrics.marginBottom;

  // prepare to iterate over all content blocks 
  
  var prior_bottom = header_bottom;
  var new_top      = min_first_top;
  var new_bottom   = null;

  // collect blocks into pages

  const pages = [];
  var   page  = [ ce.header ];
  var   pageno = 1;

  block_metrics.forEach( function(m,i) {
    new_top    = Math.max( new_top, prior_bottom + m.marginTop );
    new_bottom = new_top + m.height;

    const needs_break = (
      new_bottom > printable_height
      || (m.isSectionStart && new_top > max_section_start)
      || (m.isSectionStart && new_top + m.groupHeight > printable_height)
    );

    if( needs_break ) {
      pages.push(page);
      page = [ ce.header.clone(), $(m.el) ];

      new_top = Math.max(min_first_top, header_bottom + m.marginTop);
      prior_bottom = new_top + m.height;
    }
    else {
      page.push($(m.el));
      prior_bottom = new_bottom;
    }

    new_top = prior_bottom + m.marginBottom;
  });

  pages.push(page); /* everything still accumulating */

  // insert clone of breaking header element before the page break elements
  
  const num_pages = pages.length;

  pages.forEach( (page,pi) => {
    const page_div = $('<div>').addClass('page');
    page.forEach( (block) => { page_div.append(block) } );
    if( pi == 0 ) {
      // first page should be marked as such to avoid page break before
      page_div.addClass('first');
    }
    else {
      // all but first page should get a page number added
      const pn = $('<div class="page-number">').text('page ' + (pi+1) + ' of ' + num_pages);
      page_div.append(pn);
    }
    ce.content.append(page_div)
  });
}

function root_dimension(key) {
  // convert from inches to pixels...
  return 96 * parseFloat(
    getComputedStyle(document.documentElement).getPropertyValue('--' + key)
  );
}

$(document).ready( function() {

  ce.content = $('#content');
  ce.header  = $('div.ttt-header');
  ce.divs    = ce.content.children().not(ce.header);

  ce.page_height = root_dimension('page-height');
  ce.page_width  = root_dimension('page-width');

  ce.print_height = root_dimension('print-height');
  ce.print_width  = root_dimension('print-width');

  ce.margin_top     = root_dimension('print-margin-top');
  ce.margin_left    = root_dimension('print-margin-left');
  ce.margin_right   = root_dimension('print-margin-right');
  ce.margin_bottom  = root_dimension('print-margin-bottom');

  paginate();
});

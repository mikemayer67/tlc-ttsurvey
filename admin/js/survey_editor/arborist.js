// The arborist function sets up an object that supports the editor tree.
//   More Specifically, it handles the setting of the section and question names
//   and the question types displayed in the tree.  
//
//   But, it does much more than simply setting the text displayed in the <li> element.
//
//   - It checks to see if the the text will fit within the current width of the <li>
//     - If not, it truncates it and adds a '...' at the end to show continuation
//     - It sets the 'needs-xxx' class if the text is empty
//
//   - It handles resizing of the <li> elements and adjusts how the text is to be
//       truncated to fit... if necessary
//
//   - It manages the needs-value and needs-type classes when these are not yet
//       set in the corresponding section or question data

export default function arborist(tree)
{
  const _measurer = $('<span>').addClass('text-measurer').css({
    'position':'absolute', 'visibility':'hidden', 'white-space':'nowrap', 'pointer-events':'none',
  }).appendTo(tree);

  const self = {};

  function tend(leaf)
  {
    // leaf is expected to be part of the tree, but there isn't any 
    // real reason is must be as long as it "looks like" a leaf (duck typing)

    const full_text = (leaf.data('full-text') || '').trim();
    const is_section = leaf.hasClass('section');
    const leaf_text = is_section ? leaf.find('span') : leaf;

    if(full_text.length === 0) {
      leaf_text.text('').addClass('needs-value');
      return;
    }

    const maxWidth = leaf[0].clientWidth - 32; // to account for right-margin and type icon in ::before
    if(maxWidth <= 0) {
      leaf_text.text(full_text);
      return;
    }

    const e = leaf_text[0]; // get DOM elements
    const m = _measurer[0];

    const style = getComputedStyle(e);
    m.style.font       = style.font;
    m.style.fontSize   = style.fontSize;
    m.style.fontWeight = style.fontWeight;
    m.style.fontFamily = style.fontFamily;
    m.textContent      = full_text;

    if(m.offsetWidth <= maxWidth) {
      leaf_text.text(full_text).removeClass('needs-value');
      return;
    }

    // find longest string that fits
    const suffix = '...';
    let low = 3;
    let high = full_text.length;
    while(low < high) {
      let mid = Math.floor((low+high)/2);
      m.textContent = full_text.slice(0,mid) + suffix;
      if(m.offsetWidth <= maxWidth) {
        low = mid+1;
      } else {
        high = mid;
      }
    }

    leaf_text.text(full_text.slice(0,low-1) + suffix).removeClass('needs-value');
  }

  self.initialize = function(leaf,text,type)
  {
    leaf.data('full-text',text);
    if( type !== undefined ) {
      if(type) { leaf.removeClass('needs-type').addClass(type.toLowerCase()); }
      else     { leaf.addClass('needs-type'); }
    }
  }

  self.update_label = function(leaf,text)
  {
    leaf.data('full-text',text);
    tend(leaf);
  }

  self.update_type = function(leaf,new_type,old_type=null)
  {
    if(old_type) { leaf.removeClass(old_type.toLowerCase()); }

    if(new_type) { leaf.removeClass('needs-type').addClass(new_type.toLowerCase()); }
    else         { leaf.addClass('needs-type');                                     }
  }

  self.handle_resize = function()
  {
    const leaves = tree.find('li');
    leaves.each( function(index) { 
      tend($(this)); 
    });
  }

  return self;
}

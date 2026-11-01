/**
 * The arborist function sets up an object that supports the editor tree.
 *   More Specifically, it handles the setting of the section and question names
 *   and the question types displayed in the tree.  
 *
 *   But, it does much more than simply setting the text displayed in the <li> element.
 *
 *   - It checks to see if the the text will fit within the current width of the <li>
 *     - If not, it truncates it and adds a '...' at the end to show continuation
 *     - It sets the 'needs-xxx' class if the text is empty
 *
 *   - It handles resizing of the <li> elements and adjusts how the text is to be
 *       truncated to fit... if necessary
 *
 *   - It manages the needs-value and needs-type classes when these are not yet
 *       set in the corresponding section or question data
 *
 * @typedef {Object} Arborist
 * @property { ($tree:jQuery) => void } tend
 * @property { ($leaf:jQuery, text:string, type:QuestionType) => void } initialize
 * @property { ($leaf:jQuery, text:string) => void } update_label
 * @property { ($leaf:jQuery, type:QuestionType) => void } update_type
 * @property { (void) => Arborist } handle_resize
 */

/**
 * Initializes a Arborist object
 * @param {NavTreeController} tree 
 * @returns {Arborist} 
 */
export default function init(tree)
{
  // used to measure texxt... inserted into the DOM, but hidden away
  const _calipers = $('<span>')
    .addClass('text-measurer')
    .css({ 
      'position':'absolute', 
      'visibility':'hidden', 
      'white-space':'nowrap', 
      'pointer-events':'none' })
    .appendTo(tree)[0];

  const self = {};

  /**
   * Manages the apperance of the text within a leaf node of the navigation tree.
   *   Called whenever the geometry or content leaf's DOM element changes
   * @param {jQuery} $leaf 
   * 
   * @note leaf is expected to be part of the tree, but there isn't any 
   *    real reason is must be as long as it "looks like" a leaf (duck typing)
   */
  function tend($leaf)
  {
    const full_text  = ($leaf.data('full-text') || '');

    const $name_span = $leaf.find('span.name').first();
    const $leaf_text = $name_span.length > 0 ? $name_span : $leaf;

    if(full_text.length === 0) { 
      $leaf_text.text('');
      return; 
    }

    const maxWidth = $leaf[0].clientWidth - 32; // to account for right-margin and type icon in ::before
    if(maxWidth <= 0) {
      // tree is too narrow to show leaf labels
      $leaf_text.text('');
      return;
    }

    const style = getComputedStyle($leaf_text[0]);
    _calipers.style.font       = style.font;
    _calipers.style.fontSize   = style.fontSize;
    _calipers.style.fontWeight = style.fontWeight;
    _calipers.style.fontFamily = style.fontFamily;
    _calipers.textContent      = full_text;

    if(_calipers.offsetWidth <= maxWidth) {
      $leaf_text.text(full_text);
      return;
    }

    // find longest string that fits
    const suffix = '...';
    let low = 3;
    let high = full_text.length;
    while(low < high) {
      let mid = Math.floor((low+high)/2);
      _calipers.textContent = full_text.slice(0,mid) + suffix;
      if(_calipers.offsetWidth <= maxWidth) {
        low = mid+1;
      } else {
        high = mid;
      }
    }

    $leaf_text.text(full_text.slice(0,low-1) + suffix);
  }

  /**
   * Configures a tree leaf object to be tendable by the Arborist
   * @param {jQuery} $leaf 
   * @param {string} text 
   * @param {null|QuestionType} type 
   */
  self.initialize = function($leaf,text,type)
  {
    // leaf.text(text);
    self.update_label($leaf,text);
    self.update_type($leaf,type);
  }

  /**
   * Updates the text displayed in the leaf node... 
   *   and then calls tend to make sure it looks good
   * @param {jQuery} $leaf 
   * @param {string} text 
   */
  self.update_label = function($leaf,text)
  {
    const fulltext   = text?.trim() ?? '';
    $leaf.data('full-text',fulltext);
    $leaf.toggleClass('needs-value',!fulltext);
    tend($leaf);
  }

  /**
   * Updates the type of question leaves.
   *   Does nothing for section or group leaves
   * @param {jQuery} leaf 
   * @param {null|QuestionType} new_type 
   * @param {null|QuestionType} [old_type=null]
   */
  self.update_type = function($leaf,new_type,old_type=null)
  {
    if($leaf.hasClass('question')) {
      // remove old type (if specified)
      if(old_type) { $leaf.removeClass(old_type.toLowerCase()); }
      // adds new type (or needs-type if not specified)
      if(new_type) { $leaf.removeClass('needs-type').addClass(new_type.toLowerCase()); }
      else         { $leaf.addClass('needs-type');                                     }
    }
  }

  /**
   * Informs the arborist that it needs to tend to all of its leaves
   *   in response to a resize event
   * @returns {Arborist} to enable chaining
   */
  self.handle_resize = function()
  {
    const $leaves = tree.find('li').not('.virtual');
    $leaves.each( function(_,leaf) { tend($(leaf)); });
  }

  return self;
}

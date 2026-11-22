/// <reference path="../data-types.js" />

/**
 * Controller for the modal option editor dialog
 * @typedef {Objct} OptionEditor
 * @property {() => void} show
 */

/** 
 * @typedef {Object} OptionEditorResult
 * @property {string} label
 * @property {boolean} is_common
 */

/**
 * @callback OptionEditorCompletionHandler
 * @param {OptionEditorResult} result
 * @returns {void}
 */

/**
 * @callback NewOptionCompletionHandler
 * @param {string} label
 * @param {boolean} is_common
 * @returns {void}
 */

/**
 * Constructs an OptionEditor instance
 * @param {RuntimeContext} ce 
 * @param {SurveyViewController} controller 
 * @returns {OptionEditor}
 */
export default function init(ce,controller)
{
  const $dialog      = $('#option-editor');
  const $prompt      = $dialog.find('div.prompt');
  const $locked      = $dialog.find('div.locked.scope');
  const $label_input = $('#option-text');
  const $length_msg  = $dialog.find('div.wordcount');
  const $common_rb   = $('#option-is-common');
  const $specific_rb = $('#option-is-specific');
  const $ok_button   = $('#option-ok');

  let old_label   = null;
  let was_common = null;

  const self = {};

  /**
   * Displays the modal option editor and waits for response.
   *   On response, invokes the specified completion handler
   * @param {string} prompt_str 
   * @param {OptionEditorCompletionHandler} completion_handler 
   * @param {string} label 
   * @param {boolean|null} is_common
   * @param {boolean} must_be_common
   */
  async function show( prompt_str, completion_handler, 
    label='', is_common=null, must_be_common=false)
  {
    old_label = label;
    was_common = is_common;

    $prompt.html(prompt_str);
    $label_input.val(label ?? '');

    if(must_be_common) {
      $common_rb.setChecked().disable();
      $specific_rb.setUnchecked().disable();
      $('div.radio.scope').hide();
      $locked.removeClass('hidden');
      $locked.html("Must be for common use: in use by another question");
    } else {
      $common_rb.setChecked(is_common === true).enable();
      $specific_rb.setChecked(is_common === false).enable();
      $('div.radio.scope').show();
      $locked.addClass('hidden');
    }

    $ok_button.disable();
    $dialog
      .off('.optionEditor')
      .on('change.optionEditor','input[name="scope"]',update_submit)
      .on('input.optionEditor',update_submit);

    const response = new Promise(resolve => {
      $dialog.one('close', () => {
        if($dialog.prop('returnValue') === 'ok') {
          resolve({
            label:$label_input.val().trim().replace(/\s+/g,' '),
            is_common:$common_rb.isChecked(),
          });
        } else {
          resolve(null);
        }
      })
    });

    $dialog.prop('returnValue',null);
    $dialog[0].showModal();
    $label_input.focus();

    const result = await response;
    if( result !== null ) {
      completion_handler(result);
    }
  }

  /**
   * Called whenever the input label changes or a radio button is
   *   selected.  Enables the submit button only if there is a 
   *   valid option label and one of the radio buttons is selected 
   * @param {Event} e 
   */
  function update_submit(e) 
  {
    const scope = $dialog.find('input[name="scope"]:checked').val() ?? [];
    const label = $label_input.val().trim();

    const show_length = 50;
    const warn_length = 100;
    const max_length=125;

    if(label.length < show_length) {
      $length_msg.removeClass('show');
    } else {
      $length_msg.html(String(label.length) + "/" + String(max_length));
      $length_msg.addClass('show').removeClass('warning error');
      if(label.length > max_length) { $length_msg.addClass('error'); }
      else if(label.length > warn_length) { $length_msg.addClass('warning'); }
    }
    if( scope.length === 0 || label.length === 0 || label.length > max_length) { 
      $ok_button.disable(); 
      return;
    }

    const is_common = ( scope === 'common' );
    if( label === old_label && is_common === was_common ) { 
      $ok_button.disable(); 
      return;
    }

    $ok_button.enable();
  }

  /**
   * Displays the modal option editor to edit an existing option
   * @param {string} label
   * @param {boolean} is_common
   * @param {boolean} must_be_common
   * @param {NewOptionCompletionHandler} completion_handler
   */
  self.edit_option = function(label,is_common,must_be_common,completion_handler)
  {
    show( 'Edit Option:', function(result) {
      if(result === null) { return; }
      completion_handler(result.label,result.is_common);
    }, 
    label, is_common, must_be_common);
  };

  /**
   * Displays the modal option editor to prompt for a new option
   * @param {NewOptionCompletionHandler} completion_handler
   */
  self.new_option = function(completion_handler)
  {
    show('New Option:',function(result) {
      if(result === null) { return; }
      completion_handler(result.label, result.is_common);
    });
  }

  return self;
}


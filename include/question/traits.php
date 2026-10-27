<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/question/flags.php'));;
require_once(app_file('include/question/layout.php'));

interface QuestionHasWording {
  public function wording() : string;
}

trait QuestionIntroTrait
{
  protected string $_intro = "";

  /**
   * Question subclass constructor support for setting the (optional) intro text
   * @param array $data
   *   where $data is array { intro?: string }
   * @return void 
   */
  protected function set_intro(array $data) : void 
  {
    $this->_intro = trim($data['intro'] ?? '');
  }

  /**
   * Question has an associated intro block
   * @return bool 
   */
  public function has_intro() : bool   { return $this->_intro !== ""; }

  /**
   * Text (may be markdown) to display in the intro block
   * @return string  (will be "" if the question has no intro text)
   */
  public function intro() : string { return $this->_intro; }

  /**
   * Adds the intro parameter to an array for consumption by JavaScript
   * @param array &$params 
   * @return void 
   */
  protected function add_intro_for_js(array &$params) : void { 
    $params['intro'] = $this->_intro;
  }

  /**
   * Adds the intro parameter to an array for insertion into a MySQL database
   * @param array &$params 
   * @return void 
   */
  protected function add_intro_for_db(array &$params) : void { 
    $params['intro'] = $this->_intro ?: null;
  }
}

trait QuestionWordingTrait
{
  protected string $_wording = "";

  /**
   * Question subclass constuctor support for setting the wording text
   * @param array $data
   *   where $data is { wording? : string } 
   * @return void 
   */
  protected function set_wording(array $data) : void { 
    $this->_wording = trim($data['wording'] ?? '');
  }

  /**
   * The core wording of the question
   * @return string (should never be empty)
   */
  public function wording() : string { return $this->_wording; }

  /**
   * Adds the wording parameter to an array for consupmption by JavaScript
   * @param array &$params 
   * @return void 
   */
  protected function add_wording_for_js(array &$params) : void { 
    $params['wording'] = $this->_wording; 
  }

  /**
   * Adds the wording parameter to an array for insertion into a MySQL database
   * @param array &$params 
   * @return void 
   */
  protected function add_wording_for_db(array &$params) : void { 
    $params['wording'] = $this->_wording; 
  }
}

trait QuestionQualifierTrait
{
  protected string $_qualifier = "";

  /**
   * Question subclass constructor support for setting the (optional) qualifier text
   * @param array $data
   *   where $data is array { qualifier?: string }
   * @return void 
   */
  protected function set_qualifier(array $data) : void 
  {
    $this->_qualifier = trim($data['qualifier'] ?? '');
  }

  /**
   * Question has an associated qualifier block
   * @return bool 
   */
  public function has_qualifier() : bool   { return $this->_qualifier !== ""; }

  /**
   * Prompt to display for the qualifier input field
   * @return string  (will be "" if the question has no qualifier text)
   */
  public function qualifier() : string { return $this->_qualifier; }

  /**
   * Adds the qualifier parameter to an array for consumption by JavaScript
   * @param array &$params 
   * @return void 
   */
  protected function add_qualifier_for_js(array &$params) : void { 
    $params['qualifier'] = $this->_qualifier;
  }

  /**
   * Adds the qualifier parameter to an array for insertion into a MySQL database
   * @param array &$params 
   * @return void 
   */
  protected function add_qualifier_for_db(array &$params) : void { 
    $params['qualifier'] = $this->_qualifier ?: null;
  }
}

trait QuestionLayoutTrait
{
  protected QuestionLayout $_layout = QuestionLayout::None;

  /**
   * Question subclass constructor support for setting the layout property
   * The layout will be provided with one of the following keys:
   *   layout (if data came from Javascript)
   *   flags (if data came from database, must then be unpacked)
   * There should never be a case where both keys are present, buf it that were
   *   to happen, layout will take precedence over the flags bitfield
   * @param array $data 
   * @return void 
   */
  protected function set_layout(array $data) : void {
    if(array_key_exists('layout',$data)) {
      $this->_layout = QuestionLayout::from($data['layout']);
    } else {
      $flags = new QuestionFlags($data['flags'] ?? 0);
      $this->_layout = $flags->layout($this->_type);
    }
  }

  /**
   * QuestionLayout enum value
   * @return QuestionLayout
   */
  public function layout() : QuestionLayout { return $this->_layout; }

  public function layout_string() : string { return $this->layout()->value; }
  public function layout_class()  : string { return strtolower($this->layout_string()); }

  /**
   * Adds the layout parameter to an array for consumption by JavaScript
   * @param array &$params 
   * @return void 
   */
  protected function add_layout_for_js(array &$params) : void {
    $params['layout'] = $this->_layout;
  }

  /**
   * Adds the layout parameter to an array for insertion into a MySQL database
   * @param array &$params 
   * @return void 
   */
  protected function add_layout_for_db(array &$params) : void {
    $flags = new QuestionFlags($params['flags'] ?? 0);
    $flags->layout($this->_type, $this->_layout);
    $params['flags'] = $flags->get_bits();
  }
}

trait QuestionOtherTrait
{
  protected bool $_has_other = false;
  protected string $_other = "";

  /**
   * Question subclass constructor support for setting the 'other' property
   * The other flag will be provided with one of the following keys:
   *   has_other (if data came from Javascript)
   *   flags (if data came from database, must then be unpacked)
   * The optional `other` prompt may be provided with the 'other' key if has_other is true
   * There should never be a case where both keys are present, buf it that were
   *   to happen, has_other will take precedence over the flags bitfield
   * @param array $data 
   * @return void 
   */
  protected function set_other(array $data) : void {
    if(array_key_exists('has_other',$data)) {
      $this->_has_other = $data['has_other'];
    } else {
      $flags = new QuestionFlags($data['flags'] ?? 0);
      $this->_has_other = $flags->has_other();
    }
    if($this->_has_other) {
      $this->_other = $data['other'] ?? '';
    }
  }

  /**
   * Wheter or not the question supports addingn a user defined option
   * @return bool 
   */
  public function has_other() : bool { return $this->_has_other; }

  /**
   * Prompt for user defined option (if has_other is true)
   * @return null|string 
   */
  public function other_prompt() : ?string {
    return $this->_has_other ? ( $this->_other ?: 'Other') : null;
  }

  /**
   * Adds the has_other and other parameters to an array for consumption by JavaScript
   * @param array &$params 
   * @return void 
   */
  protected function add_other_for_js(array &$params) : void {
    $params['has_other'] = $this->_has_other;
    if($this->_has_other) {
      $params['other'] = $this->_other ?: 'Other';
    }
  }
  
  /**
   * Adds the has_other and other parameters to an array for insertion into a MySQL database
   * @param array &$params 
   * @return void 
   */
  protected function add_other_for_db(array &$params) : void {
    $flags = new QuestionFlags($params['flags'] ?? 0);
    $flags->has_other($this->_has_other);
    $params['flags'] = $flags->get_bits();

    if($this->_has_other) {
      $params['other'] = $this->_other ?: null;
    }
  }
}

trait QuestionOptionsTrait
{
  protected array $_options = [];

  /**
   * Question subclass constuctor support for setting the selectable options
   * If the data came from Javascript, the options will be associated with the 'options' key
   * If the data came from Database, the options will need to be queried from the database
   *   given the question_id and survey_id keys in the data
   * @param array $data
   * @return void 
   */
  protected function set_options(array $data) : void {
    if(array_key_exists('options',$data)) {
      $this->_options = $data['options'];
    } 
    else 
    {
      $question_id = $data['question_id'];
      $survey_id = $data['survey_id'];
      $query = <<<SQL
        SELECT option_id
        FROM   tlc_srv_question_options
        WHERE survey_id=? and question_id=?
        ORDER BY sequence
      SQL;
      $this->_options = MySQLFetchColumn($query, 'ii', $survey_id, $question_id);
    }
  }

  /**
   * Returns the selectable options
   * @return array 
   */
  public function options() : array { return $this->_options; }

  /**
   * Adds the selectable options to an array for consumption by JavaScript
   * @param array &$params 
   * @return void 
   */
  protected function add_options_for_js(array &$params) : void {
    $params['options'] = $this->_options;
  }

  /**
   * Adds the selectable options to an array for insertion into a MySQL database
   * @param array &$params 
   * @return void 
   */
  protected function add_options_for_db(array &$params) : void {
    $params['options'] = $this->_options;
  }
}

trait QuestionPopupTrait
{
  protected string $_popup = "";

  /**
   * Question subclass constuctor support for setting the help popup text
   * The (optional) popup string will be provided with one of the following keys:
   *   - popup (if data came from Javascript)
   *   - info (if data came from the database)
   * There should never be a case where both keys are present, but if that were
   *   to happend, popup will take precedence over info.
   * @param array $data
   * @return void 
   */
  protected function set_popup(array $data) : void {
    $this->_popup = trim($data['popup'] ?? $data['info'] ?? '');
  }

  /**
   * Question has an associated help popup
   * @return bool 
   */
  public function has_popup() : bool { return $this->_popup !== ""; }

  /**
   * Text (may be markdown) to display in a help popup
   * @return string  (will be "" if the question has no popup text)
   */
  public function popup_text() : string { return $this->_popup; }

  /**
   * Adds the popup text to an array for consumption by JavaScript
   * @param mixed &$params 
   * @return void 
   */
  protected function add_popup_for_js(array &$params) : void {
    $params['popup'] = $this->_popup;
  }

  protected function add_popup_for_db(array &$params) : void {
    $params['popup'] = $this->_popup ?: null;
  }
}


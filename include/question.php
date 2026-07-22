<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/question_types.php'));
require_once(app_file('include/question_layout.php'));

/**
 * Base class for the translation of questions 
 *   between database and php and between php and javascript, 
 *   including translation of keyword differences for each environment
 *   
 * The base class handles: php key:php type [mysql column / js key]
 *   id : int [ question_id / question_id ]
 *   type : QuestionType [ question_type / question_type]
 */
class Question
{
  protected int            $_id;
  protected QuestionType   $_type;

  /**
   * Constructs the question base class given a single row from a
   *   query of the questions table in the database.
   * @param array {
   *   question_id   : int
   *   question_type : string
   * } $db_entry
   */
  protected function __construct(array $db_entry)
  {
    $this->_id   = $db_entry['question_id'];
    $this->_type = QuestionType::from($db_entry['question_type']);
  }

  /**
   * Constructs and returns a new Question subclass instance given a single
   *   row from a query of the question table in the database.
   * @param int $survey_id
   * @param array {
   *   question_id   : int
   *   question_type : string
   *   wording       : string
   *   flags         : int (a bitstring)
   *   other         : null | string
   *   qualifier     : string
   *   intro         : string
   *   info          : string
   * } $db_entry
   * @return Question
   * @note the survey ID is actually only needed to instantiate SELECT questions,
   *   but as it is this factory method's job to determine the question type,
   *   the survey ID is always required.
   */
  public static function from_database_entry(int $survey_id, array $db_entry) : self
  {
    switch(QuestionType::from($db_entry['question_type']))
    {
      case QuestionType::Info:
        return new InfoQuestion($db_entry);
        break;
      case QuestionType::Bool:
        return new BoolQuestion($db_entry);
        break;
      case QuestionType::FreeText:
        return new FreetextQuestion($db_entry);
        break;
      case QuestionType::SelectOne:  // intentional fall-through
      case QuestionType::SelectMulti:
        return new SelectQuestion($survey_id, $db_entry);
        break;
      default:
        internal_error("Unknown question type: ". $db_entry['question_type']);
        throw new \LogicException("Should never get here... this is here simply to satisfy static analyzers.");
        break;
    }
  }

  public function id()   : int          { return $this->_id;   }
  public function type() : QuestionType { return $this->_type; }

  public function info_tag()   : ?string { return null; }
  public function info_text()  : ?string { return null; }
  public function popup_text() : ?string { return null; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function format_for_js() : array
  {
    return [
      'id' => $this->_id,
      'type' => $this->_type,
    ];
  }
};


/**
 * Question subclass for the translation of info questions.
 *   
 * This class handles: php key:php type [mysql column / js key]
 *   text : string [info / info]
 *   tag  : string [wording / infotag]
 */
final class InfoQuestion extends Question
{
  protected string $_tag;
  protected string $_text;
  protected bool   $_render_in_group;

  /**
   * Constructs an InfoQuestion instance given a singel row from a
   *   query of the questions table in the database.
   * @param array {
   *   question_id   : int
   *   question_type : string
   *   wording       : string  // info tag value
   *   info          : string  // info text value
   * } $db_entry
   */
  public function __construct(array $db_entry)
  {
    parent::__construct($db_entry);

    $this->_text = $db_entry['info'];
    $this->_tag = $db_entry['wording'] ?? '';

    $flags = new QuestionFlags($db_entry['flags'] ?? 0);
    $this->_render_in_group = $flags->render_in_group();
  }

  public function info_tag()  : ?string { return $this->_tag; }
  public function info_text() : ?string { return $this->_text; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function format_for_js() : array 
  {
    $rval = parent::format_for_js();
    $rval['info']    = $this->_text;
    $rval['infotag'] = $this->_tag;
    return $rval;
  }
};


/**
 * Question subclass for the translation of boolean questions.
 *   
 * This class handles: php key:php type [mysql column / js key]
 *   wording   : string [wording / wording]
 *   intro     : ?string [intro / intro]
 *   qualifier : ?string [qualifier / qualifier]
 *   popup     : ?string [info / popup]
 *   layout    : QuestionLayout [flags / layout]
 */
final class BoolQuestion extends Question
{
  protected  string $_wording;
  protected ?string $_intro = null;
  protected ?string $_qualifier = null;
  protected ?string $_popup = null;

  protected QuestionLayout $_layout = QuestionLayout::None;
  /**
   * Constructs the question base class given a single row from a
   *   query of the questions table in the database.
   * @param array {
   *   question_id   : int
   *   question_type : string
   *   wording       : string
   *   qualifier     : string
   *   intro         : string
   *   info          : string   // popup hint text
   * } $db_entry
   */
  public function __construct(array $db_entry)
  {
    parent::__construct($db_entry);

    $this->_wording   = $db_entry['wording'];
    $this->_intro     = $db_entry['intro'];
    $this->_qualifier = $db_entry['qualifier'];
    $this->_popup     = $db_entry['info'];

    $question_type = QuestionType::from($db_entry['question_type']);
    
    $flags = new QuestionFlags($db_entry['flags'] ?? 0);
    $this->_layout    = $flags->layout($question_type);
  }

  public function popup_text() : ?string { return $this->_popup; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function format_for_js() : array
  {
    $rval = parent::format_for_js();
    $rval['wording']   = $this->_wording;
    $rval['intro']     = $this->_intro;
    $rval['qualifier'] = $this->_qualifier;
    $rval['popup']     = $this->_popup;
    $rval['layout']    = $this->_layout;
    return $rval;
  }
};


/**
 * Question subclass for the translation of freetext questions.
 *   
 * This class handles: php key:php type [mysql column / js key]
 *   wording   : string [wording / wording]
 *   intro     : ?string [intro / intro]
 *   popup     : ?string [info / popup]
 */
final class FreetextQuestion extends Question
{
  protected  string $_wording;
  protected ?string $_intro = null;
  protected ?string $_popup = null;
  /**
   * Constructs the question base class given a single row from a
   *   query of the questions table in the database.
   * @param array {
   *   question_id   : int
   *   question_type : string
   *   wording       : string
   *   intro         : string
   *   info          : string   // popup hint text
   * } $db_entry
   */
  public function __construct(array $db_entry)
  {
    parent::__construct($db_entry);

    $this->_wording   = $db_entry['wording'];
    $this->_intro     = $db_entry['intro'];
    $this->_popup     = $db_entry['info'];
  }

  public function popup_text() : ?string { return $this->_popup; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function format_for_js() : array
  {
    $rval = parent::format_for_js();
    $rval['wording']   = $this->_wording;
    $rval['intro']     = $this->_intro;
    $rval['popup']     = $this->_popup;
    return $rval;
  }
};


/**
 * Question subclass for the translation of select questions.
 *   
 * This class handles: php key:php type [mysql column / js key]
 *   wording   : string [wording / wording]
 *   intro     : ?string [intro / intro]
 *   qualifier : ?string [qualifier / qualifier]
 *   has_other : bool [flags / has_other]
 *   other     : ?string [other / other]
 *   popup     : ?string [info / popup]
 *   layout    : QuestionLayout [flags / layout]
 *   options   : array<string> [options table / options]
 */
final class SelectQuestion extends Question
{
  protected  string $_wording;
  protected ?string $_intro = null;
  protected ?string $_qualifier = null;
  protected bool    $_has_other = false;
  protected ?string $_other = null;
  protected array   $_options = [];
  protected ?string $_popup= null;

  protected QuestionLayout $_layout = QuestionLayout::None;

  /**
   * Constructs the question base class given a single row from a
   *   query of the questions table in the database.
   * @param int $survey_id
   * @param array {
   *   question_id   : int
   *   question_type : string
   *   wording       : string
   *   flags         : int (a bitstring)
   *   other         : null | string
   *   qualifier     : string
   *   intro         : string
   *   info          : string   // popup hint text
   * } $db_entry
   */
  public function __construct(int $survey_id, array $db_entry)
  {
    parent::__construct($db_entry);

    $this->_wording   = $db_entry['wording'];
    $this->_intro     = $db_entry['intro'];
    $this->_qualifier = $db_entry['qualifier'];
    $this->_other     = $db_entry['other'];
    $this->_popup     = $db_entry['info'];

    $question_id   = $db_entry['question_id'];
    $question_type = QuestionType::from($db_entry['question_type']);

    $flags = new QuestionFlags($db_entry['flags'] ?? 0);
    $this->_layout    = $flags->layout($question_type);
    $this->_has_other = $flags->has_other();

    $query = <<<SQL
      SELECT option_id
      FROM   tlc_srv_question_options
      WHERE survey_id=? and question_id=?
      ORDER BY sequence
    SQL;
    $this->_options = MySQLFetchColumn($query, 'ii', $survey_id,$question_id);
  }

  public function popup_text() : ?string { return $this->_popup; }
    /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function format_for_js() : array
  {
    $rval = parent::format_for_js();
    $rval['wording']   = $this->_wording;
    $rval['intro']     = $this->_intro;
    $rval['qualifier'] = $this->_qualifier;
    $rval['popup']     = $this->_popup;
    $rval['layout']    = $this->_layout;
    $rval['options']   = $this->_options;

    $rval['has_other'] = $this->_has_other;
    if($this->_has_other) {
      $other = $this->_other ?? '';
      $rval['other'] = ($other !== '') ? $other : 'Other';
    }

    return $rval;
  }
};
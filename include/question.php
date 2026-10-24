<?php
namespace tlc\tts;

use LogicException;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/question_types.php'));
require_once(app_file('include/question_flags.php'));;
require_once(app_file('include/question_layout.php'));
require_once(app_file('include/question_traits.php'));

/**
 * Base class for the translation of questions 
 *   between database and php and between php and javascript, 
 *   including translation of keyword differences for each environment
 *   
 * The base class handles: php key:php type [mysql column / js key]
 *   id : int [ question_id / id ]
 *   type : QuestionType [ question_type / type]
 */
class Question
{
  protected int            $_id;
  protected QuestionType   $_type;

  /**
   * Constructs the question base class
   * @param array $data
   *   where $data is {question_id:int, question_type:string} | array{id:int,type:string}
   */
  protected function __construct(array $data)
  {
    $this->_id   = $data['question_id'] ?? $data['id'];
    $this->_type = QuestionType::from($data['question_type'] ?? $data['type']);
  }

  /**
   * Quesiton factory method which constructs and returns a new Question subclass
   *   instance given a data array. The source of this data will be either:
   *   - a single row from a query of the question table in the database
   *   - updated question content from a javascript AJAX call
   * @param array $data (see subclass constructors for details)
   * @param ?int $survey_id (used only to select options from the database)
   * @return Question (subclass thereof)
   */
  public static function from(array $data, ?int $survey_id=null) : self
  {
    $data['survey_id'] = $survey_id;

    $type = QuestionType::from($data['question_type'] ?? $data['type']);

    switch($type)
    {
      case QuestionType::Info:
        return new InfoQuestion($data);
        break;
      case QuestionType::Bool:
        return new BoolQuestion($data);
        break;
      case QuestionType::FreeText:
        return new FreetextQuestion($data);
        break;
      case QuestionType::SelectOne:  // intentional fall-through
      case QuestionType::SelectMulti:
        return new SelectQuestion($data);
        break;
      default:
        internal_error("Unknown question type: ". $data['question_type']);
        throw new \LogicException("Should never get here... this is here simply to satisfy static analyzers.");
        break;
    }
  }

  public function id()   : int          { return $this->_id;   }
  public function type() : QuestionType { return $this->_type; }

  public function box_class() : string { return 'question-box'; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    return [
      'id' => $this->_id,
      'type' => $this->_type,
    ];
  }

  /**
   * Converts the content to an array suitable to writing to database
   *   The aray keys are set to reflect column names in the question table
   * @return array 
   */
  public function for_database() : array
  {
    return [
      'id' => $this->_id,
      'type' => $this->_type->value,
    ];
  }
};


/**
 * Question subclass for the translation of info questions.
 *   
 * This class handles: php key:php type [mysql column / js key]
 *   id       : int [ question_id / id ]
 *   type     : QuestionType [ question_type / type]
 *   text     : string [info / info]
 *   tag      : string [wording / infotag]
 *   in_group : bool [ flags / in_group ]
 */
final class InfoQuestion extends Question
{
  protected string $_tag;
  protected string $_text;
  protected bool   $_in_group;

  /**
   * Constructor
   * @param array $data
   *   When $data_source is database
   *      array {
   *        question_id:   int,
   *        question_type: string,
   *        info:          string        // info text
   *        wording?:      string|null   // info tag
   *        flags:         bitstring     // in_group
   *      }
   *   When $data_source is Javascript
   *      array {
   *        id:       int,
   *        type:     string,
   *        infotext: string        // info text
   *        infotag?: string|null   // info tag
   *        in_group: bool
   *      }
   * @note infotag tag is optional: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data) 
  {
    parent::__construct($data);
    $this->_text = $data['info'];

    $this->_tag = $data['infotag'] ?? $data['wording'] ?? '';
    if(array_key_exists('in_group',$data)) {
      $this->_in_group = $data['in_group'];
    } else {
      $flags = new QuestionFlags($data['flags'] ?? 0);
      $this->_in_group = $flags->in_group();
    }
  }

  public function info_tag()  : string { return $this->_tag; }
  public function info_text() : string { return $this->_text; }

  public function box_class() : string { return 'info-box'; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array 
  {
    $rval = parent::for_js();
    $rval['info']     = $this->_text;
    $rval['infotag']  = $this->_tag;
    $rval['in_group'] = $this->_in_group;
    return $rval;
  }

  /**
   * Converts the content to an array suitable to writing to database
   *   The aray keys are set to reflect column names in the question table
   * @return array 
   */
  public function for_database() : array
  {
    $flags = new QuestionFlags();
    $flags->in_group($this->_in_group);

    $rval = parent::for_database();
    $rval['info'] = $this->_text;
    $rval['wording'] = $this->_tag ? $this->_tag : null;
    $rval['flags'] = $flags->get_bits();
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
final class FreetextQuestion extends Question implements QuestionHasWording
{
  use QuestionIntroTrait;
  use QuestionWordingTrait;
  use QuestionPopupTrait;

  /**
   * Constructor
   * @param array $data 
   *   When $data_source is Database
   *      array {
   *        question_id:   int,
   *        question_type: string,
   *        wording:       string
   *        intro?:        string|null
   *        info?:         string|null     // popup hint
   *      }
   *   When $data_source is Javascript
   *      array {
   *        id:         int,
   *        type:       string,
   *        wording     string
   *        intro?:     string|null
   *        popup?:     string|null     // popup hint
   *      }
   * 
   * @note for optional fields: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data)
  {
    parent::__construct($data);

    $this->set_intro($data);
    $this->set_wording($data);
    $this->set_popup($data);
  }
 
  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    $rval = parent::for_js();
    $this->add_wording_for_js($rval);
    $this->add_intro_for_js($rval);
    $this->add_popup_for_js($rval);
    return $rval;
  }
  
  /**
   * Converts the content to an array suitable to writing to database
   *   The aray keys are set to reflect column names in the question table
   * @return array 
   */
  public function for_database() : array
  {
    $rval = parent::for_database();
    $this->add_wording_for_db($rval);
    $this->add_intro_for_db($rval);
    $this->add_popup_for_db($rval);
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
final class BoolQuestion extends Question implements QuestionHasWording
{
  use QuestionIntroTrait; 
  use QuestionWordingTrait;
  use QuestionQualifierTrait;
  use QuestionLayoutTrait;
  use QuestionPopupTrait;

  /**
   * Constructor
   * @param array $data 
   *   When $data_source is Database
   *      array {
   *        question_id:   int,
   *        question_type: string,
   *        wording:       string
   *        qualifier?:    string|null
   *        intro?:        string|null
   *        info?:         string|null     // popup hint
   *        flags:         bitstring       // layout
   *      }
   *   When $data_source is Javascript
   *      array {
   *        id:         int,
   *        type:       string,
   *        wording:    string
   *        qualifier?: string|null
   *        intro?:     string|null
   *        popup?:     string|null
   *        layout:     string
   *      }
   * 
   * @note for optional fields: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data)
  {
    parent::__construct($data);
    $this->set_intro($data);
    $this->set_wording($data);
    $this->set_popup($data);
    $this->set_qualifier($data);
    $this->set_layout($data);
  }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    $rval = parent::for_js();
    $this->add_wording_for_js($rval);
    $this->add_intro_for_js($rval);
    $this->add_qualifier_for_js($rval);
    $this->add_layout_for_js($rval);
    $this->add_popup_for_js($rval);
    return $rval;
  }
  
  /**
   * Converts the content to an array suitable to writing to database
   *   The aray keys are set to reflect column names in the question table
   * @return array 
   */
  public function for_database() : array
  {
    $rval = parent::for_database();
    $this->add_wording_for_db($rval);
    $this->add_intro_for_db($rval);
    $this->add_qualifier_for_db($rval);
    $this->add_layout_for_db($rval);
    $this->add_popup_for_db($rval);
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
final class SelectQuestion extends Question implements QuestionHasWording
{
  use QuestionIntroTrait;
  use QuestionWordingTrait;
  use QuestionQualifierTrait;
  use QuestionLayoutTrait;
  use QuestionOtherTrait;
  use QuestionOptionsTrait;
  use QuestionPopupTrait;

  /**
   * Constructor
   * @param array $data 
   *   When $data_source is Database
   *      array {
   *        question_id:   int,
   *        question_type: string,
   *        wording:       string
   *        other?:        string|null
   *        qualifier?:    string|null
   *        intro?:        string|null
   *        info?:         string|null     // popup hint
   *        flags:         bitstring       // layout and has_other
   *      }
   *   When $data_source is Javascript
   *      array {
   *        id:         int,
   *        type:       string,
   *        wording:    string
   *        has_other:  bool
   *        other?:     string|null
   *        qualifier?: string|null
   *        intro?:     string|null
   *        popup?:     string|null
   *        layout:     string
   *      }
   * 
   * @note for optional fields: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data)
  {
    parent::__construct($data);
    $this->set_intro($data);
    $this->set_wording($data);
    $this->set_qualifier($data);
    $this->set_layout($data);
    $this->set_other($data);
    $this->set_options($data);
    $this->set_popup($data);
  }

  /**
   * Returns whether or not this is a multi select
   *   True = multi select
   *  False = single select
   * 
   * @return bool 
   */
  public function is_multi() : bool 
  {
    return $this->_type === QuestionType::SelectMulti;
  }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    $rval = parent::for_js();
    $this->add_intro_for_js($rval);
    $this->add_wording_for_js($rval);
    $this->add_qualifier_for_js($rval);
    $this->add_layout_for_js($rval);
    $this->add_other_for_js($rval);
    $this->add_popup_for_js($rval);
    $this->add_options_for_js($rval);
    return $rval;
  }
  
  /**
   * Converts the content to an array suitable to writing to database
   *   The aray keys are set to reflect column names in the question table
   * @return array 
   */
  public function for_database() : array
  {
    $rval = parent::for_database();
    $this->add_intro_for_db($rval);
    $this->add_wording_for_db($rval);
    $this->add_qualifier_for_db($rval);
    $this->add_layout_for_db($rval);
    $this->add_other_for_db($rval);
    $this->add_options_for_db($rval);
    $this->add_popup_for_db($rval);
    return $rval;
  }
};
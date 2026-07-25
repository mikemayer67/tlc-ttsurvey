<?php
namespace tlc\tts;

use LogicException;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/question_types.php'));
require_once(app_file('include/question_flags.php'));;
require_once(app_file('include/question_layout.php'));

enum QuestionDataSource {
  case Database;
  case Javascript;
};

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
   * @param int $id 
   * @param string $type 
   */
  protected function __construct(int $id, string $type)
  {
    $this->_id   = $id;
    $this->_type = QuestionType::from($type);
  }

  /**
   * Question factory method which constructs and returns a new Question subclass
   *   instance given a single row from a query of the question table in the database.
   * @param int $survey_id
   * @param array $db_entry  (see subclass constructors for details)
   * @return Question (subclass thereof)
   * @note the survey ID is actually only needed to instantiate SELECT questions,
   *   but as it is this factory method's job to determine the question type,
   *   the survey ID is always required.
   */
  public static function from_database(int $survey_id, array $db_entry) : self
  {
    $db_entry['survey_id'] = $survey_id;
    $type = QuestionType::from($db_entry['question_type']);
    return self::from_array($type,$db_entry, QuestionDataSource::Database);
  }

  /**
   * Question factory method which constructs and returns a new Question subclass
   *   instance given updated question content from a javascript AJAX call
   * @param array $js_entry  (see subclass constructors for details)
   * @return Question (subclass thereof)
   * @note the survey ID is actually only needed to instantiate SELECT questions,
   *   but as it is this factory method's job to determine the question type,
   *   the survey ID is always required.
   */
  public static function from_js(array $js_entry) : self
  {
    $type = QuestionType::from($js_entry['type']);
    return self::from_array($type,$js_entry, QuestionDataSource::Javascript);
  }

  /**
   * The heart of the Question factory methods
   * @param QuetionType $type
   * @param array $data (see subclass constructors for details)
   * @param QuestionDataSource $data_source 
   * @return Question (subclass thereof)
   */
  private static function from_array(QuestionType $type, array $data, QuestionDataSource $data_source) : self
  {
    switch($type)
    {
      case QuestionType::Info:
        return new InfoQuestion($data,$data_source);
        break;
      case QuestionType::Bool:
        return new BoolQuestion($data,$data_source);
        break;
      case QuestionType::FreeText:
        return new FreetextQuestion($data,$data_source);
        break;
      case QuestionType::SelectOne:  // intentional fall-through
      case QuestionType::SelectMulti:
        return new SelectQuestion($data,$data_source);
        break;
      default:
        internal_error("Unknown question type: ". $data['question_type']);
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
   *   When $data_source is QuestionDataSource::Database
   *      array {
   *        question_id:   int,
   *        question_type: string,
   *        info:          string        // info text
   *        wording?:      string|null   // info tag
   *        flags:         bitstring     // in_group
   *      }
   *   When $data_source is QuestionDataSource::Javascript
   *      array {
   *        id:       int,
   *        type:     string,
   *        infotext: string        // info text
   *        infotag?: string|null   // info tag
   *        in_group: bool
   *      }
   * @param QuestionDataSource $data_source 
   * @note infotag tag is optional: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data,QuestionDataSource $data_source) 
  {
    if($data_source === QuestionDataSource::Database)
    {
      parent::__construct($data['question_id'],$data['question_type']);
      $this->_text = $data['info'];
      $this->_tag  = $data['wording'] ?? '';
      $flags = new QuestionFlags($data['flags'] ?? 0);
      $this->_in_group = $flags->in_group();
    }
    else if($data_source === QuestionDataSource::Javascript)
    {
      parent::__construct($data['id'],$data['type']);
      $this->_text     = $data['info'];
      $this->_tag      = $data['infotag'] ?? '';
      $this->_in_group = $data['in_group'];
    }
  }

  public function info_tag()  : ?string { return $this->_tag; }
  public function info_text() : ?string { return $this->_text; }

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
   * Constructor
   * @param array $data 
   *   When $data_source is QuestionDataSource::Database
   *      array {
   *        question_id:   int,
   *        question_type: string,
   *        wording:       string
   *        qualifier?:    string|null
   *        intro?:        string|null
   *        info?:         string|null     // popup hint
   *        flags:         bitstring       // layout
   *      }
   *   When $data_source is QuestionDataSource::Javascript
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
   * @param QuestionDataSource $data_source 
   * @note for optional fields: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data,QuestionDataSource $data_source)
  {
    if($data_source === QuestionDataSource::Database)
    {
      parent::__construct($data['question_id'],$data['question_type']);

      $this->_wording   = $data['wording'];
      $this->_qualifier = $data['qualifier'] ?? '';
      $this->_intro     = $data['intro'] ?? '';
      $this->_popup     = $data['info'] ?? '';

      $flags = new QuestionFlags($data['flags'] ?? 0);
      $this->_layout = $flags->layout($this->_type);
    }
    else if($data_source === QuestionDataSource::Javascript)
    {
      parent::__construct($data['id'],$data['type']);
      $this->_wording   = $data['wording'];
      $this->_qualifier = $data['qualifier'] ?? '';
      $this->_intro     = $data['intro'] ?? '';
      $this->_popup     = $data['popup'] ?? '';
      $this->_layout    = QuestionLayout::from($data['layout']);
    }
  }

  public function popup_text() : ?string { return $this->_popup; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    $rval = parent::for_js();
    $rval['wording']   = $this->_wording;
    $rval['intro']     = $this->_intro;
    $rval['qualifier'] = $this->_qualifier;
    $rval['popup']     = $this->_popup;
    $rval['layout']    = $this->_layout;
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
    $flags->layout($this->_type, $this->_layout);

    $rval = parent::for_database();
    $rval['wording']   = $this->_wording;
    $rval['qualifier'] = $this->_qualifier ? $this->_qualifier : null;
    $rval['intro']     = $this->_intro     ? $this->_intro :     null;
    $rval['info']      = $this->_popup     ? $this->_popup :     null;
    $rval['flags']     = $flags->get_bits();
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
   * Constructor
   * @param array $data 
   *   When $data_source is QuestionDataSource::Database
   *      array {
   *        question_id:   int,
   *        question_type: string,
   *        wording:       string
   *        intro?:        string|null
   *        info?:         string|null     // popup hint
   *      }
   *   When $data_source is QuestionDataSource::Javascript
   *      array {
   *        id:         int,
   *        type:       string,
   *        wording     string
   *        intro?:     string|null
   *        popup?:     string|null     // popup hint
   *      }
   * 
   * @param QuestionDataSource $data_source 
   * @note for optional fields: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data,QuestionDataSource $data_source)
  {
    if($data_source === QuestionDataSource::Database)
    {
      parent::__construct($data['question_id'],$data['question_type']);
      $this->_wording   = $data['wording'];
      $this->_intro     = $data['intro'] ?? '';
      $this->_popup     = $data['info'] ?? '';
    }
    else if($data_source === QuestionDataSource::Javascript)
    {
      parent::__construct($data['id'],$data['type']);
      $this->_wording   = $data['wording'];
      $this->_intro     = $data['intro'] ?? '';
      $this->_popup     = $data['popup'] ?? '';
    }
  }

  public function popup_text() : ?string { return $this->_popup; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    $rval = parent::for_js();
    $rval['wording']   = $this->_wording;
    $rval['intro']     = $this->_intro;
    $rval['popup']     = $this->_popup;
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
    $rval['wording']   = $this->_wording;
    $rval['intro']     = $this->_intro     ? $this->_intro :     null;
    $rval['info']      = $this->_popup     ? $this->_popup :     null;
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
  /**
   * Constructor
   * @param array $data 
   *   When $data_source is QuestionDataSource::Database
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
   *   When $data_source is QuestionDataSource::Javascript
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
   * @param QuestionDataSource $data_source 
   * @note for optional fields: an empty string, null, or missing key are all equivalent
   */
  public function __construct(array $data, QuestionDataSource $data_source)
  {
    if($data_source === QuestionDataSource::Database)
    {
      $question_id = $data['question_id'];
      parent::__construct($question_id,$data['question_type']);

      $this->_wording   = $data['wording'];
      $this->_qualifier = $data['qualifier'] ?? '';
      $this->_intro     = $data['intro'] ?? '';
      $this->_popup     = $data['info'] ?? '';

      $flags = new QuestionFlags($data['flags'] ?? 0);
      $this->_layout    = $flags->layout($this->_type);
      $this->_has_other = $flags->has_other();

      if($this->_has_other) {
        $this->_other = $data['other'] ?? '';
      }

      $survey_id = $data['survey_id'];
      $query = <<<SQL
        SELECT option_id
        FROM   tlc_srv_question_options
        WHERE survey_id=? and question_id=?
        ORDER BY sequence
      SQL;
      $this->_options = MySQLFetchColumn($query, 'ii', $survey_id, $question_id);
    }
    else if($data_source === QuestionDataSource::Javascript)
    {
      parent::__construct($data['id'],$data['type']);
      $this->_wording   = $data['wording'];
      $this->_qualifier = $data['qualifier'] ?? '';
      $this->_intro     = $data['intro'] ?? '';
      $this->_popup     = $data['popup'] ?? '';
      $this->_layout    = QuestionLayout::from($data['layout']);
      $this->_has_other = $data['has_other'];
      if($this->_has_other) {
        $this->_other = $data['other'] ?? '';
      }
      $this->_options = $data['options'];
    }

  }

  public function popup_text() : ?string { return $this->_popup; }

  /**
   * Converts the content to an array suitable to passing to JavaScript via AJAX
   *   The aray keys are set to reflect what will be used with the js code.
   * @return array 
   */
  public function for_js() : array
  {
    $rval = parent::for_js();
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
  
  /**
   * Converts the content to an array suitable to writing to database
   *   The aray keys are set to reflect column names in the question table
   * @return array 
   */
  public function for_database() : array
  {
    $flags = new QuestionFlags();
    $flags->layout($this->_type, $this->_layout);
    $flags->has_other($this->_has_other);

    $rval = parent::for_database();
    $rval['wording']   = $this->_wording;
    $rval['qualifier'] = $this->_qualifier ? $this->_qualifier : null;
    $rval['intro']     = $this->_intro     ? $this->_intro :     null;
    $rval['info']      = $this->_popup     ? $this->_popup :     null;
    
    if($this->_has_other) {
      $rval['other'] = $this->_other ? $this->_other : null;
    }
    if($this->_options) {
      $rval['options'] = $this->_options;
    }

    $rval['flags']     = $flags->get_bits();

    return $rval;
  }
};
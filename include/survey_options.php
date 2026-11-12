<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

/**
 * Class containing the details about a single selectable option.
 * Each option has a unique ID, its text, and an optiona question ID.
 * Options associated with a question ID are specific to that question
 * Options without a question ID are usable by any multiple choice
 *   question in the survey.
 * @package tlc\tts
 */
class SurveyOption
{
  private int $option_id;
  private string $text;
  private ?int $question_id;

  public function __construct(int $option_id, string $text, ?int $question_id=null) {
    $this->option_id = $option_id;
    $this->text = $text;
    $this->question_id = $question_id;
  }

  /**
   * The option ID associated with this option
   */
  public function id() : int { return $this->option_id; }

  /**
   * The text string associated with this option
   * @return string
   */
  public function text() : string { return $this->text; }

  /**
   * The question ID of the specific question this option is associated
   *   with or null if this option is available to all select questions
   * @return null|int 
   */
  public function question_id() : ?int { return $this->question_id; }
  
  /**
   * Whether or not this option is meant to be available for all select questions
   * @return bool 
   */
  public function is_common() : bool { return $this->question_id === null; }
};

/**
 * Class containing all of the available options in the database
 */
class SurveyOptions
{
  private array $options = [];

  /**
   * Constructor is private to force use of factory methods
   */
  private function __construct() {}

  /**
   * Constructs and returns a SurveyOptions instance populated from database
   * @param int $survey_id ID of the survey to load
   * @return SurveyOptions
   */
  public static function fromSurveyID(int $survey_id) : self
  {
    $instance = new self();
    
    $query = <<<SQL
      SELECT option_id, option_str, question_id
        FROM tlc_srv_survey_options
       WHERE survey_id=(?)
       ORDER BY option_id;
    SQL;
    $rows = MySQLFetchAllIndexed($query, 'i', $survey_id);

    foreach( $rows as [$option_id, $text, $question_id] ) {
      $instance->options[$option_id] = new SurveyOption($option_id,$text,$question_id);
    }
    return $instance;
  }

  /**
   * Updates the survey options in the datbase for the specified survey ID
   * @param int $survey_id 
   * @throws FailedToUpdate on mysqli exception
   */
  public function updateDatabase(int $survey_id) : void
  {
    $insert_option = new MySQLPreparedExec(
      <<<SQL
      INSERT into tlc_srv_survey_options (survey_id, option_id, option_str, question_id) 
      VALUES (?,?,?,?)
      ON DUPLICATE KEY UPDATE option_str = values(option_str)
    SQL,
      'iis',
      build_exception_handler('update option %2$d for survey %1$d')
    );
    foreach($this->options as $option) {
      $insert_option->run( $survey_id, $option->id(), $option->text(), $option->question_id() );
    }
  }

  /**
   * Constructs and returns a SurveyOptions instance from a JSON string
   * @param array $content decoded from a JSON string
   * @return SurveyOptions
   */
  public static function from_js(array $content) : self
  {
    $instance = new self();
    foreach($content as $option) {
      $id = $option['option_id'];
      $instance->options[$id] = new SurveyOption($id, $option['text'], $option['question_id']);
    }
    return $instance;
  }

  /**
   * Converts the options data into an array suitable for use by Javascript
   * @return array<int, array{text: string, question_id: int|null}>
   */
  public function for_js() : array
  {
    return array_map( fn($opt) => [
      'option_id'   => $opt->id(),
      'text'        => $opt->text(),
      'question_id' => $opt->question_id(),
    ],
    $this->options);
  }

  /**
   * Returns the text associated with the specified option ID
   * @param int $option_id 
   * @return null|string 
   */
  public function text(int $option_id) : ?string
  {
    return $this->options[$option_id]?->text() ?? null;
  }
};
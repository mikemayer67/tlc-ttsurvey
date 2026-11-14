<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('utils/set.php'));

/**
 * Class containing the details about a single selectable option.
 * Each option has a unique ID, its text, a list of the question IDs
 * currently using this option, and an optional question ID to which
 * this option is specfically bound.
 */
class SurveyOption
{
  private int $option_id;
  private string $text;
  private Set $used_by;
  private ?int $specific_to; // Applies only to this question

  /**
   * Constructor
   * @param int $option_id 
   * @param string $text 
   * @param list<int|string> $used_by
   * @param null|int $question_id option is specific to this question
   */
  public function __construct(int $option_id, string $text, array $used_by, ?int $question_id=null) {
    $this->option_id = $option_id;
    $this->text = $text;
    $this->used_by = new Set($used_by);
    $this->specific_to = $question_id;

    // audit the optional link to a specific question
    if( $this->specific_to !== null ) 
    {
      $num_used_by = count($this->used_by);

      $error_prefix = sprintf("attempted to link option (%d) specific to question (%d)",
                        $this->option_id, $this->specific_to);

      if( $num_used_by > 1 ) {
        internal_error("$error_prefix, but there are other questions associated with this option");
      }
      if( $num_used_by === 1  && !$this->used_by->contains($this->specific_to) ) {
        internal_error("$error_prefix, but option is in use by another question");
      }
    }
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
   * The question ID of the question to which this option is specific
   *   or null if this option is available to all select questions
   * @return null|int 
   */
  public function specific_to() : ?int { return $this->specific_to; }
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
    // Create a cross reference from option_id to question_ids based on
    //   the mapping from question_id to option_ids in the database

    $question_xref = [];

    $query = <<<SQL
      SELECT question_id, option_id
        FROM tlc_srv_question_options
       WHERE survey_id=?
    SQL;
    $rows = MySQLFetchAllIndexed($query, 'i', $survey_id);
    
    foreach($rows as [$question_id, $option_id]) {
      $question_xref[$option_id][] = $question_id;
    }

    // Load the survey options from the database

    $instance = new self();
    
    $query = <<<SQL
      SELECT option_id, option_str, question_id
        FROM tlc_srv_survey_options
       WHERE survey_id=?
       ORDER BY option_id;
    SQL;
    $rows = MySQLFetchAllIndexed($query, 'i', $survey_id);

    foreach($rows as [$option_id, $text, $specific_to]) {
      $instance->options[$option_id] = new SurveyOption(
        $option_id,
        $text,
        $question_xref[$option_id] ?? [],
        $specific_to
      );
    }

    return $instance;
  }

  /**
   * Constructs and returns a SurveyOptions instance from a JSON string
   * @param array $content decoded survey content
   * @return SurveyOptions
   */
  public static function from_js(array $content) : self
  {
    $options = $content['options'];
    $questions = $content['questions'];

    // Create a cross reference from option_id to question_ids based on
    //   the mapping from question_id to option_ids in content array

    $question_xref = [];
    foreach($questions as $question_id=>$question) {
      $question_options = $question['options'] ?? [];
      foreach($question_options as $option_id) {
        $question_xref[$option_id][] = $question_id;
      }
    }

    // Load the survey options from content array

    $instance = new self();
    foreach($options as $option_id => $option) {
      $instance->options[$option_id] = new SurveyOption(
        $option_id, 
        $option['text'], 
        $question_xref[$option_id] ?? [],
        $option['specific_to']
      );
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
      ON DUPLICATE KEY UPDATE 
          option_str = values(option_str),
          question_id = values(question_id)   
    SQL,
      'iisi',
      build_exception_handler('update option %2$d for survey %1$d')
    );
    foreach($this->options as $option) {
      $insert_option->run( $survey_id, $option->id(), $option->text(), $option->specific_to() );
    }
  }

  /**
   * Converts the options data into an array suitable for use by Javascript
   * @return array<int, array{text: string, specific_to: int|null}>
   */
  public function for_js() : array
  {
    return array_map( fn($opt) => [
      'text'        => $opt->text(),
      'specific_to' => $opt->specific_to(),
    ],
    $this->options);
  }

  /**
   * Returns the requested survey option details if it exists
   * @param int $option_id 
   * @return null|SurveyOption 
   */
  public function option(int $option_id) : ?SurveyOption
  {
    return $this->options[$option_id] ?? null;
  }
};
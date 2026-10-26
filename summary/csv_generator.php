<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('include/survey_content.php'));
require_once(app_file('include/responses.php'));
require_once(app_file('include/users.php'));
require_once(app_file('include/question_types.php'));
require_once(app_file('summary/summary_content.php'));

class CSVGenerator
{
  private SummaryContent $_content;

  private array  $_responses;
  private string $_filename;
  private array  $_fullnames;
  private array  $_column_headers;
  private array  $_column_keys;

  private int    $_cur_section_index;
  private int    $_cur_question_index;
  private string $_cur_refid;
  private string $_cur_section_name;

  /**
   * Constructs the CSVGenerator, initialized for the specified survey
   * @param int $survey_id 
   * @return void 
   */
  public function __construct(int $survey_id)
  {
    // TODO: replace all internal_error with send_ajax_internal_error after merge with ajax upgrade branch
    $this->_column_headers = [
      'refid'       => 'RefID',
      'section'     => 'Section',
      'question'    => 'Question',
      'participant' => 'Participant',
      'selected'    => 'Selection',
      'notes'       => 'Notes',
    ];
    $this->_column_keys = array_keys($this->_column_headers);

    $survey_content = SurveyContent::fromSurveyID($survey_id);
    $responses = get_all_responses($survey_id);
    $this->_content = new SummaryContent($survey_content, $responses);
    
    $this->_fullnames = [];

    $info = survey_info($survey_id);
    if(!$info) { internal_error("Invalid survey_id: $survey_id"); }

    $title = $info['title']??app_name();
    $title = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
    $title = preg_replace('/[^A-Za-z0-9._-]+/', '_', $title);
    $title = trim($title, '._-');
    $this->_filename = $title . "_summary.csv";
  }
  
  private function start_new_section(int $section_id)
  {
    $this->_cur_section_index += 1;
    $this->_cur_question_index = 0;
    $this->_cur_section_name = $this->_content->section_name($section_id);
  }

  private function start_new_question(int $question_id)
  {
    $this->_cur_question_index += 1;
    $this->_cur_refid = sprintf("%d.%02d", $this->_cur_section_index, $this->_cur_question_index);
  }

  /**
   * Returns the fullname for a participant given their userid
   *   Caches responses so that it doen't need to do repetitive database lookups
   * @param string $userid 
   * @return void 
   */
  private function fullname(string $userid)
  {
    if(!key_exists($userid,$this->_fullnames)) {
      $user = User::from_userid($userid);
      $this->_fullnames[$userid] = $user->fullname();
    }
    return $this->_fullnames[$userid];
  }

  /**
   * Adds the columnn header row to the output csv
   * @return mixed 
   */
  private function add_header()
  {
    echo '"' . implode('","', array_values($this->_column_headers)) . '"' . "\n";
  }

  /**
   * Conditions and adds a data row to the output csv
   * @param array $row 
   * @return void 
   */
  private function add_row(array $row)
  {
    if(!isset($row['refid']))   { $row['refid']   = $this->_cur_refid;        }
    if(!isset($row['section'])) { $row['section'] = $this->_cur_section_name; }

    $cells = array_map(fn($k) => $row[$k] ?? '', $this->_column_keys);
    $cells = array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $cells);
    $cells = implode(',', $cells);

    echo "$cells\n";
  }

  /**
   * Generates the csv "file" to stdout (back to browser)
   *   Handles the http header, including filename
   *   Handles the BOM code to keep Excel happy
   *   Handles of the row/column csv data
   * @return void 
   */
  public function render()
  {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$this->_filename.'"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    // add UTF-8 BOM (for Excel)
    echo "\xEF\xBB\xBF";

    // add csv header row
    $this->add_header();

    // add response data

    $this->_cur_section_index = $this->_cur_question_index = 0;

    foreach($this->_content->section_ids() as $section_id)
    {
      $this->start_new_section($section_id);
      foreach($this->_content->question_groups($section_id) as $questions)
      {
        foreach($questions as $question)
        {
          $this->start_new_question($question->id());

          switch($question->type()) {
            case QuestionType::Info:        $this->add_info_question($question);         break;
            case QuestionType::Bool:        $this->add_bool_question($question);         break;
            case QuestionType::FreeText:    $this->add_freetext_question($question);     break;
            case QuestionType::SelectOne:   $this->add_select_one_question($question);   break;
            case QuestionType::SelectMulti: $this->add_select_multi_question($question); break;
            default:
              internal_error("Invalid Question Type");
              break;
          }
        }
      }
    }
  }

  /**
   * Adds a summary of an info question to the csv output
   * @param InfoQuestion $question 
   */
  private function add_info_question(InfoQuestion $question) : void
  {
    $info = $question->info_text();
    $this->add_row([
      'question' => $info,
    ]);
  }

  /**
   * Adds a summary of a boolean question to the csv output
   * @param BoolQuestion $question 
   */
  private function add_bool_question(BoolQuestion $question) : void
  {
    $qid       = $question->id();
    $wording   = $question->wording();
    $responses = $this->_content->responses($qid);

    $found = false;
    foreach ($responses as $r) {
      if ($r['selected']) {
        $found = true;
        $this->add_row([
          'question'    => $wording,
          'participant' => $this->fullname($r['userid']),
          'notes'       => $r['qualifier'] ?? '',
        ]);
      }
    }
    if (!$found) {
      $this->add_row([
        'question' => $wording,
      ]);
    }
  }

  /**
   * Adds a summary of a freetext question to the csv output
   * @param FreeTextQuestion $question 
   */
  private function add_freetext_question(FreeTextQuestion $question) : void
  {
    $qid       = $question->id();
    $wording   = $question->wording();
    $responses = $this->_content->responses($qid);

    $found = false;
    foreach ($responses as $r) {
      $this->add_row([
        'question'    => $wording,
        'participant' => $this->fullname($r['userid']),
        'notes'       => $r['freetext'] ?? '',
      ]);
    }
    if(!$responses) {
      $this->add_row([
        'question' => $wording,
      ]);
    }
  }

  /**
   * Adds a summary of a single select question to the csv output
   * @param SelectQuestion $question 
   */
  private function add_select_one_question(SelectQuestion $question) : void
  {
    $qid       = $question->id();
    $wording   = $question->wording();
    $responses = $this->_content->responses($qid);

    foreach($responses as $r) {
      $opt_id = $r['selected'];
      if($opt_id > 0) {
        $selected = $this->_content->option($opt_id);
      } else {
        $selected = implode(': ', [
          $question->other_prompt(),
          $r['other'] ?? '???'
        ]);
      }
      $this->add_row([
        'question'    => $wording,
        'participant' => $this->fullname($r['userid']),
        'notes'       => $r['qualifier'] ?? '',
        'selected'    => $selected,
      ]);
    }
    if(!$responses) {
      $this->add_row([
        'question' => $wording,
      ]);
    }
  }

  /**
   * Adds a summary of a multiple select question to the csv output
   * @param SelectQuestion $question 
   */
  private function add_select_multi_question(SelectQuestion $question) : void
  {
    $qid       = $question->id();
    $wording   = $question->wording();
    $responses = $this->_content->responses($qid);

    foreach($responses as $r) {
      $name     = $this->fullname($r['userid']);
      $selected = $r['options'] ?? [];
      $notes    = $r['qualifier'] ?? '';
      foreach($selected as $opt_id) {
        $this->add_row([
          'question'    => $wording,
          'participant' => $name,
          'selected'    => $this->_content->option($opt_id),
          'notes'       => $notes,
        ]);
      }
      if($r['selected'] === 0) {
        $other = implode(': ', [
            $question->other_prompt(),
            ($r['other'] ?? '???')
        ]);
        $this->add_row([
          'question'    => $wording,
          'participant' => $name,
          'selected'    => $other,
          'notes'       => $notes,
        ]);
      }
    }
    if(!$responses) {
      $this->add_row([
        'question' => $wording,
      ]);
    }
  } 
}
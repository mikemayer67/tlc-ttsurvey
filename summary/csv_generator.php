<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/surveys.php'));
require_once(app_file('include/responses.php'));
require_once(app_file('include/users.php'));

class CSVGenerator
{
  private array  $_content;
  private array  $_responses;
  private string $_filename;
  private array  $_fullnames;
  private array  $_column_headers;
  private array  $_column_keys;

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
      'notes'       => 'Feedback/Notes',
    ];
    $this->_column_keys = array_keys($this->_column_headers);

    $this->_content   = survey_content($survey_id);
    $this->_responses = get_all_responses($survey_id);
    $this->_fullnames = [];

    $info = survey_info($survey_id);
    if(!$info) { internal_error("Invalid survey_id: $survey_id"); }

    $title = $info['title']??app_name();
    $title = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
    $title = preg_replace('/[^A-Za-z0-9._-]+/', '_', $title);
    $title = trim($title, '._-');
    $this->_filename = $title . "_summary.csv";
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

    $sections = $this->_content['sections'];
    usort($sections, fn($a, $b) => $a['sequence'] <=> $b['sequence']);

    foreach( $sections as $section ) {
      $section_id   = $section['section_id'];

      $questions = array_filter(
        $this->_content['questions'],
        fn($a) => ($a['section'] ?? null) === $section_id
      );
      uasort($questions, fn($a, $b) => $a['sequence'] <=> $b['sequence']);

      foreach ($questions as $question) {
        $question_type = $question['type'];

        switch($question_type) {
        case 'INFO':         $rows = $this->add_info_question($question);         break;
        case 'BOOL':         $rows = $this->add_bool_question($question);         break;
        case 'FREETEXT':     $rows = $this->add_freetext_question($question);     break;
        case 'SELECT_ONE':   $rows = $this->add_select_one_question($question);   break;
        case 'SELECT_MULTI': $rows = $this->add_select_multi_question($question); break;
        default:
          $rows = [['question'=>$question_type]];
          break;
        }

        foreach($rows as $row) {
          $row['refid']   = sprintf("%d.%02d", $section['sequence'], $question['sequence']);
          $row['section'] = $section['name'];
          $this->add_row($row);
        }
      }

      // section feedback
      $sr = $this->_responses['sections'][$section_id] ?? [];
      foreach($sr as $userid=>$feedback) {
        $row = [];
        $row['refid']       = $section['sequence'];
        $row['section']     = $section['name'];
        $row['question']    = $section['feedback'];
        $row['notes']       = $feedback;
        $row['participant'] = $this->fullname($userid);
        $this->add_row($row);
      }
      if($section['feedback'] && !$sr) {
        $row = [];
        $row['refid']       = $section['sequence'];
        $row['section']     = $section['name'];
        $row['question']    = $section['feedback'];
        $this->add_row($row);
      }
    }
  }

  /**
   * Adds a summary of an info question to the csv output
   * @param array $question 
   * @return array question specific row fields
   */
  private function add_info_question(array $question) : array
  {
    $rows = [];
    $info = $question['info'] ?? '';
    if ($info) { $rows[] = ['question' => $info]; }
    return $rows;
  }

  /**
   * Adds a summary of a boolean question to the csv output
   * @param array $question 
   * @return array question specific row fields
   */
  private function add_bool_question(array $question) : array
  {
    $rows = [];
    $qid = $question['id'];
    $wording = $question['wording'] ?? '???';
    $qr = $this->_responses['questions'][$qid] ?? [];
    foreach ($qr as $r) {
      if ($r['selected']) {
        $row = [];
        $row['question']    = $wording;
        $row['participant'] = $this->fullname($r['userid']);
        $row['notes']       = $r['qualifier'] ?? '';
        $rows[] = $row;
      }
    }
    if(!$rows) {
      $rows[] = ['question' => $wording ];
    }
    return $rows;
  }

  /**
   * Adds a summary of a freetext question to the csv output
   * @param array $question 
   * @return array question specific row fields
   */
  private function add_freetext_question(array $question) : array
  {
    $rows = [];
    $qid = $question['id'];
    $wording = $question['wording'] ?? '???';
    $qr = $this->_responses['questions'][$qid] ?? [];
    foreach ($qr as $r) {
      $row = [];
      $row['question']    = $wording;
      $row['participant'] = $this->fullname($r['userid']);
      $row['notes']       = $r['free_text'] ?? '';
      $rows[] = $row;
    }
    if(!$rows) {
      $rows[] = ['question' => $wording ];
    }
    return $rows;
  }

  /**
   * Adds a summary of a single select question to the csv output
   * @param array $question 
   * @return array question specific row fields
   */
  private function add_select_one_question(array $question) : array
  {
    $rows = [];
    $qid = $question['id'];
    $wording = $question['wording'] ?? '???';
    $qr = $this->_responses['questions'][$qid] ?? []; 
    foreach($qr as $r) {
      $row = [];
      $row['question']    = $wording;
      $row['participant'] = $this->fullname($r['userid']);
      $row['notes']       = $r['qualifier'] ?? '';

      $opt_id = $r['selected'];
      if($opt_id > 0) {
        $row['selected'] = $this->_content['options'][$opt_id] ?? '???';
      } else {
        $row['selected'] = implode(': ', [
            ($question['other'] ?? 'Other'),
            ($r['other'] ?? '???')
        ]);
      }
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Adds a summary of a multiple select question to the csv output
   * @param array $question 
   * @return array 
   */
  private function add_select_multi_question(array $question) : array
  {
    $rows = [];
    $qid = $question['id'];
    $wording = $question['wording'] ?? '???';
    $qr = $this->_responses['questions'][$qid] ?? []; 
    foreach($qr as $r) {
      $name = $this->fullname($r['userid']);
      $selected = $r['options'] ?? [];
      $notes = $r['qualifier'] ?? '';
      foreach($selected as $opt_id) {
        $row = [];
        $row['question']    = $wording;
        $row['participant'] = $name;
        $row['selected'] = $this->_content['options'][$opt_id] ?? '???';
        $row['notes'] = $notes;
        $rows[] = $row;
      }
      $other = $r['selected'] === 0;
      if($r['selected'] === 0) {
        $row = [];
        $row['question']    = $wording;
        $row['participant'] = $name;
        $row['notes'] = $notes;
        $row['selected'] = implode(': ', [
            ($question['other'] ?? 'Other'),
            ($r['other'] ?? '???')
        ]);
        $rows[] = $row;
      }
      $row = [];
    }
    return $rows;
  } 
}
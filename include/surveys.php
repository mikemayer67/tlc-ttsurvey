<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/strings.php'));
require_once(app_file('include/question_flags.php'));

class Surveys
{
  static function active_id()
  {
    $ids = MySQLSelectValues("select survey_id from tlc_tt_active_surveys");
    if(count($ids)>1) {
      internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
    }
    return $ids[0] ?? false;
  }

  static function active_title()
  {
    $titles = MySQLSelectValues("select title from tlc_tt_active_surveys");
    if(count($titles)>1) {
      internal_error("Multiple active surveys found in the database: ".implode(', ',$titles));
    }
    return $titles[0] ?? null;
  }

  static function info($id)
  {
    $info = MySQLSelectRow("select * from tlc_tt_view_surveys where survey_id=?",'i',$id);

    // javascript is expecting the survey ID to have the key 'id', not 'survey_id'
    // PHP is not using the survey_id key, but retaining it just in case this ever changes
    $info['id'] = $info['survey_id'];

    return $info;
  }

  static function get_all()
  {
    $surveys = [];

    $active = MySQLSelectRows('select * from tlc_tt_active_surveys');
    $drafts = MySQLSelectRows('select * from tlc_tt_draft_surveys');
    $closed = MySQLSelectRows('select * from tlc_tt_closed_surveys');

    $nactive = count($active);
    if($nactive) {
      if($nactive>1) { internal_error('Multiple active surveys found'); }
      $survey = $active[0];
      $survey['status'] = 'active';
      $surveys[] = $survey;
    }
    foreach($drafts as $survey) {
      $survey['status'] = 'draft';
      $surveys[] = $survey;
    }
    foreach($closed as $survey) {
      $survey['status'] = 'closed';
      $surveys[] = $survey;
    }

    return $surveys;
  }

  static function content($survey_id)
  {
    $rval = [
      'options' => self::_options($survey_id),
      'sections' => self::_sections($survey_id),
      'questions' => self::_questions($survey_id),
      'next_ids'  => self::next_ids($survey_id),
    ];

    return $rval;
  }

  static function _options($survey_id)
  {
    $query = <<<SQL
      SELECT so.option_id, text.str as text
        FROM tlc_tt_survey_options so
       INNER JOIN tlc_tt_strings text ON text.string_id = so.text_sid
       WHERE so.survey_id=(?)
       ORDER BY so.option_id;
    SQL;
    $rows = MySQLSelectRows($query, 'i', $survey_id);
  
    return $rows ? array_column($rows,'text','option_id') : [];
  }

  static function _sections($survey_id)
  {
    $query = <<<SQL
      SELECT s.section_id    as section_id,
             s.sequence      as sequence,
             name.str        as name,
             s.collapsible   as collapsible,
             intro.str       as intro,
             feedback.str    as feedback
      FROM   tlc_tt_survey_sections s
      INNER JOIN tlc_tt_strings name     ON name.string_id     = s.name_sid
       LEFT JOIN tlc_tt_strings intro    ON intro.string_id    = s.intro_sid
       LEFT JOIN tlc_tt_strings feedback ON feedback.string_id = s.feedback_sid
      WHERE s.survey_id=(?)
      ORDER BY s.sequence;
    SQL;
    $rows = MySQLSelectRows($query, 'i', $survey_id);
  
    return $rows ? array_column($rows,null,'section_id') : [];
  }

  static function _questions($survey_id)
  {
    $query = <<<SQL
      SELECT q.question_id    as question_id,
             m.section_id     as section,
             m.question_seq   as sequence,
             wording.str      as wording,
             q.question_type  as question_type,
             q.question_flags as flags,
             other.str        as other,
             qualifier.str    as qualifier,
             intro.str        as intro,
             info.str         as info
        FROM tlc_tt_survey_questions q
       INNER JOIN tlc_tt_question_map m      ON m.survey_id=q.survey_id AND m.question_id=q.question_id
        LEFT JOIN tlc_tt_strings wording     ON wording.string_id     = q.wording_sid
        LEFT JOIN tlc_tt_strings other       ON other.string_id       = q.other_sid
        LEFT JOIN tlc_tt_strings qualifier   ON qualifier.string_id   = q.qualifier_sid
        LEFT JOIN tlc_tt_strings intro       ON intro.string_id       = q.intro_sid
        LEFT JOIN tlc_tt_strings info        ON info.string_id        = q.info_sid
       WHERE q.survey_id=(?)
       ORDER BY section_id, sequence;
    SQL;
    $rows = MySQLSelectRows($query, 'i', $survey_id);
  
    if(!$rows) { return array(); }
  
    $q_fields = [
      'INFO'         => ['wording'=>'infotag',                     'info'         ],
      'BOOL'         => ['wording', 'intro', 'qualifier',          'info'=>'popup'],
      'SELECT_MULTI' => ['wording', 'intro', 'qualifier', 'other', 'info'=>'popup'],
      'SELECT_ONE'   => ['wording', 'intro', 'qualifier', 'other', 'info'=>'popup'],
      'FREETEXT'     => ['wording', 'intro',                       'info'=>'popup']
    ];
  
    $questions = array();
    foreach($rows as $row) {
      $id   = $row['question_id'];
      $type = $row['question_type'];
  
      $q = [ 
        'id'       => $id, 
        'type'     => $type,
        'section'  => $row['section'],
        'sequence' => $row['sequence'],
      ];
  
      foreach ($q_fields[$type] ?? [] as $from => $to)
      {
        if(is_int($from)) { $from = $to; } // straight copy from row to question
        $q[$to] = $row[$from];
      }

      # decode the question_flags bitmap
      $flags = new QuestionFlags( $row['flags'] ?? 0 );
      $q['grouped'] = $flags->grouped();
      $q['layout']  = $flags->layout($type);
      if(str_starts_with($type,'SELECT')) {
        $q['other_flag'] = $flags->has_other() ? 1 : 0;
      }

      $questions[$id] = $q;
    }
  
    self::_add_question_options($questions,$survey_id);
    self::_add_archived_questions($survey_id,$questions);

    return $questions;
  }
  
  static function _ancestors($survey_id)
  {
    $query = "SELECT parent_id from tlc_tt_surveys where survey_id=?";
    $survey_id = MySQLSelectValue($query,'i',$survey_id);
    while($survey_id) {
      yield $survey_id;
      $survey_id = MySQLSelectValue($query,'i',$survey_id);
    }
  }

  static function _add_archived_questions($survey_id, &$questions)
  {
    $exclude = array_keys($questions);

    $q_fields = [
      'INFO'         => ['wording'=>'infotag',                     'info'         ],
      'BOOL'         => ['wording', 'intro', 'qualifier',          'info'=>'popup'],
      'SELECT_MULTI' => ['wording', 'intro', 'qualifier', 'other', 'info'=>'popup'],
      'SELECT_ONE'   => ['wording', 'intro', 'qualifier', 'other', 'info'=>'popup'],
      'FREETEXT'     => ['wording', 'intro',                       'info'=>'popup']
    ];

    # loop over current survey + up the parent tree
    foreach(self::_ancestors($survey_id) as $sid)
    {
      $exclude_clause = $exclude ? ' and question_id not in ('.implode(',',$exclude).')' : "";

      $query = <<<SQL
        SELECT question_id
          FROM tlc_tt_question_map
         WHERE survey_id=? $exclude_clause
      SQL;
      $qids = MySQLSelectValues($query,'i',$sid);

      if($qids) {
        # any found, extract their question info
        $in_clause = ' q.question_id in (' . implode(',', $qids) . ')';

        $query = <<<SQL
          SELECT q.question_id    as question_id,
                 wording.str      as wording,
                 q.question_type  as question_type,
                 q.question_flags as flags,
                 other.str        as other,
                 qualifier.str    as qualifier,
                 intro.str        as intro,
                 info.str         as info
            FROM tlc_tt_survey_questions q
           INNER JOIN tlc_tt_strings wording     ON wording.string_id     = q.wording_sid
            LEFT JOIN tlc_tt_strings other       ON other.string_id       = q.other_sid
            LEFT JOIN tlc_tt_strings qualifier   ON qualifier.string_id   = q.qualifier_sid
            LEFT JOIN tlc_tt_strings intro       ON intro.string_id       = q.intro_sid
            LEFT JOIN tlc_tt_strings info        ON info.string_id        = q.info_sid
           WHERE q.survey_id=(?) and $in_clause
        SQL;

        $new_questions = [];
        foreach(MySQLSelectRows($query,'i',$sid) as $row) {
          $qid  = $row['question_id'];
          $type = $row['question_type'];
          $q = [
            'id'   => $qid,
            'type' => $type,
          ];
          foreach ($q_fields[$type] ?? [] as $from => $to)
          {
            if(is_int($from)) { $from = $to; } // straight copy from row to question
            $q[$to] = $row[$from];
          }

          # decode the question_flags bitmap
          $flags = new QuestionFlags( $row['flags'] ?? 0 );
          $q['grouped'] = $flags->grouped();
          $q['layout']  = $flags->layout($type);
          if(str_starts_with($type,'SELECT')) {
            $q['other_flag'] = $flags->has_other();
          }

          $new_questions[$qid] = $q;
          $exclude[] = $qid;
        }

        self::_add_question_options($new_questions,$sid);

        $questions += $new_questions;
      }
    }
  }

  static function _add_question_options(&$questions,$survey_id)
  {
    $query = <<<SQL
      SELECT question_id, option_id
      FROM   tlc_tt_question_options qo 
      WHERE survey_id=?
      ORDER BY question_id, sequence
    SQL;

    $rows = MySQLSelectRows($query, 'i', $survey_id);
    if(!$rows) { return; }

    foreach ($rows as $row) {
      $qid = $row['question_id'];
      if(isset($questions[$qid])) {
        $questions[$qid]['options'][] = $row['option_id'];
      }
    }
  }


  static function next_ids($survey_id) 
  {
    // Notes:
    // - the results of this query are sent to javascript code on the admin dashboard
    // - question IDs must be unique across all surveys
    // - option IDs must be unique within each survey
    return [
      'survey'   => 1 + MySQLSelectValue('select max(survey_id)   from tlc_tt_surveys'),
      'question' => 1 + MySQLSelectValue('select max(question_id) from tlc_tt_survey_questions'),
      'option'   => 1 + MySQLSelectValue('select max(option_id)   from tlc_tt_survey_options where survey_id=(?)','i',$survey_id),
    ];
  }

};

function active_survey_id()    { return Surveys::active_id();    }
function active_survey_title() { return Surveys::active_title(); }
function survey_info($id)      { return Surveys::info($id);      }
function all_surveys()         { return Surveys::get_all();      }

function next_survey_ids($survey_id) { return Surveys::next_ids($survey_id); }

function survey_content($survey_id)  { return Surveys::content($survey_id);  }


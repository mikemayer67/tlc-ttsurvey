<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/question_flags.php'));

class Surveys
{
  static function active_id()
  {
    $ids = MySQLSelectValues("select survey_id from tlc_srv_active_surveys");
    if(count($ids)>1) {
      internal_error("Multiple active surveys found in the database: ".implode(', ',$ids));
    }
    return $ids[0] ?? false;
  }

  static function active_title()
  {
    $titles = MySQLSelectValues("select title from tlc_srv_active_surveys");
    if(count($titles)>1) {
      internal_error("Multiple active surveys found in the database: ".implode(', ',$titles));
    }
    return $titles[0] ?? null;
  }

  static function info($id)
  {
    $info = MySQLSelectRow("select * from tlc_srv_surveys where survey_id=?",'i',$id);
    if(!$info) { return null; }

    // javascript is expecting the survey ID to have the key 'id', not 'survey_id'
    // PHP is not using the survey_id key, but retaining it just in case this ever changes
    $info['id'] = $info['survey_id'];

    return $info;
  }

  static function get_all()
  {
    $surveys = [];

    $active = MySQLSelectRows('select * from tlc_srv_active_surveys');
    $drafts = MySQLSelectRows('select * from tlc_srv_draft_surveys');
    $closed = MySQLSelectRows('select * from tlc_srv_closed_surveys');

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
      SELECT option_id, option_str as text
        FROM tlc_srv_survey_options
       WHERE survey_id=(?)
       ORDER BY option_id;
    SQL;
    $rows = MySQLSelectRows($query, 'i', $survey_id);
  
    return $rows ? array_column($rows,'text','option_id') : [];
  }

  static function _sections($survey_id)
  {
    $query = <<<SQL
      SELECT section_id, sequence, name, collapsible, intro
      FROM   tlc_srv_survey_sections
      WHERE survey_id=(?)
      ORDER BY sequence;
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
             q.wording        as wording,
             q.question_type  as question_type,
             q.question_flags as flags,
             q.other          as other,
             q.qualifier      as qualifier,
             q.intro          as intro,
             q.info           as info
        FROM tlc_srv_survey_questions q
       INNER JOIN tlc_srv_question_map m ON m.survey_id=q.survey_id AND m.question_id=q.question_id
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
    $query = "SELECT parent_id from tlc_srv_surveys where survey_id=?";
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
          FROM tlc_srv_question_map
         WHERE survey_id=? $exclude_clause
      SQL;
      $qids = MySQLSelectValues($query,'i',$sid);

      if($qids) {
        # any found, extract their question info
        $in_clause = ' question_id in (' . implode(',', $qids) . ')';

        $query = <<<SQL
          SELECT question_id, wording, question_type, question_flags as flags,
                 other, qualifier, intro, info
            FROM tlc_srv_survey_questions
           WHERE survey_id=(?) and $in_clause
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
      FROM   tlc_srv_question_options qo 
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
      'survey'   => 1 + MySQLSelectValue('select max(survey_id)   from tlc_srv_surveys'),
      'question' => 1 + MySQLSelectValue('select max(question_id) from tlc_srv_survey_questions'),
      'option'   => 1 + MySQLSelectValue('select max(option_id)   from tlc_srv_survey_options where survey_id=(?)','i',$survey_id),
    ];
  }

};

function active_survey_id()    { return Surveys::active_id();    }
function active_survey_title() { return Surveys::active_title(); }
function survey_info($id)      { return Surveys::info($id);      }
function all_surveys()         { return Surveys::get_all();      }

function next_survey_ids($survey_id) { return Surveys::next_ids($survey_id); }

function survey_content($survey_id)  { return Surveys::content($survey_id);  }


<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/strings.php'));

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
    $info = MySQLSelectRow("select * from tlc_tt_surveys where survey_id=?",'i',$id);

    if(self::pdf_file($id)) {
      $info['has_pdf'] = true;
    }

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

    foreach( $surveys as &$survey ) {
      $survey['has_pdf'] = (null !== self::pdf_file($survey['survey_id']));
    }

    return $surveys;
  }

  static function content($survey_id, $survey_rev=NULL)
  {
    // current survey revision
  
    if(is_null($survey_rev)) {
      $survey_rev = MySQLSelectValue('select survey_rev from tlc_tt_surveys where survey_id=(?)', 'i', $survey_id);
      log_dev("content => $survey_id / $survey_rev");
      if(!$survey_rev) { internal_error("Cannot find revision for survey_id=$survey_id"); }
    }
  
    $rval = [
      'rev' => $survey_rev,
      'options' => self::_options($survey_id,$survey_rev),
      'sections' => self::_sections($survey_id,$survey_rev),
      'questions' => self::_questions($survey_id,$survey_rev),
      'next_ids'  => self::next_ids($survey_id),
    ];
  
    return $rval;
  }

  static function _options($survey_id,$survey_rev)
  {
    // survey_rev is per (survey id, option id)
  
    $query = <<<SQL
      SELECT so.option_id, text.str as text
        FROM tlc_tt_survey_options so
       INNER JOIN tlc_tt_strings text ON text.string_id = so.text_sid
       WHERE so.survey_id=(?) and so.survey_rev=(?)
       ORDER BY so.option_id;
    SQL;
    $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  
    return $rows ? array_column($rows,'text','option_id') : [];
  }

  static function _sections($survey_id,$survey_rev)
  {
    //   survey_rev is per survey_id
  
    $query = <<<SQL
      SELECT s.sequence      as sequence,
             name.str        as name,
             s.labeled       as labeled,
             description.str as description,
             feedback.str    as feedback
      FROM   tlc_tt_survey_sections s
      INNER JOIN tlc_tt_strings name        ON name.string_id        = s.name_sid
       LEFT JOIN tlc_tt_strings description ON description.string_id = s.description_sid
       LEFT JOIN tlc_tt_strings feedback    ON feedback.string_id    = s.feedback_sid
      WHERE s.survey_id=(?) AND s.survey_rev=(?)
      ORDER BY s.sequence;
    SQL;
    $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  
    return $rows ? array_column($rows,null,'sequence') : [];
  }

  static function _questions($survey_id,$survey_rev)
  {
    $query = <<<SQL
      SELECT q.question_id   as question_id,
             m.section_seq   as section,
             m.question_seq  as sequence,
             wording.str     as wording,
             q.question_type as question_type,
             q.multiple      as multiple,
             other.str       as other,
             qualifier.str   as qualifier,
             description.str as description,
             info.str        as info
        FROM tlc_tt_survey_questions q
       INNER JOIN tlc_tt_question_map m      
          ON m.survey_id=q.survey_id AND m.survey_rev=q.survey_rev AND m.question_id=q.question_id
       INNER JOIN tlc_tt_strings wording     ON wording.string_id     = q.wording_sid
        LEFT JOIN tlc_tt_strings other       ON other.string_id       = q.other_sid
        LEFT JOIN tlc_tt_strings qualifier   ON qualifier.string_id   = q.qualifier_sid
        LEFT JOIN tlc_tt_strings description ON description.string_id = q.description_sid
        LEFT JOIN tlc_tt_strings info        ON info.string_id        = q.info_sid
       WHERE q.survey_id=(?) and q.survey_rev=(?)
       ORDER BY section, sequence;
    SQL;
    $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  
    if(!$rows) { return array(); }
  
    $q_fields = [
      'INFO'     => ['section','sequence','wording'=>'infotag', 'info'],
      'BOOL'     => ['section','sequence','wording', 'description', 'qualifier', 'info'=>'popup'],
      'OPTIONS'  => ['section','sequence','wording', 'description', 'qualifier', 'other', 'info'=>'popup'],
      'FREETEXT' => ['section','sequence','wording', 'description', 'info'=>'popup']
    ];
  
    $questions = array();
    foreach($rows as $row) {
      $id = $row['question_id'];
      $type = $row['question_type'];
      $actual_type = ($type !== 'OPTIONS' ? $type : ($row['multiple'] ? 'SELECT_MULTI' : 'SELECT_ONE'));
  
      $q = [ 
        'id'   => $id, 
        'type' => $actual_type,
      ];
  
      foreach ($q_fields[$type] ?? [] as $from => $to)
      {
        if(is_int($from)) { $from = $to; } // straight copy from row to question
        $q[$to] = $row[$from];
      }
  
      $questions[$id] = $q;
    }
  
    self::_add_question_options($questions,$survey_id,$survey_rev);
    self::_add_archived_questions($survey_id,$survey_rev,$questions);

    return $questions;
  }
  
  static function _ancestors($survey_id,$survey_rev)
  {
    log_dev("_ancestors($survey_id,$survey_rev)");
    while($survey_rev > 1) {
      $survey_rev -= 1;
      yield[$survey_id,$survey_rev];
    }
    $survey_id = MySQLSelectValue("SELECT parent_id from tlc_tt_survey_status where survey_id=$survey_id");
    while($survey_id) {
      [$parent_id,$survey_rev] = 
        MySQLSelectArray("SELECT parent_id,survey_rev from tlc_tt_surveys where survey_id=$survey_id");
      while($survey_rev) {
        yield [$survey_id,$survey_rev];
        $survey_rev -= 1;
      }
      $survey_id = $parent_id;
    }
  }

  static function _add_archived_questions($survey_id, $survey_rev, &$questions)
  {
    log_dev("_add_archived_questions($survey_id,$survey_rev,questions)");
    $exclude = array_keys($questions);

    $q_fields = [
      'INFO'     => ['wording'=>'infotag', 'info'],
      'BOOL'     => ['wording', 'description', 'qualifier', 'info'=>'popup'],
      'OPTIONS'  => ['wording', 'description', 'qualifier', 'other', 'info'=>'popup'],
      'FREETEXT' => ['wording', 'description', 'info'=>'popup']
    ];
  

    # loop over current survey + up the parent tree
    foreach(self::_ancestors($survey_id,$survey_rev) as [$sid,$rev])
    {
      log_dev("ancestors => $sid, $rev");
      $exclude_clause = $exclude ? ' and question_id not in ('.implode(',',$exclude).')' : "";

      $query = <<<SQL
        SELECT question_id,max(survey_rev) as max_rev
          FROM tlc_tt_question_map
         WHERE survey_id=? AND survey_rev<=? $exclude_clause
         GROUP BY question_id
      SQL;
      $rows = MySQLSelectArrays($query,'ii',$sid,$rev);

      if($rows) {
        # any found, extract their question info
        $in_clause = ' (q.question_id,q.survey_rev) in (';
        $in_clause .= implode(',', array_map(fn($r) => "($r[0],$r[1])", $rows));
        $in_clause .= ')';

        $query = <<<SQL
          SELECT q.question_id   as question_id,
                 wording.str     as wording,
                 q.question_type as question_type,
                 q.multiple      as multiple,
                 other.str       as other,
                 qualifier.str   as qualifier,
                 description.str as description,
                 info.str        as info
            FROM tlc_tt_survey_questions q
           INNER JOIN tlc_tt_strings wording     ON wording.string_id     = q.wording_sid
            LEFT JOIN tlc_tt_strings other       ON other.string_id       = q.other_sid
            LEFT JOIN tlc_tt_strings qualifier   ON qualifier.string_id   = q.qualifier_sid
            LEFT JOIN tlc_tt_strings description ON description.string_id = q.description_sid
            LEFT JOIN tlc_tt_strings info        ON info.string_id        = q.info_sid
           WHERE q.survey_id=(?) and $in_clause
        SQL;

        $new_questions = [];
        foreach(MySQLSelectRows($query,'i',$sid) as $row) {
          $qid  = $row['question_id'];
          $type = $row['question_type'];
          $actual_type = ($type !== 'OPTIONS' ? $type : ($row['multiple'] ? 'SELECT_MULTI' : 'SELECT_ONE'));
          $q = [
            'id'   => $qid,
            'type' => $actual_type,
          ];
          foreach ($q_fields[$type] ?? [] as $from => $to)
          {
            if(is_int($from)) { $from = $to; } // straight copy from row to question
            $q[$to] = $row[$from];
          }

          $new_questions[$qid] = $q;
          $exclude[] = $qid;
        }

        self::_add_question_options($new_questions,$sid,$rev);

        $questions += $new_questions;
      }
    }
  }

  static function _add_question_options(&$questions,$survey_id,$survey_rev)
  {
    log_dev("_add_question_options(questions,$survey_id,$survey_rev)");
    $query = <<<SQL
      SELECT question_id, secondary, option_id
      FROM   tlc_tt_question_options qo 
      WHERE survey_id=? and survey_rev=?
      ORDER BY question_id, secondary, sequence
    SQL;
  
    $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
    if(!$rows) { return; }

    foreach ($rows as $row) {
      $qid = $row['question_id'];

      if(isset($questions[$qid])) {
        $questions[$qid]['options'][] = [ $row['option_id'], $row['secondary'] ];
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

  static function pdf_path($survey_id)
  {
    return app_file("pdf/survey_$survey_id.pdf");
  }

  static function pdf_file($survey_id)
  {
    $pdf_file = self::pdf_path($survey_id);

    if(file_exists($pdf_file)) {
      return $pdf_file;
    } else {
      return null;
    }
  }

  // @@@ WORK HERE

  static function update($id,$rev,$name,$pdf_action,$new_pdf_file,$new_content,&$error=null)
  {
    // TODO: Revision tracking
  
    $error = '';
    $errid = bin2hex(random_bytes(3));

    // We want the update to be all or nothing, so wrap it in a MySQL transaction
    //   so that we can do a rollback if something goes wrong
    MySQLBeginTransaction();

    $has_change = false;
    try {
      self::_update_timestamp($id);

      if( self::_update_name($id,$name) )                                { $has_change = true; }
      if( self::_update_options($id,$rev, $new_content['options']) )     { $has_change = true; }
      if( self::_update_sections($id,$rev, $new_content['sections']) )   { $has_change = true; }
      if( self::_update_questions($id,$rev, $new_content['questions']) ) { $has_change = true; }
      self::_update_pdf($id,$rev,$pdf_action,$new_pdf_file);
  
    }
    catch(FailedToUpdate $e)
    {
      MySQLRollback();
      $error = "Failed to update survey. Please report error $errid to a tech admin";
      return false;
    }
  
    if($has_change) { 
      // Strip all future survey revision data as this may now be compromised
      MySQLExecute("delete from tlc_tt_question_options where survey_id=$id and survey_rev>$rev");
      MySQLExecute("delete from tlc_tt_survey_questions where survey_id=$id and survey_rev>$rev");
      MySQLExecute("delete from tlc_tt_survey_sections  where survey_id=$id and survey_rev>$rev");
      MySQLExecute("delete from tlc_tt_survey_options   where survey_id=$id and survey_rev>$rev");
      log_dev("Commit"); MySQLCommit(); 
    }
    else { 
      log_dev("Rollback"); MySQLRollback(); 
    }
  
    return true;
  }
};

function active_survey_id()    { return Surveys::active_id();    }
function active_survey_title() { return Surveys::active_title(); }
function survey_info($id)      { return Surveys::info($id);      }
function all_surveys()         { return Surveys::get_all();      }

function next_survey_ids($survey_id)
{ 
  return Surveys::next_ids($survey_id);
}

function survey_content($survey_id, $survey_rev=NULL) 
{ 
  return Surveys::content($survey_id,$survey_rev); 
}

function survey_pdf_file($survey_id) 
{ 
  return Surveys::pdf_file($survey_id);
}

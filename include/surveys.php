<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('include/db.php'));
require_once(app_file('include/logger.php'));
require_once(app_file('include/strings.php'));

class FailedToCreate extends \Exception {}
class FailedToUpdate extends \Exception {}

class Surveys
{
  static function active_id()
  {
    $ids = MySQLSelectValues("select id from tlc_tt_active_surveys");
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
    $info = MySQLSelectRow("select * from tlc_tt_surveys where id=?",'i',$id);
    if(self::pdf_file($id)) {
      $info['has_pdf'] = true;
    }
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
      $survey['has_pdf'] = (null !== self::pdf_file($survey['id']));
    }

    return $surveys;
  }

  static function content($survey_id, $survey_rev=NULL)
  {
    // current survey revision
  
    if(is_null($survey_rev)) {
      $survey_rev = MySQLSelectValue('select revision from tlc_tt_surveys where id=(?)', 'i', $survey_id);
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
      SELECT so.id    as option_id,
             text.str as text
      FROM   tlc_tt_survey_options so
      JOIN 
      ( 
        SELECT survey_id, id, max(survey_rev) as rev 
          FROM tlc_tt_survey_options
         WHERE survey_id=(?) AND survey_rev<=(?) 
         GROUP BY survey_id, id
      ) f on so.survey_id = f.survey_id AND so.id = f.id AND so.survey_rev = f.rev
      LEFT JOIN tlc_tt_strings text ON text.id = so.text_sid
      ORDER BY so.id;
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
      JOIN 
      ( 
        SELECT survey_id, max(survey_rev) as rev 
          FROM tlc_tt_survey_sections
         WHERE survey_id=(?) AND survey_rev<=(?) 
         GROUP BY survey_id
      ) f on s.survey_id = f.survey_id AND s.survey_rev = f.rev
      LEFT JOIN tlc_tt_strings name        ON name.id        = s.name_sid
      LEFT JOIN tlc_tt_strings description ON description.id = s.description_sid
      LEFT JOIN tlc_tt_strings feedback    ON feedback.id    = s.feedback_sid
      ORDER BY s.sequence;
    SQL;
    $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  
    return $rows ? array_column($rows,null,'sequence') : [];
  }

  static function _questions($survey_id,$survey_rev)
  {
    //  survey_rev is per (survey id, question id)
  
    $query = <<<SQL
      SELECT q.id            as question_id,
             q.section       as section,
             q.sequence      as sequence,
             wording.str     as wording,
             q.question_type as question_type,
             q.multiple      as multiple,
             other.str       as other,
             qualifier.str   as qualifier,
             description.str as description,
             info.str        as info
      FROM   tlc_tt_survey_questions q
      JOIN 
      (
        SELECT survey_id, id, max(survey_rev) as rev
          FROM tlc_tt_survey_questions
         WHERE survey_id=(?) AND survey_rev<=(?)
         GROUP BY survey_id, id
      ) f ON q.survey_id = f.survey_id AND q.id = f.id AND q.survey_rev = f.rev
      LEFT JOIN tlc_tt_strings wording     ON wording.id     = q.wording_sid
      LEFT JOIN tlc_tt_strings other       ON other.id       = q.other_sid
      LEFT JOIN tlc_tt_strings qualifier   ON qualifier.id   = q.qualifier_sid
      LEFT JOIN tlc_tt_strings description ON description.id = q.description_sid
      LEFT JOIN tlc_tt_strings info        ON info.id        = q.info_sid
      ORDER BY q.section, q.sequence;
    SQL;
    $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
  
    if(!$rows) { return array(); }
  
    $q_fields = [
      'INFO'     => ['wording'=>'infotag', 'info'],
      'BOOL'     => ['wording', 'description', 'qualifier', 'info'=>'popup'],
      'OPTIONS'  => ['wording', 'description', 'qualifier', 'other', 'info'=>'popup', 'options'],
      'FREETEXT' => ['wording', 'description', 'info'=>'popup']
    ];
  
    $questions = array();
    foreach($rows as $row) {
      $id = $row['question_id'];
      $type = $row['question_type'];
      $actual_type = ($type !== 'OPTIONS' ? $type : ($row['multiple'] ? 'SELECT_MULTI' : 'SELECT_ONE'));
  
      $q = [ 'id' => $id, 'type' => $actual_type ];
  
      if($row['sequence']) {
        $q['section']  = $row['section'];
        $q['sequence'] = $row['sequence'];
      }
  
      foreach ($q_fields[$type] ?? [] as $from => $to)
      {
        if(is_int($from)) { $from = $to; } // straight copy from row to question
        $q[$to] = ($to !== 'options' ? $row[$from] : array());
      }
  
      $questions[$id] = $q;
    }
  
    self::_add_question_options($questions, $survey_id,$survey_rev);
  
    return $questions;
  }
  
  static function _add_question_options(&$questions,$survey_id,$survey_rev)
  {
    $query = <<<SQL
      SELECT qo.question_id as question_id, 
             qo.sequence    as sequence, 
             qo.option_id   as option_id,
             qo.secondary   as secondary
      FROM   tlc_tt_question_options qo 
      JOIN 
      (
        SELECT survey_id, question_id, sequence, max(survey_rev) as rev
          FROM tlc_tt_question_options 
         WHERE survey_id=(?) AND survey_rev<=(?)
         GROUP BY survey_id, question_id, sequence
      ) f
      ON  qo.survey_id   = f.survey_id 
      AND qo.question_id = f.question_id 
      AND qo.sequence    = f.sequence 
      AND qo.survey_rev  = f.rev
      ORDER BY qo.question_id, qo.sequence;
    SQL;
  
    $rows = MySQLSelectRows($query, 'ii', $survey_id, $survey_rev);
    if(!$rows) { return; }
  
    foreach ($rows as $row) {
      $qid = $row['question_id'];
      if(!isset($questions[$qid]))            { internal_error("Options found for non-existent questions"); }
      if(!isset($questions[$qid]['options'])) { internal_error("Options found on non-options type question"); }
      $questions[$qid]['options'][] = [ $row['option_id'], $row['secondary'] ];
    }
  }

  static function next_ids($survey_id) 
  {
    return [
      'survey'   => 1 + MySQLSelectValue('select max(id) from tlc_tt_surveys'),
      'question' => 1 + MySQLSelectValue('select max(id) from tlc_tt_survey_questions'),
      'option'   => 1 + MySQLSelectValue('select max(id) from tlc_tt_survey_options where survey_id=(?)','i',$survey_id),
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

  static function create_new($name,$parent_id,$pdf_file,&$error=null)
  {
    $error = '';
    $new_id = null;
  
    try {
      MySQLBeginTransaction();
  
      $query = "insert into tlc_tt_surveys (title) values (?)";
      $rc = MySQLExecute($query,'s',$name);
  
      if(!$rc) { 
        throw new FailedToCreate('Failed to create a new entry in the database');
      }
      $new_id = MySQLInsertID();
  
      if($parent_id) {
        $query = "select revision from tlc_tt_surveys where id = $parent_id";
        $parent_rev = MySQLSelectValue($query);
        if(!$parent_rev) {
          throw new FailedToCreate("Failed to find survey to clone ($parent_id)");
        }
  
        $query = "update tlc_tt_surveys set parent=$parent_id where id=$new_id";
        $rc = MySQLExecute($query);
        if(!$rc) {
          throw new FailedToCreate("Failed to update parent id in clone");
        }
  
        self::_clone_options($parent_id,$new_id);
        self::_clone_sections($parent_id,$new_id);
        self::_clone_questions($parent_id,$new_id);
        self::_clone_question_options($parent_id,$new_id);
      }
  
      if($pdf_file) {
        $tgt_file = app_file("pdf/survey_$new_id.pdf");
        if(!move_uploaded_file($pdf_file,$tgt_file)) {
          throw new FailedToCreate("Failed to upload PDF file");
        }
      }
  
      MySQLCommit();
    }
    catch(FailedToCreate $e)
    {
      MySQLRollback();
      $error = "Failed to create new survey (" . $e->getMessage() . ")";
      $new_id = null;
    }
  
    return $new_id;
  }

  static function _clone_options($parent_id,$child_id)
  {
    $query = <<<SQL
    INSERT into tlc_tt_survey_options
    SELECT a.id, $child_id, 1, a.text
      FROM tlc_tt_survey_options a
     WHERE a.survey_id=$parent_id
       AND a.survey_rev = (
             SELECT MAX(b.survey_rev)
               FROM tlc_tt_survey_options b
              WHERE b.survey_id=$parent_id
                AND b.id = a.id )
      ;
    SQL;
    if(!MySQLExecute($query)) {
      throw new FailedToCreate('Failed to copy survey options from cloned survey');
    }
  }

  static function _clone_sections($parent_id,$child_id)
  {
    $query = <<<SQL
    INSERT into tlc_tt_survey_sections
    SELECT $child_id, 1, a.section, a.name, a.labeled, a.description, a.feedback
      FROM tlc_tt_survey_sections a
     WHERE a.survey_id=$parent_id
       AND a.survey_rev = (
             SELECT MAX(b.survey_rev)
               FROM tlc_tt_survey_sections b
              WHERE b.survey_id=$parent_id
                AND b.section=a.section )
      ;
    SQL;
    if(!MySQLExecute($query)) {
      throw new FailedToCreate('Failed to copy survey sections from cloned survey');
    }
  }

  static function _clone_questions($parent_id,$child_id)
  {
    $query = <<<SQL
    INSERT into tlc_tt_survey_questions
    SELECT a.id, $child_id, 1, 
           a.section, a.sequence, a.wording, 
           a.question_type, a.multiple, a.other, a.qualifier, a.description, a.info
      FROM tlc_tt_survey_questions a
     WHERE a.survey_id=$parent_id
       AND a.survey_rev = (
             SELECT MAX(b.survey_rev)
               FROM tlc_tt_survey_questions b
              WHERE b.survey_id=$parent_id
                AND b.id=a.id )
      ;
    SQL;
    if(!MySQLExecute($query)) {
      throw new FailedToCreate('Failed to copy survey questions from cloned survey');
    }
  }

  static function _clone_question_options($parent_id,$child_id)
  {
    $query = <<<SQL
    INSERT into tlc_tt_question_options
    SELECT $child_id, 1, a.question_id, a.sequence, a.option_id, a.secondary
      FROM tlc_tt_question_options a
     WHERE a.survey_id=$parent_id
       AND a.sequence is not NULL
       AND a.survey_rev = (
             SELECT MAX(b.survey_rev)
               FROM tlc_tt_question_options b
              WHERE b.survey_id=$parent_id
                AND a.question_id=b.question_id
                AND a.option_id=b.option_id )
      ;
    SQL;
    if(!MySQLExecute($query)) {
      throw new FailedToCreate('Failed to copy survey questions from cloned survey');
    }
  }

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
      log_dev("has_change: $has_change");

      if( self::_update_name($id,$name) )                              { log_dev("name change"); $has_change = true; }
      log_dev("has_change: $has_change");
      if( self::_update_options($id,$rev, $new_content['options']) )   { log_dev("options change"); $has_change = true; }
      log_dev("has_change: $has_change");
      if( self::_update_sections($id,$rev, $new_content['sections']) ) { log_dev("sections change"); $has_change = true; }
      log_dev("has_change: $has_change");
      self::_update_pdf($id,$rev,$pdf_action,$new_pdf_file);
      log_dev("has_change: $has_change");
  
    }
    catch(FailedToUpdate $e)
    {
      MySQLRollback();
      $error = "Failed to update survey. Please report error $errid to a tech admin";
      return false;
    }
  
    log_dev("has_change: $has_change");
    if($has_change) { log_dev("Commit"); MySQLCommit(); }
    else            { log_dev("Rollback"); MySQLRollback(); }
  
    return true;
  }
  
  
  static function _update_timestamp($id)
  {
    $rc = MySQLExecute('update tlc_tt_surveys set modified=now() where id=?','i',$id);
    if(!$rc) {
      log_error("[$errid] Failed to update modification timestamp for id=$id");
      throw FailedToUpdate("update modification timestamp");
    }
  }
  
  
  static function _update_name($id,$name)
  {
    if(!$name) { return false; }

    $cur_name = MySQLSelectValue("select title from tlc_tt_surveys where id='$id'");
    if($name === $cur_name) { return false; }

    $rc = MySQLExecute('update tlc_tt_surveys set title=? where id=?','si',$name,$id);
    if($rc === false) {
      log_error("[$errid] Failed to update entry ($id,$name)");
      throw FailedToUpdate("updating name");
    }

    return true;
  }

  static function _update_pdf($id,$rev,$action,$new_pdf) 
  {
    log_dev(" _update_pdf($id,$rev,$action,$new_pdf) ");
    $pdf_path = self::pdf_path($id);

    if($action === 'drop' || $action === 'replace') {
      log_dev("drop old pdf: $pdf_path");
      if(file_exists($pdf_path)) {
        log_dev("...exists");
        if(!unlink($pdf_path)) {
          log_error("[$errid] Failed to unlink $pdf_path");
          throw FailedToUpdate("drop existing PDF file");
        }
        log_dev("...unlinked");
      }
    }

    if($action === 'add' || $action === 'replace') {
      log_dev("move new pdf: $pdf_path <-- $new_pdf");
      if(!move_uploaded_file($new_pdf,$pdf_path)) {
        log_error("[$errid] Failed to save updloded PDF to $pdf_path");
        throw FailedToUpdate("updating PDF file");
      }
      log_dev("...moved");
    }
  }

  static function _update_options($id,$rev, $new_options)
  {
    log_dev(" _update_options($id,$rev, ...");
    $cur_options = self::_options($id,$rev);

    // no need to redefine the insert statement in the for loop
    $query = <<<SQL
      INSERT into tlc_tt_survey_options (survey_id, id, survey_rev, text_sid) 
      VALUES (?,?,?,?)
      ON DUPLICATE KEY UPDATE text_sid = values(text_sid)
    SQL;

    $has_change = false;
    foreach($new_options as $option_id => $new_text) 
    {
      $cur_text = $cur_options[$option_id] ?? '';

      if( $new_text !== $cur_text ) {
        $has_change = true;

        $new_sid = strings_find_or_create($new_text);

        MySQLExecute($query,'iiii', $id, $option_id, $rev, $new_sid);
      }
    }
    return $has_change;
  }

  static function _sections_changed($cur,$new) 
  {
    log_dev(" _sections_changed($cur,$new) ");
    $n = count($cur);

    log_dev("Cur section count = $n");

    if($n !== count($new)) { return true; }

    log_dev("New section count also = $n");

    for($i = 0; $i < $n; ++$i) {
      log_dev("Looking for differences in section $i");
      foreach(['name','labeled','description','feedback'] as $key)
      {
        $cur_val = $cur[$i][$key] ?? '';
        $new_val = $new[$i][$key] ?? '';
        log_dev("comparing $key: cur=$cur_val new=$new_val");
        if( $cur_val !== $new_val ) { return true; }
      }
    }
    log_dev("everything matched");
    return false;
  }

  static function _update_sections($id,$rev, $new_sections)
  {
    log_dev(" _update_sections($id,$rev, $new_sections)");
    // There is no bleed-through or section data.  If there are
    //   ANY differences from the current max rev in the database,
    //   then we need to insert/upddate all section data for the
    //   current rev.
    
    // sort the new sections and extract the section data from the
    //   associative array mapping sequence to section data
    ksort($new_sections);
    log_dev("ksort complete: ".print_r($new_sections,true));
    $new_sections = array_values($new_sections);
    log_dev("sections extrqcted: ".print_r($new_sections,true));

    // do the same for the current section data, but sorting is not
    //   necessary as the _sections method does this for us
    $cur_sections = self::_sections($id,$rev);
    log_dev("cur sections retrieved: ".print_r($cur_sections,true));
    $cur_sections = array_values( $cur_sections );
    log_dev("sections extrqcted: ".print_r($cur_sections,true));

    // see if there are any difference in the section data
    if(!self::_sections_changed($cur_sections, $new_sections)) { return false; }

    log_dev("section changes found");

    // define the query that will be used to update the section data
    //   (no need to do this within the for loop)

    MySQLExecute(
      "delete from tlc_tt_survey_sections where survey_id=$id and survey_rev=$rev"
    );

    $query = <<<SQL
      INSERT into tlc_tt_survey_sections
             (survey_id, survey_rev, sequence, name_sid, labeled, description_sid, feedback_sid)
      VALUES (?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
             name_sid = VALUES(name_sid),
             labeled = VALUES(labeled),
             description_sid = VALUES(description_sid),
             feedback_sid = VALUES(feedback_sid)
    SQL;

    log_dev("query defined");

    $seq = 0;
    foreach($new_sections as $new_data) {
      log_dev("inserting updated section ".(1+$seq));
      MySQLExecute($query, 
        'iiiiiii', 
        $id, $rev, ++$seq, 
        strings_find_or_create($new_data['name']        ?? null),
        $new_data['labeled'] ? 1 : 0,
        strings_find_or_create($new_data['description'] ?? null),
        strings_find_or_create($new_data['feedback']    ?? null),
      );
    }

    log_dev("section update complete");
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

function create_new_survey($name,$parent_id,$pdf_file,&$error=null) 
{
  return Surveys::create_new($name,$parent_id,$pdf_file,$error);
}

function update_survey($id,$rev,$name,$pdf_action,$new_pdf_file,$new_content,&$error=null)
{
  $rval = Surveys::update($id,$rev,$name,$pdf_action,$new_pdf_file,$new_content,$error);
  return $rval;
}

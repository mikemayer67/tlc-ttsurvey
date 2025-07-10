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
             q.survey_rev    as rev,
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
      $rev = $row['rev'];
      $type = $row['question_type'];
      $actual_type = ($type !== 'OPTIONS' ? $type : ($row['multiple'] ? 'SELECT_MULTI' : 'SELECT_ONE'));
  
      $q = [ 'id' => $id, 'rev' => $rev, 'type' => $actual_type ];
  
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
             qo.option_id   as option_id,
             qo.secondary   as secondary
      FROM   tlc_tt_question_options qo 
      JOIN 
      (
        SELECT survey_id, question_id, max(survey_rev) as rev
          FROM tlc_tt_question_options 
         WHERE survey_id=(?) AND survey_rev<=(?)
         GROUP BY survey_id, question_id
      ) f
      ON  qo.survey_id   = f.survey_id 
      AND qo.question_id = f.question_id 
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
    log_dev("update... new_content.questions.1: ".print_r($new_content['questions'][1],true));
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
    $pdf_path = self::pdf_path($id);

    if($action === 'drop' || $action === 'replace') {
      if(file_exists($pdf_path)) {
        if(!unlink($pdf_path)) {
          log_error("[$errid] Failed to unlink $pdf_path");
          throw FailedToUpdate("drop existing PDF file");
        }
      }
    }

    if($action === 'add' || $action === 'replace') {
      if(!move_uploaded_file($new_pdf,$pdf_path)) {
        log_error("[$errid] Failed to save updloded PDF to $pdf_path");
        throw FailedToUpdate("updating PDF file");
      }
    }
  }

  static function _update_options($id,$rev,$new_options)
  {
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
    $n = count($cur);
    if($n !== count($new)) { return true; }

    for($i = 0; $i < $n; ++$i) {
      if($new[$i]['sequence'] != (1+$i)) { return true; }
    }

    for($i = 0; $i < $n; ++$i) {
      foreach(['name','labeled','description','feedback'] as $key)
      {
        $cur_val = $cur[$i][$key] ?? '';
        $new_val = $new[$i][$key] ?? '';
        if( $cur_val !== $new_val ) { return true; }
      }
    }
    return false;
  }

  static function _update_sections($id,$rev, $new_sections)
  {
    // There is no bleed-through or section data.  If there are
    //   ANY differences from the current max rev in the database,
    //   then we need to insert/upddate all section data for the
    //   current rev.
    
    // sort the new sections and extract the section data from the
    //   associative array mapping sequence to section data
    ksort($new_sections);
    $new_sections = array_values($new_sections);

    // do the same for the current section data, but sorting is not
    //   necessary as the _sections method does this for us
    $cur_sections = self::_sections($id,$rev);
    $cur_sections = array_values( $cur_sections );

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

    $seq = 0;
    foreach($new_sections as $new_data) {
      MySQLExecute($query, 
        'iiiiiii', 
        $id, $rev, ++$seq, 
        strings_find_or_create($new_data['name']        ?? null),
        $new_data['labeled'] ? 1 : 0,
        strings_find_or_create($new_data['description'] ?? null),
        strings_find_or_create($new_data['feedback']    ?? null),
      );
    }

    return true;
  }

  private static function map_options($options) {
    $rval = [];
    foreach($options as $kv) { $rval[$kv[0]] = $kv[1]; }
    return $rval;
  }

  static function _question_changed($cur_question, $new_question)
  {
    $cur_question_id = $cur_question['id'] ?? null;
    $new_question_id = $new_question['id'] ?? null;
    if($cur_question_id !== $new_question_id) { 
      internal_error("Question IDs don't match ($cur_question_id vs $new_question_id)");
    }

    $cur_type = $cur_question['type'] ?? null;
    $new_type = $new_question['type'] ?? null;
    if($cur_type !== $new_type) {
      internal_error("Something is off in the javascript... type for question $cur_question_id changed from $cur_type to $new_type");
    }

    $type_keys = [
      'INFO'         => ['section','sequence','infotag','info'],
      'FREETEXT'     => ['section','sequence','wording','description','popup'],
      'BOOL'         => ['section','sequence','wording','description','qualifier','popup'],
      'SELECT_ONE'   => ['section','sequence','wording','description','qualifier','other','popup'],
      'SELECT_MULTI' => ['section','sequence','wording','description','qualifier','other','popup'],
    ];

    foreach($type_keys[$cur_type] as $key) {
      $cur_value = $cur_question[$key] ?? null;
      $new_value = $new_question[$key] ?? null;
      if($cur_value !== $new_value) { 
        log_dev("Question $cur_question_id has new $key value ($cur_value --> $new_value)");
        return true; 
      }
    }

    return false;
  }

  static function _question_options_changed($question_id,$cur_options,$new_options)
  {
    $cur_primary = [];
    $cur_secondary = [];
    foreach( $cur_options as [$qid,$secondary] ) {
      if($secondary) { $cur_secondary[] = $qid; }
      else           { $cur_primary[]   = $qid; }
    }
    $new_primary = [];
    $new_secondary = [];
    foreach( $new_options as [$qid,$secondary] ) {
      if($secondary) { $new_secondary[] = $qid; }
      else           { $new_primary[]   = $qid; }
    }
    $n_primary = count($cur_primary);
    if(count($new_primary) !== $n_primary) {
      log_dev("Question $question_id has different number of primary options");
      return true;
    }
    $n_secondary = count($cur_secondary);
    if(count($new_secondary) !== $n_secondary) {
      log_dev("Question $question_id has different number of secondary options");
      return true;
    }
    for($i=0; $i<$n_primary; ++$i) {
      if($new_primary[$i] !== $cur_primary[$i]) {
        log_dev("Primary option $i has changed for question $question_id");
        return true;
      }
    }
    for($i=0; $i<$n_secondary; ++$i) {
      if($new_secondary[$i] !== $cur_secondary[$i]) {
        log_dev("secondary option $i has changed for question $question_id");
        return true;
      }
    }

    return false;
  }

  static function _update_questions($id,$rev, $new_questions)
  {
    $rval = false;
    $cur_questions = self::_questions($id,$rev);

    foreach($new_questions as $question_id=>$new_data) {
      $type = $new_data['type'];
      $has_options = str_starts_with($type,'SELECT');

      $cur_data = $cur_questions[$question_id] ?? null;
      if($cur_data) {
        if(self::_question_changed($cur_data,$new_data)) {
          self::_update_question($id,$rev,$question_id,$new_data);
        }
        if($has_options) {
          $cur_options = $cur_data['options'] ?? [];
          $new_options = $new_data['options'] ?? [];
          if( self::_question_options_changed($question_id,$cur_options,$new_options) ) {
            self::_update_question_options($id,$rev,$question_id,$new_options);
          }
        }
      } 
      else {
        self::_insert_new_question($id,$rev,$question_id,$new_data);
        if($has_options) {
          $options = $new_data['options'] ?? [];
          self::_update_question_options($id,$rev,$question_id,$options);
        }
      }
    }
    foreach($cur_questions as $question_id=>$cur_data) {
      if(!array_key_exists($question_id,$new_questions)) {
        if(($cur_data['section']??0) > 0) {
          self::_disable_question($id,$rev,$question_id,$cur_data['rev']);
          $rval = true;
        }
      }
    }
  }

  static function _update_question($id,$rev,$question_id,$data)
  {
    log_dev("_update_question($id,$rev,$question_id,...) data=".print_r($data,true));

    $type = $data['type'];
    $wording_key = $type === 'INFO' ? 'infotag' : 'wording';
    $info_key = $type === 'INFO' ? 'info' : 'popup';

    if($data['rev'] == $rev) {
      log_dev("update");
      // update existing quesiton data for current rev
      $query = <<<SQL
      UPDATE tlc_tt_survey_questions
         SET section=?, sequence=?, wording_sid=?, 
             other_sid=?, qualifier_sid=?, description_sid=?, info_sid=?
       WHERE id=$question_id
         AND survey_id=$id
         AND survey_rev=$rev
      SQL; 
      $value_types = 'iiiiiii';

      MySQLExecute( 
        $query, $value_types,
        $data['section'], $data['sequence'],
        strings_find_or_create( $data[$wording_key] ?? null ),
        strings_find_or_create( $data['other'] ?? null ),
        strings_find_or_create( $data['qualifier'] ?? null ),
        strings_find_or_create( $data['description'] ?? null ),
        strings_find_or_create( $data[$info_key] ?? null )
      );
    } 
    else {
      log_dev("insert");
      // insert new rev for current question
      $query = <<<SQL
        INSERT into tlc_tt_survey_questions
               ( id,survey_id,survey_rev,section,sequence,
                 wording_sid,question_type,multiple,
                 other_sid,qualifier_sid,description_sid,info_sid )
        VALUES ($question_id, $id, $rev, ?, ?, ?, ?, ?, ?, ?, ?, ? )
      SQL;
      $value_types = 'iiisiiiii';

      MySQLExecute( 
        $query, $value_types,
        $data['section'], $data['sequence'],
        strings_find_or_create( $data[$wording_key] ?? null ),
        str_starts_with($type,'SELECT') ? 'OPTIONS' : $type,
        $type === 'SELECT_MULTI' ? 1 : 0,
        strings_find_or_create( $data['other'] ?? null ),
        strings_find_or_create( $data['qualifier'] ?? null ),
        strings_find_or_create( $data['description'] ?? null ),
        strings_find_or_create( $data[$info_key] ?? null )
      );
    }
  }

  static function _update_question_options($id,$rev,$question_id,$options)
  {
    log_dev("_update_question_options($id,$rev,$question_id,...) => ".print_r($options,true));
    // clear out any existing options
    $query = <<<SQL
      DELETE from tlc_tt_question_options
       WHERE survey_id=$id AND survey_rev=$rev AND question_id=$question_id
    SQL;
    MySQLExecute($query);

    // insert the new/updated options
    $query = <<<SQL
      INSERT into tlc_tt_question_options
             (survey_id,survey_rev,question_id,sequence,option_id,secondary)
      VALUES ($id,$rev,$question_id,?,?,?)
    SQL;
    $value_types = 'iii';
    $seq = 1;
    foreach($options as $option) {
      log_dev("$query\n$value_types\n$seq\n$option[0] $option[1]");
      MySQLExecute($query,$value_types,$seq++,$option[0],$option[1]?1:0);
    }
  }

  static function _insert_new_question($id,$rev,$question_id,$data)
  {
    log_dev("_insert_new_question($id,$rev,$question_id,...) => ".print_r($data,true));

    $query = <<<SQL
      INSERT INTO tlc_tt_survey_questions
             ( id, survey_id, survey_rev, section, sequence, 
               wording_sid, question_type, multiple, 
               other_sid, qualifier_sid, description_sid, info_sid )
      VALUES ($question_id,$id,$rev,?,?,?,?,?,?,?,?,?);
    SQL;
    $value_types = 'iiisiiiii';

    $type = $data['type'];
    $wording_key = $type === 'INFO' ? 'infotag' : 'wording';
    $info_key = $type === 'INFO' ? 'info' : 'popup';

    MySQLExecute( 
      $query, $value_types,
      $data['section'], $data['sequence'],
      strings_find_or_create( $data[$wording_key] ?? null ),
      str_starts_with($type,'SELECT') ? 'OPTIONS' : $type,
      $type === 'SELECT_MULTI' ? 1 : 0,
      strings_find_or_create( $data['other'] ?? null ),
      strings_find_or_create( $data['qualifier'] ?? null ),
      strings_find_or_create( $data['description'] ?? null ),
      strings_find_or_create( $data[$info_key] ?? null )
    );
  }

  static function _disable_question($survey_id,$survey_rev,$question_id,$current_rev) 
  {
    $update_query = <<<SQL
      UPDATE tlc_tt_survey_questions
         SET section = NULL, sequence = NULL
       WHERE survey_id=$survey_id
         AND survey_rev=$survey_rev
         AND id=$question_id
      SQL;

    static $insert_query = <<<SQL
      INSERT INTO tlc_tt_survey_questions
             ( id, survey_id, survey_rev, section, sequence, 
               wording_sid, question_type, multiple, 
               other_sid, qualifier_sid, description_sid, info_sid )
      SELECT id, survey_id, $survey_rev, NULL, NULL, 
             wording_sid, question_type, multiple, 
             other_sid, qualifier_sid, description_sid, info_sid
      FROM   tlc_tt_survey_questions
      WHERE  id=$question_id
        AND  survey_id=$survey_id
        AND  survey_rev=$current_rev
      SQL;

    if($current_rev === $survey_rev) {
      $query = $update_query;
    } else {
      $query = $insert_query;
    }

    log_dev("Removing question with:\n$query");
    MySQLExecute($query);
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

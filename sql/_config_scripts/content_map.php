<?php

if(!defined('IN_MIGRATION')) { 
  throw new Exception(
    __FILE__ . " cannot be run directly.\n".
    "It must be included by configure_database.php"
  );
}

/**
 * Populates the v1.1.0 structure map from the v1.0.0 question map and question grouping atttributes
 * @param PDO $pdo 
 * @return void 
 */
function populate_content_map(PDO $pdo)
{
  $sections = [];
  $query = 'SELECT survey_id,section_id,sequence FROM tlc_tt_survey_sections';
  foreach ($pdo->query($query, PDO::FETCH_NUM) as [$survey_id, $section_id, $sequence] ) {
    $sections[$survey_id][$sequence] = $section_id;
  }

  $questions = [];
  $query = 'SELECT survey_id,question_id,question_flags FROM tlc_tt_survey_questions';
  foreach ($pdo->query($query, PDO::FETCH_NUM) as [$survey_id,$question_id,$flags] ) {
    $grouped = ($flags & 0x08) ? true : (($flags & 0x10) ? 'new' : false);
    $questions[$survey_id][$question_id] = $grouped;
  }

  $question_map = [];
  $query = 'SELECT survey_id,section_id,question_seq,question_id from tlc_tt_question_map';
  foreach ($pdo->query($query, PDO::FETCH_NUM) as [$survey_id,$section_id,$sequence,$question_id] ) {
    $question_map[$survey_id][$section_id][$sequence] = $question_id;
  }

  $insert = $pdo->prepare( <<<SQL
    INSERT into tlc_tts_survey_content
          (survey_id, section_id, content_seq, content_type, content_id) 
          values (?,?,?,?,?);
  SQL );

  $new_group = $pdo->prepare( <<<SQL
    INSERT into tlc_tts_survey_sections
           (survey_id, section_id, name)
           values (?,?,?);
  SQL );

  $root_id = 0;

  foreach( $sections as $survey_id => $survey_sections )
  {
    ksort($survey_sections);
    $section_ids = array_values($survey_sections);

    $survey_seq = 0;
    $base_id = max($section_ids);
    $group_id = $base_id;

    foreach($section_ids as $section_id) {
      $insert->execute([$survey_id, $root_id, ++$survey_seq, 'SECTION', $section_id]);

      print_r([$survey_id,$section_id]);
      $section_questions = $question_map[$survey_id][$section_id] ?? [];
      ksort($section_questions);
      $question_ids = array_values($section_questions);

      $section_seq = 0;
      $group_seq = 0;
      $in_group = false;
      foreach($question_ids as $question_id) {
        $grouped = $questions[$survey_id][$question_id];
        if($in_group) {
          if($grouped === 'new') {
            // question is first in a group but we're already in a group
            //   start a new group and add the question to it
            $group_id += 1;
            $group_seq = 0;
            $new_group->execute([$survey_id, $group_id, "Group " . ($group_id - $base_id)]);
            $insert->execute([$survey_id,$section_id,++$section_seq,'SECTION',$group_id]);
            $insert->execute([$survey_id,$group_id,++$group_seq,'QUESTION',$question_id]);
            $in_group = true;
          } elseif($grouped) {
            // question is in a group and we're already in a group
            //   simply add the question to the group
            $insert->execute([$survey_id,$group_id,++$group_seq,'QUESTION',$question_id]);
            $in_group = true;
          } else {
            // question is not in group, but we're currently in a group
            //   simply add the question to the section and change in_group flag
            $insert->execute([$survey_id,$section_id,++$section_seq,'QUESTION',$question_id]);
            $in_group = false;
          }
        } else {
          if($grouped) {
            // question is grouped, but we're not currently filling a group...
            //   start the group and add the question to it
            $group_id += 1;
            $group_seq = 0;
            $new_group->execute([$survey_id, $group_id, "Group " . ($group_id - $base_id)]);
            $insert->execute([$survey_id,$section_id,++$section_seq,'SECTION',$group_id]);
            $insert->execute([$survey_id,$group_id,++$group_seq,'QUESTION',$question_id]);
            $in_group = true;
          } else {
            // question is not in group and we're not currently filling a group
            //   simply add the question to the section
            $insert->execute([$survey_id,$section_id,++$section_seq,'QUESTION',$question_id]);
            $in_group = false;
          }

        }
      }

    }
  }
}

?>
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
  $query = 'SELECT survey_id,section_id FROM tlc_tt_survey_sections';
  foreach ($pdo->query($query, PDO::FETCH_NUM) as [$survey_id, $section_id] ) {
    $sections[$survey_id][] = $section_id;
  }

  $question_is_grouped = [];
  $query = 'SELECT survey_id,question_id,question_flags FROM tlc_tt_survey_questions';
  foreach ($pdo->query($query, PDO::FETCH_NUM) as [$survey_id,$question_id,$flags] ) {
    $question_is_grouped[$survey_id][$question_id] = ($flags & 0x08) === 0x08;
  }

  $question_map = [];
  $query = 'SELECT survey_id,section_id,question_seq,question_id from tlc_tt_question_map';
  foreach ($pdo->query($query, PDO::FETCH_NUM) as [$survey_id,$section_id,$sequence,$question_id] ) {
    $question_map[$survey_id][$section_id][$sequence] = $question_id;
  }

  $add_named_group = $pdo->prepare( <<< SQL
    INSERT into tlc_srv_question_groups
          (survey_id,group_id,name)
          values (?,?,?)
  SQL);

  $add_auto_group = $pdo->prepare( <<< SQL
    INSERT into tlc_srv_question_groups
          (survey_id,group_id)
          values (?,?)
  SQL);

  $add_group_to_section = $pdo->prepare( <<<SQL
    INSERT into tlc_srv_section_content
          (survey_id, section_id, sequence, group_id)
          values (?,?,?,?);
  SQL );

  $add_group_content = $pdo->prepare( <<<SQL
    INSERT into tlc_srv_group_content
           (survey_id, group_id,sequence,question_id)
           values (?,?,?,?);
  SQL );

  foreach( $sections as $survey_id => $section_ids )
  {
    $group_id = 0;

    foreach($section_ids as $section_id) {
      $section_questions = $question_map[$survey_id][$section_id] ?? [];
      ksort($section_questions);
      $question_ids = array_values($section_questions);

      $section_seq = 0;
      $group_seq = 0;
      $in_group = false;
      foreach($question_ids as $question_id) {
        $grouped = $question_is_grouped[$survey_id][$question_id];
        if($in_group && $grouped) {
          // question is in a group and we're already in a group
          $group_seq += 1;
        } 
        elseif($grouped) {
          // question is in a group, but we're currently not in a group... start a new group
          $group_id += 1;
          $group_seq = 1;
          $group_name = "Group_{$section_id}.{$group_seq}";
          $in_group = true;
        }
        else {
          // question is not in a group, start a new unnamed group
          $group_id += 1;
          $group_seq = 0;
          $group_name = null;
          $in_group = false;
        }

        if($group_seq < 2) {
          // start a new group and add it to the current section
          if($group_name) {
            $add_named_group->execute([$survey_id,$group_id,$group_name]);
          } else {
            $add_auto_group->execute([$survey_id,$group_id]);
          }
          $add_group_to_section->execute([$survey_id,$section_id,++$section_seq,$group_id]);
        }

        $add_group_content->execute([$survey_id,$group_id,$group_seq,$question_id]);
      }
    }
  }
}

?>
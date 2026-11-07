<?php

if(!defined('IN_MIGRATION')) { 
  throw new Exception( __FILE__ . " cannot be run directly.\n".  "It must be included by configure_database.php");
}

class SurveyOptionMigration
{
  private PDO    $pdo;
  private string $filename;
  private array  $all_options       = [];  // option_id => option_str
  private array  $question_map      = [];  // option_id => list<question_id>
  private array  $question_wording  = [];  // question_id => string
  
  private ?string $editor = null;

  public function __construct(PDO $pdo)
  {
    $this->pdo      = $pdo;
    $this->filename = __DIR__ . "/option_review.txt";

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = 'SELECT option_id,option_str from tlc_srv_survey_options order by survey_id';
    foreach ($pdo->query($query, PDO::FETCH_NUM) as [$option_id, $option_str]) 
    {
      $this->all_options[$option_id] = $option_str;
      $this->question_map[$option_id] = [];
    }

    $query = 'SELECT option_id,question_id from tlc_srv_question_options';
    foreach ($pdo->query($query, PDO::FETCH_NUM) as [$option_id, $question_id]) {
      $this->question_map[$option_id][] = $question_id;
    }
    $this->question_map = array_map(
      fn($qids) => array_values(array_unique($qids)),
      $this->question_map
    );

    $query = 'SELECT question_id,wording from tlc_srv_questions where wording is not NULL order by survey_id';
    foreach($pdo->query($query, PDO::FETCH_NUM) as [$question_id,$wording])
    {
      $this->question_wording[$question_id] = $wording;
    }
  }

  /**
   * Prompts user to ask if they wish to continue
   * @throws Exception if they decline to continue
   */
  public function prompt_to_continue() : void
  {
    do {
      printf("\nDo you wish to continue (Y/N): ");
      $response = strtoupper(trim(readline()));
    } while (!in_array($response, ['Y', 'N']));
    if ($response === 'N') {
      throw new Exception("Aborted by user.");
    }
  }

  public function run()
  {
    $body = $this->gen_review_content();

    // As the following requires a human to edit the options review file, it is
    //   possible that we may need to do this a couple times to make sure they
    //   don't corrupt the file format.
    while(true) {
      // create a fresh copy of the option review file
      $rc = file_put_contents($this->filename, $body);
      if(!$rc) {
        throw new Exception("Failed to create option review file: " . $this->filename);
      }

      $rc = $this->present_review_to_user();
      if(!$rc) {
        throw new Exception("Failed to find a text editor... needed to review common options");
      }

      $mapped_options = $this->get_user_response();

      if($mapped_options !== false)  break; 

      // Give user opportunity to abort the migration
      $this->prompt_to_continue();
    }

    if(count($mapped_options)===0) {
      printf("\nNo quesiton specific options... no changes required in database.\n\n");
      return;
    }

    // Verify with user and then update the database

    $option_map = [];
    foreach($mapped_options as $option_id) {
      $question_id = $this->question_map[$option_id][0];
      $option_map[$question_id][] = $option_id;
    }
    ksort($option_map);

    printf("\nThe following options will be mapped to a specfic question:\n");
    foreach($option_map as $question_id => $option_ids) {
      $question_str = $this->question_wording[$question_id];
      printf("   $question_str\n");
      foreach($option_ids as $option_id) {
        $option_str = $this->all_options[$option_id];
        printf("    %3d %s\n",$option_id,$option_str);
      }
      printf("\n");
    }
    $this->prompt_to_continue();

    // User ok'd the changes, let's do this

    $query = "update tlc_srv_survey_options set question_id=? where option_id=?";
    $stmt = $this->pdo->prepare($query);
    foreach($mapped_options as $option_id) {
      $question_id = $this->question_map[$option_id][0];
      $stmt->execute([$question_id,$option_id]);
    }

  }

  /**
   * Create the content that will be displayed in the option review file
   * @return string 
   */
  private function gen_review_content() : string
  {
    // invert the question map, but only for options that map to a single question
    $option_map = [];
    foreach($this->question_map as $option_id => $question_ids) {
      if(count($question_ids) === 1) {
        $question_id = $question_ids[0];
        $option_map[$question_id][]  = $option_id;
      }
    }

    $content = implode("\n", [
      "#-------------------------------------------------------------------------------",
      "# Review the following mapping of multiple choice questions to the associated",
      "#   options and mark which options are uniquely bound to that question.  All",
      "#   unmarked options will be made available for general use across all multiple",
      "#   choice questions.  Note that the only options shown below are those for",
      "#   which the distinction between general use and question specific cannot be",
      "#   inferred from existing usage.",
      "#",
      "# Options which should apply to a specific question should be marked with",
      "#   an [x] below.  Options which should be available for general use should",
      "#   leave the box empty, (i.e., [ ]).",
      "#",
      "# Do not change anything else on the editable lines as doing so may result in the",
      "#   migration being aborted.",
      "#",
      "# Close the editor when you have finished reviewing.  The migration will resume",
      "#   automatically when the editor closes.",
      "#-------------------------------------------------------------------------------",
      "",
    ]);

    $content .= "\n";

    foreach($option_map as $question_id => $option_ids) {
      $question_str = $this->question_wording[$question_id];
      $content .= "   Q: $question_str\n";
      foreach($option_ids as $option_id) {
        $option_str = $this->all_options[$option_id];
        $mark = str_contains($option_str," ") ? 'x' : ' ';
        $content .= sprintf("      [$mark]  %3d: $option_str\n", $option_id);
      }
      $content .= "\n";
    }

    return $content;
  }

  /**
   * Presents the review to the user via a text editor and waits for the editor to close
   * @return bool true on success, false on failure
   * @throws Exception if failed to present the text editor
   */
  private function present_review_to_user() : bool
  {
    $editors = $this->editor 
      ? [$this->editor] 
      : array_filter([getenv('VISUAL'), getenv('EDITOR'), 'nano', 'vim', 'vi']);

    foreach($editors as $editor) {
      $cmd = $editor . ' ' . escapeshellarg($this->filename);
      $proc  = proc_open($cmd, [STDIN,STDOUT,STDERR], $pipes );

      if(!is_resource($proc)) { continue; }

      $rc = proc_close($proc);
      if($rc === 127) { continue; }

      $this->editor = $editor;
      return true;
    }

    return false;
  }

  /**
   * Parses the (potentially modified) options review file and return a list
   *   of all option IDs that should be mapped to a specific question
   * 
   * Returns false if any of the following are discovered while parsing:
   *   - An unexpected line format
   *   - An unrecognized option ID
   *   - A modified option that was for information only
   *   - A missing option ID that was expecting user feedback
   * 
   * Returns array of question specific option IDs otherwise.
   *
   * @return false|array 
   */
  private function get_user_response() : false|array
  {
    $file = new SplFileObject($this->filename);

    $mapped_options = [];
    $common_options = [];
    foreach($file as $lineno => $line) 
    {
      $line = trim($line);
      if(strlen($line) === 0)         { continue; } 
      if(str_starts_with($line,'#'))  { continue; }
      if(str_starts_with($line,'Q:')) { continue; }

      $err_note = sprintf("Please only edit the selection marks\n  [line %d] %s",$lineno,$line);
      $warn_note = "Changes to option strings are ignored. Please only edit the selection marks.";

      if( !preg_match('/^\[\s*([*xX]?)\s*\]\s+(\d+):\s+(\w.*)$/',$line,$match) )  {
        printf("\nUnrecognized line format. $err_note\n");
        return false;
      }

      $mark       = $match[1];
      $option_id  = $match[2];
      $option_str = $match[3];

      if( !key_exists($option_id, $this->all_options) ) {
        printf("\nInvalid option ID ($option_id). $err_note\n");
        return false;
      }
      if( trim($option_str) !== trim($this->all_options[$option_id]) ) {
        // not a failable error... no need to return false
        if($warn_note) {
          // only warn once
          print("\n$warn_note\n");
          $warn_note = null;
        }
      }

      if($mark) { $mapped_options[] = $option_id; } 
      else      { $common_options[] = $option_id; }
    }

    foreach($this->all_options as $option_id => $option_str) {
      if(count($this->question_map[$option_id]) !== 1) { continue; }
      if(in_array($option_id,$mapped_options)) { continue; }
      if(in_array($option_id,$common_options)) { continue; }
      printf("Cannot find response to option $option_id ($option_str).\n");
      printf("   Please only edit the selection marks themselves, nothing else.\n");
      return false;
    }
    return $mapped_options;
  }

};
    
// DO IT...

$config_script = function(PDO $pdo)
{
  $som = new SurveyOptionMigration($pdo);
  $som->run();
};
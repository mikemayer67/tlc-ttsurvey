<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_mark("-------------- Start of Render --------------");

handle_warnings();

require_once(app_file('include/logger.php'));
require_once(app_file('include/survey_content.php'));
require_once(app_file('include/question.php'));
require_once(app_file('include/question_types.php'));
require_once(app_file('survey/markdown.php'));

/**
 * The RenderEngine class handles the generation of the HTML used for the online survey.
 * It handles both the live survey for a logged in participant and preview surveys for the
 *   admin dashboard.  For live surveys, it aadds the submit toolbar at the bottom of the page.
 * 
 * Sections:
 * - Each section is contained in eaither a <details> or <div>.  The former is used for
 *     collapsible sections, the latter for non-collapsible sections.
 * - Section containers are given the class 'section' and a data-section=section ID attribute
 * - Section intros, if specified, are added as the first <div> in the container and are
 *     given the classes section and intro and a data-section=sectionID attribute
 * 
 * Question Boxes:
 * - All true questions are rendered in a question box.  This includes both grouped and
 *     and ungrouped questions.  The mental model should be that an ungrouped question
 *     is in a "group of one".  This should provide visual continuity between the two
 *     without the need for dual-maintenance css.
 *  - The question boxes are <div> elements with the class question-box
 *  - Question boxes use a grid layout to manage the display of the question elements
 *  - Ungrouped info boxes may or may not be wrapped in a question box.
 * 
 * Question Elements:
 * - There are 5 types of questions, each with their own quirks:
 *   - Common classes and attributes
 *     - All question <divs> have classes: question and the [question type]
 *     - All elements which are a form input have attribute data-question=[question ID]
 *   - Info boxes (type class=info):
 *     - Consists of a single <div>
 *     - Spans all columns and are left aligned
 *     - All questions following an info box will be indented 1 count (via indent# class)
 *   - Freetext (type class=freetext):
 *     - Consists of one or two <div> elements:
 *       - Intro [optional] (additional class=intro)
 *         - if present, all subsequent elements are indented an additional 1 count
 *       - Inputs:
 *         - contains a label and a textarea
 *         - internal layout controlled by css and responsive design
 *     - All divs span all columns
 *   - Boolean (type class=bool):
 *     - Contains a number of <div> elements in the following order
 *       - Intro [optional] (additional class=intro)
 *         - if present, all subsequent elements are indented an additional 1 count
 *         - spans all columns
 *       - Question (additional class=question)
 *         - contains label and checkbox
 *         - content layout controlled by layout class (left or right)
 *         - spans column 1 only
 *       - Qualifier (additional class=qualifier)
 *         - contains label and input box
 *         - always indented 1 additional count
 *         - spans all columns
 *   - Select (single or multi) (class=select and multi or single)
 *     - Contains a number of <div> elements in the following order
 *       - Intro [optional] (class=intro)
 *         - if present, all subsequent elements are indented an additional 1 count
 *         - spans all columns
 *       - Label (class=label)
 *         - always in column 1
 *       - Options (class=options)
 *         - contains one or more option <divs>
 *         - content layout controlled by layout class (lcol, rcol, or row)
 *         - column 1 or 2 depending on responsive design
 *       - Qualifier (additional class=qualifier)
 *         - contains label and input box
 *         - always indented 1 additional count
 *         - spans all columns
 *     
 * Feedback (optional):
 *   - Always lies outside of question boxes
 *   - Always last element in the section container, if present
 * 
 * @package tlc\tts
 */
class RenderEngine 
{
  private $popup_icon = null;

  private $follows_info = false;
  private $content      = null;
  private $responses    = null;
  private $userid       = null;
  private $survey_id    = null;
  private $timestamps   = [];

  /**
   * Constructs the render engine for the online survey
   * @param  SurveyContent $content 
   * @param array {
   *   userid: string,
   *   survey_id: int,
   *   responses: array,
   *   timestamps: string (draft:submitted)
   * } $kwargs 
   * @return void 
   */
  function __construct(SurveyContent $content, array $kwargs)
  {
    $this->popup_icon = "<img class='popup' src='" . img_uri('icons8/info.png') . "'></img>";

    $this->follows_info = false;

    $this->content   = $content;
    $this->responses = $kwargs['responses'] ?? [];

    $this->userid     = $kwargs['userid'] ?? null;
    $this->survey_id  = $kwargs['survey_id'] ?? null;
    $this->timestamps = $kwargs['timestamps'] ?? [];
  }

  /**
   * Renders the HTML for displaying the survey in a browswer
   * @param ('preview'|'draft_updates'|'submitted'|'draft'|'new') $state
   * @return void 
   */
  public function render(string $state)
  {
    if($state === 'preview')
    {
      $action = null;
      echo "<form id='survey'>";
    } 
    else {
      $action     = app_uri();

      if(is_null($this->userid))    { internal_error("missing userid in kwargs");    }
      if(is_null($this->survey_id)) { internal_error("missing survey_id in kwargs"); }

      echo "<form id='survey' action='$action' method='post'>";
      $ui_cache_id = $_SESSION['ui-cache-id'] ?? null;

      add_hidden_input('submit',1);
      if($ui_cache_id) { add_hidden_input('ui-cache-id',$ui_cache_id); }
      add_hidden_input('ajaxuri',app_uri());
      add_hidden_input('userid',$this->userid);
      add_hidden_input('survey_id',$this->survey_id);
      add_hidden_input('timestamps', $this->timestamps);
    }

    foreach($this->content->sections() as $section) {
      $this->add_section($section);
    }

    if($action) { $this->add_submit_bar($state); }

    echo "</form>";
  }

  /**
   * Renders the HTML for the submit bar at the bottom of the survey.  There are four 
   *   recognized state values which drive the content of the bar:
   *     new:           "Save As Draft", "Submit"
   *     draft:         "Revert to Saved", "Update Draft", "Submit"
   *     draft Updates: "Revert to Saved", "Delete Draft", "Save Draft", "Submit"
   *     submitted:     "Save as Draft", "Submit"
   * 
   * Each state has its own set of associated actions:
   *     submit: Save form data as submitted, clear draft
   *     save:   Save form data as draft
   *     cancel: Save nothing, reload page
   *     delete: Delete draft, reload page
   * @param ('new'|'draft'|'draft_updates'|'submitted') $state 
   * @return void 
   */
  private function add_submit_bar(string $state)
  {
    echo "<div class='submit-bar'>";
    switch($state) {
    case 'new':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Submit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Save As Draft</button>";
      echo "</div>";
      echo "<div></div>"; // empty, but needed for alignment
      break;
    case 'draft':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Submit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Update Draft</button>";
      echo "</div><div>";
      echo "<button class='cancel' type='submit' name='action' value='cancel'>Discard Changes</button>";
      echo "</div>";
      break;
    case 'draft_updates':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Submit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Update Draft</button>";
      echo "</div><div>";
      echo "<button class='delete' type='submit' name='action' value='delete'>Delete Draft</button>";
      echo "<button class='cancel' type='submit' name='action' value='cancel'>Discard Changes</button>";
      echo "</div>";
      break;
    case 'submitted':
      echo "<div>";
      echo "<button class='submit' type='submit' name='action' value='submit'>Resubmit</button>";
      echo "<button class='save'   type='submit' name='action' value='save'  >Save As Draft</button>";
      echo "</div><div>";
      echo "<button class='cancel' type='submit' name='action' value='cancel'>Cancel</button>";
      echo "</div>";
      break;
    }
    echo "</div>";
  }

  private function add_section(array $section)
  {
    $sid         = $section['section_id'];
    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    $index = "data-section=$sid";

    if($collapsible) {
      echo "<details class='section' $index>";
      echo "<summary><span>$name</span></summary>";
      $closing_tag = "</details>";
    } else {
      echo "<div class='section' $index>";
      $closing_tag = "</div>";
    }

    if($intro) {
      echo "<div class='section intro' $index>";
      echo MarkdownParser::parse($intro);
      echo "</div>";
    }

    foreach($section['content'] as $item) 
    {
      $item_id = $item['id'];

      # reset the "follows info" flag
      $this->follows_info = false;

      # handle section level questions and groups differently
      switch($item['type']) {
        case 'question':
          $question = $this->content->question($item_id);
          $box_class = $question->box_class();
          echo "<div class='$box_class' data-qid=$item_id>";
          $this->add_question($question);
          echo "</div>";
          break;

        case 'group':
          $group = $this->content->group($item_id);
          $question_ids = $group['content'];
          $group_name = $group['name'];
          echo "<div class='question-box' data-gid=$item_id data-group='$group_name'>";
          foreach($question_ids as $question_id) {
            $question = $this->content->question($question_id);
            $this->add_question($question);
          }
          echo "</div>";
          break;
      }
    }
    
    echo $closing_tag;
  }

  private function add_question(Question $question)
  {
    switch($question->type()) {
      case QuestionType::Info:         $this->add_info($question);      break;
      case QuestionType::FreeText:     $this->add_freetext($question);  break;
      case QuestionType::Bool:         $this->add_bool($question);      break;
      case QuestionType::SelectOne:
      case QuestionType::SelectMulti:  $this->add_select($question);    break;
    }
  }

  private function add_info(InfoQuestion $question)
  {
    $id   = $question->id();
    $info = $question->info_text();
    $info = MarkdownParser::parse($info);

    echo "<div class='question info' data-qid='$id'>$info</div>";

    $this->follows_info = true;
  }

  private function add_freetext(FreetextQuestion $question)
  {
    $id         = $question->id();
    $wording    = $question->wording();
    $intro      = $question->intro();
    $has_popup  = $question->has_popup();

    $response = $this->responses[$id]['free_text'] ?? '';

    $class    = 'question freetext';
    $data_id  = "data-qid='$id'";
    $input_id = "question-freetext-$id";

    $indent = $this->follows_info ? 1 : 0;
    $indent = $this->add_intro($id,$class,$indent,$intro);

    echo "<div class='$class indent$indent' $data_id>";
    echo "<label for='$input_id' class='$class'>$wording</label>";
    if($has_popup) { $this->add_popup_trigger($id); }
    echo "<textarea id='$input_id' type='text' name='$input_id' placeholder='[optional]'>$response</textarea>";
    if($has_popup) { $this->add_popup($id,$question->popup_text()); }
    
    echo "</div>";
  }

  private function add_bool(BoolQuestion $question)
  {
    $id        = $question->id();
    $wording   = $question->wording();
    $intro     = $question->intro();
    $layout    = $question->layout_class();
    $qualifier = $question->qualifier();
    $has_popup = $question->has_popup();

    $responses = $this->responses[$id] ?? [];
    $selected  = $responses['selected'] ?? '';
    $checked   = $selected ? 'checked' : '';

    $class = 'question bool';
    $data_id = "data-qid='$id'";
    $input_id = "question-bool-$id";

    $indent = $this->follows_info ? 1 : 0;

    $indent = $this->add_intro($id,$class,$indent,$intro);

    echo "<div class='$class indent$indent $layout' $data_id>";
    echo "<input id='$input_id' type='checkbox' name='$input_id' value='1' $checked>";
    echo "<label for='$input_id' class='$class'>$wording</label>";
    echo "</div>";
    if($has_popup) { $this->add_popup_trigger($id); }
    $this->add_qualifier($id, $class, $indent, $qualifier, $responses['qualifier'] ?? '');
    if($has_popup) { $this->add_popup($id,$question->popup_text()); }
  }

  private function add_select(SelectQuestion $question)
  {
    $multi = ($question->type() === QuestionType::SelectMulti);
    
    $id        = $question->id();
    $wording   = $question->wording();
    $intro     = $question->intro();
    $layout    = $question->layout_class();
    $qualifier = $question->qualifier();
    $options   = $question->options();
    $has_other = $question->has_other();
    $other     = $question->other_prompt();
    $has_popup = $question->has_popup();
    
    if(!$options) {
      log_warning("Failed to render select question $id ($wording) as there were no options provided");
      return;
    }

    if($multi) {
      $type = 'checkbox';
      $class = 'question select multi';
      $name = "question-multi-$id";
    } else {
      $type = 'radio';
      $class = 'question select one';
      $name = "question-select-$id";
    }
    $data_id = "data-qid='$id'";

    $response = $responses[$id] ?? null;
    $selected    = $response['selected'] ?? [];
    $other_value = $response['other'] ?? '';

    $indent = $this->follows_info ? 1 : 0;

    $indent = $this->add_intro($id,$class,$indent,$intro);

    echo "<div class='$class wording indent$indent'>$wording</div>";
    if($has_popup) { $this->add_popup_trigger($id,'narrow'); }
    echo "<div class='$class options $layout'>";
    foreach($options as $option_id) {
      $input_id = "$name-$option_id";
      $input_name = $multi ? $input_id : $name;
      $option_str = $this->content->option_text($option_id);
      if(!$option_str) { 
        internal_error("Question $id is associated with undefined option $option_id");
      }
      $checked = in_array($option_id,$selected) ? 'checked' : '';
      echo "<div class='option'>";
      echo "<input id='$input_id' type='$type' name='$input_name' value='$option_id' $checked>";
      echo "<label for='$input_id'>$option_str</label>";
      echo "</div>";
    }
    if($has_other) {
      $input_id = "$name-has-other";
      $input_name = $multi ? $input_id : $name;
      $value      = $multi ? 1 : 0;
      $other_id = "$name-other";
      $checked = in_array(0,$selected) ? 'checked' : '';
      echo "<div class='option'>";
      echo "<input id='$input_id' type='$type' class='has-other' name='$input_name' value='$value' $checked>";
      echo "<textarea id='$other_id' class='other' name='$other_id' rows='1' placeholder='$other'>$other_value</textarea>";
      echo "</div>";
    }
    echo "</div>"; // option-wrapper
    if($has_popup) { $this->add_popup_trigger($id,'nominal'); }
    $this->add_qualifier($id, $class, $indent, $qualifier, $response['qualifier'] ?? '');
    if($has_popup) {$this->add_popup($id, $question->popup_text()); }
  }

  /**
   * Adds intro box to a question (if specified) and increases indent
   * @param int $id
   * @param string $class 
   * @param int $indent 
   * @param string $intro 
   * @return int new indent level
   */
  protected function add_intro(int $id, string $class, int $indent, string $intro) : int
  {
    if($intro) {
      if(possibleMarkdown($intro)) {
        $intro = MarkdownParser::parse($intro);
      }
      echo "<div class='$class intro indent$indent'>$intro</div>";
      $indent += 1;
    }
    return $indent;
  }

  protected function add_qualifier(int $id, string $class, int $indent, string $qualifier, string $response) : void
  {
    if($qualifier) {
      $qualifier_id = "question-qualifier-$id";
      echo "<div class='$class qualifier indent$indent'>";
      echo "<label for='$qualifier_id' class='qualifier'>$qualifier</label>";
      echo "<textarea id='$qualifier_id' class='qualifier' type='text' name='$qualifier_id' placeholder='[optional]' rows='1'>$response</textarea>";
      echo "</div>";
    }
  }

  protected function add_popup(int $id, string $popup_text) : void 
  {
    $hint_id  = "hint-$id";
    $hintlock_id  = "hint-lock-$id";
    if (possibleMarkdown($popup_text)) {
      $popup_text = MarkdownParser::parse($popup_text);
    }
    echo "<input id='$hintlock_id' type='checkbox' class='hint-toggle' hidden>";
    echo "<div id='$hint_id' class='question-hint'>$popup_text</div>";
  }

  /**
   * Adds the trigger "button" for toggling the popup info on/off
   * @param int $id 
   * @param ''|'nominal'|'narrow' $column_class 
   * @return void 
   */
  protected function add_popup_trigger(int $id, string $column_class='') : void
  {
    $hintlock_id  = "hint-lock-$id";
    $icon = $this->popup_icon;
    echo "<label for='$hintlock_id' class='hint-toggle $column_class' data-question-id=$id>$icon</label>";
  }
};


/**
 * Renders the HTML for displaying the survey in a browswer
 * @param ('preview'|'draft_updates'|'submitted'|'draft'|'new') $state
 * @param  SurveyContent $content 
 * @param array {
 *   userid: string,
 *   survey_id: int,
 *   responses: array,
 *   timestamps: string (draft:submitted)
 * } $kwargs 
 * @return void 
 */
function render_survey(string $state, SurveyContent $content, $kwargs=[])
{
  $re = new RenderEngine($content,$kwargs);
  $re->render($state);
}


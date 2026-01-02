<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

log_dev("-------------- Start of Print Render --------------");

handle_warnings();

require_once(app_file('include/logger.php'));
require_once(app_file('survey/markdown.php'));

function debug_html($msg,$nl=false)
{
  $level = $_GET['debug_html'] ?? 0;
  if($level>1) {
    if($nl) { echo "\n"; }
    echo "<!--$msg-->\n";
  }
}


function render_printable($tcpdf,$content)
{
  $re = new PrintRenderEngine($tcpdf);
  $re->render($content);
}

function render_header($title)
{
  $logo_file = app_logo();
  $logo_uri  = $logo_file ? img_uri($logo_file) : '';

  echo "<table class='ttt-header'><tbody><tr>";
  if($logo_uri) { echo "<td class='logo'><img class='ttt-logo' src='$logo_uri'></td>"; }
  echo "<td class='title'><span class='ttt-title'>$title</span></td>";
  echo "</tr></tbody></table>";
}

function render_footer()
{
  echo "<table class='ttt-footer page-{PAGENO}'><tbody><tr>";
  echo "<td>page {PAGENO} of {nbpg}</td>";
  echo "</tr></tbody></table>";
}

class PrintRenderEngine 
{
  // Creating a PDF using the tcpdf package means that we want to layout the html
  //  content using tables.  To facility keeping content together to the best extent
  //  possible, all tables are given the attribute page-break-inside:avoid. There are
  //  three top level table types:
  //    - section label : table name (div) -- preceded by <pagebreak> after first section
  //    - question box  : container (table) for a single question
  //    - group box     : container (table) for a group of questions
  //
  // Each question box can contains a single question. Each question is contained in a 
  //   table with 1, 2, or 3 rows based on question type + optional question attributes.
  //
  //   info: 
  //     1: single cell with the info text
  //   freetext: 
  //     1: (optional) additional information about the question
  //     2: single cell with the freetext label
  //     3: single "textarea" cell
  //   bool:
  //     1: (optional) additional information about the question
  //     2: two columns: wording + checkbox (order is determined by left/right layout)
  //     3: (optional) single cell containing qualifier table (see below)
  //   select: (multi or single)
  //     1: (optional) additional information about the question
  //     2: two columns: wording + options (multiple options all in same cell)
  //     3: (optional) single cell containing qualifier table (see below)
  //
  // Each group box can contain 1 or more question boxes, but note that the only question
  //   types that support grouping are info boxes and single or multi select. Each info
  //   box is contained in its own single row, single column table.  Each contiguous 
  //   block of select questions is contained in a common table where each question
  //   can span 1, 2, or 3 rows (as above).
  //
  // Within each section and within each group box, all content is left justified.
  //   The initial indent level for each content element is 0 until an info box 
  //   is encountered.  The info box itself will have an indent level of 0, but all
  //   subsequent content will have an indent level of 1.

  private $tcpdf = null;

  private $first_page   = null; // used to determine page breaks
  private $box_indent   = null; // indent level of question box within a section

  private $select_label_font  = 'quicksand';
  private $select_label_style = 'normal';
  private $select_label_size  = 10;

  public function __construct($tcpdf) 
  {
    $this->tcpdf = $tcpdf;
  }

  public function render($content)
  {
    $sections = $content['sections'];
    usort($sections, fn($a,$b) => $a['sequence'] <=> $b['sequence']);

    $this->first_page = true; // to avoid pagebreak before first section

    foreach($sections as $section) {
      // reset all flags for the new section
      $this->box_indent = 0;
      $this->add_section($section,$content);
    }
  }

  private function add_section($section,$content)
  {
    $sid         = $section['section_id'];
    $name        = $section['name'];
    $collapsible = $section['collapsible'] ?? true;
    $intro       = $section['intro'] ?? '';

    debug_html("add_section $sid",true);

    // add pagebreak at the top of all but the first section
    if($this->first_page) { $this->first_page = false; }
    else                  { echo "<pagebreak/>"; }

    // only show the section name/label if the section is collapsible
    //   this is true of the online survey as well
    if($collapsible) { 
      self::add_single_cell_table('section label',$name);
    }

    // add the section introduction info... if defined
    if($intro) {
      self::add_single_cell_table('section intro', MarkdownParser::parse($intro,false));
    }

    // add the questions associated with the current section
    $this->add_questions($sid,$content);

    // add the section feedback box... if defined
    $feedback_label = $section['feedback'] ?? '';
    if( $feedback_label )
    {
      echo "<table class='section feedback'><tbody>";
      self::add_single_cell_row('label',$feedback_label);
      self::add_single_cell_row('textarea');
      echo "</tbody></table>";
    }
  }

  private function add_questions($sid,$content)
  {
    // find the questions to add to this section
    $questions = array_values(array_filter(
      $content['questions'],
      fn($q) => (
        (($q['section'] ?? null) === $sid) &&  // question must be associated with this section
        array_key_exists('sequence', $q)       // question must have a sequence index
      )
    ));

    // and sort them by sequence index
    usort($questions, function($a,$b) {
      return $a['sequence'] <=> $b['sequence'];
    });

    // determine which questions can be grouped
    $groups = self::group_questions($questions);

    // add the gruouped questions to the printable survey form
    foreach($groups as $group) 
    {
      if( count($group) === 1 ) {
        $this->add_question_box($group[0],$content);
      } else {
        $this->add_group_box($group,$content);
      }
    }
  }

  private function add_question_box($question,$content)
  {
    debug_html("start question box");
    // create a one column table
    $type   = strtolower($question['type']);
    $indent = $this->box_indent;

    $class = str_replace('_',' ',$type);

    echo "<table class='$class box indent-$indent'><tbody>";
    switch( $type ) {
      case 'info':         
        self::add_info($question);
        $this->box_indent = 1;     
        break;
      case 'freetext':     
        self::add_freetext($question); 
        break;
      case 'bool':         
        self::add_bool($question);     
        break;
      case 'select_one':
      case 'select_multi': 
        self::add_select($question,$content);  
        break;
    }
    echo "</tbody></table>";
  }

  private function add_group_box($questions,$content)
  {
    debug_html("start group box");

    $indent = $this->box_indent;
    echo "<table class='group box indent-$indent'><tbody>";

    // create a two column table
    // 1: select label
    // 2: select options
    // info, intro, and qualifier will spand both columns

    // partition group questions at info lines
    $grids = $this->question_grids($questions);

    foreach($grids as $grid) 
    {
      // determine the required width for the first column
      $w = $grid['w'];
      if($grid['indent']) { $w += 6; } // 6mm ~ 0.25"

      // render the grid
      echo "<tr><td><table class='group grid'>";
      echo "<colgroup><col width='{$w}mm'/><col/></colgroup>";
      echo "<tbody>";
      if($grid['info']) {
        self::add_single_cell_row("info grouped", MarkdownParser::parse($info,false) );
      }
      foreach($grid['questions'] as $question)
      {
        self::add_select($question,$content);
      }
      echo "</tbody></table></td></tr>";
    }
    echo "</tbody></table>";
  }

  private static function add_info($question)
  {
    $info    = $question['info'] ?? '';
    $grouped = strtolower($question['grouped'] ?? 'no');

    // info should never be empty... but let's handle this gracefully
    if(!$info) { 
      log_warning("Failed to render info text $id as there was no info provided");
      return; 
    }
    debug_html("add_info: $grouped");

    self::add_single_cell_row("info grouped-$grouped", MarkdownParser::parse($info,false) );
  }

  private function add_freetext($question)
  {
    // freetext is not groupable... therefore no need to account for 
    //   multiple columns or group indentation

    debug_html("add_freetext");
    $label  = $question['wording'];

    // label should never be empty... but let's handle this gracefully
    if(!$label) { 
      log_warning("Failed to render freetext question $id as there was no label provided");
      return; 
    }

    $indent = self::add_intro($question) ? 1 : 0;
    self::add_single_cell_row('label',$label,indent:$indent);
    self::add_single_cell_row('textarea',indent:$indent);
  }

  private static function add_bool($question)
  {
    // bool is not groupable... therefore no need to account for 
    //   multiple columns or group indentation

    debug_html("add_bool");
    $wording = $question['wording'] ?? '';
    $intro   = $question['intro'] ?? '';
    $layout  = strtolower($question['layout'] ?? 'left');

    // wording should never be empty... but let's handle this gracefully
    if(!$wording) { 
      log_warning("Failed to render bool question $id as there was no wording provided");
      return; 
    }

    $checkbox_img = img_uri('icons8/empty_checkbox.png');
    $checkbox = "<img class='checkbox-icon' src='$checkbox_img'>";
    $checkbox = "<td class='icon'>$checkbox</td>";

    $wording = "<td class='wording'>$wording</td>";

    if($layout === 'left') { $checkbox = $checkbox . $wording; }
    else                   { $checkbox = $wording . $checkbox; }

    $checkbox = "<table class='checkbox'><tbody><tr>$checkbox</tr></tbody></table>";

    $indent = self::add_intro($question) ? 1 : 0;

    // free text cannot be grouped, so only a single column in the table
    self::add_single_cell_row('checkbox',$checkbox,indent:$indent);
    self::add_qualifier($question,indent:$indent);
  }

  private static function add_select($question,$content)
  {
    $multi   = $question['type'] === 'select_multi';

    $id      = $question['id'];
    $wording = $question['wording'] ?? '';
    $intro   = trim($question['intro'] ?? '');
    $layout  = strtolower($question['layout'] ?? 'row');
    $options = $question['options'] ?? [];
    $other   = ($question['other_flag'] ?? false) ? 'other' : '';

    debug_html("add_select $layout");

    // wording should never be empty... but let's handle this gracefully
    if(!$wording) { 
      log_warning("Failed to render select question $id as there was no wording provided");
      return; 
    }
    if(!$options) {
      log_warning("Failed to render select question $id ($wording) as there were no options provided");
      return;
    }

    if($multi) {
      $checkbox_img = img_uri('icons8/empty_checkbox.png');
    } else {
      $checkbox_img = img_uri('icons8/empty_radiobutton.png');
    }
    $checkbox = "<img class='checkbox-icon' src='$checkbox_img'>";
    $checkbox = "<td class='icon'>$checkbox</td>";

    $indent = self::add_intro($question,ncol:2) ? 1 : 0;

    echo "<tr>";
    echo "<td class='wording indent-$indent'>$wording</td>";
    echo "<td class='options'><table class='options'><tbody>";
    if($layout === 'row') { echo "<tr>"; }
    foreach($options as $option) 
    {
      $option_str = $content['options'][$option];
      $label = "<td class='label'>$option_str</td>";

      switch($layout) {
      case 'row':  $option = "$checkbox$label"; break;
      case 'lcol': $option = "$checkbox$label"; break;
      case 'rcol': $option = "$label$checkbox"; break;
      }
      $option = "<table class='option'><tbody><tr>$option</tr></tbody></table>";

      switch($layout) {
      case 'row': echo "<td>$option</td>";          break;
      default   : echo "<tr><td>$option</td></tr>"; break;
      }
    }

    echo "</tbody></table></td></tr>";

    self::add_qualifier($question,$indent,2);
  }

  // support functions
  private static function add_single_cell_table($class,$content='',$indent=0)
  {
    debug_html("add_single_cell_table class=$class");
    echo "<table class='$class indent-$indent'><tbody>";
    echo "<tr><td>$content</td></tr>";
    echo "</tbody></table>";
  }

  private static function add_single_cell_row($class,$content='',$indent=0,$ncol=1)
  {
    debug_html("add_single_cell_row class=$class");
    echo "<tr><td class='$class indent-$indent' colspan='$ncol'>$content</td></tr>";
  }

  private static function add_intro($question,$indent=0,$ncol=1)
  {
    debug_html("add_intro(...,$ncol)");
    $intro = $question['intro'] ?? '';
    if($intro) {
      self::add_single_cell_row('intro', MarkdownParser::parse($intro,false), indent:$indent, ncol:$ncol);
      return true;
    } else {
      return false;
    }
  }

  private static function add_qualifier($question,$indent=0,$ncol=1)
  {
    $qualifier = $question['qualifier'] ?? '';
    if($qualifier) {
      $qualifier = "$qualifier <span style='font-size:20pt; color:red;'>X</span>";
      self::add_single_cell_row('qualifier label',$qualifier,indent:$indent,ncol:$ncol);
      $attr = "width='100%' height='100%' cellpadding='0' cellspacing='0'";
      $textarea = "<table $attr><tbody><tr><td class='boxed'></td></tr></tbody></table>";
      self::add_single_cell_row('qualifier textarea',$textarea,indent:$indent,ncol:$ncol);
      return true;
    } else {
      return false;
    }
  }

  private static function group_questions($questions)
  {
    //   - must be an info box, single select, or multi select
    //   - must be identified as groupable
    //   - must preceed or follow another info box or select that is groupable
    $groups = [];
    $prev_can_group = false;
    foreach($questions as $question) {
      $cur_can_group = false;
      $group_with_prev = false;

      $type    = strtolower($question['type']);
      $grouped = strtolower($question['grouped'] ?? 'no');

      switch($type) 
      {
        case 'info':
          switch($grouped) 
          {
            case 'boxed': $cur_can_group = true;  $group_with_prev = false; break;
            case 'yes':   $cur_can_group = true;  $group_with_prev = true;  break;
            default:      $cur_can_group = false; $group_with_prev = false; break;
          }
          break;
        case 'select_one':
        case 'select_multi':
          switch($grouped) 
          {
            case 'yes':   $cur_can_group = true;  $group_with_prev = true;  break;
            default:      $cur_can_group = false; $group_with_prev = false; break;
          }
          break;
        default:          $cur_can_group = false; $group_with_prev = false; break;
      }
      if($prev_can_group && $group_with_prev) {
        $groups[count($groups)-1][] = $question;
      } else {
        $groups[] = [$question];
      }
      $prev_can_group = $cur_can_group;
    }

    return $groups;
  }

  private function question_grids($questions)
  {
    $grids = [];
    $grid  = null;
    foreach($questions as $question) 
    {
      $type    = strtolower($question['type']);
      $wording = $question['wording'] ?? '';
      if( $type === 'info' ) { 
        if($grid) { 
          $grids[]=$grid; 
        }
        $grid=['w'=>0, 'indent'=>true, 'info'=>$wording, 'questions'=>[]];
      } 
      else 
      {
        if(!$grid) {
          $grid=['w'=>0, 'indent'=>false, 'info'=>null, 'questions'=>[]];
        }
        $grid['questions'][] = $question;
        $grid['w'] = max( $grid['w'],
          $this->tcpdf->GetStringWidth(
            $wording, 
            $this->select_label_font,
            $this->select_label_style,
            $this->select_label_size,
          )
        );
      }
    }
    if($grid) { $grids[]=$grid; }

    return $grids;
  }
}

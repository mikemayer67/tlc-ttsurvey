<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { error_log("Invalid entry attempt: ".__FILE__); die(); }
echo <<<HTML
<div id='editor-frame'>
  <div class='content-header'>Section/Question Editor</div>

  <!--Section Editor-->
  <div class='grid section editor'>
    Section Editor
  </div>

  <!--Section Viewer-->
  <div class='grid section viewer'>
    <span class='name label'>Name:</span>
    <div class='name value'>
      <span class='value'></span>
      <span class='note'>(not shown in survey)</span>
    </div>
    <span class='description label'>Description:</span>
    <span class='description value'></span>
    <span class='feedback label'>Feedback:</span>
    <span class='feedback value'></span>
  </div>

  <!--Question Editor-->
  <div class='grid question editor'>
    Question Editor
  </div>

  <!--Question Viewer-->
  <div class='grid question viewer'>
    <span class='type label'>Type:</span>
    <div class='type value'>
      <span class='value'></span>
      <span class='note'></span>
    </div>

    <span class='wording label'>Wording:</span>
    <span class='wording value'></span>

    <span class='description label'>Description:</span>
    <span class='description value'></span>

    <span class='primary options label'>Primary Options:</span>
    <div class='primary options value'></div>

    <span class='secondary options label'>Secondary Options:</span>
    <div class='secondary options value'></div>

    <span class='other options label'>Prompt for Other:</span>
    <span class='other options value'></span>

    <span class='qualifier label'>Qualifier:</span>
    <span class='qualifier value'></span>

    <span class='info label'>Info:</span>
    <span class='info value'></span>
  </div>
</div>

HTML;

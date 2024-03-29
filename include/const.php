<?php
namespace TLC\TTSurvey;

if( ! defined('WPINC') ) { die; }

const POST_CATEGORY_NAME = 'Time & Talent Survey';
const POST_CATEGORY_SLUG = 'tlc-ttsurvey';

const OPTIONS_NONCE      = 'tlc-ttsurvey-settings';
const DATA_NONCE         = 'tlc-ttsurve-data';
const SETTINGS_PAGE_SLUG = 'tlc-ttsurvey-settings';
const LOGIN_FORM_NONCE   = 'tlc-ttsurvey-login';
const SURVEY_FORM_NONCE  = 'tlc-ttsurvey-survey-form';
const USER_PROFILE_NONCE = 'tlc-ttsurvey-user-profile';

const PLUGIN_LOG_FILE    = 'plugin.log';

const LOGGER_DEV = "DEV";
const LOGGER_INFO = "INFO";
const LOGGER_WARN = "WARNING";
const LOGGER_ERR = "ERROR";
const LOGGER_ = array(
  "DEV" => "Development",
  "INFO" => "Information",
  "WARNING" => "Warnings/Errors",
  "ERROR" => "Errors only",
);

const POST_UI_NONE = 'NONE';
const POST_UI_POSTS = 'POSTS';
const POST_UI_TOOLS = 'TOOLS';
const POST_UI_ = array(
  'NONE' => "Disabled",
  'POSTS' => "Posts menu",
  'TOOLS' => "Tools menu",
);

const SURVEY_IS_DRAFT = 'draft';
const SURVEY_IS_ACTIVE = 'active';
const SURVEY_IS_CLOSED = 'closed';
const SURVEY_POST_TYPE = 'tlc-ttsurvey-form';
const SURVEY_STATUS_VALUES = array('draft','active','closed');

const SENDMAIL_TEMPLATES = array(
  'welcome' => array(
    'label' => 'Welcome',
    'when' => 'a user registers for the survey',
    'demo_data' => array(
      'email' => 't.smith@t3mail.net',
      'userid' => 'tsmith13',
      'fullname' => 'Thomas Smith',
    ),
  ),
  'recovery' => array(
    'label' => 'Login Recovery',
    'when' => 'a user requests help logging in',
    'demo_data' => array(
      'keys' => array(
        'hiskey' => array('fullname'=>'Thomas Smith', 'userid'=>'tsmith13'),
        'herkey' => array('fullname'=>'Theresa Smith', 'userid'=>'thsmith28'),
      ),
    ),
  ),
  'profile' => array(
    'label' => 'Updated Profile',
    'when' => 'a user updates their profile',
    'demo_data' => array(
      'userid' => 'tsmith13',
      'changed' => 'email address',
      'from' => 't.smith@t3mail.net',
      'to' => 'theresa.smith@3mail.net',
    ),
  ),
);

const LOGIN_RECOVERY_TIMEOUT = 900;

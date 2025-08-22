<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

require_once(app_file('vendor/autoload.php'));
use League\CommonMark\CommonMarkConverter;
use HTMLPurifier;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_ErrorCollector;

require_once(app_file('include/logger.php'));

handle_warnings();
ob_start();

log_dev("Validate Markdown POST: ".print_r($_POST,true));
$markdown   = $_POST['markdown'];

// not yet implementing allowing links, but just to set the hooks
// if/when this capability is added
$allow_link = $_POST['allow_links'] ?? false;

$converter = new CommonMarkConverter([ 'html_input' => 'allow' ]);
$raw_html = $converter->convertToHtml($markdown);
log_dev("raw_html: ".$raw_html);

$config = HTMLPurifier_Config::createDefault();
log_dev('check');
$config->set('Cache.DefinitionImpl',null);
log_dev('check');
$config->set('HTML.Allowed','b,strong,em,i,p,ul,ol,li'); # consider adding a[href]
$config->set('Core.CollectErrors',true);

log_dev('check');
$context = new HTMLPurifier_Context();
log_dev('check');
$errors = new HTMLPurifier_ErrorCollector($config);
log_dev('check');
$context->register('ErrorCollector',$errors);
log_dev('check');

$purifier = new HTMLPurifier($config);
log_dev('check');
$clean_html = $purifier->purify($raw_html,$context);
log_dev("clean_html: ".$clean_html);

$findings = [];
$tmp = $errors->getRaw();
log_dev("errors: ".print_r($tmp,true));
foreach ($tmp as $error) {
  $findings[] = '- ' . $error[0] . ' (' . $error[1] .')';
}
log_dev("findings: ".print_r($findings,true));

ob_end_clean();

if($findings) {
  $response = ['success'=>false, 'findings'=>$findings];
}
else {
  $response = ['success'=>true];
}

echo json_encode($response);
die();

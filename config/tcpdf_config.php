<?php
namespace tlc\tts;

if (!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: " . __FILE__); die(); }

// This file provides the customization of the built-in TCPDF constants

// TCPDF defines a number of constants in its two config files:
//   vendor/tecnickcom/tcpdf/config/tcpdf_config.php
//   vendor/technickcom/tcpdf/tcpdf_autoconfig.php
// The first of these defines constants using the form:
//    define('CONSTANT_NAME', [value])
// The latter defines constants that have not already be defined:
//    if(!defined('CONSTANT_NAME')) { define ('CONSTANT_NAME', [value]) }
// 
// At first glance, this would appear to mean that any constant defined
//   in config would be immutable from the point of view of autoconfig,
//   and more importantly to any use of the TCPDF package.
// But... once you dig into the autoconfig code, you find that the config
//   file is loaded by autoconfig UNLESS the constant K_TCPDF_EXTERNAL_CONFIG
//   is set to true.  In this case, the config file that ships with TCPDF is 
//   ignored.  This allows sers of the package to pre-override the autoconfig
//   constants before loading TCPDF via autoload.
// 
// I recommend looking through the two config files that ship with TCPDF
//   to find the list of all such constants.  Unfortunately they aren't 
//   all very well documented and a code crawl might also be necessary
//   to understand what they're used for.

// MUST be defined before TCPDF loads 
if(!defined('K_TCPDF_EXTERNAL_CONFIG')) {
  define('K_TCPDF_EXTERNAL_CONFIG', true);

  // Constants defined for convenience of anyone who thinks in inches rather than millimeters.

  define('K_INCH', 25.4); // mm
  define('K_HALF_INCH', 0.5 * K_INCH);
  define('K_QUARTER_INCH', 0.25 * K_INCH);
  define('K_EIGHTH_INCH', 0.125 * K_INCH);

  // App overrides to the autoconfig constants defined in TCPDF.
  //   Configure eveything based on 8.5x11 paper size

  define('PDF_PAGE_FORMAT','LETTER');

  define('PDF_MARGIN_HEADER', K_QUARTER_INCH);
  define('PDF_MARGIN_FOOTER', K_QUARTER_INCH);
  define('PDF_MARGIN_TOP', K_INCH);
  define('PDF_MARGIN_RIGHT', K_HALF_INCH);
  define('PDF_MARGIN_BOTTOM', 3*K_QUARTER_INCH);
  define('PDF_MARGIN_LEFT', K_HALF_INCH);

  define('K_PATH_FONTS', app_file('fonts/tcpdf/'));
  define('PDF_FONT_NAME_MAIN', 'quicksand'); // sans-serif font used in online survey
  define('K_MONOSPACE_FONT','courier');
  define('K_SERIF_FONT','notoserifdisplay');
  define('K_SANS_SERIF_FONT','plusjakartasans');

  define('K_PATH_IMAGES', app_file('img/'));
}

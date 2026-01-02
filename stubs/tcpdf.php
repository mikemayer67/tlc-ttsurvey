<?php

namespace tecnickcom\tcpdf;

if (false) {
    class TCPDF
    {
        public function __construct(
            string $orientation = 'P',
            string $unit = 'mm',
            string $format = 'A4',
            bool $unicode = true,
            string $encoding = 'UTF-8',
            bool $diskcache = false
        ) {}
        public function __destruct() {}
        public function setPageUnit($unit) {}
        public function setPageOrientation($orientation, $autopagebreak = null, $bottommargin = null) {}
        public function setSpacesRE($re = '/[^\S\xa0]/') {}
        public function setRTL($enable, $resetx = true) {}
        public function getRTL() {}
        public function setTempRTL($mode) {}
        public function isRTLTextDir() {}
        public function setLastH($h) {}
        public function getCellHeight($fontsize, $padding = TRUE) {}
        public function resetLastH() {}
        public function getLastH() {}
        public function setImageScale($scale) {}
        public function getImageScale() {}
        public function getPageDimensions($pagenum = null) {}
        public function getPageWidth($pagenum = null) {}
        public function getPageHeight($pagenum = null) {}
        public function getBreakMargin($pagenum = null) {}
        public function getScaleFactor() {}
        public function setMargins($left, $top, $right = null, $keepmargins = false) {}
        public function setLeftMargin($margin) {}
        public function setTopMargin($margin) {}
        public function setRightMargin($margin) {}
        public function setCellPadding($pad) {}
        public function setCellPaddings($left = null, $top = null, $right = null, $bottom = null) {}
        public function getCellPaddings() {}
        public function setCellMargins($left = null, $top = null, $right = null, $bottom = null) {}
        public function getCellMargins() {}
        public function setAutoPageBreak($auto, $margin = 0) {}
        public function getAutoPageBreak() {}
        public function setDisplayMode($zoom, $layout = 'SinglePage', $mode = 'UseNone') {}
        public function setCompression($compress = true) {}
        public function setSRGBmode($mode = false) {}
        public function setDocInfoUnicode($unicode = true) {}
        public function setTitle($title) {}
        public function setSubject($subject) {}
        public function setAuthor($author) {}
        public function setKeywords($keywords) {}
        public function setCreator($creator) {}
        public function setAllowLocalFiles($allowLocalFiles) {}
        public function Error($msg) {}
        public function Open() {}
        public function Close() {}
        public function setPage($pnum, $resetmargins = false) {}
        public function lastPage($resetmargins = false) {}
        public function getPage() {}
        public function getNumPages() {}
        public function addTOCPage($orientation = '', $format = '', $keepmargins = false) {}
        public function endTOCPage() {}
        public function AddPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false) {}
        public function endPage($tocpage = false) {}
        public function startPage($orientation = '', $format = '', $tocpage = false) {}
        public function setPageMark() {}
        public function setHeaderData($ln = '', $lw = 0, $ht = '', $hs = '', $tc = array(0, 0, 0), $lc = array(0, 0, 0)) {}
        public function setFooterData($tc = array(0, 0, 0), $lc = array(0, 0, 0)) {}
        public function getHeaderData() {}
        public function setHeaderMargin($hm = 10) {}
        public function getHeaderMargin() {}
        public function setFooterMargin($fm = 10) {}
        public function getFooterMargin() {}
        public function setPrintHeader($val = true) {}
        public function setPrintFooter($val = true) {}
        public function getImageRBX() {}
        public function getImageRBY() {}
        public function resetHeaderTemplate() {}
        public function setHeaderTemplateAutoreset($val = true) {}
        public function Header() {}
        public function Footer() {}
        public function PageNo() {}
        public function getAllSpotColors() {}
        public function AddSpotColor($name, $c, $m, $y, $k) {}
        public function setSpotColor($type, $name, $tint = 100) {}
        public function setDrawSpotColor($name, $tint = 100) {}
        public function setFillSpotColor($name, $tint = 100) {}
        public function setTextSpotColor($name, $tint = 100) {}
        public function setColorArray($type, $color, $ret = false) {}
        public function setDrawColorArray($color, $ret = false) {}
        public function setFillColorArray($color, $ret = false) {}
        public function setTextColorArray($color, $ret = false) {}
        public function setColor($type, $col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '') {}
        public function setDrawColor($col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '') {}
        public function setFillColor($col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '') {}
        public function setTextColor($col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '') {}
        public function GetStringWidth($s, $fontname = '', $fontstyle = '', $fontsize = 0, $getarray = false) {}
        public function GetArrStringWidth($sa, $fontname = '', $fontstyle = '', $fontsize = 0, $getarray = false) {}
        public function GetCharWidth($char, $notlast = true) {}
        public function getRawCharWidth($char) {}
        public function GetNumChars($s) {}
        public function AddFont($family, $style = '', $fontfile = '', $subset = 'default') {}
        public function setFont($family, $style = '', $size = null, $fontfile = '', $subset = 'default', $out = true) {}
        public function setFontSize($size, $out = true) {}
        public function getFontBBox() {}
        public function getAbsFontMeasure($s) {}
        public function getCharBBox($char) {}
        public function getFontDescent($font, $style = '', $size = 0) {}
        public function getFontAscent($font, $style = '', $size = 0) {}
        public function isCharDefined($char, $font = '', $style = '') {}
        public function replaceMissingChars($text, $font = '', $style = '', $subs = array()) {}
        public function setDefaultMonospacedFont($font) {}
        public function AddLink() {}
        public function setLink($link, $y = 0, $page = -1) {}
        public function Link($x, $y, $w, $h, $link, $spaces = 0) {}
        public function Annotation($x, $y, $w, $h, $text, $opt = array('Subtype' => 'Text'), $spaces = 0) {}
        public function EmbedFile($opt) {}
        public function EmbedFileFromString($filename, $content) {}
        public function Text($x, $y, $txt, $fstroke = 0, $fclip = false, $ffill = true, $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M', $rtloff = false) {}
        public function AcceptPageBreak() {}
        public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M') {}
        public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = null, $y = null, $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false) {}
        public function getNumLines($txt, $w = 0, $reseth = false, $autopadding = true, $cellpadding = null, $border = 0) {}
        public function getStringHeight($w, $txt, $reseth = false, $autopadding = true, $cellpadding = null, $border = 0) {}
        public function Write($h, $txt, $link = '', $fill = false, $align = '', $ln = false, $stretch = 0, $firstline = false, $firstblock = false, $maxh = 0, $wadj = 0, $margin = null) {}
        public function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = array()) {}
        public function Ln($h = null, $cell = false) {}
        public function GetX() {}
        public function GetAbsX() {}
        public function GetY() {}
        public function setX($x, $rtloff = false) {}
        public function setY($y, $resetx = true, $rtloff = false) {}
        public function setXY($x, $y, $rtloff = false) {}
        public function setAbsX($x) {}
        public function setAbsY($y) {}
        public function setAbsXY($x, $y) {}
        public function Output($name = 'doc.pdf', $dest = 'I') {}
        public function _destroy($destroyall = false, $preserve_objcopy = false) {}
        public function setExtraXMP($xmp) {}
        public function setExtraXMPRDF($xmp) {}
        public function setExtraXMPPdfaextension($xmp) {}
        public function setDocCreationTimestamp($time) {}
        public function setDocModificationTimestamp($time) {}
        public function getDocCreationTimestamp() {}
        public function getDocModificationTimestamp() {}
        public function setHeaderFont($font) {}
        public function getHeaderFont() {}
        public function setFooterFont($font) {}
        public function getFooterFont() {}
        public function setLanguageArray($language) {}
        public function getPDFData() {}
        public function addHtmlLink($url, $name, $fill = false, $firstline = false, $color = null, $style = -1, $firstblock = false) {}
        public function pixelsToUnits($px) {}
        public function unhtmlentities($text_to_convert) {}
        public function setProtection($permissions = array('print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'), $user_pass = '', $owner_pass = null, $mode = 0, $pubkeys = null) {}
        public function StartTransform() {}
        public function StopTransform() {}
        public function ScaleX($s_x, $x = '', $y = '') {}
        public function ScaleY($s_y, $x = '', $y = '') {}
        public function ScaleXY($s, $x = '', $y = '') {}
        public function Scale($s_x, $s_y, $x = null, $y = null) {}
        public function MirrorH($x = null) {}
        public function MirrorV($y = null) {}
        public function MirrorP($x = null, $y = null) {}
        public function MirrorL($angle = 0, $x = null, $y = null) {}
        public function TranslateX($t_x) {}
        public function TranslateY($t_y) {}
        public function Translate($t_x, $t_y) {}
        public function Rotate($angle, $x = null, $y = null) {}
        public function SkewX($angle_x, $x = null, $y = null) {}
        public function SkewY($angle_y, $x = null, $y = null) {}
        public function Skew($angle_x, $angle_y, $x = null, $y = null) {}
        public function setLineWidth($width) {}
        public function GetLineWidth() {}
        public function setLineStyle($style, $ret = false) {}
        public function Line($x1, $y1, $x2, $y2, $style = array()) {}
        public function Rect($x, $y, $w, $h, $style = '', $border_style = array(), $fill_color = array()) {}
        public function Curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $style = '', $line_style = array(), $fill_color = array()) {}
        public function Polycurve($x0, $y0, $segments, $style = '', $line_style = array(), $fill_color = array()) {}
        public function Ellipse($x0, $y0, $rx, $ry = 0, $angle = 0, $astart = 0, $afinish = 360, $style = '', $line_style = array(), $fill_color = array(), $nc = 2) {}
        public function Circle($x0, $y0, $r, $angstr = 0, $angend = 360, $style = '', $line_style = array(), $fill_color = array(), $nc = 2) {}
        public function PolyLine($p, $style = '', $line_style = array(), $fill_color = array()) {}
        public function Polygon($p, $style = '', $line_style = array(), $fill_color = array(), $closed = true) {}
        public function RegularPolygon($x0, $y0, $r, $ns, $angle = 0, $draw_circle = false, $style = '', $line_style = array(), $fill_color = array(), $circle_style = '', $circle_outLine_style = array(), $circle_fill_color = array()) {}
        public function StarPolygon($x0, $y0, $r, $nv, $ng, $angle = 0, $draw_circle = false, $style = '', $line_style = array(), $fill_color = array(), $circle_style = '', $circle_outLine_style = array(), $circle_fill_color = array()) {}
        public function RoundedRect($x, $y, $w, $h, $r, $round_corner = '1111', $style = '', $border_style = array(), $fill_color = array()) {}
        public function RoundedRectXY($x, $y, $w, $h, $rx, $ry, $round_corner = '1111', $style = '', $border_style = array(), $fill_color = array()) {}
        public function Arrow($x0, $y0, $x1, $y1, $head_style = 0, $arm_size = 5, $arm_angle = 15) {}
        public function setDestination($name, $y = -1, $page = '', $x = -1) {}
        public function getDestination() {}
        public function setBookmark($txt, $level = 0, $y = -1, $page = '', $style = '', $color = array(0, 0, 0), $x = -1, $link = '') {}
        public function Bookmark($txt, $level = 0, $y = -1, $page = '', $style = '', $color = array(0, 0, 0), $x = -1, $link = '') {}
        public function IncludeJS($script) {}
        public function addJavascriptObject($script, $onload = false) {}
        public function setFormDefaultProp($prop = array()) {}
        public function getFormDefaultProp() {}
        public function TextField($name, $w, $h, $prop = array(), $opt = array(), $x = null, $y = null, $js = false) {}
        public function RadioButton($name, $w, $prop = array(), $opt = array(), $onvalue = 'On', $checked = false, $x = null, $y = null, $js = false) {}
        public function ListBox($name, $w, $h, $values, $prop = array(), $opt = array(), $x = null, $y = null, $js = false) {}
        public function ComboBox($name, $w, $h, $values, $prop = array(), $opt = array(), $x = null, $y = null, $js = false) {}
        public function CheckBox($name, $w, $checked = false, $prop = array(), $opt = array(), $onvalue = 'Yes', $x = null, $y = null, $js = false) {}
        public function Button($name, $w, $h, $caption, $action, $prop = array(), $opt = array(), $x = null, $y = null, $js = false) {}
        public function setUserRights(
            $enable = true,
            $document = '/FullSave',
            $annots = '/Create/Delete/Modify/Copy/Import/Export',
            $form = '/Add/Delete/FillIn/Import/Export/SubmitStandalone/SpawnTemplate',
            $signature = '/Modify',
            $ef = '/Create/Delete/Modify/Import',
            $formex = ''
        ) {}
        public function setSignature($signing_cert = '', $private_key = '', $private_key_password = '', $extracerts = '', $cert_type = 2, $info = array(), $approval = '') {}
        public function setSignatureAppearance($x = 0, $y = 0, $w = 0, $h = 0, $page = -1, $name = '') {}
        public function addEmptySignatureAppearance($x = 0, $y = 0, $w = 0, $h = 0, $page = -1, $name = '') {}
        public function setTimeStamp($tsa_host = '', $tsa_username = '', $tsa_password = '', $tsa_cert = '') {}
        public function startPageGroup($page = null) {}
        public function setStartingPageNumber($num = 1) {}
        public function getAliasRightShift() {}
        public function getAliasNbPages() {}
        public function getAliasNumPage() {}
        public function getPageGroupAlias() {}
        public function getPageNumGroupAlias() {}
        public function getGroupPageNo() {}
        public function getGroupPageNoFormatted() {}
        public function PageNoFormatted() {}
        public function startLayer($name = '', $print = true, $view = true, $lock = true) {}
        public function endLayer() {}
        public function setVisibility($v) {}
        public function setOverprint($stroking = true, $nonstroking = null, $mode = 0) {}
        public function getOverprint() {}
        public function setAlpha($stroking = 1, $bm = 'Normal', $nonstroking = null, $ais = false) {}
        public function getAlpha() {}
        public function setJPEGQuality($quality) {}
        public function setDefaultTableColumns($cols = 4) {}
        public function setCellHeightRatio($h) {}
        public function getCellHeightRatio() {}
        public function setPDFVersion($version = '1.7') {}
        public function setViewerPreferences($preferences) {}
        public function colorRegistrationBar($x, $y, $w, $h, $transition = true, $vertical = false, $colors = 'A,R,G,B,C,M,Y,K') {}
        public function cropMark($x, $y, $w, $h, $type = 'T,R,B,L', $color = array(100, 100, 100, 100, 'All')) {}
        public function registrationMark($x, $y, $r, $double = false, $cola = array(100, 100, 100, 100, 'All'), $colb = array(0, 0, 0, 0, 'None')) {}
        public function registrationMarkCMYK($x, $y, $r) {}
        public function LinearGradient($x, $y, $w, $h, $col1 = array(), $col2 = array(), $coords = array(0, 0, 1, 0)) {}
        public function RadialGradient($x, $y, $w, $h, $col1 = array(), $col2 = array(), $coords = array(0.5, 0.5, 0.5, 0.5, 1)) {}
        public function CoonsPatchMesh($x, $y, $w, $h, $col1 = array(), $col2 = array(), $col3 = array(), $col4 = array(), $coords = array(0.00, 0.0, 0.33, 0.00, 0.67, 0.00, 1.00, 0.00, 1.00, 0.33, 1.00, 0.67, 1.00, 1.00, 0.67, 1.00, 0.33, 1.00, 0.00, 1.00, 0.00, 0.67, 0.00, 0.33), $coords_min = 0, $coords_max = 1, $antialias = false) {}
        public function Gradient($type, $coords, $stops, $background = array(), $antialias = false) {}
        public function PieSector($xc, $yc, $r, $a, $b, $style = 'FD', $cw = true, $o = 90) {}
        public function PieSectorXY($xc, $yc, $rx, $ry, $a, $b, $style = 'FD', $cw = false, $o = 0, $nc = 2) {}
        public function ImageEps($file, $x = null, $y = null, $w = 0, $h = 0, $link = '', $useBoundingBox = true, $align = '', $palign = '', $border = 0, $fitonpage = false, $fixoutvals = false) {}
        public function setBarcode($bc = '') {}
        public function getBarcode() {}
        public function write1DBarcode($code, $type, $x = null, $y = null, $w = null, $h = null, $xres = null, $style = array(), $align = '') {}
        public function write2DBarcode($code, $type, $x = null, $y = null, $w = null, $h = null, $style = array(), $align = '', $distort = false) {}
        public function getMargins() {}
        public function getOriginalMargins() {}
        public function getFontSize() {}
        public function getFontSizePt() {}
        public function getFontFamily() {}
        public function getFontStyle() {}
        public function fixHTMLCode($html, $default_css = '', $tagvs = null, $tidy_options = null) {}
        public function getCSSPadding($csspadding, $width = 0) {}
        public function getCSSMargin($cssmargin, $width = 0) {}
        public function getCSSBorderMargin($cssbspace, $width = 0) {}
        public function getHTMLFontUnits($val, $refsize = 12, $parent_size = 12, $defaultunit = 'pt') {}
        public function serializeTCPDFtag($method, $params = array()) {}
        public function writeHTMLCell($w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true) {}
        public function writeHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = '') {}
        public function setLIsymbol($symbol = '!') {}
        public function setBooklet($booklet = true, $inner = -1, $outer = -1) {}
        public function setHtmlVSpace($tagvs) {}
        public function setListIndentWidth($width) {}
        public function setOpenCell($isopen) {}
        public function setHtmlLinksStyle($color = array(0, 0, 255), $fontstyle = 'U') {}
        public function getHTMLUnitToUnits($htmlval, $refsize = 1, $defaultunit = 'px', $points = false) {}
        public function movePage($frompage, $topage) {}
        public function deletePage($page) {}
        public function copyPage($page = 0) {}
        public function addTOC($page = null, $numbersfont = '', $filler = '.', $toc_name = 'TOC', $style = '', $color = array(0, 0, 0)) {}
        public function addHTMLTOC($page = null, $toc_name = 'TOC', $templates = array(), $correct_align = true, $style = '', $color = array(0, 0, 0)) {}
        public function startTransaction() {}
        public function commitTransaction() {}
        public function rollbackTransaction($self = false) {}
        public function setEqualColumns($numcols = 0, $width = 0, $y = null) {}
        public function resetColumns() {}
        public function setColumnsArray($columns) {}
        public function selectColumn($col = null) {}
        public function getColumn() {}
        public function getNumberOfColumns() {}
        public function setTextRenderingMode($stroke = 0, $fill = true, $clip = false) {}
        public function setTextShadow($params = array('enabled' => false, 'depth_w' => 0, 'depth_h' => 0, 'color' => false, 'opacity' => 1, 'blend_mode' => 'Normal')) {}
        public function getTextShadow() {}
        public function hyphenateText($text, $patterns, $dictionary = array(), $leftmin = 1, $rightmin = 2, $charmin = 1, $charmax = 8) {}
        public function setRasterizeVectorImages($mode) {}
        public function setFontSubsetting($enable = true) {}
        public function getFontSubsetting() {}
        public function stringLeftTrim($str, $replace = '') {}
        public function stringRightTrim($str, $replace = '') {}
        public function stringTrim($str, $replace = '') {}
        public function isUnicodeFont() {}
        public function getFontFamilyName($fontfamily) {}
        public function startTemplate($w = 0, $h = 0, $group = false) {}
        public function endTemplate() {}
        public function printTemplate($id, $x = null, $y = null, $w = 0, $h = 0, $align = '', $palign = '', $fitonpage = false) {}
        public function setFontStretching($perc = 100) {}
        public function getFontStretching() {}
        public function setFontSpacing($spacing = 0) {}
        public function getFontSpacing() {}
        public function getPageRegions() {}
        public function setPageRegions($regions = array()) {}
        public function addPageRegion($region) {}
        public function removePageRegion($key) {}
        public function ImageSVG($file, $x = null, $y = null, $w = 0, $h = 0, $link = '', $align = '', $palign = '', $border = 0, $fitonpage = false) {}
    }
}

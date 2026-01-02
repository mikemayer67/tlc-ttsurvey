<?php
namespace tlc\tts;

if(!defined('APP_DIR')) { http_response_code(405); error_log("Invalid entry attempt: ".__FILE__); die(); }

// provide a hack so that VSCode's PHP Intelephense will be able to find TCPDF
if(false) {
    require_once(__DIR__ . '/../stubs/tcpdf.php');
}
require_once(app_file('vendor/autoload.php'));

use tecnickcom\tcpdf\TCPDF;

class MyPDF extends TCPDF
{
    public function __construct()
    {
        parent::__construct(
            'P',        // Portrait
            'mm',       // Units
            'LETTER',   // Page size
            true,       // Unicode
            'UTF-8',
            false
        );
        
        $this->SetMargins(20, 25, 20);
        $this->SetAutoPageBreak(true, 25);
        $this->SetFont('surveyfont-regular', '', 11);
    }
    
    public function Header(): void
    {
        $this->SetFont('surveyfont-bold', '', 12);
        $this->Cell(0, 10, 'Survey Printout', 0, 1, 'C');
        $this->Ln(5);
    }
    
    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('surveyfont-regular', '', 9);
        $this->Cell(
            0,
            10,
            sprintf('Page %s of %s', $this->getAliasNumPage(), $this->getAliasNbPages()),
            0,
            0,
            'C'
        );
    }
}

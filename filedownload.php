<?php

require_once __DIR__ . '/bootstrap.php';

use PhpOffice\PhpWord\Settings;

Settings::loadConfig();

// Set writers
$writers = array('Word2007' => 'docx');

// Turn output escaping on
Settings::setOutputEscapingEnabled(true);

$array = array(); 
$stuInfo = $_POST['stuInfo'];
$sentenceList = $_POST['sentenceList'];

$title = $stuInfo["fileName"];
$author = $stuInfo["stuName"];

$phpWord = new \PhpOffice\PhpWord\PhpWord();

$fontStyleName = 'rStyle';
$phpWord->addFontStyle($fontStyleName, array('bold' => true, 'italic' => true, 'size' => 16, 'allCaps' => true, 'doubleStrikethrough' => true));

$paragraphStyleName = 'pStyle';
$phpWord->addParagraphStyle($paragraphStyleName, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100));

$phpWord->addTitleStyle(1, array('bold' => true), array('spaceAfter' => 240));

// New portrait section
$section = $phpWord->addSection();

// Simple text
$section->addTitle('Welcome to PhpWord', 1);
$section->addText('Hello World!');

// Two text break
$section->addTextBreak();

// Define styles
$section->addText('I am styled by a font style definition.', $fontStyleName);
$section->addText('I am styled by a paragraph style definition.', null, $paragraphStyleName); 
   $section->addText('I am styled by both font and paragraph style.', $fontStyleName, $paragraphStyleName);

$section->addTextBreak();
foreach($sentenceList as $st)
{

$section->addText($st["content"], $fontStyleName);
}

echo write($phpWord, basename(__FILE__, '.php'), $writers);

function write($phpWord, $filename, $writers)
{
    $result = '';

    // Write documents
    foreach ($writers as $format => $extension) {
        $result .= date('H:i:s') . " Write to {$format} format";
        if (null !== $extension) {
            $targetFile = __DIR__ . "/download/{$filename}.{$extension}";
            $phpWord->save($targetFile, $format);
        } else {
            $result .= ' ... NOT DONE!';
        }
        $result .= PHP_EOL;
    }

   // $result .= getEndingNotes($writers);

    return $result;
}

?>
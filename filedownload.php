<?php

require_once __DIR__ . '/bootstrap.php';
require_once("char.php");
require_once("plagiarismModel.php");

use PhpOffice\PhpWord\Settings;
$articleColor = array("000000","ffff00","ff0000","00ff00","0000ff","ff00ff","ff8000","8000ff","c000ff");


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
$phpWord->addFontStyle($fontStyleName, array('size' => 16, 'bgColor'=>'000000'));

$paragraphStyleName = 'pStyle';
$phpWord->addParagraphStyle($paragraphStyleName, array('alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100));

$phpWord->addTitleStyle(1, array('bold' => true), array('spaceAfter' => 240));

// New portrait section
$section = $phpWord->addSection();

// Simple text
$section->addTitle($title." ".$author, 1);

// Two text break
$section->addTextBreak();

// Define styles

$phpWord->addFontStyle($fontStyleName, array('size' => 16));
$textrun = $section->addTextRun();

foreach($sentenceList as $st)
{
    $content = $st["content"];
    $plagList = $st["plagiarismList"];
    if($plagList == null || $plagList == "")
    {
        $textrun->addText($content, $fontStyleName);
    }else{
        $colorSt = new ColorSentence($content);
        foreach($plagList as $plag)
        {
           $lcsPartTmpArray =  CharUtils::mb_str_split($plag["lcsPart"]); 
           $tmpStr = $colorSt->sentence;
           $start = 0;
           $index = 0;
           foreach($lcsPartTmpArray as $lcsSingle)
           {
               while ($index < count($colorSt->stStrList)) {
                  if($lcsSingle == $colorSt->stStrList[$index])
                  {
                    $colorSt->colorArray[$index] += 1;
                    $index ++;
                    break;
                  } 
                  $index++;
               }
           }
        }
        for($i = 0;$i < count($colorSt->stStrList);$i++)
        {
            $textrun->addText($colorSt->stStrList[$i],  array('size' => 16, 'bgColor'=>$articleColor[$colorSt->colorArray[$i]]));
        }
    }
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
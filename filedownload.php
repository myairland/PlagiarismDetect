<?php

require('../../config.php');
require_once __DIR__ . '/bootstrap.php';
require_once("char.php");
require_once("plagiarismModel.php");
require_once($CFG->libdir . '/excellib.class.php');

use PhpOffice\PhpWord\Settings;
$articleColor = array("ffffff","ffff00","ff0000","00ff00","0000ff","ff00ff","ff8000","8000ff","c000ff");


Settings::loadConfig();

// Set writers
$docExtension = "docx";
$excelExtension = "xlsx";
$wordFormat = "Word2007";
$excelFormat = "Excel2007";
// Turn output escaping on
Settings::setOutputEscapingEnabled(true);

$array = array(); 
$stuList = $_POST['stuList'];
$articleList = $_POST['articleList'];

$tempFolder = date("Ymd_Hms");
$zipFolder = date("YmdHms");
mkdir(__DIR__ . "/download/{$tempFolder}");

$zip = new ZipArchive();

if ($zip->open("./download/" . $zipFolder, ZIPARCHIVE::CREATE)!==TRUE) {   
    exit('无法打开文件，或者文件创建失败');
}   
$fileIndex = 1;

try{
foreach($stuList as $stu)
{

    // $stuInfo = $_POST['stuInfo'];
    // $sentenceList = $_POST['sentenceList'];

    $stuInfo = $stu['stuInfo'];
    $sentenceList = $stu['sentenceList'];

    $title = $stuInfo["fileName"];
    $author = $stuInfo["stuName"];

    // word part
    $phpWord = new \PhpOffice\PhpWord\PhpWord();

    $fontStyleName = 'rStyle';

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

    $phpWord->addFontStyle($fontStyleName, array('size' => 12));
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
                        $colorSt->colorArray[$index] = intval($plag["articleId"]) + 1;
                        $index ++;
                        break;
                    } 
                    $index++;
                }
            }
            }
            for($i = 0;$i < count($colorSt->stStrList);$i++)
            {
                $textrun->addText($colorSt->stStrList[$i],  array('size' => 12, 'bgColor'=>$articleColor[$colorSt->colorArray[$i]]));
            }
        }
    }   
    $filename = trim($author."_").date("Ymd_Hms") . str_pad(strval($fileIndex),3,"0",STR_PAD_LEFT);

    $targetFile = __DIR__ . "/download/{$tempFolder}/{$filename}.{$docExtension}";
    $phpWord->save($targetFile, $wordFormat);

    $zip->addFile($targetFile,$filename .".". $docExtension);

    // execel part
    $workbook = new MoodleExcelWorkbook('-',$excelExtension);
    $worksheet = array();
    $worksheet = $workbook->add_worksheet('相似列表');

    //标题列

    $worksheet->set_column(0, 0, 5);
    $worksheet->write(0, 0, '序号');
    
    $worksheet->set_column(1, 1, 30);
    $worksheet->write(0, 1, '学生姓名');

    $refIndex = 0;
    foreach($articleList as $ref)
    {
        $worksheet->set_column(2 + 4 * $refIndex, 2 + 4 * $refIndex, 50);
        $worksheet->write(0, 2 + 4 * $refIndex, '学生的句子');

        $worksheet->set_column(3 + 4 * $refIndex, 3 + 4 * $refIndex, 50);
        $worksheet->write(0, 3 + 4 * $refIndex, $ref['articleName'] . '的句子');

        $worksheet->set_column(4 + 4 * $refIndex, 4 + 4 * $refIndex, 50);
        $worksheet->write(0, 4 + 4 * $refIndex, '相似的句子');

        $worksheet->set_column(5 + 4 * $refIndex, 5 + 4 * $refIndex, 10);
        $worksheet->write(0, 5 + 4 * $refIndex, '相似度');
        
        $refIndex++;
    }

    $refPos = array_fill(0,count($articleList),1);

    $excelIndex = 1;
    foreach($sentenceList as $st)
    {
        $content = $st["content"];
        $plagList = $st["plagiarismList"];
        if($plagList == null || $plagList == "")
        {
            continue;
        }else{
            foreach($plagList as $plag)
            {
                $artilceId = intval($plag["articleId"]);
                $column = 2 + $artilceId * 4; 
                $row = $refPos[$artilceId];
                $cell = $worksheet->worksheet->getCellByColumnAndRow(0,$row + 1);
                if($cell->getValue() == "")
                {
                    $worksheet->write($row,0 ,$excelIndex);
                    $worksheet->write($row,1 ,$author);
                    $excelIndex = $excelIndex + 1;
                }
                
                $worksheet->write($row, $column, $content);
                //文献的句子
                $worksheet->write($row, $column + 1, $articleList[$plag['articleId']]["sentenceList"][$plag['sentenceId']]["content"]);
        
                $worksheet->write($row, $column + 2, $plag["lcsPart"]);
        
                $worksheet->write($row, $column + 3, $plag["similarity"]);

                $refPos[$artilceId] = $refPos[$artilceId] + 1;
            }
        }
    }

    $targetFile = __DIR__ . "/download/{$tempFolder}/{$filename}.{$excelExtension}";

    $objWriter = PHPExcel_IOFactory::createWriter($workbook->objPHPExcel,"Excel2007");
    $objWriter->save($targetFile);

    $zip->addFile($targetFile,$filename .".". $excelExtension);

    $fileIndex++;
}
}catch (Exception $e) {   
    print $e->getMessage();   
    exit();   
    }   
$zip->close();//关闭   
echo  "download/{$zipFolder}";

?>
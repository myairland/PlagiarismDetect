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

try
{
    foreach($stuList as $stu)
    {
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

        $rowIndex = 0;
        foreach($articleList as $ref)
        {

            $worksheet->write($rowIndex, 0 , $ref['articleName'] . '相似的句子',$workbook->add_format(array('size'=>15, 'bold'=>1, 'underline'=>1)));
            $worksheet->set_row($rowIndex,30);
        
            $stIndex  = 1;
            $rowIndex = $rowIndex + 1;
            //标题列
            $worksheet->set_column(0, 0, 5);
            $worksheet->write($rowIndex, 0, '序号');
            
            $worksheet->set_column(1, 1 , 50);
            $worksheet->write($rowIndex, 1 , '学生的句子');

            $worksheet->set_column(2, 2, 50);
            $worksheet->write($rowIndex, 2, $ref['articleName'] . '的句子');

            $worksheet->set_column(3 , 3, 50);
            $worksheet->write($rowIndex, 3, '相似的句子');

            $worksheet->set_column(4 , 4, 10);
            $worksheet->write($rowIndex, 4, '相似度');

            $rowIndex = $rowIndex + 1;
            foreach($sentenceList as $st){

                $content = $st["content"];
                $plagList = $st["plagiarismList"];
                if($plagList == null || $plagList == ""){
                    continue;
                }else{
                    foreach($plagList as $plag){

                        if($plag["articleId"] == $ref["articleId"]){
                            $worksheet->write($rowIndex,0 ,$stIndex);

                            $worksheet->write($rowIndex, 1, $content);
                            //文献的句子
                            $worksheet->write($rowIndex, 2, $articleList[$plag['articleId']]["sentenceList"][$plag['sentenceId']]["content"]);
                    
                            $worksheet->write($rowIndex, 3, $plag["lcsPart"]);
                    
                            $worksheet->write($rowIndex, 4, $plag["similarity"]);

                            $stIndex++;
                            $rowIndex++;
                        }

                    }
                }
            }
            $rowIndex = $rowIndex + 1;

        }

        $worksheet->worksheet->setSelectedCells('A1');
        $workbook->objPHPExcel->setActiveSheetIndex(0);

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
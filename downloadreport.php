<?php
require('../../config.php');
require_once("$CFG->dirroot/mod/url/lib.php");
require_once("$CFG->dirroot/mod/url/locallib.php");
require_once($CFG->libdir . '/completionlib.php');
require_once("$CFG->dirroot/mod/url/reportmodel.php");
$type       = optional_param('type', 0, PARAM_INT);        // Course module ID
$authorId   = optional_param('authorid', 0, PARAM_INT);        // Course module ID
$contextid   = optional_param('contextid', 0, PARAM_INT);        // Course module ID
if($type == 0){
    // 单独作者下载
    $studentReportInfo = array();
    $titleRecords = $DB->get_records_sql("select titleid,titlename,author_name from {workshop_plag_report} where author_id = ? group by titleid,titlename,author_name",array("author_id"=>$authorId));
    foreach($titleRecords as $title)
    {
        $index = 0;
        $authorname = $title->author_name;
        $records = $DB->get_records("workshop_plag",array("titleid"=>$title->titleid,"author_id"=>$authorId),"plag_id");
        foreach($records as $record)
        {   
            $index++;
            if(array_key_exists($index,$studentReportInfo))
            {
                $refContent = new StudentReportRef();
                $refContent->titleId = $title->titleid;
                $refContent->sentence = $record->sentence_content;
                $refContent->refSentence = $record->ref1_content;
                $refContent->lcs = $record->ref2_content;
                $refContent->similarity = round($record->ref1_similarity,4) * 100 . "%";
                $studentReportInfo[$index]->refConent = $studentReportInfo[$index]->refConent + array($title->titleid => $refContent); 
            }else{
                $stuReport = new StudentReportInfo();
                $stuReport->author = $title->author_name;
                $stuReport->lineNo = $index;
                $refContent = new StudentReportRef();
                $refContent->titleId = $title->titleid;
                $refContent->sentence = $record->sentence_content;
                $refContent->refSentence = $record->ref1_content;
                $refContent->lcs = $record->ref2_content;
                $refContent->similarity = round($record->ref1_similarity,4) * 100 . "%";
                $stuReport->refConent=array($title->titleid => $refContent); 
                $studentReportInfo = $studentReportInfo + array($index => $stuReport);
            }                
        }
    }
    $fileContent  = "";
    $fileContent  = "序号,姓名,";
    foreach($titleRecords as $title){
        $fileContent .= "学生的句子,参考文献" . $title->titlename . "的句子,相似度,相似部分,   ";
    }

    $fileContent .= PHP_EOL;

    foreach($studentReportInfo as $id=>$line)
    {
        $fileContent .= "$line->lineNo,$line->author,";
        foreach($titleRecords as $title){
            if(array_key_exists($title->titleid,$line->refConent)){
                $fileContent .= $line->refConent[$title->titleid]->sentence. ",";
                $fileContent .= $line->refConent[$title->titleid]->refSentence. ",";
                $fileContent .= $line->refConent[$title->titleid]->similarity.",";
                $fileContent .= $line->refConent[$title->titleid]->lcs.",";
            }else{
                $fileContent .= ",,,,";
            }
        }
        $fileContent .= PHP_EOL;
    }
    
    $fs = get_file_storage();
    
    //Prepare file record object
    $fileinfo = array(
    'contextid' => $contextid, // ID of context
    'component' => 'mod_url',     // usually = table name
    'filearea' => 'work_shop_plag_report',     // usually = table name
    'itemid' => 0,               // usually = ID of row in table
    'filepath' => '/',           // any path beginning and ending in /
    'filename' => $authorname . '报告.csv'); // any filename
        
        //Create csv file containing text 
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
    $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    if ($file) {
            $file->delete();
    }

    $csvfile = $fs->create_file_from_string($fileinfo,$fileContent);
    send_file($csvfile, $authorname . '报告.csv', 0, false, null);


}else if($type ==1){
    // 全部报告下载
    $titleRecords = $DB->get_records_sql("select titleid,titlename from {workshop_plag_report}   group by titleid,titlename",null);
    $refFile = array();
    foreach($titleRecords as $title)
    {
       $refFile =  $refFile + array($title->titleid => $title->titlename); 
    }

    $fullReportInfo = array();
    $stuIdRecords = $DB->get_records_sql("select author_id from {workshop_plag_report} group by author_id",null);
    foreach($stuIdRecords as $stuId){
        $studentfullReportInfo = array();
        foreach($refFile as $key=>$value)
        {
            $index = 0;
            $records = $DB->get_records("workshop_plag_report",array("titleid"=>$key,"author_id"=>$stuId->author_id),"item_id");
            foreach($records as $record)
            {   
                $index++;
                if(array_key_exists($index,$studentfullReportInfo))
                {
                    $refContent = new FullReportRef();
                    $refContent->titleId = $key;
                    $refContent->plagCnt = $record->plg_cnt;
                    $refContent->plagPercent = round($record->plg_cent,4) * 100 . "%";
                    $studentfullReportInfo[$index]->refConent = $studentfullReportInfo[$index]->refConent + array($key => $refContent); 
                }else{
                    $stuReport = new FullReportInfo();
                    $stuReport->author = $record->author_name;
                    $stuReport->lineNo = $index;
                    $stuReport->sentenceCnt = $record->sentence_cnt;
                    $refContent = new FullReportRef();
                    $refContent->titleId = $key;
                    $refContent->plagCnt = $record->plg_cnt;
                    $refContent->plagPercent = round($record->plg_cent,4) * 100 . "%";
                    $stuReport->refConent=array($key => $refContent); 
                    $studentfullReportInfo = $studentfullReportInfo + array($index => $stuReport);
                }                
            }
        }
        $fullReportInfo = $fullReportInfo + array($stuId->author_id => $studentfullReportInfo);
    }
    $fileContent = "";
    $fileContent .= "序号,学生姓名,作业文字数量,";
    //参考文献循环 
    foreach($refFile as $key=>$value)
    {
        $fileContent .= $value."相似数量,抄袭率,";
    }

    $fileContent .= PHP_EOL;
    foreach($fullReportInfo as $stuid=>$stuInfo)
    {
        foreach($stuInfo as $id=>$line)
        {
            $fileContent .= "$line->lineNo,$line->author,$line->sentenceCnt,";
            foreach($refFile as $key=>$value)
            {
                if(array_key_exists($key,$line->refConent))
                {
                    $fileContent .= $line->refConent[$key]->plagCnt . ",";
                    $fileContent .= $line->refConent[$key]->plagPercent . ",";
                }else{
                    $fileContent .= ",,";
                }
            }
            $fileContent .= PHP_EOL;
        }
    }
    $fs = get_file_storage();
    
    //Prepare file record object
    $fileinfo = array(
    'contextid' => $contextid, // ID of context
    'component' => 'mod_url',     // usually = table name
    'filearea' => 'work_shop_plag_report',     // usually = table name
    'itemid' => 1,               // usually = ID of row in table
    'filepath' => '/',           // any path beginning and ending in /
    'filename' => '全部学生报告.csv'); // any filename
        
        //Create csv file containing text 
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], 
    $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
    if ($file) {
            $file->delete();
    }

    $csvfile = $fs->create_file_from_string($fileinfo,$fileContent);
    send_file($csvfile, '全部学生报告.csv', 0, false, null);

}


?>
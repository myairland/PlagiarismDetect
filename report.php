<?php

require('../../config.php');
require_once("$CFG->dirroot/mod/url/lib.php");
require_once("$CFG->dirroot/mod/url/locallib.php");
require_once("$CFG->dirroot/mod/url/refuploadform.php");
require_once($CFG->libdir . '/completionlib.php');
require_once("$CFG->dirroot/mod/url/plagiarismDetect.php");
require_once("$CFG->dirroot/mod/url/reportmodel.php");
require_once("$CFG->dirroot/mod/url/plagiarismModel.php");
include 'vendor/autoload.php';
$courseModuleid       = optional_param('courseModuleid', 0, PARAM_INT);        // Course module ID
$id       = optional_param('courseid', 0, PARAM_INT);        // Course module ID
// 设置脚本执行最大时间
ini_set("max_execution_time", "0");

$mform = new refuploadform(null,array("courseid" => $id));
//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} elseif ($data = $mform->get_data()) {
  //In this case you process validated data. $mform->get_data() returns data posted in form.
    $draftitemid = file_get_submitted_draft_itemid('mydraft');
    $contextid = $data->contextid;

    if ($draftitemid) {
        $messagetext =  file_save_draft_area_files($draftitemid, $contextid, 'mod_url', 'mydraft',
                                0, null);
    }

    $fs = get_file_storage();
    $files = $fs->get_area_files($contextid, 'mod_url', 'mydraft', 0);
    $plag = new PlagiarismDetect();
    $plag->setCon($DB);
    //阈值
    if($data->plagThrehold == "")
        $plag->plagThreshold = 0.7;
    else
        $plag->plagThreshold = $data->plagThrehold;
    //最小检测单元
    if($data->minUnit == "")
        $plag->minUnit = 6;
    else
        $plag->minUnit = $data->minUnit;
    //无意义介词
    if($data->meanlessWords == "")
        $plag->meanlessWords = array();
    else{
        $plag->meanlessWords = mb_split("[,，]",$data->meanlessWords);
    }

    $plag->workshopId = $data->workshopId;
    $plag->clearWorkTable();


    $titleid = 0;
    $refFile = array();
    $refArticleList = array();
    $studentArticleList = array();
        
    foreach ($files as $f) {
        // $f is an instance of stored_file
       
        $fileinfo = array(
        'component' => 'mod_url',     // usually = table name
        'filearea' => 'mydraft',     // usually = table name
        'itemid' => 0,               // usually = ID of row in table
        'contextid' => $contextid, // ID of context
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => $f->get_filename()); // any filename
        
        // Get file
        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
                       $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
        if (!$file->is_directory()) {
            if(strpos(strtoupper($f->get_filename()),".PDF") == false)
                $filecontent = $file->get_content();
            else
            {
                $parser = new \Smalot\PdfParser\Parser();
                $file->get_parent_directory()->get_filepath();
                $pdf    = $parser->parseContent($file->get_content());
                $filecontent = $pdf->getText();
            }    

            $article = new Article();
            $article->articleId = $titleid;
            $article->filename = $f->get_filename();
            $plag->titleId = $titleid;
            $plag->titleName = $f->get_filename();
            $plag->parseArticle($article,$filecontent);
        
            $refArticleList += array($titleid=>$article);
            $titleid += 1;
        }
    }

    // parse student homework
    $studentArticleList = $plag->parseStudentArticle();

     $plag->compareArticle($studentArticleList,$refArticleList,true);

    $cm = get_coursemodule_from_id('url', $courseModuleid, 0, false, MUST_EXIST);
    $url = $DB->get_record('url', array('id'=>$cm->instance), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
    $context = context_module::instance($id);
    url_view($url, $course, $cm, $context);
    $PAGE->set_url('/mod/url/report.php', array('id' => $id));
    $PAGE->set_title("报告");
    $PAGE->set_heading("分析结果");
    $PAGE->set_pagelayout('incourse');
    $PAGE->requires->js('/mod/url/javascript/jquery-3.1.0.min.js', true);
    $PAGE->requires->js('/mod/url/javascript/report.js');

   //$PAGE->set_course($course);
    echo $OUTPUT->header();
    $temp = json_encode($studentArticleList[0]);
    $data = new stdClass();
    $data->stu = $studentArticleList; 
    $data->ref = $refArticleList;
    echo $OUTPUT->render_from_template('url/report', $data);
    echo $OUTPUT->footer();


    
    // echo var_dump($studentArticleList[0]->sentenceList);
    // echo var_dump($studentArticleList[1]->sentenceList);

    // // extract data from report table
    // $reportInfo = array();
    // $stuIdRecords = $DB->get_records_sql("select author_id from {workshop_plag} group by author_id",null);
    // foreach($stuIdRecords as $stuId){
    //     $studentReportInfo = array();
    //     $authorNameRecord = $DB->get_record_sql("select author_name from {workshop_plag_report} where author_id = ? group by author_name",array("author_id"=>$stuId->author_id));
    //     $authorName = $authorNameRecord->author_name;
    //     foreach($refFile as $key=>$value)
    //     {
    //         $index = 0;
    //         $records = $DB->get_records("workshop_plag",array("titleid"=>$key,"author_id"=>$stuId->author_id),"plag_id");
    //         foreach($records as $record)
    //         {   
    //             $index++;
    //             if(array_key_exists($index,$studentReportInfo))
    //             {
    //                 $refContent = new StudentReportRef();
    //                 $refContent->titleId = $key;
    //                 $refContent->sentence = $record->sentence_content;
    //                 $refContent->refSentence = $record->ref1_content;
    //                 $refContent->lcs = $record->ref2_content;
    //                 $refContent->similarity = round($record->ref1_similarity,4) * 100 . "%";
    //                 $studentReportInfo[$index]->refConent = $studentReportInfo[$index]->refConent + array($key => $refContent); 
    //             }else{
    //                 $stuReport = new StudentReportInfo();
    //                 $stuReport->author = $authorName;
    //                 $stuReport->lineNo = $index;
    //                 $refContent = new StudentReportRef();
    //                 $refContent->titleId = $key;
    //                 $refContent->sentence = $record->sentence_content;
    //                 $refContent->refSentence = $record->ref1_content;
    //                 $refContent->lcs = $record->ref2_content;
    //                 $refContent->similarity = round($record->ref1_similarity,4) * 100 . "%";
    //                 $stuReport->refConent=array($key => $refContent); 
    //                 $studentReportInfo = $studentReportInfo + array($index => $stuReport);
    //             }                
    //         }
    //     }
    //     $reportInfo = $reportInfo + array($stuId->author_id => $studentReportInfo);
    // }
    //     // show the report

    // $str = "";
    // $index = 0;
    // // $str = $str . $record->sentence_id . ',' . $record->sentence_content . ',' . $record->ref1_content . ',' . $record->ref1_similarity . PHP_EOL;
    // echo "<!DOCTYPE html>\n"; 
    // echo "<html>\n"; 
    // echo "<head>\n"; 
    // echo "<meta charset=\"UTF-8\">\n"; 
    // echo "<title>抄袭报告</title>\n"; 
    // echo "<style>\n"; 
    // echo "body {font-family: \"Lato\", sans-serif;}\n"; 
    // echo "\n"; 
    // echo "/* Style the tab */\n"; 
    // echo "div.tab {\n"; 
    // echo "    overflow: hidden;\n"; 
    // echo "    border: 1px solid #ccc;\n"; 
    // echo "    background-color: #f1f1f1;\n"; 
    // echo "}\n"; 
    // echo "\n"; 
    // echo "/* Style the buttons inside the tab */\n"; 
    // echo "div.tab button {\n"; 
    // echo "    background-color: inherit;\n"; 
    // echo "    float: left;\n"; 
    // echo "    border: none;\n"; 
    // echo "    outline: none;\n"; 
    // echo "    cursor: pointer;\n"; 
    // echo "    padding: 14px 16px;\n"; 
    // echo "    transition: 0.3s;\n"; 
    // echo "    font-size: 17px;\n"; 
    // echo "}\n"; 
    // echo "\n"; 
    // echo "/* Change background color of buttons on hover */\n"; 
    // echo "div.tab button:hover {\n"; 
    // echo "    background-color: #ddd;\n"; 
    // echo "}\n"; 
    // echo "\n"; 
    // echo "/* Create an active/current tablink class */\n"; 
    // echo "div.tab button.active {\n"; 
    // echo "    background-color: #ccc;\n"; 
    // echo "}\n"; 
    // echo "\n"; 
    // echo "/* Style the tab content */\n"; 
    // echo ".tabcontent {\n"; 
    // echo "    display: none;\n"; 
    // echo "    padding: 6px 12px;\n"; 
    // echo "    border: 1px solid #ccc;\n"; 
    // echo "    border-top: none;\n"; 
    // echo "}\n"; 
    // echo "table a:link {\n"; 
    // echo "	color: #666;\n"; 
    // echo "	font-weight: bold;\n"; 
    // echo "	text-decoration:none;\n"; 
    // echo "}\n"; 
    // echo "table a:visited {\n"; 
    // echo "	color: #999999;\n"; 
    // echo "	font-weight:bold;\n"; 
    // echo "	text-decoration:none;\n"; 
    // echo "}\n"; 
    // echo "table a:active,\n"; 
    // echo "table a:hover {\n"; 
    // echo "	color: #bd5a35;\n"; 
    // echo "	text-decoration:underline;\n"; 
    // echo "}\n"; 
    // echo "table {\n"; 
    // echo "	font-family:Arial, Helvetica, sans-serif;\n"; 
    // echo "	color:#666;\n"; 
    // echo "	font-size:12px;\n"; 
    // echo "	text-shadow: 1px 1px 0px #fff;\n"; 
    // echo "	background:#eaebec;\n"; 
    // echo "	margin:20px;\n"; 
    // echo "	border:#ccc 1px solid;\n"; 
    // echo "\n"; 
    // echo "	-moz-border-radius:3px;\n"; 
    // echo "	-webkit-border-radius:3px;\n"; 
    // echo "	border-radius:3px;\n"; 
    // echo "\n"; 
    // echo "	-moz-box-shadow: 0 1px 2px #d1d1d1;\n"; 
    // echo "	-webkit-box-shadow: 0 1px 2px #d1d1d1;\n"; 
    // echo "	box-shadow: 0 1px 2px #d1d1d1;\n"; 
    // echo "}\n"; 
    // echo "table th {\n"; 
    // echo "	padding:21px 25px 22px 25px;\n"; 
    // echo "	border-top:1px solid #fafafa;\n"; 
    // echo "	border-bottom:1px solid #e0e0e0;\n"; 
    // echo "\n"; 
    // echo "	background: #ededed;\n"; 
    // echo "	background: -webkit-gradient(linear, left top, left bottom, from(#ededed), to(#ebebeb));\n"; 
    // echo "	background: -moz-linear-gradient(top,  #ededed,  #ebebeb);\n"; 
    // echo "}\n"; 
    // echo "table th:first-child {\n"; 
    // echo "	text-align: left;\n"; 
    // echo "	padding-left:20px;\n"; 
    // echo "}\n"; 
    // echo "table tr:first-child th:first-child {\n"; 
    // echo "	-moz-border-radius-topleft:3px;\n"; 
    // echo "	-webkit-border-top-left-radius:3px;\n"; 
    // echo "	border-top-left-radius:3px;\n"; 
    // echo "}\n"; 
    // echo "table tr:first-child th:last-child {\n"; 
    // echo "	-moz-border-radius-topright:3px;\n"; 
    // echo "	-webkit-border-top-right-radius:3px;\n"; 
    // echo "	border-top-right-radius:3px;\n"; 
    // echo "}\n"; 
    // echo "table tr {\n"; 
    // echo "	text-align: center;\n"; 
    // echo "	padding-left:20px;\n"; 
    // echo "}\n"; 
    // echo "table td:first-child {\n"; 
    // echo "	text-align: left;\n"; 
    // echo "	padding-left:20px;\n"; 
    // echo "	border-left: 0;\n"; 
    // echo "}\n"; 
    // echo "table td {\n"; 
    // echo "	padding:18px;\n"; 
    // echo "	border-top: 1px solid #ffffff;\n"; 
    // echo "	border-bottom:1px solid #e0e0e0;\n"; 
    // echo "	border-left: 1px solid #e0e0e0;\n"; 
    // echo "  text-align: left;\n";
    // echo "  word-space: nowrap;\n";
    // echo "\n"; 
    // echo "	background: #fafafa;\n"; 
    // echo "	background: -webkit-gradient(linear, left top, left bottom, from(#fbfbfb), to(#fafafa));\n"; 
    // echo "	background: -moz-linear-gradient(top,  #fbfbfb,  #fafafa);\n"; 
    // echo "}\n"; 
    // echo "table tr.even td {\n"; 
    // echo "	background: #f6f6f6;\n"; 
    // echo "	background: -webkit-gradient(linear, left top, left bottom, from(#f8f8f8), to(#f6f6f6));\n"; 
    // echo "	background: -moz-linear-gradient(top,  #f8f8f8,  #f6f6f6);\n"; 
    // echo "}\n"; 
    // echo "table tr:last-child td {\n"; 
    // echo "	border-bottom:0;\n"; 
    // echo "}\n"; 
    // echo "table tr:last-child td:first-child {\n"; 
    // echo "	-moz-border-radius-bottomleft:3px;\n"; 
    // echo "	-webkit-border-bottom-left-radius:3px;\n"; 
    // echo "	border-bottom-left-radius:3px;\n"; 
    // echo "}\n"; 
    // echo "table tr:last-child td:last-child {\n"; 
    // echo "	-moz-border-radius-bottomright:3px;\n"; 
    // echo "	-webkit-border-bottom-right-radius:3px;\n"; 
    // echo "	border-bottom-right-radius:3px;\n"; 
    // echo "}\n"; 
    // echo "table tr:hover td {\n"; 
    // echo "	background: #f2f2f2;\n"; 
    // echo "	background: -webkit-gradient(linear, left top, left bottom, from(#f2f2f2), to(#f0f0f0));\n"; 
    // echo "	background: -moz-linear-gradient(top,  #f2f2f2,  #f0f0f0);	\n"; 
    // echo "}\n";
    // echo "</style>\n"; 
    // echo "</head>\n";

    // echo "<body>\n"; 
    // echo "\n"; 
    // echo "<div class=\"tab\">\n"; 
    // echo "  <button class=\"tablinks\" onclick=\"openTab(event, 'Whole')\">全部学生</button>\n"; 
    // foreach($reportInfo as $stuid=>$stuInfo){
    //     $authorname = $DB->get_record_sql("select author_name from {workshop_plag_report} where author_id = ? group by author_name",array("author_id"=>$stuid));
    //     echo "  <button class=\"tablinks\" onclick=\"openTab(event, '$stuid')\">$authorname->author_name</button>\n"; 
    // }
    // echo "</div>\n"; 
    // echo "\n"; 
    // echo "\n";
    // //按学生循环
    // foreach($reportInfo as $stuid=>$stuInfo)
    // {
    //     echo "<div id=\"$stuid\" class=\"tabcontent\">\n";
    //     echo "    <span><a href='downloadreport.php?contextid=$contextid&type=0&authorid=".$stuid."'>点击下载</a></span>\n";
    //     echo "    <table>\n"; 
    //     echo "        <th>序号</th>\n"; 
    //     echo "        <th>姓名</th>\n"; 
    //     //参考文献循环 
    //     foreach($refFile as $key=>$value)
    //     {
    //         echo "        <th>学生的句子</th>\n"; 
    //         echo "        <th>参考文献".$value."的句子</th>\n"; 
    //         echo "        <th>相似度</th> \n"; 
    //         echo "        <th>相似部分</th> \n"; 
    //     }
    //     foreach($stuInfo as $id=>$line)
    //     {
    //         echo "        <tr>\n"; 
    //         echo "         <td>" .$line->lineNo . "</td>\n"; 
    //         echo "        <td>" . $line->author . "</td>\n";
    //         $colorIndex = 0;
    //         $colorValue = "";
    //         foreach($refFile as $key=>$value)
    //         {
    //             $colorIndex++;
    //             if($colorIndex % 2 == 1)
    //                 $colorValue = " style=\"background:rgb(220, 247, 200)\"";
    //             else
    //                 $colorValue = "";
    //             if(array_key_exists($key,$line->refConent))
    //             {
    //                 echo "      <td$colorValue>" . $line->refConent[$key]->sentence . "</td>\n";
    //                 echo "      <td$colorValue>" . $line->refConent[$key]->refSentence. "</td>\n"; 
    //                 echo "      <td$colorValue>" . $line->refConent[$key]->similarity. "</td>\n";
    //                 echo "      <td$colorValue>" . $line->refConent[$key]->lcs. "</td>\n";
    //             }else{
    //                 echo "            <td$colorValue></td>\n"; 
    //                 echo "            <td$colorValue></td>\n"; 
    //                 echo "            <td$colorValue></td>\n"; 
    //                 echo "            <td$colorValue></td>\n"; 
    //             }
    //         }
    //         echo "        </tr>\n"; 
    //     }
    //     echo "    </table>\n"; 
    //     echo " </div> \n";
    // }
    // // full report
    // $fullReportInfo = array();
    // $stuIdRecords = $DB->get_records_sql("select author_id from {workshop_plag_report} group by author_id",null);
    // $index = 0;
    // foreach($stuIdRecords as $stuId){
    //     $studentfullReportInfo = array();
    //     $index++;
    //     foreach($refFile as $key=>$value)
    //     {
    //         $records = $DB->get_records("workshop_plag_report",array("titleid"=>$key,"author_id"=>$stuId->author_id),"item_id");
    //         foreach($records as $record)
    //         {   
    //             if(array_key_exists($index,$studentfullReportInfo))
    //             {
    //                 $refContent = new FullReportRef();
    //                 $refContent->titleId = $key;
    //                 $refContent->plagCnt = $record->plg_cnt;
    //                 $refContent->plagPercent = round($record->plg_cent,4) * 100 . "%";
    //                 $studentfullReportInfo[$index]->refConent = $studentfullReportInfo[$index]->refConent + array($key => $refContent); 
    //             }else{
    //                 $stuReport = new FullReportInfo();
    //                 $stuReport->author = $record->author_name;
    //                 $stuReport->lineNo = $index;
    //                 $stuReport->sentenceCnt = $record->sentence_cnt;
    //                 $refContent = new FullReportRef();
    //                 $refContent->titleId = $key;
    //                 $refContent->plagCnt = $record->plg_cnt;
    //                 $refContent->plagPercent = round($record->plg_cent,4) * 100 . "%";
    //                 $stuReport->refConent=array($key => $refContent); 
    //                 $studentfullReportInfo = $studentfullReportInfo + array($index => $stuReport);
    //             }                
    //         }
    //     }
    //     $fullReportInfo = $fullReportInfo + array($stuId->author_id => $studentfullReportInfo);
    // }
    // // print full report
    // echo "<div id=\"Whole\" class=\"tabcontent\">\n";
    // echo "    <span><a href='downloadreport.php?contextid=$contextid&type=1'>点击下载</a></span>\n";
    // echo "   <table>\n";
    // echo "        <tr>\n";
    // echo "        <th>序号</th>\n"; 
    // echo "           <th>学生姓名</th>\n";
    // echo "           <th>作业文字数量</th>\n";
    // //参考文献循环 
    // foreach($refFile as $key=>$value)
    // {
    //     echo "           <th>".$value."相似文字数量</th>\n";
    //     echo "           <th>抄袭率</th>\n";
    // }
    // foreach($fullReportInfo as $stuid=>$stuInfo)
    // {
    //     foreach($stuInfo as $id=>$line)
    //     {
    //         echo "        <tr>\n"; 
    //         echo "         <td>" .$line->lineNo . "</td>\n"; 
    //         echo "        <td>" . $line->author . "</td>\n";
    //         echo "        <td style=\"text-align:center\">" . $line->sentenceCnt . "</td>\n";
    //         foreach($refFile as $key=>$value)
    //         {
    //             if(array_key_exists($key,$line->refConent))
    //             {
    //                 echo "      <td style=\"text-align:center\">" .$line->refConent[$key]->plagCnt . "</td>\n"; 
    //                 echo "      <td style=\"text-align:center\">" . $line->refConent[$key]->plagPercent. "</td>\n"; 
    //             }else{
    //                 echo "            <td></td>\n"; 
    //                 echo "            <td></td>\n"; 
    //             }
    //         }
    //         echo "        </tr>\n"; 
    //     }
    // }
    // echo "    </table>\n"; 
    // echo " </div> \n";
    // echo "<script>\n"; 
    // echo "function openTab(evt, cityName) {\n"; 
    // echo "    var i, tabcontent, tablinks;\n"; 
    // echo "    tabcontent = document.getElementsByClassName(\"tabcontent\");\n"; 
    // echo "    for (i = 0; i < tabcontent.length; i++) {\n"; 
    // echo "        tabcontent[i].style.display = \"none\";\n"; 
    // echo "    }\n"; 
    // echo "    tablinks = document.getElementsByClassName(\"tablinks\");\n"; 
    // echo "    for (i = 0; i < tablinks.length; i++) {\n"; 
    // echo "        tablinks[i].className = tablinks[i].className.replace(\" active\", \"\");\n"; 
    // echo "    }\n"; 
    // echo "    document.getElementById(cityName).style.display = \"block\";\n"; 
    // echo "    evt.currentTarget.className += \" active\";\n"; 
    // echo "}\n"; 
    // echo " openTab(event,\"Whole\");";
    // echo "</script>\n";
// } else {
//   // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
//   // or on the first display of the form.
//   //Set default data (if any)
//     $mform->set_data($toform);
//   //displays the form
//     $mform->display();
// }
}
?>
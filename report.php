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

}
?>
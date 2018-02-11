<?php
require_once("char.php");
require_once("plagiarismModel.php");
require_once("$CFG->libdir/externallib.php");

class PlagiarismDetect extends external_api
{

    private $refSents;

    public $con;

    public $titleId;

    public $workshopId;

    public $titleName;

    public $minUnit = 6;

    public $meanlessWords;
    
    private $stuAssignmentWork;
    // 抄袭阈值
    public $plagThreshold = 0.7;
    //所有文章列表
    public $gArticles;

    private function connectDb()
    {
        if ($this->con == null) {
            $this->con = new PDO("mysql:dbname=moodle;host=127.0.0.1", "root", "123456");
            $this->con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public function setCon($db)
    {
        $this->con = $db;
    }

    public function getCon()
    {
        return $this->con;
    }

    private function closeDb()
    {
        $this->con = null;
    }

    public function clearWorkTable()
    {
        $this->con->delete_records("workshop_plag");
        $this->con->delete_records("workshop_plag_report");
    }

    public function cutSentence($article)
    {
        $start = 0;
        $i = 0;//记录每个字符的位置

        $sentence = array();
        $splitList = CharUtils::mb_str_split(',.!?:;~，。！？：；～');
        $charList = CharUtils::mb_str_split($article);
        foreach ($charList as $char) {
            if (in_array($char, $splitList)) {
                //不要句末的标点符号
                array_push($sentence, array_slice($charList, $start, $i - $start));
                $start = $i + 1; //start标记到下一句的开头
                $i += 1;
            } else {
                $i += 1; // 若不是标点符号，则字符位置继续前移
            }
        }
        
        $len = count($charList);
        if ($start < $len) {
            //不要句末的标点符号
            array_push($sentence, array_slice($charList, $start, $len - $start));//这是为了处理文本末尾没有标点符号的情况
        }
        
        return $sentence;
    }
    //lcs in php code
    // https://gist.github.com/ezzatron/1193894
    public function lcs(array $left, array $right)
    {
        $m = count($left);
        $n = count($right);
    // $a[$i][$j] = length of LCS of $left[$i..$m] and $right[$j..$n]
        $a = array();
    // compute length of LCS and all subproblems via dynamic programming
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                if ($left[$i] == $right[$j]) {
                    $a[$i][$j] = (isset($a[$i + 1][$j + 1]) ? $a[$i + 1][$j + 1] : 0) + 1;
                } else {
                      $a[$i][$j] = max(
                        (isset($a[$i + 1][$j]) ? $a[$i + 1][$j] : 0), (isset($a[$i][$j + 1]) ? $a[$i][$j + 1] : 0)
                      );
                }
            }
        }
    // recover LCS itself
        $i = 0;
        $j = 0;
        $lcs = array();
        while ($i < $m && $j < $n) {
            if ($left[$i] == $right[$j]) {
                $lcs[] = $left[$i];
                $i++;
                $j++;
            } elseif ((isset($a[$i + 1][$j]) ? $a[$i + 1][$j] : 0)
                >= (isset($a[$i][$j + 1]) ? $a[$i][$j + 1] : 0)
              ) {
                $i++;
            } else {
                $j++;
            }
        }
        return $lcs;
    }

    //parse content to sentences
    public function parseArticle($article,$content)
    {

        $article->sentenceList = array();
        $sentenceId = 0;
        $start = 0;

        $sentence = preg_split('/(?<=[,.!?:;~，。！？：；～])/u',$content);  
        foreach($sentence as $st) 
        {
            $obj = new Sentence();
            $obj->articleId = $article->articleId;          
            $obj->sentenceId = $sentenceId;
            $obj->content = CharUtils::remove_spaces($st);
            $obj->start = $start;
            $obj->length= mb_strlen($st);
            $article->sentenceList = $article->sentenceList + array($sentenceId => $obj);

            $sentenceId += 1;
        }
        
        return $sentence;
        
    }
    // parse student articles
    public function parseStudentArticle()
    {
        $stuArticleList = array();
        $titleId = 0;
        // initial student from Db 
        $this->initStudentAssignFromDb();

        foreach ($this->stuAssignmentWork as $stuAssign) {
            $article = new Article();
            $article->articleId = $titleId;
            $article->studentInfo = $stuAssign;
            $this->parseArticle($article,$stuAssign->content);
            
            $stuArticleList = $stuArticleList + array($titleId=>$article);
            $titleId += 1;
        }

        return $stuArticleList;
    }
           
    //compare student homework to reference article
    public function compareArticle($studentArticleList,$refArticleList,$strict)
    {

        foreach($studentArticleList as $stu)
        {
            foreach($stu->sentenceList as $stuSt)
            {
                foreach($refArticleList as $ref)
                {
                    $similarity = 0; 
                    $articleId = "";
                    $sentenceId = "";
                    $lcsPart = "";
                    foreach($ref->sentenceList as $refSt)
                    {

                        $tmp1 = $this->beforeCompareWork($stuSt);
                        $tmp2 = $this->beforeCompareWork($refSt);

                        if($strict){
                            $lcs = $this->lcs($tmp1, $tmp2);
                            
                            if ($lcs == null || count($lcs) <=2) {
                                $lcsLen = 0;
                            } else {
                                $lcsLen = count($lcs);
                            }

                            if($lcsLen <= $this->minUnit)
                            {
                                // 小于最小检测单位认为不是抄袭
                                $ratio = 0;
                            }else{
                                $ratio = $lcsLen / (float)count($tmp1);                         
                            }

                            if ($ratio > $similarity && $ratio > $this->plagThreshold) {
                                $similarity = $ratio;
                                $articleId = $ref->articleId;
                                $sentenceId = $refSt->sentenceId;
                                $lcsPart = implode("",$lcs);
                            }
                            
                        }else{
                            //otherwise using common equal compare
                            if($tmp1 == $tmp2)
                            {
                                $similarity = 1;
                                $articleId = $ref->articleId;
                                $sentenceId = $refSt->sentenceId;
                                $lcsPart = $stuSt->content;
                            }
                        }

                    }
                    // if plagirism happens
                    if($similarity > 0) 
                    {
                        $plagRef = new PlagiarismReference();
                        $plagRef->articleId = $articleId ;
                        $plagRef->sentenceId = $sentenceId;
                        $plagRef->similarity = $similarity;
                        $plagRef->lcsPart = $lcsPart;
                        if(!isset($stuSt->plagiarismList)){
                            $stuSt->plagiarismList = array();
                        }
                        $stuSt->plagiarismList = $stuSt->plagiarismList + array(count($stuSt->plagiarismList)=>$plagRef);
                    }
                }
            }
        }
    }
    
    private function beforeCompareWork($str)
    {
        // return CharUtils::mb_str_split(CharUtils::remove_meaningless_symbol(
        //          CharUtils::remove_spaces(
        //             CharUtils::remove_prepositionList(
        //                 $str->content,$this->meanlessWords))));
        return CharUtils::mb_str_split(CharUtils::remove_meaningless_symbol(
                CharUtils::removePunction(
                 CharUtils::remove_spaces(
                        $str->content,$this->meanlessWords))));
    }

    //整理参考文献里面的句子
    private function prepareRefData($refcontent)
    {
        // 切换为整篇文章的算法，不再去除空格
        $refStr = CharUtils::remove_spaces($refcontent);

        $this->refSents = $this->cutSentence($refStr);
    }
    
    // 从数据库中将学生作业抽出
    public function initStudentAssignFromDb()
    {
        try {
            $studentRoleId = 5;
            $sql = " SELECT distinct m_u.id, m_u.lastname, m_u.firstname, m_u.username, m_u.email, m_ws.title, m_ws.content, m_ws.grade
                    FROM {workshop_submissions} AS m_ws
                    INNER JOIN {user} AS m_u ON m_ws.authorid = m_u.id
                    INNER JOIN {role_assignments} AS m_rs ON m_ws.authorid = m_rs.userid
                    WHERE m_rs.roleid = '" . strval($studentRoleId) . "' and m_ws.workshopid = '".$this->workshopId."'";
            
            $recordSet = $this->con->get_recordset_sql($sql, null);

            $this->stuAssignmentWork = array();
            foreach ($recordSet as $record) {
                $stuAssignItem = new StuAsgnInfo();
                $stuAssignItem->author_id = $record->id;
                $stuAssignItem->last_name = $record->lastname;
                $stuAssignItem->first_name = $record->firstname;
                $stuAssignItem->username = $record->username;
                $stuAssignItem->email = $record->email;
                $stuAssignItem->title = $record->title;
                // clean the content
                // remove html tags e.g. '<h1></h1>'
                // remove spaces, meaningless symbol, nbsp
                // $stuAssignItem->content = CharUtils::remove_nbsp(CharUtils::remove_meaningless_symbol(
                // CharUtils::remove_spaces(CharUtils::remove_html_tags($record->content))));
                $stuAssignItem->content = CharUtils::remove_nbsp(CharUtils::remove_html_tags($record->content));
                $stuAssignItem->grade = $record->grade;
                array_push($this->stuAssignmentWork, $stuAssignItem);
            }


        } catch (Exception$e) {
                echo"Failed:".$e->getMessage();
        }
    }

    private function cutStuSent($content)
    {
        $sentList = array();
        $result = array();
        $sentList = $this->cutSentence($content);
        
        foreach ($sentList as $sent) {
            //句子长度要大于最小检测长度
            if (count($sent) >= $this->minUnit) {
                array_push($result, $sent);
            }
        }
        return $result;
    }

    private function sentPlagiarismReport($authorId, $stuSents, $refSents)
    {
        $sentPlagList = array();
        $cnt = 1;

        try
        {
        foreach ($stuSents as $sent) {
            $sentPlag = new MdlWorkshopPlag();
            $sentPlag->author_id = $authorId;
            $sentPlag->sentence_id = $cnt;
            $cnt += 1;
            $sentPlag->sentence_content = implode($sent);
            foreach ($refSents as $refSent) {
                // $lcs = CharUtils::remove_prepositionList(implode($this->lcs($sent, $refSent)));
                $lcs = $this->lcs($sent, $refSent);
                // 去除[的，了]
                $lcs = CharUtils::remove_prepositionList($lcs,$this->meanlessWords);
                
                if ($lcs == null || count($lcs) <=2) {
                    $lcsLen = 0;
                } else {
                    $lcsLen = count($lcs);
                }
                if($lcsLen <= $this->minUnit)
                {
                    // 小于最小检测单位认为不是抄袭
                    $similarity = 0;
                }else{
                    $similarity = $lcsLen / (float)count($sent);
                }
                if ($similarity > $sentPlag->ref1_similarity) {
                    $sentPlag->ref1_similarity = $similarity;
                    $sentPlag->ref1_content = implode($refSent);
                    //将共同句子存放在ref2里
                    $sentPlag->ref2_content = implode($lcs);
                    //将共同句子的长度存放在ref2_similarity里
                    $sentPlag->ref2_similarity = count($lcs);
                }
            }

            array_push($sentPlagList, $sentPlag);
        }

    }
    catch (Exception $e) {
            echo "Failed:".$e->getMessage();
        }
 
        return $sentPlagList;
    }

    public function detect($refcontent)
    {
       
        try {
            $this->prepareRefData($refcontent);
        //connect db
            
            // $this->connectDb();
            $transaction = $this->con->start_delegated_transaction();
            // $this->con->beginTransaction();
            //get student work form db
            $this->initStudentAssignFromDb();
            // insert report into table
            foreach ($this->stuAssignmentWork as $stuAssign) {
                $stuReport = new MdlWorkshopPlagReport();
                $stuReport->author_id = $stuAssign->author_id;
                $stuReport->author_name = $stuAssign->last_name . $stuAssign->first_name;
                $stuReport->title = $stuAssign->title;
                $stuReport->ws_grading = $stuAssign->grade;
                $stuReport->plg_cnt = 0;

                $stuSents = $this->cutStuSent($stuAssign->content);
                $stuReport->sentence_cnt = mb_strlen($stuAssign->content);

                $sentPlagReportList = $this->sentPlagiarismReport($stuAssign->author_id, $stuSents, $this->refSents);

                foreach ($sentPlagReportList as $sentPlagReport) {
                    if ($sentPlagReport->ref1_similarity > $this->plagThreshold) {
                        $stuReport->plg_cnt += $sentPlagReport->ref2_similarity;
                        $this->con->insert_record_raw("workshop_plag",array(
                            'titleid' => $this->titleId,
                            'author_id' => $sentPlagReport->author_id,
                            'sentence_id' => $sentPlagReport->sentence_id,
                            'sentence_content' => $sentPlagReport->sentence_content,
                            'ref1_content' => $sentPlagReport->ref1_content,
                            'ref2_content' => $sentPlagReport->ref2_content,
                            'ref2_similarity' => $sentPlagReport->ref2_similarity,
                            'ref1_similarity' => $sentPlagReport->ref1_similarity
                        ));
                    }
                }
                //此处plg_cnt长度存放的为相似部分的总长，而sentence_cnt存放的为文章的总长度
                $stuReport->plg_cent = (float)($stuReport->plg_cnt) / (float)($stuReport->sentence_cnt);
                $this->con->insert_record_raw("workshop_plag_report",array(
                            'author_id' => $stuReport->author_id,
                            'author_name' => $stuReport->author_name,
                            'titleid' => $this->titleId,
                            'titlename' => $this->titleName,
                            'title' => $stuReport->title,
                            'sentence_cnt' => $stuReport->sentence_cnt,
                            'plg_cnt' => $stuReport->plg_cnt,
                            'plg_cent' => $stuReport->plg_cent,
                            'ws_grading' => $stuReport->ws_grading
                        ));

            }

            // $this->con->commit();
            $transaction->allow_commit();
            // $this->closeDb();
        } catch (Exception $e) {
            // $this->con->rollBack();
            $this->con->force_transaction_rollback();
            echo"Failed:".$e->getMessage();
        }
    }
}
// $p = new PlagiarismDetect();
// $p->con = $DB;
// $p->detect("ref1111.txt");

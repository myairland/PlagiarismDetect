<?php
class StudentReportInfo{
    public  $lineNo;
    public  $author;
    public  $refConent = array();

}

class StudentReportRef{
    public $titleId;
    public $sentence;
    public $refSentence;
    public $similarity;
    public $lcs;
}


class FullReportInfo{
    public  $lineNo;
    public  $author;
    public  $refConent = array();
    public  $sentenceCnt;
}

class FullReportRef{
    public $titleId;
    public $plagCnt;
    public $plagPercent;
}
?>
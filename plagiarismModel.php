<?php
class MdlWorkshopPlagReport{
   // 学生用户id
   public $author_id = 0;
   // 学生姓名
   public $author_name = '';
   // 学生文章标题
   public $title = '';
   // 学生文章中有效句子数量
   public $sentence_cnt = 0;
   // 学生文章中可疑抄袭句子数量
   public $plg_cnt = 0;
   // 学生文章中可疑抄袭句子比例
   public $plg_cent = 0.0;
   // 该学生在互评阶段的成绩
   public $ws_grading = 0.0;
}



class MdlWorkshopPlag{
    // PK, AI
    public $plag_id = 0;
    // 学生用户id
    public $author_id = 0;
    // 学生句子id
    public $sentence_id = 0;
    // 学生句子内容
    public $sentence_content = '';
    // 与参考文献1中最相似的句子
    public $ref1_content = '';
    // 与参考文献1中最相似句子的相似度
    public $ref1_similarity = 0.0;
    // 与参考文献2中最相似的句子
    public $ref2_content = '';
    // 与参考文献2中最相似句子的相似度
    public $ref2_similarity = 0.0;
}


class StuAsgnInfo{
    // 学生用户id，与mdl_user表关联
    public $author_id = 0;
    // 学生姓
    public $last_name = '';
    // 学生名
    public $first_name = '';
    // 学生帐号登陆名
    public $username = '';
    // 学生email
    public $email = '';
    // 学生文章标题
    public $title = '';
    // 学生文章内容
    // 使用CharTools类中方法做'去html标签'、'去spaces'、'去无意义字符'处理后的纯文本数据
    public $content = '';
    // 学生互评阶段成绩
    public $grade = 0.0;
}
//文章单元
class Article{
    //唯一标识符
    public $articleId;
    //文章名
    public $filename;
    //句子列表
    public $sentenceList;
    //学生信息
    public $studentInfo;
}
//抄袭单元
class PlagiarismReference{
    public $articleId;
    public $sentenceId; 
    // 相似度
    public $similarity;
    // 相似部分 
    public $lcsPart;
}
// 句子单元
class Sentence{
    public $articleId;
    //唯一标识符
    public $sentenceId;
    //句子内容
    public $content;
    //句子在文章中的开始位置
    public $start;
    //句子长度
    public $length;
    //关联的句子列表，抄袭的句子就放在这里
    public $plagiarismList;
    //每个字的列表
    public $characterList;
}
//字单元 暂时应该用不到
class Character
{
    public $sentenceId;

    public $articlePos;

    public $sentencePos;

    public $characterIndex;

}
?>
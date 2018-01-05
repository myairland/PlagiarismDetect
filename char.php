<?php

class CharUtils{
//  去除html标签
    static public function remove_html_tags($raw){
        return preg_replace('/<.*?>/','',$raw);
    } 
    // 去除 &nbsp标志
    static public function remove_nbsp($raw){
        return preg_replace('/&nbsp;/','',$raw);
    }
    // 去除spaces
    static public function remove_spaces($raw){
        // clear meaningless space
        return preg_replace('/\s+/','',$raw);
    }
    // 去除英文单词
    static public function remove_alph($raw){
        return preg_replace('/[a-zA-Z]/','',$raw);
    }
    // 去除无意义符号
    static public function remove_meaningless_symbol($raw){
        // clear meaningless dot in Content
        $dot_text = preg_replace('/\.{7,}/',' ',$raw);
        // clear meaningless - in Content
        $content = str_replace('-', '',str_replace('-', '',$dot_text));
        $content = str_replace('●', '。',str_replace('_', '',$content));
        return $content;
    }

    static public function  removePunction($raw){
        return preg_replace('/[,.!?;~，。！？；～]/u','',$raw);
    
    } 
    // 中文字符分割 
    static public function mb_str_split($str){  
    return preg_split('/(?<!^)(?!$)/u', $str );  
    }  
    
    
    //  去除中文句子中无意义的介词、语气词
    // 该词典有目的的填充可影响分析结果
    static public function remove_prepositionList($lst,$meanlessWords){ 
        //remove meaningless word in list
        return preg_replace('/' .implode($meanlessWords). '/u','',$lst);
    }

    // 去除介词返回字符串版本
    static public function remove_preposition($list){
        return implode(remove_prepositionList($lst));
    }
    
}

// echo CharUtils::remove_html_tags("afa ajf=afaf=adf<input type='123123' /> </input>jlaj;df");
// echo "\r\n";
// echo CharUtils::remove_nbsp("afa ajf&nbsp;k=afaf=adf<input type='123123' /> </input>jlaj;df");
// echo "\r\n";
// echo CharUtils::remove_spaces("afa ajf=afa   f=j  蝇东奔西  走adf<input type='123123' /> </input>jlaj;df");
// echo "\r\n";
// echo CharUtils::remove_alph("afa ajf=afaf=adf<input type='123123' /> </input>jlaj;df");
// echo "\r\n";
// echo CharUtils::remove_meaningless_symbol("afa ...............ajf=afaf=adf<input type='123123' /> </input>jlaj;df");
// echo "\r\n";
// echo CharUtils::remove_html_tags("afa ajf=afaf=adf<input type='123123' /> </input>jlaj;df");
// echo "\r\n";



?>
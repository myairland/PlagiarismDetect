var plagLength = 40;
var continueFlag = false;
var plagArray = [];
var plagStLength = 0;
var plagbackColor= '#ffff62';

function init()
{
    // var curStu = $('#curStu').val();

    hideAll();


    //init next page
    initNextPage("leftInfo","Stu");
    initNextPage("rightInfo","Ref");

    colorPlagiarism("0","0");

    $(document).on("mouseup",function(){
        $("span[id*='selectArea'").each(function(){
            $(this).html("选中文字" + getSelectionTextLength());
        });
    });
}

function colorPlagiarism(curStu,curRef)
{
    var curInt = parseInt(curStu);
    var curRefInt = parseInt(curRef);
    //clear ref 
    $(".rightInfo>div[data-show='" + curRef + "'] .article>span").each(function(){
        $(this).css("background-color","white");  
        $(this).unbind('mouseenter').unbind('mouseleave');
    });
    
    $(".leftInfo>div[data-show='" + curStu + "'] .article>span").each(function(){
        $(this).css("background-color","white");  
        $(this).unbind('mouseenter').unbind('mouseleave');
    });

    // var stuSt = $(".leftInfo>div[data-show='" + curStu + "'] .article>span");

    // stuSt.each(function(index,st){
    //     var plag = $(this).attr("data-plag");
    //     if($.trim(plag) != "" && plag.indexOf(curRef + "-") != -1)
    //     {
    //         continueFlag = true;
    //         plagArray.push($(this));
    //     }
    //     if($.trim(plag) == "" ||  plag.indexOf(curRef + "-") == -1 ||index  == stuSt.length -1)
    //     {
    //         if(plagArray.length > 0)
    //         {
    //             $.each(plagArray,function(index,value){
    //                 plagStLength += $(this).text().length;
    //             });

    //             if(plagStLength > plagLength){
    //                 $.each(plagArray,function(i,ele){
    //                     var plag = $(this).attr("data-plag");
    //                     if($.trim(plag) != "")
    //                     {
    //                         $(this).css("background-color","yellow");  
    //                         $(this).hover(function(){
    //                             $(this).addClass("grow");
    //                         },function(){
    //                             $(this).removeClass("grow");
    //                         })
                        
    //                         var plagArray = plag.split(" ");
    //                         $.each(plagArray,function(index,value){
    //                             var refSt = $(".rightInfo>div[data-show='" + curRef + "'] .article>span[data-plag='" + value + "'");
    //                             if($.trim(value) != "" && value.substring(0,1) == curRef)
    //                             {
    //                                 refSt.css('background-color','yellow');
    //                                 var offset = refSt.offset().top;
    //                                 $(ele).hover(function(){
    //                                 // $(refSt).parent().scrollTop(offset);
    //                                 $(refSt).parent().scrollTop(refSt.offset().top - refSt.parent().offset().top + refSt.parent().scrollTop());
    //                                     $(refSt).addClass("grow");
    //                                 },function(){
    //                                     $(refSt).removeClass("grow");
    //                                 })
    //                             }
    //                         });
    //                     }
    //                 });

    //             }

    //             plagArray.splice(0,plagArray.length);
    //             plagStLength = 0;
    //         }
    //     }

    // });
    $(".leftInfo>div[data-show='" + curStu + "'] .article>span").each(function(index,st){
        var plag = $(this).attr("data-plag");
        if($.trim(plag) != "")
        {
            $(this).css("background-color",plagbackColor);  
            $(this).hover(function(){
                $(this).addClass("grow");
            },function(){
                $(this).removeClass("grow");
            })
          
            var plagArray = plag.split(" ");
            $.each(plagArray,function(refStIndex,value){
                // value = 1-0-0.9995(相同的值相同)
                // get 1-0-0 part
                var plagPart = value.substring(0,value.indexOf("("));
                // get parenthese part
                var lcsPart = value.substring(value.indexOf("(") + 1);
                lcsPart = lcsPart.substring(0,lcsPart.length -1);

                var refStPos = plagPart.substring(0,plagPart.lastIndexOf("-"));
                var refSimilarity = plagPart.substring(plagPart.lastIndexOf("-") + 1);
                var refSt = $(".rightInfo>div[data-show='" + curRef + "'] .article>span[data-plag='" + refStPos + "'");
                if($.trim(value) != "" && value.substring(0,1) == curRef)
                {
                    refSt.css('background-color',plagbackColor);
                    var offset = refSt.offset().top;
                    $(st).hover(function(){
                       $(refSt).parent().scrollTop(refSt.offset().top - refSt.parent().offset().top + refSt.parent().scrollTop());
                        $(refSt).addClass("grow");
                        $(st).css("position","relative");
                        $(st).append("<span class='tips'>相似度:" + parseFloat(round(refSimilarity,4)) * 100 + "%</span>")
                    },function(){
                        $(refSt).removeClass("grow");
                        $(st).children(":last").remove();
                        $(st).css("position","static");
                    })
                }
            });
        }
    });

}

function hideAll()
{
     $(".leftInfo>div[data-show!='']").hide();
     $(".rightInfo>div[data-show!='']").hide();
     $(".leftInfo").show();
     $(".rightInfo").show();
}

// next ref
function nextRef(curRef)
{
    var refInt = parseInt($('#curRef').val()) + 1;
    var curStu = $('#curStu').val();
    nextPage("rightInfo","Ref",curRef);
    colorPlagiarism(curStu,refInt.toString());

}
// pre ref
function preRef(curRef)
{
    var refInt = parseInt($('#curRef').val()) - 1;
    var curStu = $('#curStu').val();
    prePage("rightInfo","Ref",curRef);
    colorPlagiarism(curStu,refInt.toString());
}
// next stu
function nextStu(curStu)
{
    var stuInt = parseInt($('#curStu').val()) + 1;
    var curRef = $('#curRef').val();
    nextPage("leftInfo","Stu",curStu);
    colorPlagiarism(stuInt.toString(),curRef);
}
// prestu
function preStu(curStu)
{
    var stuInt = parseInt($('#curStu').val()) - 1;
    var curRef = $('#curRef').val();
    prePage("leftInfo","Stu",curStu);
    colorPlagiarism(stuInt.toString(),curRef);
}

function nextPage(str,sign,cur)
{
    var lastStu = $("." + str + ">div[data-show!='']").last().attr("data-show");
    var nextInt = parseInt(cur) + 1;
    var lastStuInt = parseInt(lastStu);

    if(nextInt == lastStuInt)
    {
        //hide下一页
        $("." + str + " .next" + sign + "[data-show='" + nextInt.toString() + "']").hide();
    }

    $("." + str + " .pre" + sign + "[data-show='" + nextInt.toString() + "']").show();
    $("." + str + ">div[data-show='" + cur + "']").hide();
    $("." + str + ">div[data-show='" + nextInt.toString() + "']").show();

    $('#cur' + sign).val(nextInt.toString());

}
function prePage(str,sign,cur)
{
    var firstStu = $("." + str + ">div[data-show!='']").first().attr("data-show");
    var preInt = parseInt(cur) - 1;
    var firstStuInt = parseInt(firstStu);

    if(preInt == firstStuInt)
    {
        //hide上一页
        $("." + str + " .pre" + sign + "[data-show='" + preInt.toString() + "']").hide();
    }

    $("." + str + " .next" + sign + "[data-show='" + preInt.toString() + "']").show();
    $("." + str + ">div[data-show='" + cur + "']").hide();
    $("." + str + ">div[data-show='" + preInt.toString() + "']").show();
    
    $('#cur' + sign).val(preInt.toString());

}
function initNextPage(str,sign)
{
    var lastStu = $("." + str + ">div[data-show!='']").last().attr("data-show");
    if('0' == lastStu)
    {
        //hide下一页
        $("." + str + " .next" + sign + "[data-show='0']").hide();
    }
    //hide pre page
    $("." + str + " .pre" + sign + "[data-show='0']").hide();
    $("." + str + ">div[data-show='0']").show();
}



///////////////////////////////////////////////
function fileDownload(curStu)
{
    var param = prepareData(curStu); 

    $.post( "filedownload.php", param,function( data ) {
        var tmp = JSON.parse(data); 
        alert(tmp.age);
        alert(tmp.name);
      });
}

function prepareData(curStu)
{
    var data = {};
    var stuInfo = new StuInfo($('#author' + curStu).html(),$('#title' + curStu).html());
    data.stuInfo = stuInfo;
    data.sentenceList = new Array();

    $(".leftInfo>div[data-show='" + curStu + "'] .article>span").each(function(index,st){
        var sentenceInfo = new Sentence(curStu,index,$(st).html(),null)
        var plag = $(this).attr("data-plag");
        if($.trim(plag) != "")
        {
            var plagArray = plag.split(" ");
            sentenceInfo.plagiarismList = new Array();
            $.each(plagArray,function(refStIndex,value){
                // value = 1-0-0.9995(相同的值相同)
                // get 1-0-0 part
                var plagPart = value.substring(0,value.indexOf("("));
                // get parenthese part
                var lcsPart = value.substring(value.indexOf("(") + 1);
                lcsPart = lcsPart.substring(0,lcsPart.length -1);

                var refStPos = plagPart.substring(0,plagPart.lastIndexOf("-"));
                var refSimilarity = plagPart.substring(plagPart.lastIndexOf("-") + 1);

                var plagReference = new PlagiarismReference(curStu,index,refSimilarity,lcsPart);
                sentenceInfo.plagiarismList.push(plagReference);

            });
        }
        data.sentenceList.push(sentenceInfo);
    });
    
    return data;

}

function round(num,v)
{
    var vv = Math.pow( 10 , v );
    return Math.round( num * vv ) / vv;
}

function getSelectionTextLength() {
    var text = "";
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text.length;
}

//student info
function StuInfo(stuName,fileName)
{
    this.stuName = stuName;
    this.fileName = fileName;
}
function PlagiarismReference(articleId,sentenceId,similarity,lcsPart)
{
    this.articleId = articleId;
    this.sentenceId = sentenceId; 
    // 相似度
    this.similarity = similarity;
    // 相似部分 
    this.lcsPart = lcsPart;
}
//sentence object
function Sentence(articleId,sentenceId,content,plagiarismList)
{
    this.articleId = articleId;
    //唯一标识符
    this.sentenceId = sentenceId;
    //句子内容
    this.content = content;
    //关联的句子列表，抄袭的句子就放在这里
    this.plagiarismList = plagiarismList;
}
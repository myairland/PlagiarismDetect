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
        var selectText = getSelectionText().replace(/\s+/g, "");
        var Chinense = "。●？！：;—（）{}，、“”～〈〉‹›﹛﹜『』〖〗［］《》〔〕{}「」【】";
        var English = ".?•!:;-_()\\[\\]{},'\"/\\~";
        var reg = new RegExp("[" +  Chinense + "]","g");
        //var reg = /[" + Chinense + English + "]/g;
        var selectTextRemovePunction = selectText.replace(reg, "") 
        $("#selectArea").html("选中:" + selectText.length);
        $("#selectAreaLess").html("选中(不计标点):" + selectTextRemovePunction.length);
    });
}
//color the student article and ref
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

    $(".leftInfo>div[data-show='" + curStu + "'] .article>span").each(function(index,st){
        var plag = $(this).attr("data-plag");
        if($.trim(plag) != "")
        {
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
                if($.trim(plagPart) != "" && plagPart.substring(0,1) == curRef)
                {
                    $(st).css("background-color",plagbackColor);  
                    refSt.css('background-color',plagbackColor);
                    var spanText = $(st).text();
                    var refSpanText = $(refSt).text();
                    var refAttr = $(refSt).attr("data-plag");
                    
                    $(st).hover(function(){
                       $(refSt).parent().scrollTop(refSt.offset().top - refSt.parent().offset().top + refSt.parent().scrollTop());
                        changeSpanColor($(refSt),refSpanText,lcsPart);
                       $(refSt).addClass("grow");
                        changeSpanColor($(st),spanText,lcsPart);
                        $(st).css("position","relative");
                        $(st).append("<span class='tips'>相似度:" + round(refSimilarity * 100,2) + "%</span>")
                        $(st).addClass("grow");
                        //判断相似度展示不完全的情况
                        var similaritySpan = $(st).children(":last");
                        var parentDiv = $(similaritySpan).parent().parent();
                        var diff = similaritySpan.position().left + similaritySpan.width() - parentDiv.position().left - parentDiv.width();
                        if(diff > 0 )
                        {
                            var result =  similaritySpan.position().left - diff;
                            similaritySpan.css("left",-1 * (diff + 5));
                        }
                    },function(){
                        removeSpanColor($(refSt),refSpanText,refAttr);
                        $(refSt).removeClass("grow");
                        $(st).css("position","static");
                        $(st).removeClass("grow");
                        removeSpanColor($(st),spanText,value);
                    })
                }
            });
        }
    });

}
// color lcs part
function changeSpanColor(st,text,lcs)
{
    var html = "";
    var textArr = text.split("");
    var lcsArr = lcs.split("");
    st.html("");

    var start = 0;
    var index = 0;
    for(var i = 0;i < lcsArr.length;i++)
    {
        while (index < textArr.length) {
            if(lcsArr[i] == textArr[index])
            {
                st.append("<span>" + lcsArr[i] + "</span>")
                index ++;
                break;
            } 
            st.append("<span class='samepart'>" + textArr[index] + "</span>")
            index++;
        }
    }
}
// restore
function removeSpanColor(st,text,attr) 
{
    st.children().remove();
    st.text(text);
    st.attr("data-plag",attr);
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
        $("." + str + " .next" + sign + "[data-show='" + nextInt.toString() + "']").css('visibility','hidden');
    }

    $("." + str + " .pre" + sign + "[data-show='" + nextInt.toString() + "']").css('visibility','visible');
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
        $("." + str + " .pre" + sign + "[data-show='" + preInt.toString() + "']").css('visibility','hidden');
    }

    $("." + str + " .next" + sign + "[data-show='" + preInt.toString() + "']").css('visibility','visible');
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
        $("." + str + " .next" + sign + "[data-show='0']").css('visibility','hidden');
    }
    //hide pre page
    $("." + str + " .pre" + sign + "[data-show='0']").css('visibility','hidden');
    $("." + str + ">div[data-show='0']").show();
}



///////////////////////////////////////////////
function fileDownload()
{
    var curStu = $("#curStu").val(); 
    var param = prepareData(curStu); 

    $.post( "filedownload.php", param,function( data ) {
        window.location.href = data;
      });
}
// download data
function prepareData(curStu)
{
    var data = {};
    var stuInfo = new StuInfo($('#author' + curStu).text(),$('#title' + curStu).text());
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
                if($.trim(value) != ""){
                    // value = 1-0-0.9995(相同的值相同)
                    // get 1-0-0 part
                    var plagPart = value.substring(0,value.indexOf("("));
                    // get parenthese part
                    var lcsPart = value.substring(value.indexOf("(") + 1);
                    lcsPart = lcsPart.substring(0,lcsPart.length -1);

                    var refStPos = plagPart.substring(0,plagPart.lastIndexOf("-"));
                    var refSimilarity = plagPart.substring(plagPart.lastIndexOf("-") + 1);

                    var plagReference = new PlagiarismReference(refStPos,refStIndex,refSimilarity,lcsPart);
                    sentenceInfo.plagiarismList.push(plagReference);
                }

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

function getSelectionText() {
    var text = "";
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    return text;
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
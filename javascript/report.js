var plagLength = 40;
var continueFlag = false;
var plagArray = [];
var plagStLength = 0;
var plagbackColor= '#ffff62';

function init()
{
    hideAll();

    //下载当前学生隐藏
    $(".mainheader").children("span").hide();
    $(".downloadAll").show();

    initGlobalView();

    $(document).on("mouseup",function(){
        var selectText = getSelectionText().replace(/\s+/g, "");
        var Chinense = "。●？！：;—（）{}，、“”～〈〉‹›﹛﹜『』〖〗［］《》〔〕{}「」【】";
        var English = ".?•!:;-_()\\[\\]{},'\"/\\~";
        var reg = new RegExp("[" +  Chinense + "]","g");
        //var reg = /[" + Chinense + English + "]/g;
        var selectTextRemovePunction = selectText.replace(reg, "");
        $("#selectArea").html("选中:" + selectText.length);
        $("#selectAreaLess").html("选中(不计标点):" + selectTextRemovePunction.length);
    });
}

function removePunction(text)
{
    var Chinense = "。●？！：;—（）{}，、“”～〈〉‹›﹛﹜『』〖〗［］《》〔〕{}「」【】";
    var English = ".?•!:;-_()\\[\\]{},'\"/\\~";
    var reg = new RegExp("[" +  Chinense + "]","g");
    return text.replace(/\s+/g, "").replace(reg,"");
}

function initGlobalView()
{
   var table = $("#tableAll")
   
   var stuIndex = 0;
   $(".leftInfo>div[data-show!='']").each(function(stuIndex,stu){
        var stuName = "<a onclick='jumpToDetail(" + $(stu).attr("data-show")  + ")' >" + $(stu).find(".author").html() + "</a>";
        var total = 0.0; 
        var plagCnt = 0.0;

        $(stu).children("div.article").children("span").each(function(spanIndex,span){
            var plag = $(this).attr("data-plag");
            if($.trim(plag) != "")
            {
                var plagArray = plag.split(" ");
                var originalArray = $(span).html().split("");
                var assitArray = Array.apply(null, Array(originalArray.length)).map(Number.prototype.valueOf,0)
                $.each(plagArray,function(refStIndex,value){
                    // value = 1-0-0.9995(相同的值相同)
                    // get 1-0-0 part
                    var plagPart = value.substring(0,value.indexOf("("));
                    // get parenthese part
                    var lcsPart = value.substring(value.indexOf("(") + 1);
                    lcsPart = lcsPart.substring(0,lcsPart.length -1);
                    
                    var lcsPartArray = lcsPart.split("");
                    var index = 0;
                    //一句话抄袭字数
                    for(var i = 0;i < lcsPartArray.length;i++)
                    {
                        while (index < originalArray.length) {
                            if(lcsPartArray[i] == originalArray[index])
                            {
                                assitArray[index] = 1;
                                index ++;
                                break;
                            } 
                            index++;
                        }
                    }
                });
                //统计这句话总抄袭数 
                for(var i = 0,n = assitArray.length; i < n; i++) 
                {
                    plagCnt += assitArray[i];
                }
            }
            total = total + removePunction($(span).html()).length;
        });
        

        var tr = $("<tr></tr>");
        table.append(tr);
        
        stuIndex =  stuIndex + 1;
        tr.append("<td>" + stuIndex + "</td>");
        tr.append("<td>" + stuName + "</td>");
        tr.append("<td>" + total + "</td>");
        tr.append("<td>" + plagCnt + "</td>");
        tr.append("<td>" + round(plagCnt / total * 100,2) + "%</td>");
   });

   $(".globalView").show();

}
function jumpToDetail(curStu)
{
    $(".mainheader").children("span").show();

    $(".globalView").hide();

    $('#curStu').val(curStu.toString());
    $('#curRef').val("0");
    $('#curView').val("0");
    //init next page
    initPagination("leftInfo","Stu",curStu.toString());
    initPagination("rightInfo","Ref","0");
    //跳转后 默认第一篇文献
    colorPlagiarism(curStu.toString(),"0");
    scrollTo(0,0);

}
function changeToGlobalView()
{
    $(".mainheader").children("span").hide();
    $(".downloadAll").show();
    hideAll();

    $(".globalView").show();
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
                        removeSpanColor($(st),spanText,plag);
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
     $(".mainTab").hide();
     $(".leftInfo").show();
     $(".rightInfo").show();
}

// next ref
function nextRef(curRef)
{
    var refInt = parseInt($('#curRef').val()) + 1;
    var curStu = $('#curStu').val();
    nextPage("rightInfo","Ref",curRef);

    var curView = $('#curView').val();
    //表格视图
    if(curView == "1")
    {
        changeTable(curStu,refInt.toString());
    }else{
        //文章视图
        colorPlagiarism(curStu,refInt.toString());
    } 


}
// pre ref
function preRef(curRef)
{
    var refInt = parseInt($('#curRef').val()) - 1;
    var curStu = $('#curStu').val();
    prePage("rightInfo","Ref",curRef);

    var curView = $('#curView').val();
    //表格视图
    if(curView == "1")
    {
        changeTable(curStu,refInt.toString());
    }else{
        //文章视图
        colorPlagiarism(curStu,refInt.toString());
    } 
}
// next stu
function nextStu(curStu)
{
    var stuInt = parseInt($('#curStu').val()) + 1;
    var curRef = $('#curRef').val();
    nextPage("leftInfo","Stu",curStu);

    var curView = $('#curView').val();
    //表格视图
    if(curView == "1")
    {
        changeTable(stuInt.toString(),curRef);
    }else{
        //文章视图
        colorPlagiarism(stuInt.toString(),curRef);
    } 
}
// prestu
function preStu(curStu)
{
    var stuInt = parseInt($('#curStu').val()) - 1;
    var curRef = $('#curRef').val();
    prePage("leftInfo","Stu",curStu);

    var curView = $('#curView').val();
    //表格视图
    if(curView == "1")
    {
        changeTable(stuInt.toString(),curRef);
    }else{
        //文章视图
        colorPlagiarism(stuInt.toString(),curRef);
    } 
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
function initPagination(str,sign,cur)
{
    var lastStu = $("." + str + ">div[data-show!='']").last().attr("data-show");
    if(cur == lastStu)
    {
        //hide下一页
        $("." + str + " .next" + sign + "[data-show='" + cur + "']").css('visibility','hidden');
    }

    var firstStu = $("." + str + ">div[data-show!='']").first().attr("data-show");
    if(cur == firstStu)
    {
        //hide上一页
        $("." + str + " .pre" + sign + "[data-show='" + cur + "']").css('visibility','hidden');
    }

    $("." + str + ">div[data-show='" + cur + "']").show();
}
// 表格视图
///////////////////////////////////////////////////
function changeTable(curStu,curRef)
{

    var table = $('#tableShow');
    table.find("tr").each(function(index,value){
        //avoid removing th header
        if($(value).find("th").length == 0)
            $(value).remove();
    });

    var stuIndex = 0;
    $(".leftInfo>div[data-show='" + curStu + "'] .article>span[data-plag!='']").each(function(index,st){
        var tr = $("<tr></tr>");
        table.append(tr);  

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
                    stuIndex =  stuIndex + 1;
                    tr.append("<td>" + stuIndex + "</td>");
                    tr.append("<td>" + $(st).html() + "</td>");
                    tr.append("<td>" + $(refSt).html() + "</td>");
                    tr.append("<td>" + lcsPart + "</td>");
                    tr.append("<td>" + round(refSimilarity * 100,2) + "%</td>");
                }
            });
        }

    });

    $(".mainTab").show();
}
function changeView()
{
    var curView = $("#curView").val();
    var curRef = $('#curRef').val();
    var curStu = $('#curStu').val();

    if(curView == "0"){
        //从详细视图切换到列表视图
        $("#curView").val("1");
        //隐藏详细视图
        $(".leftInfo>div[data-show!='']>.article").hide();
        $(".rightInfo>div[data-show!='']>.article").hide();

        changeTable(curStu,curRef);
    }
    else{
        //从列表视图切换到详细视图
        $("#curView").val("0");

        $(".mainTab").hide();
        colorPlagiarism($('#curStu').val(),$('#curRef').val());
        //init page
        $(".leftInfo>div[data-show!='']>.article").show();
        $(".rightInfo>div[data-show!='']>.article").show();
        //
    }

}
//全局视图
///////////////////////////////////////////////
function globalView()
{

}


//下载相关
///////////////////////////////////////////////
function filedownloadAll()
{
    var param = {};

    param.stuList = Array();

    $(".leftInfo>div[data-show!='']").each(function(index,div){
        var curStu = $(div).attr("data-show")
        var data = prepareData(curStu);
        param.stuList.push(data);
    });
    
    $.post( "filedownload.php", param,function( data ) {
        window.location.href = data;
      }).fail(function(message){alert("服务器返回错误");});


}

function fileDownload()
{
    var param = {};
    var curStu = $('#curStu').val();

    param.stuList = Array();

    param.stuList.push(prepareData(curStu));

    $.post( "filedownload.php", param,function( data ) {
        window.location.href = data;
      }).fail(function(message){alert("服务器返回错误");});
}
// download data
function prepareData(curStu)
{
    var data = {};
    var stuInfo = new StuInfo($('#author' + curStu).text(),$('#title' + curStu).text());
    data.stuInfo = stuInfo;
    data.sentenceList = new Array();
    //1是单个下载 

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
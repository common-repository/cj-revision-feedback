<?php
if (!function_exists('add_action'))
{
	//currently in wp/wp-content/plugins/cj_revision_feedback
	//need to get to wp/wp-config.php
	require_once("../../../wp-config.php");
}

global $current_user;
get_currentuserinfo();
?>

var posturl =  "<?php bloginfo('wpurl') ?>/wp-content/plugins/cj-revision-feedback/cj_revision_feedback_ajaxpost.php";
var imgPath = "<?php bloginfo('wpurl') ?>/wp-content/plugins/cj-revision-feedback/";
var userID = <?php echo $current_user->ID?>;
var input_value;
var input_area_prev;
var last_clicked_revision;
var postid;
var commentid;
<?PHP
$offsetTop = get_option( "cj_offsetTop" );
$adminOffsetLeft = get_option( "cj_admin_offsetLeft" );
$usrOffsetLeft = get_option( "cj_usr_offsetLeft" );

echo "var offsetTop = $offsetTop;\n";


//if admin
$isAdmin= ($current_user->user_level>7) ?true:false;
if ($isAdmin)
	echo "var offsetLeft=$adminOffsetLeft;\n";
else
	echo "var offsetLeft=$usrOffsetLeft;\n";
?>

function cj_addComment(revision,el,pid)
{
	el_id = el.id;
	parentPos = cj_findPos(el);
	el = document.getElementById('cj_comment_overlay');
	el.style.left=(parentPos[0] + offsetLeft) + 'px';
	el.style.top =(parentPos[1] + offsetTop) + 'px';
	cj_resetComment();
	cj_toggleLayer('cj_comment_overlay');
	last_clicked_revision=revision;
	postid=pid;

}

function cj_findPos(obj)
{
        var curleft = curtop = 0;
        if (obj.offsetParent)
        {
                curleft = obj.offsetLeft
                curtop = obj.offsetTop
                while (obj = obj.offsetParent)
                {
                        curleft += obj.offsetLeft
                        curtop += obj.offsetTop
                }
        }
        returnArray = new Array(curleft,curtop);
        return returnArray;
}

function cj_toggleLayer(el_id)
{
	el = document.getElementById(el_id);
	//if shown, then hide else show
	if (el.style.display=='block')
		el.style.display='none';
	else
		el.style.display='block';
}

function cj_previewComment()
{
	buttons = document.getElementById('cj_comment_post_buttons');
	input_area = document.getElementById('cj_comment_input_area');
	input_value=document.getElementById('cj_comment_input').value;
	input_area_prev=input_area.innerHTML;
	input_area.innerHTML=input_value.replace(/\n/g,"<br/>").replace(/\r/g,"");
	buttons.innerHTML='<div id="cj_edit_comment"><img onclick="cj_editComment()" src="' + imgPath + 'edit_comment.gif"/></div><div id="cj_submit_comment"><img onClick="cj_submitComment()" src="' + imgPath + 'submit_comment.gif"/></div>';

}

function cj_editComment()
{
	document.getElementById('cj_comment_input_area').innerHTML=input_area_prev;
	document.getElementById('cj_comment_input').value=input_value;
	buttons = document.getElementById('cj_comment_post_buttons');
	buttons.innerHTML="<img class='cj_comment_button_post' onClick='cj_previewComment()' src='" + imgPath + "post_comment.gif'/>";
}

function cj_submitComment()
{
	var pars = 'posttype=submit&postid=' +postid + '&revisionid=' + last_clicked_revision + '&postText=' + escape(input_value) + '&userid=' + userID;
	div=document.getElementById(last_clicked_revision+'_comment');
//	alert(posturl +' '+ pars);
	var myAjax = new Ajax.Updater(div, posturl, {method:'get',parameters:pars});
	input_value='';
	document.getElementById('cj_comment_input_area').innerHTML=input_area_prev;
	cj_toggleLayer('cj_comment_overlay');
}

function cj_resetComment()
{
	document.getElementById('cj_comment_input_area').innerHTML="<textarea id='cj_comment_input' class='cj_comment_input'></textarea>";
	buttons = document.getElementById('cj_comment_post_buttons');
        buttons.innerHTML="<img class='cj_comment_button_post' onClick='cj_previewComment()' src='" + imgPath + "post_comment.gif'/>";
	input_value='';
	input_area_pref='';

}

function cj_closeComment()
{
	cj_resetComment();
	cj_toggleLayer('cj_comment_overlay');
}

function cj_adminEdit(id,el,rev,pid)
{
	commentid = id;
	el_id = el.id;
        parentPos = cj_findPos(el);
        el = document.getElementById('cj_comment_overlay');
        el.style.left=(parentPos[0] + offsetLeft) + 'px';
        el.style.top =(parentPos[1] + offsetTop) + 'px';
	var pars = 'posttype=adminGet&commentid=' +commentid;
        div=document.getElementById('cj_comment_overlay');
        var myAjax = new Ajax.Updater(div, posturl, {method:'get',parameters:pars});
	postid=pid;
	last_clicked_revision=rev;
        cj_toggleLayer('cj_comment_overlay');
}

function cj_adminSaveComment()
{
	input_value=document.getElementById('cj_admin_comments_input').value;
        input_value=escape(input_value.replace(/\n/g,"<br/>").replace(/\r/g,""));
	completed = document.getElementById('cj_completed_checkbox').checked;
	approved = document.getElementById('cj_approved_checkbox').checked;
	var pars = 'posttype=adminSave&commentid=' +commentid +'&adminComment=' + input_value + '&completed=' + completed + '&approved=' + approved;
        div=document.getElementById('cj_comment_overlay');
	cj_toggleLayer('cj_comment_overlay');
        var myAjax = new Ajax.Updater(div, posturl, {method:'get',parameters:pars,onSuccess:function(){cj_adminUpdateComments()}});
//	cj_adminUpdateComments()
}

function cj_adminUpdateComments()
{
        var pars = 'posttype=refreshComments&postid=' +postid + '&revisionid=' + last_clicked_revision;
        div=document.getElementById(last_clicked_revision+'_comment');
        var myAjax = new Ajax.Updater(div, posturl, {method:'get',parameters:pars});
}


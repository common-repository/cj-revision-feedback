<?PHP

function cj_isAdmin()
{
	global $current_user;
        get_currentuserinfo();

	$author 	= (get_option("cj_admin_role_author")=='on')?true:false;
	$contributor 	= (get_option("cj_admin_role_contributor")=='on')?true:false;
	$editor 	= (get_option("cj_admin_role_editor")=='on')?true:false;
	$subscriber	= (get_option("cj_admin_role_subscriber")=='on')?true:false;

	if ($current_user->wp_capabilities['administrator'])
		return true;
	if ($current_user->wp_capabilities['author'] && $author)
		return true;
	if ($current_user->wp_capabilities['contributor'] && $contributor)
		return true;
	if ($current_user->wp_capabilities['editor'] && $editor)
		return true;
	if ($current_user->wp_capabilities['subscriber'] && $subscriber)
		return true;
	return false;
}

function cj_commentText($postid,$rev)
{
	global $wpdb, $current_user;
	$isAdmin=cj_isAdmin();


	$table_name = $wpdb->prefix . "cj_revision_feedback";
	$user_table = $wpdb->prefix . "users";
	$postid=$wpdb->escape($postid);
	$rev=$wpdb->escape($rev);
	$sql="select id,post_text,date_format(completed_on,'%m/%d/%Y %H:%i') completed_on ,date_format(approved_on,'%m/%d/%Y %H:%i') approved_on,admin_comment,(select user_login from $user_table u where u.id=t.feedback_author) user_post,date_format(post_date,'%m/%d/%Y %H:%i') post_date from $table_name t where post_id=$postid and revision_id=$rev order by post_date";
//	echo $sql ."<BR/>";
	$records = $wpdb->get_results($sql);
	$commentText;
	$showDetail=(get_option( "cj_show_feedback_detail" ) =='on') ? true:false;
	for ($i=0;$i<sizeof($records);$i++)
	{
		$record = $records[$i];
		if (!is_null($record->post_text))
		{
			$class = (is_null($record->completed_on)) ? "cj_not_completed": "cj_completed";
			$num=$i+1;
			if (is_null($record->user_post))
				$record->user_post='Anonymous User';
			if($showDetail)
				$commentText.="<table class='cj_comment_table'><tr><td><div class='cj_comment_label'>Comment $num posted on $record->post_date by $record->user_post</div></td>\n";
			else
				$commentText.="<table class='cj_comment_table'><tr><td><div class='cj_comment_label'>Comment $num</div></td>\n";
			if($isAdmin)
				$commentText.='<td><div class="cj_comment_button_edit" id="' . $record->id . '_comment_edit_button" onClick="cj_adminEdit(' . $record->id . ',this,' . $rev . ',' . $postid . ')"><img src="' . WP_PLUGIN_URL . '/cj-revision-feedback/edit_comment.gif"/></div></td>';
			else
				$commentText.='<td>&nbsp</td>';
			$commentText.="<tr><td colspan='2'><div class='$class'>" . stripslashes($record->post_text) . "</div></td>\n";
			if(!is_null($record->completed_on))
				$commentText.="<tr><td colspan='2'><div class='$class'>Completed on $record->completed_on</div></td>\n";
			if(!is_null($record->admin_comment))
                                $commentText.="<tr><td colspan='2'><div class='cj_completed_on_comment $class'>" . stripslashes($record->admin_comment) ."</div></td>\n";
			if(!is_null($record->approved_on))
                                $commentText.="<tr><td colspan='2'><div class='$class'>Approved on $record->approved_on</div></td>\n";

			$commentText.='</tr></table>';
		}
	}
	return $commentText;
}

function cj_getAdminComment($id)
{
	global $wpdb, $current_user;
	$isAdmin=cj_isAdmin();
	if($isAdmin)
	{
		echo '<div><div id="cj_admin_header" class="cj_comment_label">Comment</div><div id="cj_admin_save"><img onClick="cj_adminSaveComment()" src="' . WP_PLUGIN_URL . '/cj-revision-feedback/save_comment.gif"/></div></div>';
		$table_name = $wpdb->prefix . "cj_revision_feedback";
	        $id=$wpdb->escape($id);
		$records = $wpdb->get_results("select id,post_text,date_format(completed_on,'%m/%d/%Y %H:%i') completed_on ,date_format(approved_on,'%m/%d/%Y %H:%i') approved_on,admin_comment from $table_name where id=$id");
		$commentText;
		for ($i=0;$i<sizeof($records);$i++)
        	{
                	$record = $records[$i];
        	        if (!is_null($record->post_text))
                	{
				$class='cj_not_completed';
	                        $commentText.="<div id='cj_admin_comment_text' class='$class'>" . stripslashes($record->post_text) . "</div>\n";
				$commentText.="<div id='cj_admin_checkboxes'>\n";
				$checked='';
	                        if(!is_null($record->completed_on))
					$checked='checked';
				$commentText.='<div id="cj_admin_completed"><input type="checkbox" id="cj_completed_checkbox" name="cj_completed_checkbox" ' . $checked . '/>&nbsp;Completed</div>';
				$checked='';
				if(!is_null($record->approved_on))
					$checked='checked';
				$commentText.='<div id="cj_admin_approved"><input type="checkbox" id="cj_approved_checkbox" name="cj_approved_checkbox" '.$checked.'/>&nbsp;Approved</div>';
				$commentText.="</div>\n<div>";
				$commentText.='<div id="cj_admin_comments"><textarea id="cj_admin_comments_input">' . stripslashes($record->admin_comment) . '</textarea></div>';
        	        }
	        }
		echo $commentText;
	}//end if admin
	else
		echo "You must be an admin to use this functionality\n";
}

?>

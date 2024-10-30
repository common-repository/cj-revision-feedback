<?PHP
//print_r($_REQUEST);
/*
Array ( [posttype] => submit [postid] => 3 [revisionid] => 5 [postText] => sdfgsdg
*/
if (isset($_REQUEST) && isset($_REQUEST['posttype']))
{
	require_once("../../../wp-config.php");
	require_once('../../../wp-includes/wp-db.php');
	include_once('cj_common.php');
	extract($_REQUEST);
	if($posttype=='submit')//add new revision comment
	{
		if (isset($postid) && isset($revisionid) && isset($postText) && isset($userid) && is_numeric($postid) && is_numeric($revisionid) && !is_null($postText) && is_numeric($userid))
		{
			$wpdb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
			global $table_prefix;
			$table_name = $table_prefix . "cj_revision_feedback";
			$wpdb->prefix=$table_prefix;
			$valueArray = array('post_id'=>$postid,'revision_id'=>$revisionid,'post_text'=>cj_replaceLines($postText),'feedback_author'=>$userid);
			$return = $wpdb->insert($table_name,$valueArray);
			if(get_option( "cj_comment_notification" )=='on')
				cj_send_notification($postid,$revisionid,$postText);
			echo cj_commentText($postid,$revisionid);
		}
		else
			echo "The submit type parameters were not valid " . print_r($_REQUEST,1);
	}
	else if ($posttype=='adminGet') //show the admin overlay with comments
		cj_getAdminComment($commentid);
	else if ($posttype=='adminSave')//save the admin comments
	{
		//update table with input
		/*
		    [posttype] =&gt; adminSave
		    [commentid] =&gt; 1
		    [adminComment] =&gt; asdfadsf
		    [completed] =&gt; false
		    [approved] =&gt; false
		*/
		$wpdb = new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
                global $table_prefix;
                $table_name = $table_prefix . "cj_revision_feedback";
                $wpdb->prefix=$table_prefix;

		$records = $wpdb->get_results("select completed_on,approved_on from $table_name where id=$id");
               	$record = $records[0];
		$valueArray=array('admin_comment' =>$adminComment);
		if(is_null($record->completed_on) && $completed=='true')
			array_push_associative($valueArray,array('completed_on' => date ("Y-m-d H:i:s")));
		if(is_null($record->approved_on) && $approved=='true')
                        array_push_associative($valueArray,array('approved_on' => date ("Y-m-d H:i:s")));

		$return=$wpdb->update($table_name,$valueArray, array('id'=>$commentid));
		echo "<img src='" . WP_PLUGIN_URL . "/cj-revision-feedback/waiting.gif'/>";
	}
	else if ($posttype=='refreshComments')//um... refresh the comments
	{
		echo cj_commentText($postid,$revisionid);
	}

}
else
	echo "posttype is required when posting " . print_r($_REQUEST,1);

function cj_replaceLines($c)
{
	return preg_replace('/\n/','<BR/>',$c);
}

// Append associative array elements
//array_push_associative($theArray, $items, $moreitems)
function array_push_associative(&$arr) {
   $args = func_get_args();
   foreach ($args as $arg) {
       if (is_array($arg)) {
           foreach ($arg as $key => $value) {
               $arr[$key] = $value;
               $ret++;
           }
       }else{
           $arr[$arg] = "";
       }
   }
   return $ret;
}

function cj_send_notification($pid,$rid,$c)
{
	global $wpdb;

	//additional post specific notification addresses are stored in custom fields for the post
	//addition addresses are to be comma seperated
	global $table_prefix;
        $table_name = $table_prefix . "postmeta";
        $wpdb->prefix=$table_prefix;
	$sql="select meta_value emails from $table_name where post_id=$pid and meta_key='notification_addresses'";
        $records = $wpdb->get_results($sql);
        $record = $records[0];
	$emails = explode(',',$record->emails);//$emails is now an array of the email addresses

	$defaultEmail = get_option( "cj_default_notification_address" );

	//get post URL
	$table_name = $table_prefix . "posts";
	$sql="select guid,post_title from $table_name where id = $pid";
	$records = $wpdb->get_results($sql);
        $record = $records[0];
	$postURL = $record->guid;
	$postTitle=$record->post_title;

	if(strlen($defaultEmail)>0 || sizeof($emails)>0)
	{
		$subject="A new comment has been added to $postTitle";
		$body="Comment:<BR/>" . cj_replaceLines($c) . "<br/><BR/>View online: $postURL";
		$to=$defaultEmail;
		$headers  = 'MIME-Version: 1.0' . "\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\n";
		$server=str_replace("www.","",strtolower($_SERVER['SERVER_NAME']));
		$headers .= "From: Revision Comment Notification <donotreply@$server>\n";
		for ($i=0;$i<sizeof($emails);$i++)
			$headers .="Cc: " . trim($emails[$i]) . "\n";
		mail($to,$subject,$body,$headers);
	}

}

?>

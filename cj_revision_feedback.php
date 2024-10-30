<?php
/*
Plugin Name: CJ Revision Feedback
Plugin URI: http://chrisjohnson.blogsite.org/cj-revision-feedback-wordpress-plugin-documentation/
Description: This plugin provides the ability to add comment blocks mid post.  The use case for this is:  WP is used to show clients creative documents, a single post is dedicated to a single project.  Each revision is appended to the post.  Adding in the CJ Revision Feedback tag will allow clients to provide feedback for a specific revision.  Admin users can mark the feedback as completed and approved while adding sub-comments, and close out feedback on a revision.  The plugin includes multiple customizable elements including: look and feel, email notifications, and post-specific email notifications.
Version: 1.0.2
Author: Chris Johnson
*/
/*  Copyright 2010  Chris Johnson  (email : chrissjohnson00@yahoo.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once('cj_common.php');
register_activation_hook(__FILE__,'cj_install');
add_filter ('the_content', cj_parse_tags, 10, 1);
add_action('admin_menu', 'cj_plugin_settings_menu');
add_action( 'admin_init', 'register_cjsettings' );

wp_enqueue_script('cj_javascript',WP_PLUGIN_URL.'/cj-revision-feedback/cj_javascript.php');
wp_enqueue_script('cj_prototype',WP_PLUGIN_URL.'/cj-revision-feedback/cj_prototype.js');
wp_enqueue_style('cj_styles',WP_PLUGIN_URL.'/cj-revision-feedback/cj_styles.css');


function cj_install()
{
   global $wpdb;
   $cj_db_version="1.0";

   $table_name = $wpdb->prefix . "cj_revision_feedback";
   if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
   {
	$sql="CREATE TABLE $table_name (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `post_id` bigint(20) unsigned DEFAULT NULL,
	  `revision_id` int(11) DEFAULT NULL,
	  `feedback_author` bigint(20) unsigned DEFAULT NULL,
	  `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  `post_text` text,
	  `completed_on` datetime DEFAULT NULL,
	  `admin_comment` text,
	  `approved_on` datetime DEFAULT NULL,
	  PRIMARY KEY (`id`),
	  KEY `feedback_ix` (`post_id`,`revision_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	add_option("cj_show_feedback_detail","on");
	add_option("cj_usr_offsetLeft",0);
	add_option("cj_offsetTop",30);
	add_option("cj_admin_offsetLeft",-350);
	add_option("cj_comment_notification","on");
	add_option("cj_default_notification_address","");
	add_option("cj_db_version", $cj_db_version);
	add_option("cj_admin_role_editor","");
	add_option("cj_admin_role_contributor","");
	add_option("cj_admin_role_subscriber","");
	add_option("cj_admin_role_author","");
	add_option("cj_allow_anon","");

   }//end if table not created

   $installed_ver = get_option( "cj_db_version" );

   if( $installed_ver != $cj_db_version ) {

	$sql="CREATE TABLE $table_name (
         `id` int(11) NOT NULL AUTO_INCREMENT,
          `post_id` bigint(20) unsigned DEFAULT NULL,
          `revision_id` int(11) DEFAULT NULL,
          `feedback_author` bigint(20) unsigned DEFAULT NULL,
          `post_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `post_text` text,
          `completed_on` datetime DEFAULT NULL,
	  `admin_comment` text,
          `approved_on` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `feedback_ix` (`post_id`,`revision_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	update_option( "cj_db_version", $cj_db_version );


  }//end if db upgrade required

	//check to make sure all the options exist
	cj_check_options();

}//end cj_install

function cj_check_options()
{
      	$options = array(	"cj_show_feedback_detail",
				"cj_usr_offsetLeft",
				"cj_offsetTop",
				"cj_admin_offsetLeft",
				"cj_comment_notification",
				"cj_default_notification_address",
				"cj_db_version",
				"cj_admin_role_editor",
				"cj_admin_role_contributor",
				"cj_admin_role_subscriber",
				"cj_admin_role_author",
				"cj_allow_anon"
			);

        global $wpdb;
        $table = $wpdb->prefix . "options";
	//if the option doesn't exist in the table, add it with no value
	//this is really only meant to add new options on upgrades
	for ($i=0;$i<sizeof($options);$i++)
	{
		$sql="select option_name from $table where option_name='" . $options[$i] . "'";
	        $records = $wpdb->get_results($sql);
		if(sizeof($records)==0)
			add_option($options[$i],"");
	}



}

function cj_parse_tags($c)
{
	$isAdmin=cj_isAdmin();
	$postid = get_the_ID();
	$pattern = '/\[revision_feedback\s+revision=(\d+)\s?(\w*)\]/i';
	preg_match_all($pattern, $c, $matches);
	$matchedTags = $matches[0];
	$matchedRevisions = $matches[1];
	$nobutton = $matches[2];
	$revisionList = array(); //this is used for validaton that a revision number doesn't exist multiple times
	$errors = array(); //when a revision exists multiple times, we add to this array for the error message
	$allow_anon = (get_option("cj_allow_anon")=='on')?true:false;
	global $current_user;
        get_currentuserinfo();
	$anon = ($current_user->id==0)?true:false;

	//perform replacements for each tag found
	for($i=0;$i<sizeof($matchedTags);$i++)
	{
		$rev = $matchedRevisions[$i];
		//check to see if the current revision has already been found... if so add to error array
		if(in_array($rev,$revisionList))
			array_push($errors,"<span class=cj_error>Revision #$rev is listed more than one time, this will cause the comments to appear more than once</span>");
		array_push($revisionList,$rev);//add revision to revision list for validation later
		$replacement="";
		//only show add comment button if the nocomments tag is not present, not admin, and if anon allowed or logged in
		if($nobutton[$i]!='nobutton' && !$isAdmin)
		{
			if( ($anon && $allow_anon) || (!$anon))
				$replacement.='<div class="cj_comment_button" id="'. $rev .'_comment_button" onClick="cj_addComment(' .$rev .',this,'.$postid.')"><img src="' . WP_PLUGIN_URL . '/cj-revision-feedback/add_comment.gif"/></div>' . "\n";
		}
		$commentText=cj_commentText($postid,$rev);
		$replacement .= '<div class="cj_comments" id="' .$rev . '_comment">' .$commentText . '</div>' . "\n";
		$c=str_replace($matchedTags[$i],$replacement,$c);
	}
	if($isAdmin)
		$c.="<div id='cj_comment_overlay' class='cj_comment_overlay_admin'><img src='" . WP_PLUGIN_URL . "/cj-revision-feedback/waiting.gif'/></div>\n";
	else
		$c.="<div id='cj_comment_overlay' class='cj_comment_overlay'><div id='cj_comment_head'><div class='cj_comment_label'>Comment</div><div id='cj_comment_close'><img onClick=\"cj_closeComment()\" src='" . WP_PLUGIN_URL . "/cj-revision-feedback/close_comment.gif'/></div></div><div id='cj_comment_input_area'><textarea id='cj_comment_input' class='cj_comment_input'></textarea></div><div id='cj_comment_post_buttons'><img class='cj_comment_button_post' onClick='cj_previewComment()' src='" . WP_PLUGIN_URL . "/cj-revision-feedback/post_comment.gif'/></div></div>\n";
	$errorText;
	if($isAdmin)//only show error to admin users
	{
		for ($i=0;$i<sizeof($errors);$i++)
			$errorText=$errorText . $errors[$i];
	}
	return $errorText.$c;
}

function cj_plugin_settings_menu()
{
	add_options_page('CJ Revision Feedback Plugin Options', 'CJ Revision Feedback','administrator', 'cj_options',  'cj_plugin_options_menu');
}

function cj_plugin_options_menu()
{
	$feedbackDetail = (get_option("cj_show_feedback_detail")=='on')?'checked':'';
	$notification = (get_option("cj_comment_notification")=='on')?'checked':'';

	echo '<div class="wrap">';
	echo"<h2>CJ Revision Feedback Settings</h2>";
	echo'<form method="post" action="options.php">';
	wp_nonce_field('update-options');
	echo "\n".'<table class="form-table">' ."\n";
//feedback author/date
	echo '<tr valign="top">' ."\n";
	echo '<th scope="row">Show Feedback Author and Date</th>';
	echo '<td><input type="checkbox" name="cj_show_feedback_detail" ' . $feedbackDetail .'/></td>';
	echo '</tr>' ."\n";

//allow anonymous comments
	$allow_anon = (get_option("cj_allow_anon")=='on')?'checked':'';

        echo '<tr valign="top">' ."\n";
        echo '<th scope="row">Allow Anonymous User Comments (disabling will require uses be logged in to comment)</th>';
        echo '<td><input type="checkbox" name="cj_allow_anon" ' . $allow_anon .'/></td>';
        echo '</tr>' ."\n";

	echo '<tr><td colspan="2"><hr width="100%"/></td></tr>';
	echo '<tr><td colspan="2">This section is to move where the comment layers appear on the screen</td></tr>';
//layer top offset
	echo '<tr valign="top">' . "\n";
	echo '<th scope="row">Layer Top Offset</th>' . "\n";
	echo '<td><input type="text" name="cj_offsetTop" size="10" value="' . get_option('cj_offsetTop') . '" /></td>' . "\n";
	echo '</tr>' . "\n";
//admin layer left offset
	echo '<tr valign="top">' . "\n";
	echo '<th scope="row">Admin Layer Left Offset</th>' . "\n";
	echo '<td><input type="text" name="cj_admin_offsetLeft" size="10" value="' . get_option('cj_admin_offsetLeft') . '" /></td>' . "\n";
	echo '</tr>' . "\n";
//usr layer left offset
	echo '<tr valign="top">' . "\n";
	echo '<th scope="row">User Layer Left Offset</th>' . "\n";
	echo '<td><input type="text" name="cj_usr_offsetLeft" size="10" value="' . get_option('cj_usr_offsetLeft') . '" /></td>' . "\n";
	echo '</tr>' . "\n";

	echo '<tr><td colspan="2"><hr width="100%"/></td></tr>';

//comment notifictaion?
        echo '<tr valign="top">' . "\n";
        echo '<th scope="row">Send notification emails on new comments</th>' . "\n";
	echo '<td><input type="checkbox" name="cj_comment_notification" ' . $notification .'/></td>';
        echo '</tr>' . "\n";
//default notification address
        echo '<tr valign="top">' . "\n";
        echo '<th scope="row">Default Notification Address (will be sent all new comment notifications)</th>' . "\n";
        echo '<td><input type="text" size="35" name="cj_default_notification_address" value="' . get_option('cj_default_notification_address') . '" /></td>' . "\n";
        echo '</tr>' . "\n";

	echo '<tr><td colspan="2">Additional notification addresses can be added per post by creating a custom field named "notification_addresses", the value is a comma seperated list of email addresses</td></tr>';

	echo '<tr><td colspan="2"><hr width="100%"/></td></tr>';

//comment edit roles
        echo '<tr valign="top">' . "\n";
        echo '<th scope="row">Revision Editor Roles (Administrator is always an editor)</th>' . "\n";
//        echo '<td><select style="height:90px" name="cj_admin_roles" multiple size="5">';
	echo "<td>";

	$roles = array("editor","author","contributor","subscriber");
	for($i=0;$i<sizeof($roles);$i++)
	{
		$optVal = (get_option("cj_admin_role_" . $roles[$i])=='on')? 'checked':'';
		echo '<input name="' . "cj_admin_role_" . $roles[$i] . '" type="checkbox" ' . $optVal . '>&nbsp;&nbsp;' . ucwords($roles[$i]) . '<BR/>';
	}

	echo '</select></td>';
        echo '</tr>' . "\n";


	echo '</table>' ."\n";
	echo '<input type="hidden" name="action" value="update" />' ."\n";
	echo '<input type="hidden" name="page_options" value="cj_show_feedback_detail,cj_offsetTop,cj_admin_offsetLeft,cj_usr_offsetLeft" />' ."\n";
	settings_fields( 'cj_options_group' );
	echo '<p class="submit">' ."\n";
	echo '<input type="submit" class="button-primary" value="Save Changes" />' ."\n";
	echo '</p>' ."\n";
	echo '</form>' ."\n";
	echo '</div>';
}

function register_cjsettings()
{ // whitelist options
	register_setting( 'cj_options_group', 'cj_show_feedback_detail');
	register_setting( 'cj_options_group', 'cj_offsetTop');
	register_setting( 'cj_options_group', 'cj_admin_offsetLeft');
	register_setting( 'cj_options_group', 'cj_usr_offsetLeft');
	register_setting( 'cj_options_group', 'cj_comment_notification');
	register_setting( 'cj_options_group', 'cj_default_notification_address');
	register_setting( 'cj_options_group', "cj_admin_role_editor");
	register_setting( 'cj_options_group', "cj_admin_role_contributor");
	register_setting( 'cj_options_group', "cj_admin_role_subscriber");
	register_setting( 'cj_options_group', "cj_admin_role_author");
	register_setting( 'cj_options_group', "cj_allow_anon");
}


?>

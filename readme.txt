=== CJ Revision Feedback ===
Contributors: chrisjohnson00
Donate link: http://donate_to_a_worthy_cause/I_do_this_for_fun.nerd
Tags: comments, admin
Requires at least: 2.8.6
Tested up to: 2.9.2
Stable tag: trunk

This plugin provides the ability to add comment blocks mid post.

== Description ==

This plugin provides the ability to add comment blocks mid post. The use case for this is: WP is used to show clients creative documents, a single post is dedicated to a single project. Each revision is appended to the post. Adding in the CJ Revision Feedback tag will allow clients to provide feedback for a specific revision. Admin users can mark the feedback as completed and approved while adding sub-comments, and close out feedback on a revision. The plugin includes multiple customizable elements including: look and feel, email notifications, and post-specific email notifications.

Developed on version 2.8.6 so any previous versions haven't been tested

== Installation ==

1. Upload upload all files from zip to `cj-revision-feedback` folder in the  `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place `[revision_feedback revision=1]` in your post to enable mid post comments

== Frequently Asked Questions ==

= Ask me questions on my documentation page =
<a href="http://chrisjohnson.blogsite.org/cj-revision-feedback-wordpress-plugin-documentation/">Documentation page</a>

== Screenshots ==

1. This is a screen shot of the user comment screen
2. This is a screen shot of the admin comment screen
3. This is a screen shot of a comment after completion.
4. This is a screen shot of the admin settings screen

== Changelog ==

= 1.0 =
Initial release

= 1.0.1 =
Changed the install folder from `cj_revision_feedback` to `cj-revision-feedback` to match the folder created in the wordpress plugin zip

= 1.0.2 =
Added option to allow/disalow anonymous comments, to help prevent spam

== Upgrade Notice ==

= 1.0 =
Initial release

= 1.0.1 =
Changed the install folder from `cj_revision_feedback` to `cj-revision-feedback` to match the folder created in the wordpress plugin zip.  If you have issues with the images showing up, the folder name is likely this issue :)

= 1.0.2 = 
Added option to allow/disalow anonymous comments, to help prevent spam


== Usage ==

**Basic Tag Format:**

`[revision_feedback revision=# nobutton]`

Each tag added should have a unique revision number, starting with 1 and incrementing from there.
To disable new feedback for a revision just add nobutton to the tag.  This will only disable the user from adding new comments, they can view comments and the admin users can do everything still.


**Sample tags:**

`[revision_feedback revision=1]`

This tag is the first revision tag.  At this point the client makes comments.
Once revision 1 is completed the tag is updated by adding the nobutton tag

`[revision_feedback revision=1 nobutton]`

Now the "add feedback" button will not show
At this point you would want to open comments to the next revision by adding

`[revision_feedback revision=2]`

rinse and repeat until the final revisions are approved


If you add the same revision number to a post the admin view (not user) will see an error message on the screen.  Putting duplicate revision numbers doesn't break anything, but will cause new/existing comments to show in two places.


== Admin Features/Settings ==

If you visit the "settings" section of the admin page you will see a line item for CJ Revision Feedback.  This details what each feature/setting is for.

**Show feedback author and date**
<BR>By checking this box the user id and comment date will be displayed next to each comment.

**Allow Anonymous User Comments (disabling will require uses be logged in to comment)**
<BR>Check this checkbox to allow anonymous users, people not logged in, to make revision comments

**Layer Top Offset**
<BR>This defines how many pixels below or above the clickable button the user and admin comment layer will appear.  A positive number is below, a negative number is above.  The top left of the button is 0 offset

**Admin Layer Left Offset**
<BR>This defines how many pixels to the left or right of the clickable button the admin layer should be positioned.  A negative number is left, a positive number is right.

**User Layer Left Offset**
<BR>This defines how many pixels to the left or right of the clickable button the user layer should be positioned.  A negative number is left, a positive number is right.

**Send notification emails on new comments**
<BR>By checking this box (and providing a default address) you will enable email notification for new user comments.

**Default Notification Address (will be sent all new comment notifications)**
<BR>This is the default notification address used for all new comments.  This email address will receive an email for each new user comment.

**Revision Editor Roles (Administrator is always an editor)**
<BR>This setting gives you the ability to specify which user types to consider as an admin.  An "admin" is a user who can make sub-comments, and mark a comment as completed or approved.

== Post Specific Email Notification Addresses ==
It is possible to specify email notification addresses on a per post basis.  This enables sending new comment notifications to non-default addresses scoped to the specific post.
<BR>To do this create a new custom field named "notification_addresses" with the value as a comma seperated list of email addresses.  For example: email1@email.com,email2@email.com, email3@email.com
(You can add spaces between email addresses)

== Customizing the look and feel ==
**Images**
<BR>You can customize the buttons by replacing the gif images in the `cj-revision-feedback` folder.

**Styles**
<BR>You can customize the colors of the comment layers and some of the positioning by editing the CSS file in the `cj-revision-feedback` folder.


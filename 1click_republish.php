<?php
/*
Plugin Name: One Click Republish
Plugin URI: http://pirex.com.br/wordpress-plugins/one-click-republish
Description: Wordpress MU Plugin: Allows admin to give users permission to republish a post to the "main blog" with one click as they visit the blogs.
Author: Leo Germani
Stable tag: 1.0
Author URI: http://pirex.com.br/wordpress-plugins

    One Click Republish is released under the GNU General Public License (GPL)
    http://www.gnu.org/licenses/gpl.txt
	

*/


function oneclick_xajax(){
	global $xajax;
	$xajax->registerFunction("oneClickRepublish_go");
}

load_plugin_textdomain('oneclickrepublish', 'wp-content/mu-plugins/oneclickrepublish');

function oneclick_add_menu(){
	
	if (function_exists("add_submenu_page")) add_submenu_page("wpmu-admin.php",__('One Click Republish Options','oneclickrepublish'), __('One Click Republish','oneclickrepublish'), 8, basename(__FILE__), 'oneclick_admin_page');
	
}

function oneclick_admin_page(){
	global $wpdb, $current_blog;
	
	if(isset($_POST["submit"])){
		
		$active = $_POST["active"] == 1 ? 1 : 0;
		$newOpt["active"] = $active;
		$newOpt["mainBlog"] = $_POST["mainBlog"];
		
		update_site_option("oneClickRepublish",$newOpt);
		
		$userids = $_POST['users'];
		
		$num_users = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->users");
		$x=0;
		for ( $x==0; $x<$num_users; $x++ ) {
			$user_object = new WP_User($wpdb->get_var("SELECT ID FROM $wpdb->users", 0, $x));
			delete_usermeta($user_object->ID, "oneClickRepublish", 1);
			
		}
		
		
		foreach($userids as $id) {
			
			update_usermeta($id, "oneClickRepublish", 1);
			
		}
		
	}
	
	if (!get_site_option("oneClickRepublish")){
		#load defaults
		$options["active"] = 0;
		$options["mainBlog"] = 1;
		
		
		$sqlTable = "CREATE TABLE `".$wpdb->base_prefix."oneClickRepublish` (
		`target_blog_id` BIGINT NOT NULL ,
		`post_id` BIGINT NOT NULL ,
		`blog_id` BIGINT NOT NULL ,
		PRIMARY KEY ( `target_blog_id` , `post_id` , `blog_id` )
		) ENGINE = MYISAM ";
		
		mysql_query($sqlTable);
		update_site_option("oneClickRepublish",$options);
		
	}else{
		$options = 	get_site_option("oneClickRepublish");
	}
	?>
	
	<div class="wrap">
	
	<h2><?php _e("1 Click Republish Settings","oneclickrepublish"); ?></h2>
	
	<form name="one2publish" method="post">
	
	<BR>
	<input type="checkbox" style="width:30px; height: 30px;" value="1" name="active" <?php if($options["active"]) echo "checked"; ?>>
	<?php _e("Activate Plugin","oneclickrepublish"); ?>
	<BR><BR>
	<?php _e("Please indicate in wich blog the posts should be republished","oneclickrepublish"); ?>
	<BR>
	<select name="mainBlog">
		<?php 
		$blogs = get_site_option( "blog_list" );
		if( is_array( $blogs ) ) {
			#reset( $blogs );
			foreach ( (array) $blogs as $b ) {
					echo "<option value='".$b['blog_id']."'";
					if($b['blog_id']==$options["mainBlog"]) echo " selected";
					echo ">".$b['domain']."</option>";	
			}
			
		}
		?>
	</select>
	<BR><BR>
	<?php _e("Mark the users that should be allowed to republish content","oneclickrepublish"); ?>
	<BR>
	<table class="widefat">
	<thead>
	<tr class="thead">
		<th scope="col" class="check-column"></th>
		<th><?php _e('Username',"oneclickrepublish") ?></th>
		<th><?php _e('Name',"oneclickrepublish") ?></th>
	</tr>
	</thead>
	<tbody id="users" class="list:user user-list">
	
	<?php
	$style = '';
	
	$num_users = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->users");
	$x=0;
	for ( $x==0; $x<$num_users; $x++ ) {
		$user_object = new WP_User($wpdb->get_var("SELECT ID FROM $wpdb->users", 0, $x));
		?>
		<tr>
		<td ><input type="checkbox" name="users[]" value="<?php echo $user_object->ID; ?>" <?php if (get_usermeta($user_object->ID, "oneClickRepublish")) echo "checked"; ?>></td>
		<td><?php echo $user_object->user_login; ?></td>
		<td><?php echo $user_object->first_name . $user_object->last_name; ?></td>
		</tr>
		<?php
		
	}
	?>
	
	</tbody>
	</table>
	
	<div style="margin-top:5px; height:40px; background: url(<?php bloginfo('url'); ?>/wp-content/mu-plugins/oneclickrepublish/bg.gif) no-repeat; padding-left:50px;">
	
	<?php _e('This is the button users will see to republish content', 'oneclickrepublish'); ?>
	
	</div>
	
	<div class="submit">
	<input type="submit" name="submit" value="<?php _e('Update Settings', 'oneclickrepublish'); ?> &raquo;">
	</div>
	
	</form>
	
	</div>
	
	<?php
}


function oneclick_add_button($content){
	global $user_id,$wpdb,$current_blog;
	
	$tableName = $wpdb->base_prefix . "oneClickRepublish";
	$options = 	get_site_option("oneClickRepublish");
	$post = get_the_ID();
	$published = $wpdb->get_var("SELECT COUNT(*) from $tableName WHERE target_blog_id = ". $options["mainBlog"] ." AND blog_id = ".$current_blog->blog_id." AND post_id = $post ");
		
	
	if ($current_blog->blog_id==$options["mainBlog"] || !get_usermeta(get_current_user_id(), "oneClickRepublish") || $published || !$options["active"]) return $content;
	

	
	$publish_button = "
	<div class='oneClickRepublish_button' id='oneClickRepublish_button_".$current_blog->blog_id."_$post' ";
	
	$publish_button.= "style='cursor:pointer;' onclick='xajax_oneClickRepublish_go(".$current_blog->blog_id.",$post)'";
	
	$publish_button.= "></div>";
	return $publish_button.$content;
}

function oneclick_add_styles(){

	?>	
	<style>
	
	.oneClickRepublish_button{
	
	float:left;
	width:40px;
	height:40px;
	margin: 0px 10px 3px 0px;
	background: url(<?php bloginfo('url'); ?>/wp-content/mu-plugins/oneclickrepublish/bg.gif) no-repeat;

	color: #FFF;
	font-size: 12px;
	text-align: center;
	
	
	font-weight:bold;
	text-decoration: none;
	}


	</style>
	<?php	
}

function oneClickRepublish_go($blog,$post){
	global $wpdb;
	$tableName = $wpdb->base_prefix . "oneClickRepublish";
	
	$options = 	get_site_option("oneClickRepublish");
	$published = $wpdb->get_var("SELECT COUNT(*) from $tableName WHERE target_blog_id = ". $options["mainBlog"] ." AND blog_id = ".$blog." AND post_id = $post ");
	
	$objResponse = new xajaxResponse();
	
	if(!$published){
		
		mysql_query("INSERT INTO $tableName(target_blog_id,blog_id,post_id) VALUES(".$options['mainBlog']. ", $blog, $post)");
		
		$the_post = get_blog_post($blog, $post);
		$orig_link = get_blog_permalink($blog, $post);
		$orig_blog = "<a href='$orig_link'>".get_option("blogname")."</a>";
		
		$orig_time = apply_filters('get_the_time', $the_post->post_date, get_option('time_format'), false);
		
		
		$orig_note = __("Post originally published in %s on %s","oneclickrepublish");
		
		
		$content = "<span class='postmetadata'>".sprintf($orig_note, $orig_blog, $orig_time)."</span><BR><BR>".$the_post->post_content;
		$result = array('post_status' => 'publish', 'post_type' => 'post', 'post_author' => $the_post->post_author,
		'post_content' => $content, 'post_title'=>$the_post->post_title);
		
		switch_to_blog($options["mainBlog"]);
		$POSTID = wp_insert_post($result);
		
		#$objResponse->addAssign("oneClickRepublish_button_".$blog."_$post","style.cursor", "auto");
		$objResponse->addAssign("oneClickRepublish_button_".$blog."_$post","style.display", "none");
		
	}else{
			$objResponse->addAlert(__("Post already republished","oneclickrepublish"));
	}
	return $objResponse;
	
}

add_action('init','oneclick_xajax');
add_action('wp_head','oneclick_add_styles');
add_action('admin_menu','oneclick_add_menu');
add_filter('the_content','oneclick_add_button');


?>

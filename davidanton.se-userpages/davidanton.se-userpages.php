<?php
/*
Plugin Name: davidanton.se Userpages
*/

/*
Original post by Smashing Magazine:
http://www.smashingmagazine.com/2012/01/27/limiting-visibility-posts-username/
*/

/* Fire our meta box setup function on the post editor screen. */
add_action( 'load-post.php', 'userpages_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'userpages_post_meta_boxes_setup' );

/* Meta box setup function. */
function userpages_post_meta_boxes_setup() {

   /* Add meta boxes on the 'add_meta_boxes' hook. */
   add_action( 'add_meta_boxes', 'userpages_add_post_meta_boxes' );

   /* Save post meta on the 'save_post' hook. */
   add_action( 'save_post', 'userpages_access_save_meta', 10, 2 );
}

/* Create one or more meta boxes to be displayed on the post editor screen. */
function userpages_add_post_meta_boxes() {

   add_meta_box(
      'userpages-access',         // Unique ID
      esc_html__( 'Post Viewing Permission', 'userpages' ),     // Title
      'userpages_access_meta_box',      // Callback function
      'page',              // Admin page (or post type)
      'normal',               // Context
      'default'               // Priority
   );
}

/* Display the post meta box. */
function userpages_access_meta_box( $object, $box ) { ?>

   <?php wp_nonce_field( basename( __FILE__ ), 'userpages_access_nonce' ); ?>

   <p>
      <label for="userpages-access"><?php _e( "Enter the username of the subscriber that you want to view this content.", 'userpages' ); ?></label>
      <br />
      <!--input class="widefat" type="text" name="userpages-access" id="userpages-access" value="<?php echo esc_attr( get_post_meta( $object->ID, 'userpages_access', true ) ); ?>" size="30" /-->
   </p>
   <table class="userpages-access">
	<tr align="left">
	<th>Username</th>
	<th>    </th>
	<th>Visiblity</th>
	<th>    </th>
	<th>Name</th>
	</tr>
<?php
global $post;

         if(get_post_meta( $object->ID, 'userpages_access', true ) == '') $ifchecked = 'checked="checked" ';
         echo "<tr>";
         echo "<td>Ingen (sidan synlig för alla)</td><td>    </td>";
         echo "<td align='center'><input type='radio' name='userpages-access' id='userpages-access' value='' " . $ifchecked ."/></td><td>    </td>";
         echo "<td> </td><td>    </td>";
         echo "</tr>";
         unset($ifchecked);


//$users = get_users('role=subscriber');
   $users = get_users();
   foreach ($users as $user) {
         $user_info = get_userdata( $user->ID );
         if(get_post_meta( $object->ID, 'userpages_access', true ) == $user->user_login) $ifchecked = 'checked="checked" ';
         echo "<tr>";
         echo "<td>$user->user_login</td><td>    </td>";
         echo "<td align='center'><input type='radio' name='userpages-access' id='userpages-access' value='".$user->user_login."' " . $ifchecked ."/></td><td>    </td>";
         echo "<td>$user_info->last_name, $user_info->first_name</td><td>    </td>";
         echo "</tr>";
         unset($ifchecked);

   } ?></table>
<?php }



/* Save post meta on the 'save_post' hook. */
add_action( 'save_post', 'userpages_access_save_meta', 10, 2 );

/* Save the meta box's post metadata. */
function userpages_access_save_meta( $post_id, $post ) {

   /* Make all $wpdb references within this function refer to this variable */
   global $wpdb;
   
   /* Verify the nonce before proceeding. */
   if ( !isset( $_POST['userpages_access_nonce'] ) || !wp_verify_nonce( $_POST['userpages_access_nonce'], basename( __FILE__ ) ) )
      return $post_id;

   /* Get the post type object. */
   $post_type = get_post_type_object( $post->post_type );

   /* Check if the current user has permission to edit the post. */
   if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
      return $post_id;

   /* Get the posted data and sanitize it for use as an HTML class. */
   $new_meta_value = ( isset( $_POST['userpages-access'] ) ? sanitize_html_class( $_POST['userpages-access'] ) : '' );

   /* Get the meta key. */
   $meta_key = 'userpages_access';

   /* Get the meta value of the custom field key. */
   $meta_value = get_post_meta( $post_id, $meta_key, true );

   /* If a new meta value was added and there was no previous value, add it. */
   if ( $new_meta_value && '' == $meta_value )
      {
      add_post_meta( $post_id, $meta_key, $new_meta_value, true );
      $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_status = 'private' WHERE ID = ".$post_id." AND post_type ='post'"));
      }
   /* If the new meta value does not match the old value, update it. */
   elseif ( $new_meta_value && $new_meta_value != $meta_value )
      {
      update_post_meta( $post_id, $meta_key, $new_meta_value );
      $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_status = 'private' WHERE ID = ".$post_id." AND post_type ='post'"));
      }
   /* If there is no new meta value but an old value exists, delete it. */
   elseif ( '' == $new_meta_value && $meta_value )
      {
      delete_post_meta( $post_id, $meta_key, $meta_value );
      $wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_status = 'public' WHERE ID = ".$post_id." AND post_type ='post'"));
      }
}


/* Remove "protected" and "private" from page title */
function userpages_title_trim($title) {
   $title = attribute_escape($title);
   $needles = array(__('Protected: '),__('Private: '));
   $title = str_replace($needles,'',$title);
   return $title;
}
add_filter('protected_title_format','userpages_title_trim');
add_filter('private_title_format','userpages_title_trim');


/* allow subscribers to access private pages */
$subRole = get_role( 'subscriber' );
$subRole->add_cap( 'read_private_posts' );
$subRole->add_cap( 'read_private_pages' );


/* deploy code to the loop */


function userpages_checkrights($post){
	//echo '$post: '.$post.'<br>';
	/* Get the post's acceptable viewer. */
      $userpages_access = get_post_meta($post->ID, 'userpages_access', true );
          //echo 'Page ID: '.$post->ID.'<br>Users with access: ' . $userpages_access . '<br>';
	/* Get the post's current viewer, if he or she is logged in. */
      if(is_user_logged_in()) {
      	$current_user = wp_get_current_user();
      	$current_userpages = $current_user->user_login;
  	  }
          //echo 'Current user: ' . $current_userpages . '<br>';
	/* See if the acceptable viewer and the current viewer are the same */
      if($userpages_access == '' || $userpages_access == $current_userpages || current_user_can('author') || current_user_can('editor') || current_user_can('administrator')){
          //Debug code
          //echo '$userpages_access == $current_userpages: '.($userpages_access == $current_userpages).'<br>';
          //echo 'current_user_can(\'author\'): '.current_user_can('author').'<br>';
          //echo 'current_user_can(\'editor\'): '.current_user_can('editor').'<br>';
          //echo 'current_user_can(\'administrator\'): '.current_user_can('administrator').'<br>';
          
          //access is granted
          //echo 'Access granted.<br>';
      } else { 
         /* access is not granted. 
         Die and redirect to start page / login form. */
          echo 'Redirecting. Click <a href="http://backstage.ikiike.com">here</a> if you\'re not being redirected.<br>';
          
          echo '<meta http-equiv="Location" content="http://backstage.ikiike.com/">';
		  echo '<script type="text/javascript">
           			window.location = "http://backstage.ikiike.com/";
      			</script>';          
		  
          die();
		}
}

add_action( 'the_post', 'userpages_checkrights' );

//Add logout link
function userpages_print_logoutlink(){
	if ( is_user_logged_in() ) {
		echo '<p class="userpages-logout" style="text-align:center;"><a href="'.wp_logout_url( '/' ).'" class="userpages-logout-link">'.__('Log out').'</a></p>';
	}
}
add_action( 'loop_end', 'userpages_print_logoutlink');
?>
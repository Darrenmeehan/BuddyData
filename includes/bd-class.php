<?php
if ( !defined( 'ABSPATH' ) ) exit;

/*
* api classes
*
* @package BM API
* @since 0.1
*/

function bm_api_switchboard() {

	$api = new BM_API_CONTROLLER();
	$param = $_REQUEST;

	$user_id = get_current_user_id();
	$token = get_user_meta( $user_id , 'status_token', true );

	//if ( $param['comp'] !== 'user' && $token !== $param['token'] )
		//return false;

     switch( $_REQUEST['comp'] ){
          case $_REQUEST['comp']:
          	$output = $api->comp( $param ) ;
          break;
          default:
            $output = __( 'No API request specified' );
          break;
     }

     $output = json_encode( $output );

     if ( $_REQUEST['callback'] ) {

     	echo $_REQUEST['callback'] . '(' . $output . ')' ;

     } else {

	     echo $output;

     }

}



/*
*
* API Controller class
*/

class BM_API_CONTROLLER  {

    public function comp( $param ) {

    	$comp_func = $param['comp'];
    	$method_param = $param['method'];

    	if ( !$method_param || !$comp_func )
    	return __( 'method or parameters set incorrectly' );

        $api_class = "BM_API_" . $comp_func ;

        if ( !class_exists( $api_class ) || !method_exists( $api_class, $method_param ) ) {

        	return __( 'fail whale' );

        } else {

        	$class_param = new $api_class();

        	return $class_param->$method_param( $param ) ;

        }
    }
}


/*
*
* API Activity class
*/
class BM_API_ACTIVITY {

	//returns activity items
	public function get( $param ) {
		global $bp, $activities_template;
		
		$activities = array() ;
				
		if ( bp_has_activities( $param ) ) :
		
			while ( bp_activities() ) : bp_the_activity();

				$user_id 		= bp_get_activity_user_id() ;
				$avatar 		= bp_core_fetch_avatar( 'item_id='. $user_id . '&html=false' );
				$action 		= strip_tags( bp_get_activity_action() );
				$content 		= bp_get_activity_content_body();
				$activity_id 	= bp_get_activity_id();
				$activity_username = bp_core_get_username( bp_get_activity_user_id() );
				$comment_link 	= bp_get_activity_comment_link();
				$comment_count 	= bp_activity_get_comment_count();
				$can_comment	= bp_activity_can_comment();
				$can_favorite	= bp_activity_can_favorite();
				$favorite_link	= bp_get_activity_favorite_link();
				$unfav_link		= bp_get_activity_unfavorite_link();
				$is_favorite	= bp_get_activity_is_favorite();
				$can_delete		= bp_activity_user_can_delete();
				$delete_link	=  wp_nonce_url( bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/delete/' . $activities_template->activity->id, 'bp_activity_delete_link' );
				$more_activity  = bp_activity_has_more_items();
				$get_comments	= BM_API_ACTIVITY::bp_api_activity_comments( $activities_template->activity );
				
				$action = str_replace( $activity_username, '', $action );
							
				$activity = array( 
					'avatar' 		=> $avatar,
					'action' 		=> $action,
					'content' 		=> $content,
					'activity_id'	=> $activity_id,
					'activity_username'	=> $activity_username,
					'user_id'	    => $user_id,
					'comment_link' 	=> $comment_link,
					'comment_count' => $comment_count,
					'can_comment' 	=> $can_comment,
					'comments'	=> $get_comments,
					'can_favorite' 	=> $can_favorite,
					'favorite_link' => $favorite_link,
					'unfav_link' 	=> $unfav_link,
					'is_favorite' 	=> $is_favorite,
					'can_delete'	=> $can_delete,
					'delete_link'	=> $delete_link,				
				);
											
				$activities[] =  $activity  ;
				
				$array = array(
					'activity' => $activities,
					'more_activity' => $more_activity
				);

			
			endwhile;
			
		else :
			
			return __( 'No Activity Found' );
		
		endif ;
		 	
			return $array ;
		
	
	}
	
	//Add activity items
	public function post( $param ) {
		global $bp;
		
		$content = $param['status_content'];
		$user_id = bp_loggedin_user_id();
		$parent_id = $param['parent_id'];
		
		if ( ! is_user_logged_in() )
			return false;
		
		// Record this on the user's profile
		$from_user_link   = bp_core_get_userlink( $user_id );
		$activity_action  = sprintf( __( '%s posted an update', 'buddypress' ), $from_user_link );
		$activity_content = urldecode( $content );
		$primary_link     = bp_core_get_userlink( $user_id, false, true );
		
		// Now write the update
		$activity_id = bp_activity_add( array(
			'user_id'      => $user_id,
			'action'       => apply_filters( 'bp_activity_new_update_action', $activity_action ),
			'content'      => apply_filters( 'bp_activity_new_update_content', $activity_content ),
			'primary_link' => apply_filters( 'bp_activity_new_update_primary_link', $primary_link ),
			'component'    => $bp->activity->id,
			'type'         => 'activity_update'
		) );
		
		return $activity_id ;
	}
		
	//Add activity items
	public function comment( $param ) {
		global $bp;
		
		$content = $param['status_content'];
		$user_id = bp_loggedin_user_id();
		$parent_id = $param['parent_id'];
		
		if ( ! is_user_logged_in() )
			return false;
		
		$comment_content = urldecode( $content );
				
		// Now write the comment
		$activity_id = bp_activity_new_comment( array(
			'id' => false,
			'user_id' => $user_id,
			'content' => apply_filters( 'bp_activity_post_comment_content', $comment_content ),
			'activity_id' => apply_filters( 'bp_activity_post_comment_activity_id', $parent_id ),
			'parent_id'   => false
		));	
			
		return $activity_id ;
		
	}

	
	
	//get comments array
	private function bp_api_activity_comments( $comment ) {
		global $bp, $activities_template;
	
		if ( empty( $comment ) )
			return false;
	
		if ( empty( $comment->children ) )
			return false;
			
		$comments = array();
	
		foreach ( $comment->children as $comment_child ) {
			
			$activities_template->activity->current_comment = $comment_child;
			
			$comment_action = str_replace( $comment_child->user_login, '', $comment_child->action );
			
			$comment_array = array(
				'action' 		=> strip_tags( $comment_action ),
				'id'     		=> $comment_child->id,
				'user_id'		=> $comment_child->user_id,
				'avatar'		=>  bp_core_fetch_avatar( 'item_id='. $comment_child->user_id . '&html=false' ),
				'component' 	=> $comment_child->component,
				'type' 			=> $comment_child->type,
				'content' 		=> $comment_child->content,
				'item_id' 		=> $comment_child->item_id,
				'date_recorded' => $comment_child->date_recorded,
				'hide_sitewide' => $comment_child->hide_sitewide,
				'is_spam' 		=> $comment_child->is_spam,
				'user_nicename' => $comment_child->user_nicename,
				'user_login'	=> $comment_child->user_login,
				'primary_link'	=> $comment_child->primary_link
			);
						
			$comments[] = $comment_array ;
								
		}
				
		return $comments ;
	
	}
}


/*
*
* API Members class
*/
class BM_API_MEMBERS {

	//returns members
	public function get( $param ) {	
		global $bp;
		
		$members = array() ;
				
		if ( bp_has_members( $param ) ) :
		
			while ( bp_members() ) : bp_the_member();
			
			$avatar =  bp_core_fetch_avatar( 'item_id='. bp_get_member_user_id() . '&html=false' );
			$username = bp_get_member_name();
			$link = bp_get_member_permalink();
			$id =  bp_get_member_user_id();
			$last = bp_get_member_last_active();
			
			$member = array( 
				'avatar' => $avatar,
				'username' => $username,
				'link' => $link,
				'id' => $id,
				'last' => $last,							
			);
	
			$members[] = $member ;	
			
			$array = array(
				'members' => $members
				);		
					
		endwhile ;
			
		else :
			
			return __( 'No members Found' );
		
		endif ;
		 	
			return $array ;	
	}
}

	
/*
*
* API Groups class
*/
class BM_API_GROUPS {

	//returns groups
	public function get( $param ) {	
		global $bp,  $groups_template;
						
		$groups = '' ;
				
		if ( bp_has_groups( $param ) ) :
		
			while ( bp_groups() ) : bp_the_group();
			
			$avatar =  bp_core_fetch_avatar( 'item_id='. bp_get_group_id() . '&html=false' );
			$groupname = bp_get_group_name();
			$grouptype = bp_get_group_type();
			$groupadminlink =  bp_get_group_admin_permalink();
			$link = bp_get_group_permalink();
			$id =  bp_get_group_id();
			$slug = bp_get_group_slug();
			$description = bp_get_group_description();
			$status =  bp_get_group_public_status();
			$created =  bp_get_group_date_created();
			
			$group = array( 
				'avatar' => $avatar,
				'groupname' => $groupname,
				'grouptype' => $grouptype,
				'groupadminlink' => $groupadminlink,
				'link' => $link,
				'id' => $id,
				'slug' => $slug,
				'description' => $description,
				'status' => $status,
				'created' => $created,							
			);
	
			$groups[] = $group ;	
			
			$array = array(
				'groups' => $groups
				);		
					
		endwhile ;
			
		else :
			
			return __( 'No groups Found' );
		
		endif ;
		 	
			return $array ;	
	}
		
}	


/*
*
* API Forums class
*/
class BM_API_FORUMS {

	//returns topics
	public function get( $param ) {	
		global $bp,  $forum_template, $topic_template;
						
		$topics = '' ;
		
		if ( bp_has_forum_topics( $param ) ) :
		
			while ( bp_forum_topics() ) : bp_the_forum_topic();
			
			$avatar =  bp_core_fetch_avatar( 'item_id='. bp_get_the_topic_poster_id() . '&html=false' );
			$title 	= bp_get_the_topic_title();
			$id 	= bp_get_the_topic_id();
			$link 	= bp_get_the_topic_permalink();
			$total 	= bp_get_the_topic_total_posts();
			$since 	= bp_get_the_topic_time_since_last_post();
			$posts 	= BP_JSON_API_FORUMS::get_topic_posts( 'topic_id=' . $forum_template->topic->topic_id );
			
			$topic = array( 
				'topic_avatar' => $avatar,
				'topic_title' 	=> $title,
				'total_posts' 	=> $total,
				'since_last' 	=> $since,
				'topic_link' 	=> $link,
				'topic_id' 		=> $id,
				'posts' 		=> $posts,							
			);
	
			$topics[] = $topic ;	
			
			$array = array(
				'topics' => $topics
				);		
					
		endwhile ;
			
		else :
			
			return __( 'No topics Found' );
		
		endif ;
		 	
			return $array ;	
			
			
			
	}
	
	private function get_topic_posts( $param ) {
		global $bp, $topic_template;
		
			$posts = '';
		
		if ( bp_has_forum_topic_posts( $param ) ) :
		
			while ( bp_forum_topic_posts() ) : bp_the_forum_topic_post();
			
				$avatar =  bp_core_fetch_avatar( 'item_id='. $topic_template->post->poster_id . '&html=false' );
				$content = bp_get_the_topic_post_content();
				$postername =  $topic_template->post->poster_name ;
				$postid = bp_get_the_topic_post_id();
				
				$post = array( 
					'post_avatar' => $avatar,
					'post_content' => $content,
					'poster' => $postername,
					'post_id' => $postid,							
				);
		
				$posts[] = $post ;	
				
		
			endwhile ;
			
		else :
			
			return __( 'No posts Found' );
		
		endif ;
		 	
			return $posts ;	
	}
}	

class BM_API_USER {
	
	public function get() {
		global $current_user;
		
		$array = '';
		$user = $current_user;
		
		if ( $user ) {
		
			$array['user']['user_login'] = 'loggedout';
			
		} else {
		
			$avatar =  bp_core_fetch_avatar( 'item_id='. $user->ID . '&type=full&html=false' );
			$token = md5( strtolower( trim( $user->user_email ) . $creds['user_login'] ) );
			
			update_user_meta( $user->ID, 'status_token', $token );
	
			$array['user']['user_login'] = $user->user_nicename;
			$array['user']['user_id']  = $user->ID;
			$array['user']['avatar']  = $avatar ;
			$array['user']['token']  = $token ;
							
		}	
		
		return $array ;
			
	}
	
	public function loggin( $param ) {
		
		$creds = array();
		$array = '';
		
		$creds['user_login'] = $param['username'] ;
		$creds['user_password'] = $param['password'] ;
		$creds['remember'] = true ;
		
		$user = wp_signon( $creds, true ) ;
		
		if ( is_wp_error( $user ) ) {
		
			$array['user']['user_login'] = 'loggedout';
			
		} else {
		
		$avatar =  bp_core_fetch_avatar( 'item_id='. $user->ID . '&type=full&html=false' );
		$token = md5( strtolower( trim( $user->user_email ) . $creds['user_login'] ) );
		
		update_user_meta( $user->ID, 'status_token', $token );

		$array['user']['user_login']  = $user->user_nicename;
		$array['user']['user_id']  = $user->ID;
		$array['user']['avatar']  = $avatar ;
		$array['user']['token']  = $token ;
					
		}	
		
		return  $array ;		
	}
	
	public function logout( $param ) {
		
		if ( $param['fn'] == 'true' ) {
		wp_logout();
		}
		
		$array = '';
		
		$array['user']['user_login'] = 'loggedout';
		
		return $array ;
	}
			
}
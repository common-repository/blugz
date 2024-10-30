<?php
	/**
	 * @package Blugz
	 * @author Andrea Olivato
	 * @version 0.5
	 */
	/*
	
	Plugin Name: Blugz
	Plugin URI: http://blugz.com/wordpress
	Description: Blugz plugin for Wordpress. Easily turn your Wordpress blog into a powerful Google Buzz archive
	Author: Andrea Olivato
	Version: 0.5
	Author URI: http://olivato.me/
	
	
	Blugz Wordpress plugin
    Copyright (C) 2010 Andrea Olivato <andrea@olivato.me>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	*/

	$blugz_cfg = Array(
		'buzz_url' => 'https://www.googleapis.com/buzz/v1/activities/+USER+/@self',
		'buzz_comments_url' => 'https://www.googleapis.com/buzz/v1/activities/+USER+/@self/+POSTID+/@comments?alt=atom',
		'buzz_likes_url' => 'https://www.googleapis.com/buzz/v1/activities/+USER+/@self/+POSTID+/@liked?alt=atom',
		'buzz_single_url' => 'https://www.googleapis.com/buzz/v1/activities/+USER+/@self/+POSTID+?alt=atom',
		'version' => "0.5"
	);

	function blugz_upd() {
		global $blugz_cfg;

		$blugz_lastdownload = get_option('blugz_lastdownload');
		$blugz_mininterval = get_option('blugz_mininterval');
		if ( time() > ($blugz_lastdownload +$blugz_mininterval)) {

			$blugz_username = get_option('blugz_username');
			if(!$blugz_username)
				return;
			$blugz_lastupdate = get_option('blugz_lastupdate');
			$blugz_cfg['buzz_url'] = str_replace('+USER+', $blugz_username, $blugz_cfg['buzz_url']);

			$xml = blugz_load_url($blugz_cfg['buzz_url']);

			@require_once(WP_PLUGIN_DIR . '/blugz/xml2ary.php');
			$array = xml2ary($xml);

			$blugz_feed_lastupdate = strtotime($array['feed']['_c']['updated']['_v']);

			if ($blugz_feed_lastupdate != $blugz_lastupdate) {

				$blugz_category = get_option('blugz_category');
				$blugz_ids = unserialize(get_option('blugz_posted'));
				$blugz_new_ids = array();
				foreach ($array['feed']['_c']['entry'] as $blugz_p) {
					
					$blugz_new_post_md5 = md5($blugz_p['_c']['id']['_v']);
					if(!@in_array($blugz_new_post_md5,$blugz_ids)) {
						
						if(!$blugz_p['_c']['published']['_v'])
							$blugz_p['_c']['published']['_v'] = $blugz_p['_c']['updated']['_v'];

						$blugz_post = array();
						$blugz_post['post_title'] = strip_tags($blugz_p['_c']['title']['_v']);
						$blugz_post['post_content'] = $blugz_p['_c']['content']['_v'];
						$blugz_post['post_status'] = 'publish';
						if (!$blugz_admin_id = get_option('blugz_user')) {
							if ( function_exists('get_bloginfo') ) {
								if($blugz_admin_email = @get_bloginfo('admin_email')) {
									$blugz_lookforadmin = 'SELECT `ID` FROM `wp_users` WHERE `user_email` = "'.$blugz_admin_email.'"';
									$go_bl = @mysql_query($blugz_lookforadmin);
									$fe_bl = @mysql_fetch_row($go_bl);
									$blugz_admin_id = $fe_bl[0];
								}
							}
							if (!$blugz_admin_id)
								$blugz_admin_id = 1;
						}
						$blugz_post['post_author'] = $blugz_admin_id;
						$blugz_post['post_category'] = $blugz_category;
						$blugz_post['post_date'] = date('Y-m-d H:i:s',strtotime($blugz_p['_c']['published']['_v']));
						$blugz_post['post_date_gmt'] = date('Y-m-d H:i:s',strtotime($blugz_p['_c']['published']['_v']));
						
						$blugz_new_post_id = wp_insert_post( $blugz_post );
						if($blugz_new_post_id) {
							$blugz_new_md5s[] = $blugz_new_post_md5;

							$blugz_new_post_media = array();
							foreach($blugz_p['_c']['link'] as $blugz_link) {
								if ($blugz_link['_a']['rel'] == 'replies') {
									update_post_meta($blugz_new_post_id, 'blugz_comments_url', $blugz_link['_a']['href']);
									update_post_meta($blugz_new_post_id, 'blugz_comments_count', $blugz_link['_a']['thr:count']);
								} elseif($blugz_link['_a']['rel'] == 'enclosure') {
									$blugz_new_post_media[] = $blugz_link['_a'];
								} elseif($blugz_link['_a']['rel'] == 'alternate') {
									update_post_meta($blugz_new_post_id, 'blugz_original_url', $blugz_link['_a']['href']);
								} elseif($blugz_link['_a']['rel'] == 'http://schemas.google.com/buzz/2010#liked') {
									update_post_meta($blugz_new_post_id, 'blugz_likes_count', $blugz_link['_a']['buzz:count']);
								}
							}
							update_post_meta($blugz_new_post_id, 'blugz_media_urls', serialize($blugz_new_post_media));
							update_post_meta($blugz_new_post_id, 'blugz_buzz_id', $blugz_p['_c']['id']['_v']);
							if($blugz_p['_c']['georss:point']['_v']) {
								$blugz_coordinates = explode(" ",$blugz_p['_c']['georss:point']['_v']);
								update_post_meta($blugz_new_post_id, 'geo_latitude', $blugz_coordinates[0]);	
								update_post_meta($blugz_new_post_id, 'geo_longitude', $blugz_coordinates[1]);	
								update_post_meta($blugz_new_post_id, 'geo_public', true);
							}
						}
					}

				}

				foreach ($blugz_new_md5s as $blugz_t) {
					$blugz_ids[] = $blugz_t;
				}

				update_option('blugz_posted', serialize($blugz_ids));
				update_option('blugz_lastupdate', $blugz_feed_lastupdate);
			}

			update_option('blugz_lastdownload', time());
		}
	}

	function blugz_js() {
		wp_enqueue_script('blugz_js', WP_PLUGIN_URL . '/blugz/blugz.js',array('jquery'),$blugz_cfg['version']);
	}

	function blugz_css() {
		wp_enqueue_style('blugz_css', WP_PLUGIN_URL . '/blugz/blugz.css',array(),$blugz_cfg['version']);
	}

	function blugz_header() {
		blugz_js();
		blugz_css();
	}

	function blugz_post($content) {
		global $post;
		
		if (strpos($content,"<a class=\"geolocation-link\"")) {
			$content = preg_replace(
				'/<p><a class="geolocation-link" href="#" ([^>]*)>Posted from ([^<]*)<\/a><br\/><br\/>/',
				'<p class="geolocation-p"><a class="geolocation-link" href="#" $1>Posted from <strong>$2</strong></a></p>',
				$content
			);	
		}
			
		if(is_single()) {
			$blugz_customs = get_post_custom();
			
			$blugz_likes = unserialize($blugz_customs['blugz_likes'][0]);
			
			$content .= '
				<div id="blugz_likes">
			';
			
			if($blugz_customs['blugz_likes_count'][0] && is_array($blugz_likes)) {
				
				
				if($blugz_customs['blugz_likes_count'][0]>1)
					$content .= '
							<h3 class="blugz_likes">'.$blugz_customs['blugz_likes_count'][0].' people like this</h3>
					';
				elseif($blugz_customs['blugz_likes_count'][0]==1)
					$content .= '
							<h3 class="blugz_likes">One person likes this</h3>
					';
				
				$blugz_likes_content = Array();
				foreach($blugz_likes as $b_like) {
					$blugz_likes_content[] = '<a href="'.$b_like['url'].'" target="_Blank" rel="external nofollow" title="'.$b_like['name'].'">'.$b_like['name'].'</a>';
				}
				$content .= '<div class="blugz_likes">'.implode(", ",$blugz_likes_content)."</div>";
			}
			
			$content .='
				</div>
			';
			
			$blugz_medias = unserialize(unserialize($blugz_customs['blugz_media_urls'][0]));			
			if($blugz_medias && is_array($blugz_medias)) {
				$content .= '
					<div id="blugz_media">
						<h3 class="blugz_attachments">Media &amp; Attachments</h3>
				';
				$content_a = '';
				$blugz_count_i = 1;
				foreach($blugz_medias as $blugz_media) {
					
					if ( strpos($blugz_media['type'],"html") ) {
						if(!$blugz_media['title'])
							$blugz_media['title'] = $blugz_media['href'];
						$content .= '<a class="blugz_link" href="'.$blugz_media['href'].'" rel="external nofollow" target="_blank">'.$blugz_media['title'].'</a><br />';
					} elseif ( strpos($blugz_media['type'],"mage") ) {
						$content_a .= '<a href="'.$blugz_media['href'].'" rel="external nofollow" target="_Blank">';
						$content_a .= '<img class="blugz_media_thumb" src="'.WP_PLUGIN_URL.'/blugz/image.php?number='.($blugz_count_i-1).'&orig='.urlencode($blugz_customs['blugz_original_url'][0]).'&url='.urlencode($blugz_media['href']).'"></a>';
						if($blugz_count_i%3==0)
							$content_a .= '<br style="clear:left;"/>';
						$blugz_count_i++;
					}
					
				}
				$content .= '<br />'.$content_a;
				$content .= '<div class="blugz_spacer"></div>';
				$content .= '
					</div>
				';
			}


			$blugz_cached_comments = unserialize($blugz_customs['blugz_comments'][0]);
			$content .= '
				<div id="blugz_comments">
			';
			if ($blugz_cached_comments) {
				$content .= '
						<h3 class="blugz_comments">'.$blugz_customs['blugz_comments_count'][0].' comments so far</h3>';
				$content .= '
							<p><a href="'.$blugz_customs['blugz_original_url'][0].'" class="blugz_leavecomment">Leave a comment on Buzz</a></p>
				';
				$k = 0;
				foreach ($blugz_cached_comments as $c) {
					$content .= '<div class="blugz_comment">'.$c."</div>";
					$k++;
				}
				if($k > 5)
					$content .= '
							<p><a href="'.$blugz_customs['blugz_original_url'][0].'" class="blugz_leavecomment">Leave a comment on Buzz</a></p>
					';
				
			}
			$content .= '
				</div>
				<script type="text/Javascript">
					blugz_update_post("'.admin_url('admin-ajax.php').'",'.$post->ID.');
				</script>
			';


		}
		return $content;
	}

	function blugz_update_post() {
		global $blugz_cfg;
		
		$blugz_username = get_option('blugz_username');
		$blugz_customs = get_post_custom($_POST['post_id']);
		$blugz_post_id = $blugz_customs['blugz_buzz_id'][0];
		
		/* Fallback for old versions */
		if(!$blugz_post_id) {
			$blugz_old_comments = explode('/',$blugz_customs['blugz_comments_url'][0]);
			$blugz_old_id = $blugz_old_comments[(sizeof($blugz_old_comments)-1)];
			$blugz_post_id = 'tag:google.com,2010:buzz:'.$blugz_old_id;
			update_post_meta($_POST['post_id'], 'blugz_buzz_id', $blugz_post_id);
		}
		/* End of fallback */
		
		/* Update comments */
		$blugz_comments_url = str_replace(Array('+USER+','+POSTID+'),Array($blugz_username,$blugz_post_id),$blugz_cfg['buzz_comments_url']);
		$xml = blugz_load_url($blugz_comments_url);
		@require_once(WP_PLUGIN_DIR . '/blugz/xml2ary.php');
		$array = xml2ary($xml);
		$blugz_comments = array();
		if($array['feed']['_c']['entry']['_c']) {
		
			$c = $array['feed']['_c']['entry'];
			$blugz_comments[] = '<a href="'.$c['_c']['author']['_c']['uri']['_v'].'">'.$c['_c']['author']['_c']['name']['_v'].'</a> : '.
								strip_tags($c['_c']['content']['_v'],'<a>');
			$blugz_comments_count = 1;
		} else {
			if(is_array($array['feed']['_c']['entry'])) {
				foreach ($array['feed']['_c']['entry'] as $c) {
					$blugz_comments[] = '<a href="'.$c['_c']['author']['_c']['uri']['_v'].'">'.$c['_c']['author']['_c']['name']['_v'].'</a> : '.
					strip_tags($c['_c']['content']['_v'],'<a>');
				}
				$blugz_comments_count = sizeof($array['feed']['_c']['entry']);
			} else {
				$blugz_comments_count = 0;
			}
		}
		update_post_meta($_POST['post_id'], 'blugz_comments', $blugz_comments);
		update_post_meta($_POST['post_id'], 'blugz_comments_count', $blugz_comments_count);
		
		/* Update geo */
		$blugz_entry_url = str_replace(Array('+USER+','+POSTID+'),Array($blugz_username,$blugz_post_id),$blugz_cfg['buzz_single_url']);
		$xml = blugz_load_url($blugz_entry_url);
		@require_once(WP_PLUGIN_DIR . '/blugz/xml2ary.php');
		$array = xml2ary($xml);
		if($blugz_coordinates) {
			$blugz_coordinates = explode(" ",$array['entry']['_c']['georss:point']['_v']);
			update_post_meta($_POST['post_id'], 'geo_latitude', $blugz_coordinates[0]);	
			update_post_meta($_POST['post_id'], 'geo_longitude', $blugz_coordinates[1]);	
			update_post_meta($_POST['post_id'], 'geo_public', true);
		}
		
		/* Update likes */
		$blugz_entry_url = str_replace(Array('+USER+','+POSTID+'),Array($blugz_username,$blugz_post_id),$blugz_cfg['buzz_likes_url']);
		$xml = blugz_load_url($blugz_entry_url);
		@require_once(WP_PLUGIN_DIR . '/blugz/xml2ary.php');
		$array = xml2ary($xml);
		$blugz_likes = Array();
		if($array['response']['_c']['entry']['_c']) {
			$like = $array['response']['_c']['entry'];
			$blugz_likes[] = Array(
					'id' => $like['_c']['id']['_v'],
					'name' => $like['_c']['displayName']['_v'],				
					'url' => $like['_c']['profileUrl']['_v']
				);
			$blugz_likes_count = 1;
		} else {
			if(is_array($array['response']['_c']['entry'])) {
				foreach($array['response']['_c']['entry'] as $like) {
					$blugz_likes[] = Array(
						'id' => $like['_c']['id']['_v'],
						'name' => $like['_c']['displayName']['_v'],				
						'url' => $like['_c']['profileUrl']['_v']
					);
				}
				$blugz_likes_count = sizeof($array['response']['_c']['entry']);
			} else {
				$blugz_likes_count = 0;
			}				
		}
		update_post_meta($_POST['post_id'], 'blugz_likes', $blugz_likes);	
		update_post_meta($_POST['post_id'], 'blugz_likes_count', $blugz_likes_count);	
		
		
		/* Output */
		if(!function_exists('json_encode'))
			@require_once(WP_PLUGIN_DIR . '/blugz/json.php');
			
		
		echo json_encode(
			Array(
				'comments' 			=> $blugz_comments,
				'comments_count' 	=> $blugz_comments_count,
				'likes'				=> $blugz_likes,
				'likes_count'		=> $blugz_likes_count,
				'original_url'		=> $blugz_customs['blugz_original_url'][0]
			)
		);
		
		die;
	}

	function blugz_load_url($blugz_url) {
		if(function_exists('curl_exec')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $blugz_url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$xml = curl_exec($ch);
			curl_close($ch);
		} else {
			$xml = file_get_contents($blugz_url);
		}
		return $xml;
	}

	function blugz_admin() {
		?>
			<div class="wrap">
				<h2>Blugz Settings</h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'blugz_admingroup' ) ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Buzz Username</th>
							<td><input type="text" name="blugz_username" value="<?php echo get_option('blugz_username'); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row">Category for new Posts</th>
							<td>
								<select name="blugz_category">
									<option value="0"><?php _e('Choose the target Category') ?></option>
									<option value="0">---------</option>
									<?php
										$blugz_cats = get_categories('hide_empty=0&orderby=name');
										foreach ($blugz_cats as $blugz_c ) {
											if($blugz_c->cat_ID == get_option('blugz_category'))
												$s = 'selected';
											else
												$s = '';
											?>
									<option value="<?=$blugz_c->cat_ID ?>" <?=$s ?>><?=$blugz_c->cat_name ?></option>
											<?php
										}
									?>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Minumum Refresh Interval</th>
							<td>
								<select name="blugz_mininterval">
									<option value="60" <?php if(get_option('blugz_mininterval')==60) {echo "selected";}?>><?php echo _e('1 Minute'); ?></option>
									<option value="360" <?php if(get_option('blugz_mininterval')==360) {echo "selected";}?>><?php echo _e('6 Minutes'); ?></option>
									<option value="900" <?php if(get_option('blugz_mininterval')==900) {echo "selected";}?>><?php echo _e('15 Minutes'); ?></option>
									<option value="1800" <?php if(get_option('blugz_mininterval')==1800) {echo "selected";}?>><?php echo _e('Half an Hour'); ?></option>
									<option value="3600" <?php if(get_option('blugz_mininterval')==3600) {echo "selected";}?>><?php echo _e('1 Hour'); ?></option>
									<option value="18000" <?php if(get_option('blugz_mininterval')==18000) {echo "selected";}?>><?php echo _e('5 Hours'); ?></option>
									<option value="43200" <?php if(get_option('blugz_mininterval')==43200) {echo "selected";}?>><?php echo _e('Twice a Day'); ?></option>
									<option value="86400" <?php if(get_option('blugz_mininterval')==86400) {echo "selected";}?>><?php echo _e('Once a Day'); ?></option>
								</select>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row">Assign posts to</th>
							<td>
								<?php get_option('blugz_user') ?>
								<?php
									$args = array(
										'show_option_all'  => '',
										'show_option_none' => '',
										'orderby'          => 'display_name',
										'order'            => 'ASC',
										'include'          => '',
										'exclude'          => '',
										'multi'            => 0,
										'show'             => 'display_name',
										'echo'             => 1,
										'selected'         => get_option('blugz_user'),
										'name'             => 'blugz_user',
										'class'            => ''
									);
									wp_dropdown_users($args);
								?>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
					</p>
				</form>
			</div>
		<?php
	}

	function blugz_menu() {
		add_options_page('Blugz', 'Blugz', 'administrator', 'blugz', 'blugz_admin');
	}

	function blugz_register() {
		register_setting( 'blugz_admingroup', 'blugz_mininterval' );
		register_setting( 'blugz_admingroup', 'blugz_username' );
		register_setting( 'blugz_admingroup', 'blugz_category' );
		register_setting( 'blugz_admingroup', 'blugz_user' );
	}
	
	function blugz_activate() {
		global $wpdb;
		$query = 'DELETE FROM '.$wpdb->postmeta.' WHERE meta_key = "blugs_geochecked"';
		$wpdb->query($query);
	}

	/* Actions & Filters */
	add_action('wp_footer','blugz_upd', 10);
	add_action('init', 'blugz_header');
	add_filter('the_content', 'blugz_post');
	add_action('wp_ajax_blugz_update_post', 'blugz_update_post');
	add_action('wp_ajax_nopriv_blugz_update_post', 'blugz_update_post');
	add_action('admin_menu', 'blugz_menu');
	add_action('admin_init', 'blugz_register' );
	register_activation_hook(__FILE__,'blugz_activate');
	
?>

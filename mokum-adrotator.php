<?php
/**
 * Plugin Name: Mokum AdRotator
 * Plugin URI: http://www.mokummusic.com
 * Description: Rotate multiple types of ads. Image, SWF, IFRAME, HTML.
 * Version: 0.1.03
 * Author: Neil Foster
 * Author URI: http://www.mokummusic.com
 * License: GPL2
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function mokum_adrotator_activate() {

}

function mokum_adrotator_deactivate() {
	delete_option( 'mokum_adrotator_settings' );

}

register_activation_hook( __FILE__, 'mokum_adrotator_activate' );
register_deactivation_hook( __FILE__, 'mokum_adrotator_deactivate' );

// all the wp_admin based stuff
class adverts_posttype {

	function __construct() {
		add_action('init',array($this,'create_post_type'));
		add_action('init',array($this,'create_taxonomies'));
		add_action('post_row_actions', array($this,'remove_quickedit_actions'), 10, 2 );
		add_action('manage_mar_adverts_posts_columns',array($this,'columns'),10,2);
		add_action('manage_mar_adverts_posts_custom_column',array($this,'column_data'),11,2);
		add_filter('manage_edit-mar_adverts_sortable_columns', array($this,'sortable_columns' ));
		add_action('pre_get_posts', array($this,'columns_orderby'));
		add_action('restrict_manage_posts', array($this,'wpc_add_taxonomy_filters' ));
		add_action('save_post', array($this,'ad_type_save'));
		add_action('add_meta_boxes', array($this,'ad_type_add_image_meta_box'));
		add_action('add_meta_boxes', array($this,'ad_type_add_text_meta_box'));
		add_action('add_meta_boxes', array($this,'ad_type_add_meta_box'));
		add_action('add_meta_boxes', array($this,'ad_type_add_href_meta_box'));
		add_action('add_meta_boxes', array($this,'ad_type_add_preview_meta_box'));
		add_action( 'admin_enqueue_scripts', array($this,'enqueue_scripts'));
		add_filter('enter_title_here', array($this,'change_enter_title_here'),10,1);
		add_action( 'wp_ajax_ar_href', array($this, 'ajax_prettylink'));
	}

	function enqueue_scripts($hook) {
		global $post_type;
		if (($_GET['post_type'] == 'mar_adverts') || ($post_type == 'mar_adverts')) {
			wp_register_style('mAdRotatorAdminStyle', plugins_url('/style/adrotator-admin.css', __FILE__ ),'','0.1.01');
			wp_enqueue_style('mAdRotatorAdminStyle');
			wp_register_script('mAdRotatorAdminScript', plugins_url('/js/adrotator-admin.js', __FILE__ ), array('jquery'),'0.1.01');
			wp_localize_script( 'mAdRotatorAdminScript', 'marvars', 
			array (
				'mar_nonce' => wp_create_nonce('mar-admin-nonce')
				));
			wp_enqueue_script('mAdRotatorAdminScript');
			wp_enqueue_media();
		}
	}

	function ajax_prettylink() {
		if (!isset($_POST['mar_nonce']) || !wp_verify_nonce($_POST['mar_nonce'], 'mar-admin-nonce' )) die('<:ERROR:> Permissions Error.');

		if( !is_plugin_active('pretty-link/pretty-link.php')) die("<:ERROR:> Problem Detecting PrettyLink");

		$slug = '';
		$href = filter_var($_POST['href'], FILTER_SANITIZE_URL);
		if (filter_var($href, FILTER_VALIDATE_URL) == false) die('<:ERROR:> URL Validation Error');

		if (parse_url($href)['host'] === $_SERVER['HTTP_HOST']) $slug = ltrim(parse_url($href)['path'], '/');

		if ($slug === 'go/') die('<:ERROR:> Append a unique slug!');

		$plink_array = prli_get_all_links();

		foreach ($plink_array as $plink) {
			if ( $plink['slug'] == ltrim(parse_url($href)['path'], '/')) die('<:EXISTS:>');
				if ( $plink['url'] == $href ) {
					echo get_home_url('','/').$plink['slug'];
					die();
				}
		}

		if ($slug) {

			$href = filter_var($_POST['parenthref'], FILTER_SANITIZE_URL);
			if (filter_var($href, FILTER_VALIDATE_URL) == false) die('<:ERROR:> Parent URL Validation Error ('.$href.')');

			if ( !preg_match('/[^\w.-]/', basename($slug))) {

				if( $pretty_link = prli_create_pretty_link( $href, $slug, $_POST['alt']) ) {
					$href = prli_get_pretty_link_url($pretty_link);
					echo '<:SUCCESS:> '.$href;
				} else {
					echo '<:ERROR:> '.$href.'-'.$slug.'-'.$_POST['alt'];
					foreach($prli_error_messages as $prli_error_message) {
						echo $prli_error_message . '; ';
					}
					die();
				}

			} else {
				echo ("<:ERROR:> Pretty Link slug invalid.");
				die();
			}

		} 
		die();
	}

	function change_enter_title_here($title) {
		$screen = get_current_screen();
		if  ( 'mar_adverts' == $screen->post_type ) {
			$title = 'Enter Group (Ads with the same group are limited to one per page)';
		}
		return $title;
	}

	function create_post_type() {
		$labels = array(
			'name' => 'Adverts',
			'singular_name' => 'Advert',
			'add_new' => 'Add New',
			'all_items' => 'All Adverts',
			'add_new_item' => 'Add New Advert',
			'edit_item' => 'Edit Advert',
			'new_item' => 'New Advert',
			'view_item' => 'View Advert',
			'search_items' => 'Search Advert',
			'not_found' =>  'No Adverts found',
			'not_found_in_trash' => 'No Adverts found in trash',
			'parent_item_colon' => 'Parent Advert:',
			'menu_name' => 'Adverts'
			);
		$args = array(
			'labels' => $labels,
			'description' => "",
			'public' => false,
			'show_ui' => true,
			'show_in_nav_menus' => false,
			'show_in_menu' => true,
			'show_in_admin_bar' => true,
			'menu_position' => 25,
			'menu_icon' => 'dashicons-admin-appearance',
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array('title'),
			'has_archive' => true,
			'rewrite' => false,
			'query_var' => true,
			'can_export' => true
			);
		register_post_type('mar_adverts',$args);
	}

	function create_taxonomies() {

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => 'Ad Categories',
			'singular_name'     => 'Ad Category',
			'search_items'      => 'Search Ad Categories',
			'all_items'         => 'All Ad Categories',
			'parent_item'       => 'Parent Ad Category',
			'parent_item_colon' => 'Parent Ad Category:',
			'edit_item'         => 'Edit Ad Category',
			'update_item'       => 'Update Ad Category',
			'add_new_item'      => 'Add New Ad Category',
			'new_item_name'     => 'New Ad Category Name',
			'menu_name'         => 'Ad Categories',
			);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => false,
			);

		register_taxonomy('mar_adverts_cats',array('mar_adverts'),$args);

		// Add new taxonomy, make it non-hierarchical (like tags)
		$labels = array(
			'name'              => 'Ad Tags',
			'singular_name'     => 'Ad Tag',
			'search_items'      => 'Search Ad Tags',
			'all_items'         => 'All Ad Tags',
			'parent_item'       => 'Parent Ad Tag',
			'parent_item_colon' => 'Parent Ad Tag:',
			'edit_item'         => 'Edit Ad Tag',
			'update_item'       => 'Update Ad Tag',
			'add_new_item'      => 'Add New Ad Tag',
			'new_item_name'     => 'New Ad Tag Name',
			'menu_name'         => 'Ad Tags',
			);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => false,
			);

		register_taxonomy('mar_adverts_tags',array('mar_adverts'),$args);
	}

	function remove_quickedit_actions( $actions, $post ) {
	  if ( 'mar_adverts' == $post->post_type )
	    unset( $actions['inline hide-if-no-js'] );
	  return $actions;
	}

	function columns($columns) {
		unset($columns['date']);
		unset($columns['taxonomy-mar_adverts_attribute']);
		unset($columns['comments']);
		unset($columns['author']);
		$columns['title'] = 'Group ID';
		return array_merge(
			$columns,
			array(
				'ar_ad_type' => 'Ad Type',
				'ar_params' => 'Responsiveness',
				'ar_image' => 'Preview'
				));
	}

	function column_data($column,$post_id) {
		switch($column) {
			case 'ar_ad_type' :
			echo get_post_meta($post_id,'_ad_type_select_ad_type',1);
			break;
			case 'ar_params' :

			if (get_post_meta($post_id,'_ad_responsive',1)==1) {
				echo 'Width range '. get_post_meta($post_id,'_ad_type_min_width',1) . 'px - ' . get_post_meta($post_id,'_ad_type_max_width',1).'px<br>';
			} else {
				echo 'Fixed Width '.get_post_meta($post_id,'_ad_type_min_width',1).'px<br>';
			}
			if (get_post_meta($post_id,'_ad_type_width',1)) {
				echo 'Size: '. get_post_meta($post_id,'_ad_type_width',1) . ' x ' . get_post_meta($post_id,'_ad_type_height',1) .'px<br>';
			}
			echo get_post_meta($post_id,'_ad_disable_on_mobile',1)==1?'Disabled on Mobile Devices':'';
			break;
			case 'ar_image' :
			if (get_post_meta($post_id,'_ad_type_select_ad_type',1) === 'Image' && get_post_meta($post_id,'_ad_type_image_url',1) ) {
				echo '<img class="preview-img-col" src="'.get_post_meta($post_id,'_ad_type_image_url',1).'" />';
			} else if (get_post_meta($post_id,'_ad_type_text',1) ) {
				echo '<div style="overflow:hidden;width:300px;">'. htmlspecialchars_decode( get_post_meta($post_id,'_ad_type_text',1)) . '</div>'; 
			}
			break;

			default:
			break;
		}
	}
	
	function sortable_columns( $columns ) {

		if (!is_admin()) return $columns;

		$columns['ar_ad_type'] = 'ar_ad_type';
		$columns['taxonomy-mar_adverts_cats'] = 'taxonomy-mar_adverts_cats';
		$columns['taxonomy-mar_adverts_tags'] = 'taxonomy-mar_adverts_tags';
		return $columns;
	}

	function columns_orderby( $query ) {
	    if( ! is_admin() ) return;
	 
	    $orderby = $query->get( 'orderby');
	 
	    if( 'taxonomy-mar_adverts_cats' == $orderby ) {
	        
	        $query->set('meta_query', array (
	        	'key' => 'mar_adverts_cats'));
	        $query->set('orderby','meta_query');

	    }
	}

 // add filtering option
	function wpc_add_taxonomy_filters() {
		global $typenow;

		$taxonomies = array('mar_adverts_cats', 'mar_adverts_tags');

		if( $typenow == 'mar_adverts' ){

			foreach ($taxonomies as $tax_slug) {
				$tax_obj = get_taxonomy($tax_slug);
				$tax_name = $tax_obj->labels->name;
				$terms = get_terms($tax_slug);
				if(count($terms) > 0) {
					echo '<select name="'.$tax_slug.'" id="'.$tax_slug.'" class="postform">';
					echo '<option value="">Show All '.$tax_name.'</option>';
					foreach ($terms as $term) {
						$selected = $_GET[$tax_slug] == $term->slug ? 'selected' : '';
						echo '<option value="'. $term->slug.'" ' . $selected . ' >' . $term->name .' (' . $term->count .')</option>';
					}
					echo "</select>";
				}
			}
		}
	}

	function ad_type_get_meta( $value ) {
		global $post;

		$field = get_post_meta( $post->ID, $value, true );
		if ( ! empty( $field ) ) {
			return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
		} else {
			return false;
		}
	}

	function ad_type_add_meta_box() {
		add_meta_box(
			'ad_type-ad-type',
			__( 'Ad Type', 'ad_type' ),
			array($this, 'ad_type_html'),
			'mar_adverts',
			'normal',
			'high'
			);
	}

	function ad_type_add_image_meta_box() {
		add_meta_box(
			'ad_type-ad-type_image',
			__( 'Advert Image', 'ad_image' ),
			array($this, 'ad_type_html_image'),
			'mar_adverts',
			'normal',
			'default'
			);
	}

	function ad_type_add_text_meta_box() {
		add_meta_box(
			'ad_type-ad-type_text',
			__( 'Advert Content', 'ad_text' ),
			array($this, 'ad_type_html_text'),
			'mar_adverts',
			'normal',
			'default'
			);
	}

	function ad_type_add_href_meta_box() {
		add_meta_box(
			'ad_type-ad-type_href',
			__( 'Advert Link (href)', 'ad_href' ),
			array($this, 'ad_type_html_href'),
			'mar_adverts',
			'normal',
			'high'
			);
	}

	function ad_type_add_preview_meta_box() {
		add_meta_box(
			'ad_type-ad-type_preview',
			__( 'Advert Preview', 'ad_preview' ),
			array($this, 'ad_type_html_preview'),
			'mar_adverts',
			'normal',
			'default'
			);
	}

	function ad_type_html( $post) {
		wp_nonce_field( '_ad_type_nonce', 'ad_type_nonce' ); ?>
		<p>
			<ul class="adrotator-li type"><li>
		<label for="ad_type_select_ad_type"><?php _e( 'Select Ad Type', 'ad_type' ); ?></label><br>
		<select name="ad_type_select_ad_type" id="ad_type_select_ad_type">
			<option <?php echo ($this->ad_type_get_meta( '_ad_type_select_ad_type' ) === 'Image' ) ? 'selected' : '' ?>>Image</option>
			<option <?php echo ($this->ad_type_get_meta( '_ad_type_select_ad_type' ) === 'Text' ) ? 'selected' : '' ?>>Text</option>
			<option <?php echo ($this->ad_type_get_meta( '_ad_type_select_ad_type' ) === 'Script/HTML' ) ? 'selected' : '' ?>>Script/HTML</option>
		</select></li>
		</ul>
		<div id="ad-width-limits">
			<input type="hidden" name="ad_responsive" value="0" />
			<input type="checkbox" name="ad_responsive" id="ad_responsive" value="1" <?php if ( 1 == $this->ad_type_get_meta( '_ad_responsive') ) echo 'checked="checked"'; ?>>
			<span> Allow a variable width</span><span id="between" style="display:<?php echo $this->ad_type_get_meta( '_ad_responsive')==1?'inline-block':'none'; ?>;">
			<span>, between</span>
			<input type="number" min="0" max="1600" name="ad_type_min_width" id="ad_type_min_width" value="<?php echo $this->ad_type_get_meta( '_ad_type_min_width' ); ?>">
			<span>and</span>
			<input type="number" min="0" max="1600" name="ad_type_max_width" id="ad_type_max_width" value="<?php echo $this->ad_type_get_meta( '_ad_type_max_width' ); ?>">
			pixels</span>
			<br>
			<input type="hidden" name="ad_disable_on_mobile" value="0" />
			<input type="checkbox" name="ad_disable_on_mobile" id="ad_disable_on_mobile" value="1" <?php if ( 1 == $this->ad_type_get_meta( '_ad_disable_on_mobile') ) echo 'checked="checked"'; ?>>
			<span> Disable this ad. on mobile devices</span><br>
		<div id="image-info"><strong>Size: </strong>
		<input type="number" max="1600" min="0" name="ad_img_width_input" class="ad_img_width_input" id="ad_img_width_input" value="<?php echo $this->ad_type_get_meta( '_ad_type_width' ); ?>">
		<span> pixels wide, by </span>
		<input type="number" max="1600" min="0" name="ad_img_height_input" class="ad_img_height_input" id="ad_img_height_input" value="<?php echo $this->ad_type_get_meta( '_ad_type_height' ); ?>">
		<span> pixels high.</span></div>
		</div>
		</p><?php
	}

	function ad_type_html_image( $post) {
		?>
		<span id="ad-image-note">Enter the location of the ad image here. Alternatively, upload or choose one in the WordPress media library.</span>
		<p>
			<ul class="adrotator-li"><li>
		<label for="ad_type_image_url">Paste image SRC location here &rarr; </label>
		<input type="text" name="ad_type_image_url" id="ad_type_image_url" value="<?php echo $this->ad_type_get_meta( '_ad_type_image_url' ); ?>">
			</li><li><button class="button button-primary" id="ad_type_image_url_ok">Update Preview</button></li> or &nbsp;
		<li><button class="button button-primary" id="mca_tray_button">Choose from Library</button></li><div id="image-alert"></div></ul>
		</p><?php
	}

	function ad_type_html_text( $post) {
		?>
		<span id="ad-text-note">Enter text with only ONE linebreak. 1st line is the headline, 2nd is a sub-headline. A short desciption for the tooltip.</span>
		<p>
			<div id="ad-text-editor">
			<textarea rows="4" id="ad_type_text" name="ad_type_text"><?php echo $this->ad_type_get_meta( '_ad_type_text' ); ?></textarea>
			</div>

		</p><?php
	}

	function ad_type_html_href( $post) {
		?>
		<p>
			<div id="adrotator-href">
			<ul class="adrotator-li"><li class="href">
			<input type="text" name="ad_type_href" id="ad_type_href" value="<?php echo $this->ad_type_get_meta( '_ad_type_href' ); ?>"></li>
			<?php if(is_plugin_active('pretty-link/pretty-link.php')) : ?><li><button style="display:none;" class="button button-primary" id="ad_type_href_ok">Make PrettyLink?</button></li><?php endif; ?><li>&nbsp;<div id="pretty-alert"></div></li></ul>
			</div>
		</p><?php
	}

	function ad_type_html_preview( $post) {
		?>
		<p>
		<div id="ad-image-preview">
		<?php if ( $this->ad_type_get_meta( '_ad_type_select_ad_type' ) === 'Image' && $this->ad_type_get_meta( '_ad_type_image_url' )) :?>
			<?php  echo '<img src="'.$this->ad_type_get_meta( '_ad_type_image_url' ).'" />'; ?>
		<?php elseif ( $this->ad_type_get_meta( '_ad_type_select_ad_type' ) === 'Script/HTML') : ?>
			<div><?php echo  htmlspecialchars_decode( $this->ad_type_get_meta( '_ad_type_text' )); ?></div>
		<?php elseif ( $this->ad_type_get_meta( '_ad_type_select_ad_type' ) === 'Text') : ?>
			<?php $textlines = explode(PHP_EOL, htmlspecialchars_decode( $this->ad_type_get_meta( '_ad_type_text' ))); ?>
			<div class="adrotator-text" id="adrotator-text"><div class="ad-text">
			<h2><a href="<?php echo $this->ad_type_get_meta( '_ad_type_href' ); ?>"><?php echo $textlines[0]; ?></a></h2>
			<p><?php echo $textlines[1]; ?></p>
			</div><a href="<?php echo $this->ad_type_get_meta( '_ad_type_href' ); ?>"><div class="ad-textbutton">></div></a>
			</div>
		<?php endif; ?>
		</div>
		</p><?php
	}

	function ad_type_save( $post_id ) {
		if ( ! isset( $_POST['ad_type_nonce'] ) || ! wp_verify_nonce( $_POST['ad_type_nonce'], '_ad_type_nonce' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post' ) ) return;

		if ( isset( $_POST['post_title'] ) )
			update_post_meta( $post_id, '_ad_type_group', esc_attr( $_POST['post_title'] )); 
		if ( isset( $_POST['ad_type_select_ad_type'] ) )
			update_post_meta( $post_id, '_ad_type_select_ad_type', esc_attr( $_POST['ad_type_select_ad_type'] ) );
		if ( isset( $_POST['ad_type_image_url'] ) )
			update_post_meta( $post_id, '_ad_type_image_url', esc_attr( $_POST['ad_type_image_url'] ) );
		if ( isset( $_POST['ad_img_width_input'] ) )
			update_post_meta( $post_id, '_ad_type_width', esc_attr( $_POST['ad_img_width_input'] ) );
		if ( isset( $_POST['ad_img_height_input'] ) )
			update_post_meta( $post_id, '_ad_type_height', esc_attr( $_POST['ad_img_height_input'] ) );
		if ( isset( $_POST['ad_responsive'] ) ) {
			update_post_meta( $post_id, '_ad_responsive', esc_attr( $_POST['ad_responsive'] ) );
			if ( $_POST['ad_responsive'] == 1) {
				if ( isset( $_POST['ad_type_min_width'] ) )
					update_post_meta( $post_id, '_ad_type_min_width', esc_attr( $_POST['ad_type_min_width'] ) );
				if ( isset( $_POST['ad_type_max_width'] ) )
					update_post_meta( $post_id, '_ad_type_max_width', esc_attr( $_POST['ad_type_max_width'] ) );
			}
		}
		if ( isset( $_POST['ad_type_href'] ) )
			update_post_meta( $post_id, '_ad_type_href', esc_attr( $_POST['ad_type_href'] ) );
		if ( isset( $_POST['ad_type_text'] ) )
			update_post_meta( $post_id, '_ad_type_text', esc_attr( $_POST['ad_type_text'] ) );
		if ( isset( $_POST['ad_disable_on_mobile'] ) )
			update_post_meta( $post_id, '_ad_disable_on_mobile', esc_attr( $_POST['ad_disable_on_mobile'] ) );
	}

}

include_once (plugin_dir_path( __FILE__ ) . 'classes/ad-rotator-class.php');

new adverts_posttype;
new Mokum_AdRotator;

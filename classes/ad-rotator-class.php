<?php
// ******** front end class **********
class Mokum_AdRotator {
	function __construct() {
		if ( is_admin() ) {
			add_action('wp_ajax_ads_call', array($this, 'mar_ajax_ads_call'));
			add_action('wp_ajax_nopriv_ads_call', array($this, 'mar_ajax_ads_call'));
		} else {
		    add_action( 'wp_enqueue_scripts', array($this,'enqueue_scripts'));
		    add_shortcode( 'adrotator', array($this,'adrotator') );
		}	
	}

	function enqueue_scripts() {

		wp_register_style('mAdRotatorStyleSheet', plugins_url('/style/adrotator.css', dirname(__FILE__ )),'','0.0.06');
		wp_enqueue_style('mAdRotatorStyleSheet');

		wp_register_script('mAdRotatorScript', plugins_url('/js/adrotator.js', dirname(__FILE__) ), array('jquery'),'0.0.104', true);
		wp_enqueue_script('mAdRotatorScript');
		$iframe_url = plugins_url( '/adframe', dirname(__FILE__ ));
		wp_localize_script('mAdRotatorScript', 'mAdRotator', array('ajaxurl' => admin_url( 'admin-ajax.php' ), 'mar_nonce' => wp_create_nonce('mar-nonce')));	
	}

	function adrotator($atts) {

		$atts = shortcode_atts( array(
			'type' => 'auto',
			'style' => ''
			), $atts, 'adrotator' );

		$html = '<div class="madrotator" style="'.esc_attr($atts['style']).'"></div>';
		
		return $html;
	}

	function mar_ajax_ads_call() {
		if (!isset($_POST['mar_nonce']) || ! wp_verify_nonce($_POST['mar_nonce'], 'mar-nonce' )) die('Permissions Error.');

		$useragent=$_SERVER['HTTP_USER_AGENT'];
		$mobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4));
		$ad_slots = json_decode(stripslashes_deep($_POST['adslots']));
		$exclude_ids = array(); $dupe_titles = array(); $ad_html = array();

		foreach ($ad_slots as $ad_slot) {

			if (!$mobile) {

				$args = array(
					'post_type' => 'mar_adverts',
					'post__not_in' => $exclude_ids,
					'posts_per_page' => 1,
					'orderby'  => 'rand',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => '_ad_type_min_width',
							'value'   => $ad_slot->width,
							'type'    => 'numeric',
							'compare' => '<=',
						),
						array(
							'key' => '_ad_type_max_width',
							'value'   => $ad_slot->width,
							'type'    => 'numeric',
							'compare' => '>=',
						),
						array(
							'key' => '_ad_type_group',
							'value'   => $dupe_titles,
							'compare' => 'NOT IN',
						),
					),
				);

			} else {

				$args = array(
					'post_type' => 'mar_adverts',
					'post__not_in' => $exclude_ids,
					'posts_per_page' => 1,
					'orderby'  => 'rand',
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key' => '_ad_type_min_width',
							'value'   => $ad_slot->width,
							'type'    => 'numeric',
							'compare' => '<=',
						),
						array(
							'key' => '_ad_type_max_width',
							'value'   => $ad_slot->width,
							'type'    => 'numeric',
							'compare' => '>=',
						),
						array(
							'key' => '_ad_disable_on_mobile',
							'value'   => "1",
							'compare' => '!=',
						),
					),
				);
			}


			$advert = get_posts( $args );
			$exclude_ids[] = $advert[0]->ID;
			$custom_fields = get_post_custom($advert[0]->ID);
			$dupe_titles[] = $custom_fields['_ad_type_group'][0];

			if ($custom_fields['_ad_type_select_ad_type'][0] === 'Image') {
				$ad_html[] = '<a class="ad-image" href="'.esc_url( $custom_fields['_ad_type_href'][0]).'" title="'.esc_attr( $custom_fields['_ad_type_text'][0] ).'"><img src="'. $custom_fields['_ad_type_image_url'][0] .'" alt="'.esc_attr( $custom_fields['_ad_type_text'][0] ).'" /></a>';

			} else if ($custom_fields['_ad_type_select_ad_type'][0] === 'Text') {
				$ad_html[] = '<a class="ad-text" href="'.esc_url( $custom_fields['_ad_type_href'][0]).'" title=""><div style="width:100%;text-align:center;"><img width="100px" src="'.$custom_fields['_ad_type_image_url'][0].'" /></div><div class="ad-text-content">'.$custom_fields['_ad_type_text'][0] .'</div></a>';

			} else if ($custom_fields['_ad_type_select_ad_type'][0] === 'Script/HTML') {
				$ad_html[] = '<div class="ad-script" style=" display: flex; margin-left: -3px; width:'.$custom_fields['_ad_type_width'][0].'px; height:'.$custom_fields['_ad_type_height'][0].'px;">' . htmlspecialchars_decode( $custom_fields['_ad_type_text'][0]) . '</div>';

			} else {
				$ad_html[] = '<div class="no-ad">Debug:'.serialize($custom_fields).'</div>';
			}
		}

		wp_reset_query();

		echo json_encode($ad_html);
			
		die();
		
	}
}

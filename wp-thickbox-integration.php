<?php
/*
Plugin Name: WP Thickbox Integration
Plugin URI: http://www.web-argument.com/wp-thickbox-integration
Description: Integrate Thickbox jquery plugin into your blogs. Open Images and Native Galleries on the same window without touching the html code.
Version: 1.0.2
Author: Alain Gonzalez
Author URI: http://www.web-argument.com
*/


/*****************************************************************************
                     Inserting class name to images
******************************************************************************/

function image_attachment_fields_same($form_fields, $post)
{
  if ( substr($post->post_mime_type, 0, 5) == 'image' ) {
  $form_fields['same-window'] = array(
			'label' => __('Open in the same window'),
			'input' => 'html',
			'html'  => "
				<input type='radio' name='attachments[$post->ID][same-window]' id='same-window' value='yes'  checked='checked'/>
				<label for='same-window'>" . __('Yes') . "</label>
				<input type='radio' name='attachments[$post->ID][same-window]' id='same-window-medium' value='no' />
				<label for='same-window-medium-$post->ID'>" . __('No') . "</label><span class='help'>Remember to include the image LINK URL.</p>",
		);
   }      		
return $form_fields;
 
}

add_filter('attachment_fields_to_edit', 'image_attachment_fields_same', 10, 2);



function get_image_send_to_editor_same($id, $alt, $title, $align, $url='', $rel = false, $size, $same_window) {

	$html = get_image_tag($id, $alt, $title, $align, $size);

	if ( $url ){
	
	if ($same_window =='yes'){
		
		$html = '<a class="thickbox" rel="same-post-'.$_REQUEST['post_id'].'" title = "'.$title.'" href="' . clean_url($url) ."\">$html</a>";
		
	} else {
	
		$html = '<a href="' . clean_url($url) . "\"$rel>$html</a>";
		
	}
	}

	$html = apply_filters( 'image_send_to_editor', $html, $id, $alt, $title, $align, $url, $size );

	return media_send_to_editor($html);
}





function image_media_send_to_editor_same($html, $attachment_id, $attachment) {
	$post =& get_post($attachment_id);
	if ( substr($post->post_mime_type, 0, 5) == 'image' ) {
		$url = $attachment['url'];

		if ( isset($attachment['align']) )
			$align = $attachment['align'];
		else
			$align = 'none';

		if ( !empty($attachment['image-size']) )
			$size = $attachment['image-size'];
		else
			$size = 'medium';

		
		if ( isset($attachment['same-window']) )
			$same_window = $attachment['same-window'];
		else
			$same_window = 'no';

			$rel = ( $url == get_attachment_link($attachment_id) );

		return get_image_send_to_editor_same($attachment_id, $attachment['post_excerpt'], $attachment['post_title'], $align, $url, $rel, $size, $same_window);
	}
	return $html;
}


add_filter('media_send_to_editor', 'image_media_send_to_editor_same', 10, 3);


	
function add_tickbox_integration_css() {

    echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-thickbox-integration/wp-thickbox-integration.css" />';
}

add_action('admin_head', 'add_tickbox_integration_css');



/*****************************************************************************
                     Inserting class name to galleries
******************************************************************************/

function gallery_shortcode_tbi($attr) {
	global $post;

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	extract(shortcode_atts(array(
		'orderby'    => 'menu_order ASC, ID ASC',
		'id'         => $post->ID,
		'itemtag'    => 'dl',
		'icontag'    => 'dt',
		'captiontag' => 'dd',
		'columns'    => 3,
		'size'       => 'thumbnail',
	), $attr));

	$id = intval($id);
	$attachments = get_children("post_parent=$id&post_type=attachment&post_mime_type=image&orderby={$orderby}");

	if ( empty($attachments) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $id => $attachment )
			$output .= wp_get_attachment_link($id, $size, true) . "\n";
		return $output;
	}

	$itemtag = tag_escape($itemtag);
	$captiontag = tag_escape($captiontag);
	$columns = intval($columns);
	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	
	$output = apply_filters('gallery_style', "
		<style type='text/css'>
			.gallery {
				margin: auto;
			}
			.gallery-item {
				float: left;
				margin-top: 10px;
				text-align: center;
				width: {$itemwidth}%;			}
			.gallery img {
				border: 2px solid #cfcfcf;
			}
			.gallery-caption {
				margin-left: 0;
			}
		</style>
		<!-- see gallery_shortcode() in wp-includes/media.php -->
		<div class='gallery'>");

	foreach ( $attachments as $id => $attachment ) {
	//$link = wp_get_attachment_link($id);
		
		$a_img = wp_get_attachment_url($id);
	// Attachment page ID
		$att_page = get_attachment_link($id);
	// Returns array
		$img = wp_get_attachment_image_src($id, $size);
		$img = $img[0];
	// If no caption is defined, set the title and alt attributes to title
		$title = $attachment->post_excerpt;
		if($title == '') $title = $attachment->post_title;
		
		
		$output .= "<{$itemtag} class='gallery-item'>";
		$output .= "
			<{$icontag} class='gallery-icon'>
			
					<a href=\"$a_img\" title=\"$title\" class=\"thickbox\" rel=\"gallery-$post->ID\">
				
					<img src=\"$img\" alt=\"$title\" />
				
					</a>	
				
			</{$icontag}>";
		if ( $captiontag && trim($attachment->post_excerpt) ) {
			$output .= "
				<{$captiontag} class='gallery-caption'>
				{$attachment->post_excerpt}
				</{$captiontag}>";
		}
		$output .= "</{$itemtag}>";
		if ( $columns == 0 )
			$output .= '<br style="clear: both" />';
	}

	$output .= "
			<br style='clear: both;' />
		</div>\n";

	return $output;
}

remove_shortcode('gallery');


add_shortcode('gallery', 'gallery_shortcode_tbi');




function thickbox_css_int() {
	wp_enqueue_style('thickbox', get_bloginfo('wpurl') . '/wp-includes/js/thickbox/thickbox.css');
	wp_print_styles(array('thickbox'));
}

add_action('wp_head', 'thickbox_css_int');
wp_enqueue_script('thickbox');


function thickbox_path_int() {

    $tpi_header =  "\n<!--  WP Thickbox Integration -->\n";	
	$tpi_header .= "<script type=\"text/javascript\">\n";
	$tpi_header .= "\t var tb_pathToImage=\"".get_bloginfo('wpurl')."/wp-includes/js/thickbox/loadingAnimation.gif\";\n";
	$tpi_header .= "\t var tb_closeImage=\"".get_bloginfo('wpurl')."/wp-includes/js/thickbox/tb-close.png\";\n";
	$tpi_header .= "</script>\n";
            
print($tpi_header);
}

add_action('wp_head', 'thickbox_path_int');		

?>
<?php
/*	Seventoon PACT Shortcodes
*/

add_shortcode('seventoon-chapter-list', 'seventoon_pact_category_list');
add_shortcode('seventoon-archive-dropdown','seventoon_pact_archive_dropdown');
add_shortcode('seventoon-latest-comic', 'seventoon_pact_show_latest_comic');
/* 	Utility function
	This normalizes term names or slugs to term_ids
*/
function seventoon_pact_get_term_ids( $list, $tax ){
	$include_list  = explode(',', $list);
	$includes = array(); // holding pen
	foreach( $include_list as $included ){
		$add = $included;
		if (!is_numeric($included)){ // not a number
			if (get_term_by('name', $included, $tax)){ // check if by name
				$add = get_term_by('name', $included, $tax)->term_id;
			} else if (get_term_by('slug', $included, $tax)){ // check if by slug
				$add = get_term_by('slug', $included, $tax)->term_id;
			} else { // not a valid term for tax
				$add = null;
			}
		}
		if (!empty($add)){
			$includes[] = $add; // add it to holding pen
		}
	}
	// cast array back to string
	$list = implode(',', $includes);
	// return it
	return $list;
}
/*	Add a simple list of Comic Chapters anywhere
	Example usage: 	[seventoon-chapter-list] // list with ALL Chapters and sub-chapters in default style
					[seventoon-chapter-list exclude="124,142,143,168"] // list excluding 4 Chapters and all their sub-chapters
*/
function seventoon_pact_category_list( $atts, $content='' ){
	extract( shortcode_atts( array(
	    'include' => '',
		'exclude' => '',
		'emptychaps' => 'hide',
		'order' => 'ASC',
		'orderby' => 'name',
		'postdate' => 'last',
		'title' => 'Chapters',
		'thumbnails' => 'none',
		'postcount'  => false,
		'liststyle'  => 'flat'
	), $atts) );
	// PASSED AS STRINGS NOT BOOLEANS!!
	if ($include != 'all'){
		$include = seventoon_pact_get_term_ids( $include, 'category');
	}
		$exclude = seventoon_pact_get_term_ids( $exclude, 'category');
	// set show/hide empties
	if ($emptychaps == 'hide'){
		$hide_empty = 1;
		$hide_if_empty = true;
	} else {
		$hide_empty = 0;
		$hide_if_empty = false;
	}
	// allow multiples to be on one page
	$uid = wp_unique_id();
	// custom walker to get thumbnails
	$my_walker = new STPACT_CategoryThumbnail_Walker();	
	// Build arguments for drop-down
	$args = array(
	    'include'	    => $include,
		'exclude' 		=> $exclude,
		'exclude_tree' 	=> $exclude,
		'hierarchical'  => 1,
		'depth'			=> 0,
		'hide_empty'    => $hide_empty,
		'hide_if_empty' => $hide_if_empty,
		'walker'		=> $my_walker,
		'taxonomy'		=> 'category',
		  'order'		=> $order,
		  'orderby'     => $orderby,
		  'postdate'    => $postdate,
		'title_li'		=> $title,
		'echo'			=> 0,
		'thumbnails'    => $thumbnails,
		'show_count'	=> $postcount,
		'liststyle'     => $liststyle,
		'type'			=> 'post'
	);
	$output = '<ul id="chapters_list_'.$uid.'" class="chapters-list">';
	// get chapter terms	
//	$terms = get_terms( $args );
	$output .= wp_list_categories( $args ).'</ul>';

	return $output;
}



/*	Add a drop-down list of Comic Chapters anywhere
	Example usage: 	[seventoon-archive-dropdown] // drop-down with ALL Chapters and sub-chapters
					[seventoon-archive-dropdown exclude="124,142,143,168"] // drop-down excluding 4 Chapters and all their sub-chapters
*/
function seventoon_pact_archive_dropdown( $atts, $content='' ){
	extract( shortcode_atts( array(
	    'include' => '',
		'exclude' => '',
		'emptychaps' => true,
		'title' => 'Select Chapter'
	), $atts) );
	// PASSED AS STRINGS NOT BOOLEANS!!
	if ($include != 'all'){
		$include = seventoon_pact_get_term_ids( $include, 'category');
	}
		$exclude = seventoon_pact_get_term_ids( $exclude, 'category');
	// set show/hide empties
	if ($emptychaps == 'hide'){
		$hide_empty = 1;
		$hide_if_empty = true;
	} else {
		$hide_empty = 0;
		$hide_if_empty = false;
	}
	// allow multiples to be on one page
	$uid = wp_unique_id();
	// figure out if we need slug or term_id based on permalink structure

	// Build arguments for drop-down
	$args = array(
	    'include'	    => $include,
		'exclude' 		=> $exclude,
		'exclude_tree' 	=> $exclude,
		'hierarchical'  => 1,
		'depth'			=> 0,
		'hide_empty'    => $hide_empty,
		'hide_if_empty' => $hide_if_empty,
		'show_option_none' => $title,
		'id' 			=> 'comicpost_chapter_drop'.$uid.'',
		'name'			=> 'comicpost_chapter_drop'.$uid.'',
		'taxonomy'		=> 'category',
		'selected'		=> 'chapters',
		'value_field'	=> 'slug',
		'echo'			=> 0
	);
	$select  = wp_dropdown_categories( $args );
	// get chapter terms	
	$terms = get_terms( 'category' );
	if (empty($terms)){
		// if there are no terms dropdown would be empty, so bail...
		return;
	} else {
		// if permalink structure is empty URL ends in ?chapters=slug
		if (empty(get_option('permalink_structure'))){
			$linkfront = explode('=', get_term_link( $terms[0] ));
			$linkfront = $linkfront[0].'=';
		} else {
			if (!empty($terms[0])){
				$linkfront = dirname( get_term_link( $terms[0] ) ).'/'; // its an object, no 2nd param needed
			} else {
				$linkfront = get_option('home').'/categories/';
			}
		}
	}
    $replace = "<select$1 onchange=\"location.href='".$linkfront."'+this.options[this.selectedIndex].value\">";
    $select  = preg_replace( '#<select([^>]*)>#', $replace, $select );

	return $select;
}
/* Simple function to show image of latest comic from a chapter */
function seventoon_pact_show_latest_comic( $atts, $content='' ){
	extract( shortcode_atts( array(
			'chapter' => '',
			'size' => 'large',
			'link'    => true
		), $atts) );
		if ( !empty($chapter) ){
			$chapter = seventoon_pact_get_term_ids( $chapter, 'category' );
		} else {
			return;
		}
		$args = array(
			'showposts' => 1,
			'post_type' => 'post',
			'tax_query' => array(
				array(
					'taxonomy' => 'category',
					'terms'    => $chapter
				)
			),
			'posts_per_page' => '1',
			'order' => 'DESC'
		);
	$latest_post = null;
	$image = array();
	// get first post
	$latest_post_query = new WP_Query( $args );
	$posts = $latest_post_query->get_posts();
	if( !empty( $posts )){
		$latest_post = array_shift( $posts );
	}
	if ($latest_post){
		if (has_post_thumbnail( $latest_post->ID )){
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $latest_post->ID), $size );
		}
	}
	if (!empty($image)){
		$content = '<div class="comic-wrap">';
		if ($link) {
			$content .= '<a href="'.get_permalink( $latest_post->ID ).'" title="Go to latest post from '.get_term($chapter)->name.'">';
		}
		$content .= '<img class="comic" src="'.$image[0].'" width="'.$image[1].'" height="'.$image[2].'" alt="Latest image for '.esc_attr(get_term($chapter)->name).'."/>';
		if ($link) {
			$content .= '</a>';
		}
		$content .= '</div>';
		return $content;
	} else {
		return;
	}
}

// Polyfill for ClassicPress 1.x
if (!function_exists('wp_unique_id')){
	function wp_unique_id( $prefix = '' ) {
		static $id_counter = 0;
		return $prefix . (string) ++$id_counter;
	};
}
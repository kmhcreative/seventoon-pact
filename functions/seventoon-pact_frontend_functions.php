<?php
/*	SevenToon PACT Frontend Functions
 *
*/

function seventoon_pact_scripts_and_styles() {
	wp_enqueue_style( 'seventoon_pact_nav',  seventoon_pact_pluginfo('plugin_url') . 'css/seventoon-pact-nav.css', '', '0.1');
	wp_enqueue_style( 'dashicons' );
}
add_action( 'wp_enqueue_scripts', 'seventoon_pact_scripts_and_styles' );	

/* This makes sure child categories of the "Chapters" category are shown using
   the category-chapters.php template
   
   NOTE: rumor has it the "category_template" filter might get deprecated.
   If that happens change to "archive_template" rename the category_chapters.php
   to archive_chapters.php and change "category" in the function below to "archive"
   but you'll also need to change other functions from "is_category()" to "is_archive()"
   with "!is_date() && !is_author() && !is_tag()" which is why category check is nicer.
*/
function seventoon_pact_get_template_for_chapter_category( $template ){
	if( basename( $template ) === 'category.php' ){ // no custom template for this cat
		// get the current term
		$term = get_queried_object();
		// check for template file for this category
		$slug_template = locate_template( "category-{$term->slug}.php" );
		if ( $slug_template ){
			return $slug_template;
		}
		// if category does not have a template start looking for ancestors that do
		$term_to_check = $term;
		while( $term_to_check->parent ){
			// get the parent of this level's parent
			$term_to_check = get_category( $term_to_check->parent );
			if (!$term_to_check || is_wp_error( $term_to_check )){
				break; // no valid parent found, use category.php or archive.php
			}
			// use locate_template to see if a template exists for ancestor
			$slug_template = locate_template( "category-{$term_to_check->slug}.php");
			// if we find a template return it
			if( $slug_template ){
				return $slug_template;
			}
		}
	}
	return $template;
}
add_filter( 'category_template', 'seventoon_pact_get_template_for_chapter_category' );


function seventoon_pact_category_chapter_to_class($post, $classes = ''){
	if (!empty($post) && $post->post_type == 'post'){
		if (is_category() ){
			$prefix = 'tax';
		} else {
			$prefix = 'story';
		}
		$terms = get_the_category( $post->ID );
		foreach ($terms as $term){
			$classes[] = $prefix.'-'.$term->slug;
			// get root ancestor
			$ancestors = get_ancestors( $term->term_id, 'category' );
			if (!empty($ancestors)){
				$classes[] = $prefix.'-'.$term->slug;
			    foreach ( $ancestors as $ancestor ){
			    	$story = get_term( $ancestor, 'category');
			    	$classes[] = $prefix.'-'.$story->slug;
			    }
			}
		}

	}
	if (!empty($post) && is_page() ){
		$classes[] = 'page-'.$post->post_name;
	}
	return $classes;
};

function seventoon_pact_append_body_class($classes = '') {
	global $post, $wp_query;
	return seventoon_pact_category_chapter_to_class($post, $classes );
};
add_filter('body_class', 'seventoon_pact_append_body_class');

/* Utility Function:
 * Return an array with the family tree of current category 
 */
function seventoon_pact_get_category_chapters( $category ){
	$catObj = get_term_by('slug', $category, 'category');
	$tree = array();
	$tree[] = $category;
	if (!empty($catObj)){
		$ancestors = get_ancestors( $catObj->term_id, 'category');
		if (!empty($ancestors)){
			foreach( $ancestors as $ancestor ){
				$chapter = get_term( $ancestor, 'category');
				$tree[]  = $chapter->slug;
			}
		}
	}
	return $tree;
}

/* Over-ride Archive sort order so posts-as-comics can be read in order */
add_action( 'pre_get_posts', 'seventoon_pact_category_chapters_sort_order'); 
function seventoon_pact_category_chapters_sort_order($query){
	if(!is_admin() && is_archive() && array_intersect(array('comics','chapters'),seventoon_pact_get_category_chapters($query->get('category_name'))) ) {
		//Set the order ASC or DESC
		$query->set( 'order', 'ASC' );
		//Set the orderby
		$query->set( 'orderby', 'date' );
		// adjust number of items to return per page
		$query->set( 'posts_per_page', get_option('posts_per_page') );
	};
};

/* Over-ride Archive sort order for custom CHAPTER TAXONOMY */
add_action( 'pre_get_posts', 'seventoon_pact_new_chapter_sort_order'); 
function seventoon_pact_new_chapter_sort_order($query){
	if(!is_admin() && is_archive() && 
		(
		is_post_type_archive( "comic" ) || is_tax("chapters") 
		||
		is_post_type_archive( "mangapress_comic") || is_tax( "mangapress_series")
		)
	) {
		//Set the order ASC or DESC
		$query->set( 'order', 'ASC' );
		//Set the orderby
		$query->set( 'orderby', 'date' );
		// adjut number of items on General Settings
		$query->set( 'posts_per_page', get_option('posts_per_page') );
	};
};


function seventoon_pact_category_thumbnail($chapter,$firstlast = 'first',$taxonomy = 'category', $post_type = 'post'){
	if ( $firstlast == 'first' ){
		$order = 'ASC';
	} else if ( $firstlast == 'last' ){
		$order = 'DESC';
	} else {
		return;
	}
	$args = array(
		'showposts' => 1,
		'post_type' => $post_type,
		'tax_query' => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $chapter
			)
		),
		'posts_per_page' => '1',
		'order' => $order
	);
	$firstlast_post = null;
	$image = array();
	// get first post
	$firstlast_post_query = new WP_Query( $args );
	$posts = $firstlast_post_query->get_posts();
	if( !empty( $posts )){
		$firstlast_post = array_shift( $posts );
	}
	if ($firstlast_post){
		if (has_post_thumbnail( $firstlast_post->ID )){
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $firstlast_post->ID), 'thumbnail' );
		}
	}
	return $image;
}

class STPACT_CategoryThumbnail_Walker extends Walker_Category {

	function start_el(&$output, $item, $depth=0, $args=array(), $current_object_id = 0){
        $image = seventoon_pact_category_thumbnail($item,$args['thumbnails'],$args['taxonomy'],$args['type']);
			if (!empty($image)){
				$thumbnail = '<img class="seventoon-chapter-thumbnail" src="'.$image[0].'" width="'.$image[1].'" height="'.$image[2].'" alt="Chapter thumbnail image for'.esc_attr($item->name).'."/>';
			} else {
				$thumbnail = '';
			}
		if ( $args['show_count']){
			$show_count = ' <span class="seventoon-post-count">('.$item->count.')</span>';
		} else {
			$show_count = '';
		}
		if ( $args['hierarchical'] == 1 ){
			$list_style = " seventoon-list-indent";
		} else {
			$list_style = "";
		}
		$output .= '<li class="seventoon-chapter-list-item'.$list_style.'"><a href="'.get_category_link( $item ).'" class="chapter-list-item-link">'.$thumbnail.'<span class="chapter-title">'.esc_attr($item->name).'</span>'.$show_count.'</a>';
    }
	function end_el(&$output, $item, $depth=0, $args=array() ){
		$output .= "</li>\n";
	}
}

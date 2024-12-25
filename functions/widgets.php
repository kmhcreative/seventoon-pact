<?php
/*	SevenToon PACT Widgets
 *  (These are not loaded if using SevenToon theme as it already has them)
*/
class seventoon_pact_chapters_widget extends WP_Widget {

	/**
	 * Sets up a new Categories widget instance.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'seventoon_chapters',
			'description'                 => __( 'A list or dropdown of posts-as-comics chapter categories (not custom taxonomy).', 'seventoon-pact' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		parent::__construct( 'chapters', __( 'SevenToon Chapters' ), $widget_ops );
	}

	/**
	 * Outputs the content for the current Categories widget instance.
	 *
	 * @since 2.8.0
	 * @since 4.2.0 Creates a unique HTML ID for the `<select>` element
	 *              if more than one instance is displayed on the page.
	 *
	 * @param array $args     Display arguments including 'before_title', 'after_title',
	 *                        'before_widget', and 'after_widget'.
	 * @param array $instance Settings for the current Categories widget instance.
	 */
	public function widget( $args, $instance ) {
		static $first_dropdown = true;

		$default_title = __( 'Chapters', 'seventoon-pact' );
		$title         = ! empty( $instance['title'] ) ? $instance['title'] : $default_title;

		/** This filter is documented in wp-includes/widgets/class-wp-widget-pages.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$count        = ! empty( $instance['count'] ) ? '1' : '0';
		$hierarchical = ! empty( $instance['hierarchical'] ) ? '1' : '0';
		$dropdown     = ! empty( $instance['dropdown'] ) ? '1' : '0';
		$include	  = ! empty( $instance['include'] ) ? $instance['include'] : '';
		$exclude      = ! empty( $instance['exclude'] ) ? $instance['exclude'] : '';
		$emptychaps   = ! empty( $instance['emptychaps'] ) ? '1' : '0';
		$orderby	  = ! empty( $instance['orderby'] ) ? $instance['orderby'] : 'name';
		$order		  = ! empty( $instance['order'] ) ? $instance['order'] : 'ASC';
		$thumbnails   = ! empty( $instance['thumbnails'] ) ? $instance['thumbnails'] : 'none';
		
		$type		  = ! empty( $instance['type'] ) ? $instance['type'] : 'post';
		$taxonomy	  = ! empty( $instance['taxonomy'] ) ? $instance['taxonomy'] : 'category';

		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		if ( $emptychaps ){
			$hide_empty = 1;
			$hide_if_empty = true;
		} else {
			$hide_empty = 0;
			$hide_if_empty = false;
		}


		$cat_args = array(
			'taxonomy'     => $taxonomy,
		    'include'	   => $include,
		    'exclude'      => $exclude,
		    'exclude_tree' => $exclude,
		    'hierarchical' => $hierarchical,
		    'depth'		   => 0,
		    'hide_empty'   => $hide_empty,
		    'hide_if_empty'=> $hide_if_empty,
		    'order'		   => $order,
			'orderby'      => $orderby,
			'show_count'   => $count,
		);
		
		if ( $taxonomy == 'category' ){
			// we need to find the category id for "comics" because we don't want to show ALL categories
			$top_level = get_category_by_slug('comics');
			if (empty($top_level) || $top_level->count === 0 ){
				// no "comics" slug or "comics" has no posts, so try "chapters"
				$top_level = get_category_by_slug('chapters');
			}
			if (empty($top_level)){
				// no chapters either! Fail gracefully...
				$topID = 0;
			} else {
				$topID = $top_level->term_id;
			}
			$cat_args['child_of'] = $topID;
		}

		if ( $dropdown ) {
			printf( '<form action="%s" method="get">', esc_url( home_url() ) );
			$dropdown_id    = ( $first_dropdown ) ? 'cat' : "{$this->id_base}-dropdown-{$this->number}";
			$first_dropdown = false;

			echo '<label class="screen-reader-text" for="' . esc_attr( $dropdown_id ) . '">' . $title . '</label>';

			$cat_args['show_option_none'] = __( 'Select Category', 'seventoon-pact' );
			$cat_args['id']               = $dropdown_id;
			if ($taxonomy != 'category'){
				$cat_args['value_field'] = 'slug';
			}
			$cat_args['echo'] = 0;

			/**
			 * Filters the arguments for the Categories widget drop-down.
			 *
			 * @since 2.8.0
			 * @since 4.9.0 Added the `$instance` parameter.
			 *
			 * @see wp_dropdown_categories()
			 *
			 * @param array $cat_args An array of Categories widget drop-down arguments.
			 * @param array $instance Array of settings for the current widget.
			 */
			$select = wp_dropdown_categories( apply_filters( 'widget_categories_dropdown_args', $cat_args, $instance ) );
			if ( $taxonomy != 'category' ){
				// get chapter terms
				$terms = get_terms( $taxonomy );
				if (empty($terms)){
					// drop down will be empty so bail...
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
							if ( $taxonomy == 'mangapress_series' ){
								$archive = 'series';
							} else {
								$archive = $taxonomy;
							}
							$linkfront = get_option('home').'/'.$archive.'/';
						}
					}
				}
				$replace = "<select$1 onchange=\"javascript:location.href='".$linkfront."'+this.options[this.selectedIndex].value\">";
				$select  = preg_replace( '#<select([^>]*)>#', $replace, $select );
			}
			echo $select;
			echo '</form>';

			$type_attr = current_theme_supports( 'html5', 'script' ) ? '' : ' type="text/javascript"';
			if ( $taxonomy == 'category'){
			?>

<script<?php echo $type_attr; ?>>
/* <![CDATA[ */
(function() {
	var dropdown = document.getElementById( "<?php echo esc_js( $dropdown_id ); ?>" );
	function onCatChange() {
		if ( dropdown.options[ dropdown.selectedIndex ].value > 0 ) {
			dropdown.parentNode.submit();
		}
	}
	dropdown.onchange = onCatChange;
})();
/* ]]> */
</script>

			<?php
			} // end script for cat drop
		} else {
			// if thumbnails == first/last otherwise no thumbnails
			if ($thumbnails == 'first' || $thumbnails == 'last'){
				$my_walker = new STPACT_CategoryThumbnail_Walker();
				$cat_args['walker'] = $my_walker;
				$cat_args['thumbnails'] = $thumbnails;
				$cat_args['type'] = $type;
			}
			$format = current_theme_supports( 'html5', 'navigation-widgets' ) ? 'html5' : 'xhtml';

			/** This filter is documented in wp-includes/widgets/class-wp-nav-menu-widget.php */
			$format = apply_filters( 'navigation_widgets_format', $format );

			if ( 'html5' === $format ) {
				// The title may be filtered: Strip out HTML and make sure the aria-label is never empty.
				$title      = trim( strip_tags( $title ) );
				$aria_label = $title ? $title : $default_title;
				echo '<nav aria-label="' . esc_attr( $aria_label ) . '" class="seventoon-chapter-list">';
			}
			?>

			<ul>
				<?php
				$cat_args['title_li'] = '';

				/**
				 * Filters the arguments for the Categories widget.
				 *
				 * @since 2.8.0
				 * @since 4.9.0 Added the `$instance` parameter.
				 *
				 * @param array $cat_args An array of Categories widget options.
				 * @param array $instance Array of settings for the current widget.
				 */
				wp_list_categories( apply_filters( 'widget_categories_args', $cat_args, $instance ) );
				?>
			</ul>

			<?php
			if ( 'html5' === $format ) {
				echo '</nav>';
			}
		}

		echo $args['after_widget'];
	}

	/**
	 * Handles updating settings for the current Categories widget instance.
	 *
	 * @since 2.8.0
	 *
	 * @param array $new_instance New settings for this instance as input by the user via
	 *                            WP_Widget::form().
	 * @param array $old_instance Old settings for this instance.
	 * @return array Updated settings to save.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                 = $old_instance;
		$instance['title']        = sanitize_text_field( $new_instance['title'] );
		$instance['count']        = ! empty( $new_instance['count'] ) ? 1 : 0;
		$instance['hierarchical'] = ! empty( $new_instance['hierarchical'] ) ? 1 : 0;
		$instance['dropdown']     = ! empty( $new_instance['dropdown'] ) ? 1 : 0;
		
		$instance['include']      = ! empty( $new_instance['include'] ) ? sanitize_text_field( $new_instance['include'] ) : '';
		$instance['exclude']      = ! empty( $new_instance['exclude'] ) ? sanitize_text_field( $new_instance['exclude'] ) : '';
		$instance['emptychaps']   = ! empty( $new_instance['emptychaps'] ) ? 1 : 0;
		$instance['orderby']      = ! empty( $new_instance['orderby'] ) ? sanitize_text_field( $new_instance['orderby'] ) : 'name';
		$instance['order']        = ! empty( $new_instance['order'] ) ? sanitize_text_field( $new_instance['order'] ) : 'ASC';
		$instance['thumbnails']   = ! empty( $new_instance['thumbnails'] ) ? sanitize_text_field( $new_instance['thumbnails'] ) : 'none';
		
		$instance['type']		  = ! empty( $new_instance['type'] ) ? sanitize_text_field( $new_instance['type'] ) : 'post';
		$instance['taxonomy']	  = ! empty( $new_instance['taxonomy'] ) ? sanitize_text_field( $new_instance['taxonomy'] ) : 'category';

		return $instance;
	}

	/**
	 * Outputs the settings form for the Categories widget.
	 *
	 * @since 2.8.0
	 *
	 * @param array $instance Current settings.
	 */
	public function form( $instance ) {
		// Defaults.
		$instance     = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		$count        = isset( $instance['count'] ) ? (bool) $instance['count'] : false;
		$hierarchical = isset( $instance['hierarchical'] ) ? (bool) $instance['hierarchical'] : false;
		$dropdown     = isset( $instance['dropdown'] ) ? (bool) $instance['dropdown'] : false;
		
		$include	  = isset( $instance['include'] ) ? $instance['include'] : '';
		$exclude      = isset( $instance['exclude'] ) ? $instance['exclude'] : '';
		$emptychaps   = isset( $instance['emptychaps'] ) ? (bool) $instance['emptychaps'] : false;
		$orderby	  = isset( $instance['orderby'] ) ? $instance['orderby'] : 'name';
		$order		  = isset( $instance['order'] ) ? $instance['order'] : 'ASC';
		$thumbnails   = isset( $instance['thumbnails'] ) ? $instance['thumbnails'] : 'none';
		
		$type		  = isset( $instance['type'] ) ? $instance['type'] : 'post';
		$taxonomy     = isset( $instance['taxonomy'] ) ? $instance['taxonomy'] : 'category';
		
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'include' ); ?>"><?php _e( 'Include: (optional comma-separated category IDs)', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'include' ); ?>" name="<?php echo $this->get_field_name( 'include' ); ?>" type="text" value="<?php echo esc_attr( $include ); ?>" />
		</p>	
		<p>
			<label for="<?php echo $this->get_field_id( 'exclude' ); ?>"><?php _e( 'Exclude: (optional comma-separated category IDs)', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'exclude' ); ?>" name="<?php echo $this->get_field_name( 'exclude' ); ?>" type="text" value="<?php echo esc_attr( $exclude ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Order: (ASC | DESC )', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>" type="text" value="<?php echo esc_attr( $order ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order By:', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'orderby' ); ?>" name="<?php echo $this->get_field_name( 'orderby' ); ?>" type="text" value="<?php echo esc_attr( $orderby ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'thumbnails' ); ?>"><?php _e( 'Thumbnails: (first | last | none)', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'thumbnails' ); ?>" name="<?php echo $this->get_field_name( 'thumbnails' ); ?>" type="text" value="<?php echo esc_attr( $thumbnails ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'type' ); ?>"><?php _e( 'Post Type:', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" type="text" value="<?php echo esc_attr( $type ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy: ', 'seventoon-pact' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>" type="text" value="<?php echo esc_attr( $taxonomy ); ?>" />
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'dropdown' ); ?>" name="<?php echo $this->get_field_name( 'dropdown' ); ?>"<?php checked( $dropdown ); ?> />
			<label for="<?php echo $this->get_field_id( 'dropdown' ); ?>"><?php _e( 'Display as dropdown', 'seventoon-pact' ); ?></label>
			<br />

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>"<?php checked( $count ); ?> />
			<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Show post counts', 'seventoon-pact' ); ?></label>
			<br />

			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'hierarchical' ); ?>" name="<?php echo $this->get_field_name( 'hierarchical' ); ?>"<?php checked( $hierarchical ); ?> />
			<label for="<?php echo $this->get_field_id( 'hierarchical' ); ?>"><?php _e( 'Show hierarchy', 'seventoon-pact' ); ?></label>
			<br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'emptychaps' ); ?>" name="<?php echo $this->get_field_name( 'emptychaps' ); ?>"<?php checked( $emptychaps ); ?> />
			<label for="<?php echo $this->get_field_id( 'emptychaps' ); ?>"><?php _e( 'Hide Empty Chapters', 'seventoon-pact' ); ?></label>

		</p>
		<?php
	}

}

/*  PROMO SLIDER 
 *	===================
 *  Modified version of the one from https://wordpress.org/plugins/several-images-slider-widget/
 */
 
class seventoon_pact_promo_slider extends WP_Widget {
 	/**
	 * Sets up a new Categories widget instance.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'                   => 'seventoon_promos',
			'description'                 => __( 'A promotional image slider.', 'seventoon-pact' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		);
		parent::__construct( 'promos', __( 'SevenToon Promos', 'seventoon-pact' ), $widget_ops );
	}

    // The widget form (for the backend )
    public function form( $instance ) {
        // Set widget defaults
        $defaults = array(
            'image_url'    => array(),
            'slider_title'=> 'Promotions',
            'nav_option'=> 'true',
            'autoslide_option'=> 'true',
            'imagefit_option' => 'contain',
            'slider_pause_option'=> '5',
            'backcolor' => 'transparent',
            'target_tab'=>array(''),
            'image_link'=>array(''),
        );    
                 
        // Parse current settings with defaults        
        if($instance)
            extract($instance);
        else
            extract($defaults);
        
       
        ?>
        <p class="slider_title"><?php _e('Title', 'seventoon-pact');?>: <input type="text" style="width:100%;" name="<?php echo esc_attr( $this->get_field_name( 'slider_title'));?>" value="<?php echo $slider_title; ?>"></p>
        <a class="seventoon_upload_image_media" href="javascript:void(0)"><?php _e("Add Images","seventoon-pact");?></a><br/>
        <table class="seventoon_multi_image_slider_table_wrapper"> 
            <tbody id="recipeTableBody">
            <?php  
            $targetval =(isset($instance['target_tab']))? $instance['target_tab'] : "";
            $linkval =  (isset($instance['image_link']))? $instance['image_link'] : "";
			$backcolor =(isset($instance['backcolor']))? $instance['backcolor'] : "transparent";
            
            if(isset($image_url) && count($image_url) > 0){
                $imagelength = array();
                $count=0;
                foreach($image_url as $url)
                { 
                    if(!empty($url))
                    {   $imgurl = wp_get_attachment_image_src( $url,'full' );
                    ?>
                        <tr class="seventoon_individual_image_section">
                            <td class="drag-handler">
                                <span class="seventoon_drag_Section">&#8942;&#8942;</span>
                            </td>
                            <td class="image_thumbnail">
                                <a href="<?php if(!empty($imgurl[0])){ echo $imgurl[0]; } ?>" target="_blank" ><img class="seventoon_admin_image_preview active" src="<?php if(!empty($imgurl[0])){ echo $imgurl[0]; } ?>"></a>
                            </td>

                            <td class="image_td_fields">
                                <input class="" name="<?php echo esc_attr( $this->get_field_name( 'image_url'));?>[]" type="hidden" value="<?php echo $url; ?>" /> 
                                <input class="seventoon_image_input_field" name="<?php echo esc_attr( $this->get_field_name( 'image_link'));?>[]" type="text" value="<?php echo esc_url($linkval[$count]); ?>" placeholder="Link (optional)" />
                                <span class="seventoon_image_new_tab_label"><?php _e("Open link in a new tab","seventoon");?></span>  <select name="<?php echo esc_attr( $this->get_field_name( 'target_tab'));?>[]" class="seventoon_opentab" style="display:none;">
                                    <option <?php selected("",$targetval[$count]);?> value =""><?php _e("Select","seventoon");?></option>
                                    <option <?php selected("newtab",$targetval[$count]);?> value="newtab" ><?php _e("New Window","seventoon");?></option>
                                </select>
                                <input type="checkbox" name="seventoon_checkurl" <?php checked("newtab",$targetval[$count]);?> value="newtab" class="seventoon_checkurl">
                            </td>
                            <td class="recipe-table__cell">
                                <a class="seventoon_remove_field_upload_media_widget" title="Delete" href="javascript:void(0)">&times;</a>
                            </td>
                        </tr>    
                    <?php 
                    }
                    $imagelength[]= $count;
                    $count++;
                }
            }
            ?>
             <tr class="seventoon_no_images" <?php if( isset($image_url) && count($image_url)>=1){?> style="display:none;" <?php }?> >
                            <td colspan="3">
                                <?php _e("No images selected");?>
                            </td>
            </tr>
            </tbody>
        </table>
        <input type="hidden" class="seventoon_temp_image_name" value="<?php echo esc_attr( $this->get_field_name( 'image_url'));?>[]" />
        <input type="hidden" class="seventoon_temp_image_link" value="<?php echo esc_attr( $this->get_field_name( 'image_link'));?>[]" />
        <input type="hidden" class="seventoon_temp_image_tab" value="<?php echo esc_attr( $this->get_field_name( 'target_tab'));?>[]" />
        <input type="hidden" class="seventoon_temp_text_val" value="" />
        <div class="seventoon_multi_image_slider_setting" <?php if(isset($image_url) && count($image_url)<=1){?> style="display:none;" <?php }?> >
            <h4><?php _e('Slider Settings',"seventoon");?></h4>

            <!-- Slider Navigation -->
            <div class="seventoon_slider_options_section"> 
                  <label><?php _e('Slider Navigation',"seventoon");?>: </label>     
                  <input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'nav_option'));?>" <?php if($nav_option=='true'){ ?> checked="checked" <?php } ?>  value="true"><?php _e("Enable","seventoon");?>
                  <input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'nav_option'));?>" <?php if($nav_option=='false'){ ?> checked="checked" <?php } ?> value="false" ><?php _e("Disable","seventoon");?>
            </div>

            <!-- Autoslide -->
            <div class="seventoon_slider_options_section"> 
                  <label><?php _e('Autoslide',"seventoon");?>: </label>     
                  <input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'autoslide_option'));?>" <?php if($autoslide_option=='true'){ ?> checked="checked" <?php } ?> checked="checked" value="true"><?php _e("Enable","seventoon");?>
                  <input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'autoslide_option'));?>" <?php if($autoslide_option=='false'){ ?> checked="checked" <?php } ?> value="false" ><?php _e("Disable","seventoon");?>
            </div>
            
        	<!-- Image Fit -->
            <div class="seventoon_imagefit_options_section"> 
                  <label><?php _e('Image Fit',"seventoon");?>: </label>     
                  <input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'imagefit_option'));?>" <?php if($imagefit_option=='contain'){ ?> checked="checked" <?php } ?> checked="checked" value="contain"><?php _e("Contain","seventoon");?>
                  <input type="radio" name="<?php echo esc_attr( $this->get_field_name( 'imagefit_option'));?>" <?php if($imagefit_option=='cover'){ ?> checked="checked" <?php } ?> value="cover" ><?php _e("Cover","seventoon");?>
            </div>
            
        	<!-- Background Color -->
            <div class="seventoon_backcolor_options_section"> 
                  <label><?php _e('Background Color',"seventoon");?>: </label>     
                  <input type="text" name="<?php echo esc_attr( $this->get_field_name( 'backcolor'));?>" value="<?php echo $backcolor; ?>">
            </div>

            <!-- Pause Interval -->
            <div class="seventoon_slider_options_section"> 
                  <label><?php _e('Pause(In second)',"seventoon");?>: </label>     
                  <select name="<?php echo esc_attr( $this->get_field_name( 'slider_pause_option'));?>">
                      <?php
                        for ($i=1; $i <=10 ; $i++) 
                        { 
                        ?>    
                            <option <?php if($slider_pause_option==$i){ ?> selected="selected" <?php } ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>    
                        <?php
                        }
                        ?>
                  </select>
            </div>

        </div>
    <?php }

    // Update widget settings
    public function update( $new_instance, $old_instance ){
        $instance = $old_instance;
        $instance['image_url']    =  $new_instance['image_url'];
        $instance['slider_title'] =  $new_instance['slider_title'];
        $instance['nav_option']    =  $new_instance['nav_option'];
        $instance['autoslide_option'] =  $new_instance['autoslide_option'];
        $instance['imagefit_option']  =  $new_instance['imagefit_option'];
        $instance['backcolor'] =  $new_instance['backcolor'];
        $instance['slider_pause_option'] =  $new_instance['slider_pause_option'];
        $instance['target_tab'] =  $new_instance['target_tab'];
        $instance['image_link']=array();

        if($new_instance['image_link'])
        {
            foreach ($new_instance['image_link'] as $temp_link) {
                $temp_link=esc_url($temp_link);
                
                $urlstring =    parse_url($temp_link, PHP_URL_HOST);
                
                if(wp_http_validate_url($temp_link) && strpos($urlstring, ".") !== false)
                {
                $instance['image_link'][] =  $temp_link;
                }
                else
                {
                 $instance['image_link'][] = "";   
                }
            }
        }
        else
        {
            $instance['image_link'] =  $new_instance['image_link'];
        }
        return $instance;
    }
    // Display the widget on frontend
    public function widget( $args, $instance ) {
        extract($instance);
        $count_images = 0;

        if (!empty($image_url)) {
            foreach ($image_url as $available_image_url) {
                if(!empty($available_image_url)){
                    $count_images++;
                }
            }
        }
        echo $args['before_widget'];
        if(isset($slider_title))
        {
        echo $args['before_title'];
        echo $slider_title;
        echo $args['after_title'];
        }

        $targetval =(isset($instance['target_tab']))? $instance['target_tab'] : "";
		$backcolor =(isset($instance['backcolor']))? $instance['backcolor'] : "";
        $linkval =  (isset($instance['image_link']))? $instance['image_link'] : "";
        
        // fix for missing widget_id key error
        if ( !isset($args['widget_id']) ){
        	$args['widget_id'] = $this->id;
        }
        
        $func_id = str_replace('-','_', $args['widget_id']);
        
        if ( !isset($instance['imagefit_option']) ){
        	$instance['imagefit_option'] = 'contain';
        }
        
        if ( $instance['imagefit_option'] == 'cover' ){
        	$image_fit = 'style="object-fit: cover;"';
        } else {
        	$image_fit = ''; // object-fit: contain; is in styles.css
        }
        
        if ( $backcolor == '' || $backcolor == 'transparent' ){
        	$backcolor = '';
        } else {
        	$backcolor = 'style="background-color: '.esc_html($backcolor).';"';
        }
        $count = 0;
        if($count_images >1){  ?>
             
            <div class="seventoon_promos_wrapper <?php echo $args['widget_id']; ?> " <?php echo $backcolor; ?>>
            	<?php if( $instance['nav_option'] == 'true' ){ ?>
            	<a href="javascript:void(0);" class="slidenav promo_back" onclick="seventoon.<?php echo $func_id; ?>.stopshow();seventoon.<?php echo $func_id; ?>.slide('previous');"><span class="screen-reader-text">Previous Promotion</span></a>
            <?php
            	}; 
                foreach ($image_url as $image_src) { 
                 $alt_text = get_post_meta( $image_src,"_wp_attachment_image_alt",true);
                 $imgurl = wp_get_attachment_image_src( $image_src,'full' );
                 if ($count == 0){
                 	$style_xtra = 'style="opacity:1;display:block;"';
                 } else {
                 	$style_xtra = 'style="opacity:0;display:none;"';
                 }
                 

                    if (!empty($image_src)) { ?>
                        <div class="slide" <?php echo $style_xtra; ?>>
                       <?php  if(!empty($linkval[$count])) {?>
                            <a href="<?php if(!empty($linkval[$count])){echo esc_url($linkval[$count]);}?>" <?php if($targetval[$count] == 'newtab'){echo "target='_blank'";}?> title="<?php _e("Click here");?>" >
                                <img src="<?php if(!empty($imgurl[0])){echo $imgurl[0];} ?>" alt="<?php echo $alt_text;?>" width="<?php echo $imgurl[1]; ?>" height="<?php echo $imgurl[2]; ?>" <?php echo $image_fit; ?>/>
                            </a>
                         <?php }
                          else
                        {?>
                            <img src="<?php if(!empty($imgurl[0])){echo $imgurl[0];} ?>" alt="<?php echo $alt_text;?>"  width="<?php echo $imgurl[1]; ?>" height="<?php echo $imgurl[2]; ?>" <?php echo $image_fit; ?>/>
                <?php   }?>
                        </div>    
                    <?php 
                    }
                    $count++;
                }
                $slider_nav_option = $instance['nav_option'];
                if( $instance['nav_option'] == 'true' ){
            ?>
            	<a href="javascript:void(0);" class="slidenav promo_next" onclick="seventoon.<?php echo $func_id; ?>.stopshow();seventoon.<?php echo $func_id; ?>.slide('next');"><span class="screen-reader-text">Next Promotion</span></a>
            <?php }; ?>
            </div>
			<!--// seventoon slider functions for this promo widget //-->
            <script>
            	var seventoon = seventoon || {};
            	// This is clumsy AF but it works and I'm sick of effing with it
            	seventoon['<?php echo $func_id; ?>'] = function(){
            		var nav_option = <?php echo $slider_nav_option;  ?>; 
                    var autoplay_option = <?php echo $instance['autoslide_option']; ?>; 
                    var pause_option = <?php echo $instance['slider_pause_option']."000"; ?>;
					
					var play = null;
					var delay= null;
					// autoplay slideshow if enabled
					var autoplay = function(){
						if (autoplay_option){
							play = setInterval(function(){seventoon.<?php echo $func_id; ?>.slide('next');},pause_option);
						}
					}
					// stop autoplay slideshow if previous/next clicked
					var stopshow = function(){
						clearInterval(play);
						clearTimeout(delay);
					}
					// change slide function
					var change_slide = function(direction){
						if (!direction){ return; }
            			var slides = document.getElementsByClassName('<?php echo $args["widget_id"]; ?>')[0].getElementsByClassName('slide');
						for( var s=0; s < slides.length; s++){
							if (slides[s].style.opacity=='1'){ // current slide
								slides[s].style.opacity = "0";
								delay = setTimeout(function(){slides[s].style.display = "none";},1000);
								if (direction == 'previous' ){
									if (!slides[s-1]){
										var prevSlide = slides[slides.length-1];
									} else {
										var prevSlide = slides[s-1];
									}
									prevSlide.style.display = "block";
									setTimeout(function(){prevSlide.style.opacity = "1";},1);
								} else if (direction == 'next'){
									if (!slides[s+1]){
										var nextSlide = slides[0];
									} else {
										var nextSlide = slides[s+1];
									}
									nextSlide.style.display = "block";
									setTimeout(function(){nextSlide.style.opacity = "1";},1);
								} else {
									// direction not sent
								}
								break;
							}
						}
					} 
					// expose public           
            		return {
            			slide : change_slide,
            			stopshow  : stopshow,
            			autoplay : autoplay
            		}
            	}();
            	// autoplay if needed
                window.addEventListener("load", function(){seventoon.<?php echo $func_id; ?>.autoplay();});
            </script>
        <?php
        }
        elseif ($count_images==1) 
        { 
            $temp_img_id=$image_url[0];
            $alt_text = get_post_meta( $temp_img_id,"_wp_attachment_image_alt",true);
            $imgurl = wp_get_attachment_image_src( $temp_img_id,'full' );
        ?>
            <div id="seventoon_single_image_wrapper">
				<div class="slide">
            <?php if(isset($linkval[0])&&trim($linkval[0])!="")
            {?>
              <a href="<?php echo esc_url($linkval[0]);?>" <?php if($targetval[$count] == 'newtab'){echo "target='_blank'";}else{echo "";}?> title="<?php _e("Click here");?>" >
                <img src="<?php if(!empty($imgurl[0])){echo $imgurl[0];}?>" alt="<?php if(isset($alt_text)) echo $alt_text;?>"   width="<?php echo $imgurl[1]; ?>" height="<?php echo $imgurl[2]; ?>" <?php echo $image_fit; ?>/>
            </a>
             <?php
            }
            else
            {
            ?>
            <img src="<?php if(!empty($imgurl[0])){echo $imgurl[0];}?>" alt="<?php if(isset($alt_text)) echo $alt_text;?>"   width="<?php echo $imgurl[1]; ?>" height="<?php echo $imgurl[2]; ?>" <?php echo $image_fit; ?>/>
            <?php
            }
            ?>
            	</div>
            </div> 
        <?php
        }
        echo $args['after_widget'];
    }
}
add_action( 'widgets_init', 'seventoon_pact_register_widgets' );
function seventoon_pact_register_widgets(){
	register_widget('seventoon_pact_chapters_widget');
	register_widget('seventoon_pact_promo_slider');
}
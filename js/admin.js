jQuery(document).ready(function($){  //Open media window for select image
  var mediaUploader;
  jQuery('body').on("click",'.seventoon_upload_image_media',function(e) {
    e.preventDefault();
    seventoontempthis=$(this);
      
      
    // If the uploader object has already been created, reopen the dialog
      if (mediaUploader) {
      mediaUploader.open();
      return;
      }
    // Extend the wp.media object
    mediaUploader = wp.media.frames.file_frame = wp.media({
      title: 'Select Slider Images',
      button: {
      text: 'Add'
    }, multiple: true });

    // When a file is selected, grab the URL and set it as the text field's value
    mediaUploader.on('select', function() {
      attachments = mediaUploader.state().get('selection').toJSON();
      //console.log(attachments.length);

      for (i in attachments) {
                attachment= attachments[i];
        jQuery('.seventoon_image_input_field.active_image_section ').val(attachment.url);
        jQuery('img.seventoon_admin_image_preview.active_admin_preview').addClass('active').attr('src',attachment.url);
       // jQuery('.active_widget_form .widget-control-actions input[type="submit"]').removeAttr('disabled');
        var current_input_name = seventoontempthis.parents(".widget").find(".seventoon_temp_image_name").val();
        var current_input_link = seventoontempthis.parents(".widget").find(".seventoon_temp_image_link").val();
        var current_input_tab = seventoontempthis.parents(".widget").find(".seventoon_temp_image_tab").val();
        
          seventoontempthis.parents(".widget").find('.seventoon_temp_text_val').trigger("change");
        if (attachment.url.match(/.(jpg|jpeg|png|gif)$/i))
        {
        var new_element = `<tr class='seventoon_individual_image_section'>
        <td class="drag-handler"><span class="seventoon_drag_Section">&#8942;&#8942;</span></td>
        <td class="image_thumbnail"><a href=`+attachment.url+` target="_blank"><img src='`+attachment.url+`' class='seventoon_admin_image_preview'></a></td>
        <td class="image_td_fields"><input class='' name='`+current_input_name+`' value=`+attachment.id+` type='hidden'>
        <input class="seventoon_image_input_field" name='`+current_input_link+`' type='text' value='' placeholder='Link (optional)'><span class="seventoon_image_new_tab_label">New tab</span> <select name='`+current_input_tab+`' class='seventoon_opentab' style="display: none;">
                                        <option value="">Same tab</option>
                                        <option value="newtab">New tab</option>
                                </select>
                                <input type="checkbox" name="seventoon_checkurl" value="newtab" class="seventoon_checkurl">
        </td><td class="recipe-table__cell"><a class="seventoon_remove_field_upload_media_widget" title="Delete" href="javascript:void(0)">&times;</a></td></tr>`;

          seventoontempthis.parents(".widget").find('.seventoon_multi_image_slider_table_wrapper tbody').append(new_element);
          }

          var current_imag_length = seventoontempthis.parents(".widget").find('.seventoon_multi_image_slider_table_wrapper .seventoon_individual_image_section').length;
          if(current_imag_length >1){
            seventoontempthis.parents(".widget").find('.seventoon_multi_image_slider_setting').show();
          }
          else{
            seventoontempthis.parents(".widget").find('.seventoon_multi_image_slider_setting').hide();       
          }
          if(current_imag_length>0){
            seventoontempthis.parents(".widget").find('.seventoon_no_images').hide();
          }
          else
          {
            seventoontempthis.parents(".widget").find('.seventoon_no_images').show();
          }
      }
      
    });
    // Open the uploader dialog
    mediaUploader.open();




  });

});


jQuery(document).ready(function($){ // Remove the image section
    jQuery('body').on("click",'a.seventoon_remove_field_upload_media_widget',function(){
    jQuery(this).parents(".widget").find('input[type="submit"]').removeAttr('disabled');
    jQuery(this).parents(".widget").find('.seventoon_temp_text_val').trigger("change");
    jQuery(this).parents('table').addClass('countrows');
    var temppobj=jQuery(this).parents(".widget");
     var current_imag_length = temppobj.find(".seventoon_multi_image_slider_table_wrapper .seventoon_individual_image_section").length;
    if(current_imag_length ==1)
    {
      var resultseventoon = confirm('This is the last image of this Widget');
      if (resultseventoon) {
      jQuery(this).parents('tr').remove();
      }
    }
    else
    {
      jQuery(this).parents('tr').remove();
    }
    current_imag_length = temppobj.find(".seventoon_multi_image_slider_table_wrapper .seventoon_individual_image_section").length;
        if(current_imag_length >1)
        {
         temppobj.find('.seventoon_multi_image_slider_setting').show();
         }
        else
        {
          temppobj.find('.seventoon_multi_image_slider_setting').hide();
         }
        if(current_imag_length>0){
            temppobj.find('.seventoon_no_images').hide();
          }
          else
          {
            temppobj.find('.seventoon_no_images').show();
          }

  });
 // 
        $('.seventoon_temp_text_val').change(function(){
          $(".seventoon_temp_text_val").val("abc");
        })

        
      $(document).on('click', '.seventoon_reset', function () {
          //$( this ).prevAll( ".seventoon_image_input_field" ).val('');

      });

       $(document).on('click', '.seventoon_checkurl', function () {
       
        if($(this).is(':checked'))
        {
            $( this ).prev( ".seventoon_opentab" ).val('newtab');
        }
        else
        {
            $( this ).prev( ".seventoon_opentab" ).val('');
        }
          
          

      });
 
});


jQuery(document).ready(function($){
	setTimeout(function(){
		$('#recipeTableBody').sortable({
			helper: '.drag-handler',
			zIndex: 999999,
			update : function() {
				jQuery(this).parents('.widget').find('.seventoon_temp_text_val').trigger("change");
				jQuery('button.components-button.is-primary').removeAttr('disabled');
			}
		}).disableSelection();
	}, 1000);
});

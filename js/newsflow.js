/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery.fn.toggleCheckbox = function() {
    this.attr('checked', !this.attr('checked'));
}

jQuery(document).ready( function($) {

        $('#hypernews_name').focus();
        $('#hypernews_name').select();

        $(".hypernews_edit_row").click(function() {
            
            id = $(this).attr('row_id');
            
            panel = '.hypernews_row_'+id;
            prepanel = '.hypernews_row_pre_'+id;
            
            if( !$(panel).is(':visible') ) 
            {
                //$(panel).html('Det här är formulär för id='+id+'<br/>Lorem Ipsum');
                $(panel).show();
                $(prepanel).hide();
                
                src = $('#hypernews_row_icon_'+id).attr("src");
                src=src.replace('lightbulb.png','lightbulb_off.png');
                $('#hypernews_row_icon_'+id).attr("src",src);
                
                //Pass new value to ajax-function to save database row status!
                jQuery.get(newsflowAjax.ajaxurl, {
                    action : 'hypernews_update_status',
	  id		: id,
	  status		: 'READ'
                });
                
            }
            else 
            {
                $(panel).hide();
                $(prepanel).show();
            }

            return false;
        });

        //Mark row unread
        $(".hypernews_unread_row").click(function() {
            
            id = $(this).attr('row_id');
            
            src = $('#hypernews_row_icon_'+id).attr("src");
            
            //get the last /
            pos = src.lastIndexOf('/');
            
            src = src.substring(0,pos) + '/lightbulb.png';

            $('#hypernews_row_icon_'+id).attr("src",src);

            //Pass new value to ajax-function to save database row status!
            jQuery.get(newsflowAjax.ajaxurl, {
            action : 'hypernews_update_status',
            id		: id,
            status		: 'NEW'
            });
                
            return false;
        });
        
        //Mark row favorite
        $(".hypernews_star_row").click(function() {
            
            id = $(this).attr('row_id');
            
            src = $('#hypernews_row_icon_'+id).attr("src");
            
            //get the last /
            pos = src.lastIndexOf('/');
            
            src = src.substring(0,pos) + '/star.png';
            
            $('#hypernews_row_icon_'+id).attr("src",src);

            //Pass new value to ajax-function to save database row status!
            jQuery.get(newsflowAjax.ajaxurl, {
            action : 'hypernews_update_status',
            id		: id,
            status		: 'STAR'
            });
                
            return false;
        });
        
                
        //Mark row favorite
        $(".hypernews_hide_row").click(function() {
            
            id = $(this).attr('row_id');
            
            src = $('#hypernews_row_icon_'+id).attr("src");
            
            //get the last /
            pos = src.lastIndexOf('/');
            
            src = src.substring(0,pos) + '/cross.png';
            
            $('#hypernews_row_icon_'+id).attr("src",src);

            //Pass new value to ajax-function to save database row status!
            jQuery.get(newsflowAjax.ajaxurl, {
            action : 'hypernews_update_status',
            id		: id,
            status		: 'HIDE'
            });
                
            return false;
        });

        //Mark row favorite
        $(".hypernews_row_note").click(function() {
            
            id = $(this).attr('row_id');
            
            
            note = $('#hypernews_row_notearea_'+id).val();
            
            //Pass new value to ajax-function to save database row status!
            jQuery.get(newsflowAjax.ajaxurl, {
            action : 'hypernews_update_note',
            id   : id,
            note : note
            });

            $("#hypernews_row_notetext_"+id).html(note);

            return false;
        });

        //Mark row published
        $(".hypernews_publish_row").click(function() {

            button = $(this);
            id = $(this).attr('row_id');

            $('#hypernews_row_icon_'+id).effect('pulsate');

            posttype = $(this).attr('posttype');
            
            src = $('#hypernews_row_icon_'+id).attr("src");
            
            //get the last /
            pos = src.lastIndexOf('/');
            
            src = src.substring(0,pos) + '/page_white_go.png';
            
            //Pass new value to ajax-function to save database row status!
            jQuery.get(newsflowAjax.ajaxurl, {
            action : 'hypernews_publish',
            id          : id,
            posttype  : posttype
            })
            .success(function(result) { 
                
                    if (result.indexOf('OK!')>0)
                    {
                        $(button).effect('pulsate');
//                        $('#record_'+id).effect('pulsate');
                        $('#hypernews_row_icon_'+id).attr("src",src);
                        $('#hypernews_row_icon_'+id).effect('pulsate');
                    }
                    else
                    {
                        alert('CHANNEL POST PUBLISH ERROR!'); 
                    }
                
                });
                
            return false;
        });

});



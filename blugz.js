function blugz_update_post(BLUGZ_AJAX_URL,BLUGZ_POST_ID) {

	jQuery.post(
		BLUGZ_AJAX_URL,
		{action:"blugz_update_post",post_id:BLUGZ_POST_ID},
		function (data){
			
			if(data.comments) {
				comments = data.comments;
				jQuery('#blugz_comments').html('<h3 class="blugz_comments">'+data.comments_count+' comments so far</h3><p><a href="'+data.original_url+'" class="blugz_leavecomment">Leave a comment on Buzz</a></p>');
				for(i=0; i<comments.length; i++) {
					if(comments[i].toString().indexOf('function (iterator')==-1)
						jQuery('#blugz_comments').append('<div class="blugz_comment">'+comments[i]+"</div>")
				}
				if(data.comments_count>5)
					jQuery('#blugz_comments').append('<p><a href="'+data.original_url+'" class="blugz_leavecomment">Leave a comment on Buzz</a></p>');
			}
			
			if(data.likes) {
				likes = data.likes;
				if(data.likes_count>1)
					jQuery('#blugz_likes').html('<h3 class="blugz_likes">'+data.likes_count+' people like this</h3>');
				else if (data.likes_count==1)
					jQuery('#blugz_likes').html('<h3 class="blugz_likes">One person likes this</h3>');
					
				likes_content = new Array();
				for(i=0; i<likes.length; i++) {
					if(likes[i].toString().indexOf('function (iterator')==-1)
						likes_content.push('<a href="'+likes[i]['url']+'" rel="external nofollow" title="'+likes[i]['name']+'">'+likes[i]['name']+'</a>');
				}
				jQuery('#blugz_likes').append('<div class="blugz_likes">'+likes_content.join(', ')+'</div>')
				
			}

		},
		'json'
	);

}




jQuery(document).ready(
	function() {
		jQuery('#blugz_togglecomments').click(function() {	jQuery('#blugz_comments').toggle('fade'); });
		jQuery('#blugz_togglemedia').click(function() {	jQuery('#blugz_media').toggle('fade'); });
	}
)

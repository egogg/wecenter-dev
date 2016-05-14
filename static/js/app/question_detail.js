var ATTACH_ACCESS_KEY
var COMMENT_UNFOLD;
var QUESTION_ID;
var UNINTERESTED_COUNT;
var EDITOR;
var EDITOR_CALLBACK;

$(function()
{

	// 答案评论

	$('.comment-list').on('click', '.sub-comment-reply', function(e) {
		e.preventDefault();

		var replyButton = $(this);
		var targetUser = replyButton.attr('data-user-name');
		var index = replyButton.attr('data-index');
		var commentBox = $('#comment-box-answer-' + replyButton.attr('data-answer-id'));
		var commentInput = commentBox.find('textarea');
		var currentIndex = commentInput.attr('data-index');

		var atUser = '@' + targetUser + ' ';
		if(typeof targetUser == 'undefined') {
			atUser = '';
		}

		if(commentBox.is(":visible")){
			if(index != currentIndex) {
				commentInput.attr('data-index', index);
				commentInput.val(atUser);
				commentInput.focus();
			} else {
				commentInput.removeAttr('data-index');
				commentBox.hide();
				commentInput.val('');
			}
			
		} else {
			commentBox.show();
			commentInput.attr('data-index', index);
			commentInput.insertAtCaret(atUser);
			commentInput.focus();
			$.scrollTo(commentInput.offset()['top'] - 100, 600, {queue:true});
		}
	});

    //问题页添加评论
    // AWS.Init.init_comment_box('.aw-add-comment');

	if ($('#c_log_list').attr('id'))
	{
		AWS.load_list_view(G_BASE_URL + '/question/ajax/log/id-' + QUESTION_ID, $('#bp_log_more'), $('#c_log_list'));
	}
	else
	{
		ITEM_IDS = ITEM_IDS.split(',');

		if ($('#wmd-input').length)
		{
			if (G_ADVANCED_EDITOR_ENABLE == 'Y')
			{
				EDITOR = CKEDITOR.replace( 'wmd-input');

				EDITOR_CALLBACK = function (evt)
				{
					if (evt.editor.getData().length)
					{
						$.post(G_BASE_URL + '/account/ajax/save_draft/item_id-' + QUESTION_ID + '__type-' + ANSWER_TYPE, 'message=' + evt.editor.getData(), function (result) {
							$('#answer_content_message').html(result.err + ' <a href="#" onclick="$(\'textarea#advanced_editor\').attr(\'value\', \'\'); AWS.User.delete_draft(QUESTION_ID, ANSWER_TYPE); $(this).parent().html(\' \'); return false;">' + _t('删除草稿') + '</a>');
						}, 'json');
					}
				}

				// 自动保存草稿
				if(EDITOR) {
					EDITOR.on( 'blur', EDITOR_CALLBACK);
				}
			}
			
		}

		if ($('.upload-box').length)
		{
			if (G_ADVANCED_EDITOR_ENABLE == 'Y')
			{
				var fileupload = new FileUpload('file', '.upload-box .btn', '.upload-box .upload-container', G_BASE_URL + '/publish/ajax/attach_upload/id-' + ANSWER_TYPE + '__attach_access_key-' + ATTACH_ACCESS_KEY, {
					'editor' : EDITOR
				});
			}
			else
			{
				var fileupload = new FileUpload('file', '.upload-box .btn', '.upload-box .upload-container', G_BASE_URL + '/publish/ajax/attach_upload/id-' + ANSWER_TYPE + '__attach_access_key-' + ATTACH_ACCESS_KEY, {
					'editor' : $('.wmd-input')
				});
			}
		}

		//折叠回复
		$.each($('.aw-question-comment .aw-item'), function (i, e)
		{
			if ($(this).attr('uninterested_count') >= UNINTERESTED_COUNT || $(this).attr('force_fold') == 1)
			{
				$('#uninterested_answers_list').append($(e));
			}
		});

		//折叠回复数量
		if ($('#uninterested_answers_list div.aw-item').length > 0)
		{
			$('#load_uninterested_answers span.hide_answers_count').html($('#uninterested_answers_list div.aw-item').length);
			$('#load_uninterested_answers').fadeIn();
		}

		//回复折叠显示按钮
	    $('#load_uninterested_answers a').click(function()
	    {
	    	$('#uninterested_answers_list').toggle();
	    });

		//自动展开评论
		if (COMMENT_UNFOLD == 'all')
		{
			$('.aw-add-comment').click();
		}
		else if (COMMENT_UNFOLD == 'question')
		{
			$('.aw-question-detail-meta .aw-add-comment').click();
		}

		//回复高亮
		$.each(ITEM_IDS, function (i, answer_id) {
			if ($('#answer_list_' + answer_id).attr('id'))
			{
				if ($('#answer_list_' + answer_id).find('.aw-add-comment').data('comment-count') > 0)
				{
					$('#answer_list_' + answer_id).find('.aw-add-comment').click();
				}

				AWS.hightlight($('#answer_list_' + answer_id), 'active');
			}
		});
	}

	//关注用户列表
	$.get(G_BASE_URL + '/question/ajax/get_focus_users/question_id-' + QUESTION_ID, function (result) {
		if (result) {
			$('#focus_users').append(result);
			// $.each(result, function (i, e) {
			// 	if (e['uid'])
			// 	{
			// 		$('#focus_users').append('<a href="' + e['url'] + '"><img src="' + e['avatar_file'] + '" class="user-tag user-img" data-id="' + e['uid'] + '" alt="' + e['user_name'] + '" /></a> ');
			// 	}
			// 	else
			// 	{
			// 		$('#focus_users').append('<a href="javascript:;" title="' + _t('匿名用户') + '"><img src="' + e['avatar_file'] + '" alt="' + _t('匿名用户') + '" /></a> ');
			// 	}
			// });
		}
	}, 'html');

    //邀请用户下拉绑定
    AWS.Dropdown.bind_dropdown_list($('#invite-input'), 'invite');

    //邀请用户回答点击事件
	// $(document).on('click', '.aw-invite-box .aw-dropdown-list a', function () {
	//     AWS.User.invite_user($(this),$(this).find('img').attr('src'));
	// });

	//相关链接按钮
	$('.aw-question-detail .aw-add-question-related').click(function()
	{
		$('.aw-question-detail .aw-comment-box, .aw-question-detail .aw-invite-box, .aw-question-detail .aw-edit-question-solution-box').hide();
    	if ($('.aw-question-detail .aw-question-related-box').is(':visible'))
    	{
    		$('.aw-question-detail .aw-question-related-box').fadeOut();
    	}
    	else
    	{
    		/*给邀请三角形定位*/
    		$('.aw-question-detail .aw-question-related-box .i-dropdown-triangle').css('left', $(this).position().left + 14);
    		$('.aw-question-detail .aw-question-related-box').fadeIn();
    	}
	});

    //回复内容超链接新窗口打开
    $('.markitup-box a').attr('target','_blank');
});

function one_click_add_topic(selector, topic_title, question_id)
{
	$.post(G_BASE_URL + '/topic/ajax/save_topic_relation/', 'type=question&item_id=' + question_id + '&topic_title=' + topic_title, function (result) {
		if (result.err)
		{
			AWS.alert(result.err);
		}
		else
		{
			$('.aw-topic-bar .tag-bar').prepend('<span class="topic-tag" data-id="' + result.rsm.topic_id + '"><a class="text" href="topic/' + result.rsm.topic_id + '">' + topic_title + '</a></a></span>').hide().fadeIn();

			selector.hide();
		}
	}, 'json');
}
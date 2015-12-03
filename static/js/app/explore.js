$(function()
{

	function LoadQuestionList(url, selector, container, start_page, callback)
	{
		if (!selector.attr('id'))
		{
			return false;
		}

		if (!start_page)
		{
			start_page = 0
		}

		// 把页数绑定在元素上面
		if (selector.attr('data-page') == undefined)
		{
			selector.attr('data-page', start_page);
		}
		else
		{
			selector.attr('data-page', parseInt(selector.attr('data-page')) + 1);
		}

		selector.bind('click', function ()
		{
			var _this = this;

			var spinner = $('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>');
			spinner.insertBefore($(this).hide());
			$(this).addClass('loading');

			$.get(url + '__page-' + $(_this).attr('data-page'), function (result)
			{
				$(_this).removeClass('loading');
				spinner.remove();
				$(_this).show();

				if ($.trim(result) != '')
				{
					if ($(_this).attr('data-page') == start_page && $(_this).attr('auto-load') != 'false')
					{
						container.html(result);
					}
					else
					{
						container.append(result);
					}

					// 页数增加1
					$(_this).attr('data-page', parseInt($(_this).attr('data-page')) + 1);
				}
				else
				{
					//没有内容
					if ($(_this).attr('data-page') == start_page && $(_this).attr('auto-load') != 'false')
					{
						container.html('<p class="text-center p-t-15 p-b-15">' + '没有内容' + '</p>');
					}

					$(_this).unbind('click').attr('href', G_BASE_URL + '/question/');
					$(_this).find('h3').html('海量精彩答题，进入题库');
				}

				if (callback != null)
				{
					callback();
				}
			});

			return false;
		});

		// 自动加载
		if (selector.attr('auto-load') != 'false')
		{
			selector.click();
		}
	}

	// 加载更多绑定

	LoadQuestionList(G_BASE_URL + '/explore/ajax/load_question_list/', $('#question-load-more'), $('#question-list'), 2);
});
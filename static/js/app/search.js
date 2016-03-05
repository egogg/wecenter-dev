var search_query = '';
var split_query = '';
var ajax_template = '';
$(function()
{
	$('#search-type li').click(function(e){
		e.preventDefault();

		var _this = $(this);
		window.location.hash = _this.find('a').attr('href').replace(/#/g, '');
		_this.addClass('active').siblings().removeClass('active');

		var spiner = '<div class="search-spiner sk-circle">' +
			'<div class="sk-circle1 sk-child"></div>' +
			'<div class="sk-circle2 sk-child"></div>' +
			'<div class="sk-circle3 sk-child"></div>' +
			'<div class="sk-circle4 sk-child"></div>' +
			'<div class="sk-circle5 sk-child"></div>' +
			'<div class="sk-circle6 sk-child"></div>' +
			'<div class="sk-circle7 sk-child"></div>' +
			'<div class="sk-circle8 sk-child"></div>' +
			'<div class="sk-circle9 sk-child"></div>' +
			'<div class="sk-circle10 sk-child"></div>' +
			'<div class="sk-circle11 sk-child"></div>' +
			'<div class="sk-circle12 sk-child"></div>' +
			'</div>';
		$('#search_result').html(spiner);

		$('#search_result_more').html('<i class="md md-refresh"></i> 加载更多').removeClass('disabled').attr('data-page', 1).click();
	});

	$('#search_result_more').click(function()
	{
		var _this = $(this);

		if(_this.hasClass('disabled')) {
			return false;
		}

		var page = parseInt(_this.attr('data-page'));

		var request_url = G_BASE_URL + '/search/ajax/search_result/search_type-' +  window.location.hash.replace(/#/g, '') + '__q-' + encodeURIComponent(search_query) + '__template-' + ajax_template + '__page-' + page;

		if (typeof search_recommend != 'undefined')
		{
			var request_url = request_url + '__is_recommend-1';
		}

		var spinner = $('<div class="spinner m-t-0"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>');
		spinner.insertBefore(_this.hide());

		$.get(request_url, function (response)
		{
			if (response.length)
			{
				if (_this.attr('data-page') == 1)
				{
					$('#search_result').html(response);
				}
				else
				{
					$('#search_result').append(response);
				}

				$('#search_result .lv-title .title').highText(split_query, 'span', 'c-lightblue');
				_this.attr('data-page', parseInt(_this.attr('data-page')) + 1);
			}
			else
			{
				if (_this.attr('data-page') == 1)
				{
					$('#search_result').html('<p class="text-center m-t-20">没相应的结果</p>');
				}

				// _this.addClass('disabled').unbind('click').bind('click', function () { return false; });
				_this.addClass('disabled');
				_this.html('<span class="c-gray">没有更多了</span>');
			}

			_this.removeClass('loading');
			spinner.remove();
			_this.show();

		});

		return false;
	});

	switch (window.location.hash)
	{
		case '#questions':
		case '#topics':
		case '#users':
		case '#articles':
			$("#search-type a[href='" + window.location.hash + "']").click();
		break;

		default:
			$("#search-type a[href='#questions']").click();
		break;
	}
});
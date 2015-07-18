$(function()
{
	if ($('#question_id').length)
	{
		ITEM_ID = $('#question_id').val();
	}
	else if ($('#article_id').length)
	{
		ITEM_ID = $('#article_id').val();
	}
    else
    {
        ITEM_ID = '';
    }

    // 判断是否开启ck编辑器
	if (G_ADVANCED_EDITOR_ENABLE == 'Y')
	{
		// 初始化编辑器
		var editor = CKEDITOR.replace( 'wmd-input' );
	}

    if (ATTACH_ACCESS_KEY != '' && $('.aw-upload-box').length)
    {
    	if (G_ADVANCED_EDITOR_ENABLE == 'Y')
		{
	    	var fileupload = new FileUpload('file', '.aw-editor-box .aw-upload-box .btn', '.aw-editor-box .aw-upload-box .upload-container', G_BASE_URL + '/publish/ajax/attach_upload/id-' + PUBLISH_TYPE + '__attach_access_key-' + ATTACH_ACCESS_KEY, {
					'editor' : editor
				});
	    }
	    else {
	    	var fileupload = new FileUpload('file', '.aw-editor-box .aw-upload-box .btn', '.aw-editor-box .aw-upload-box .upload-container', G_BASE_URL + '/publish/ajax/attach_upload/id-' + PUBLISH_TYPE + '__attach_access_key-' + ATTACH_ACCESS_KEY, {
					'editor' : $('.wmd-input')
				});
	    }
    }

    if (ITEM_ID && G_UPLOAD_ENABLE == 'Y' && ATTACH_ACCESS_KEY != '')
    {
        if ($(".aw-upload-box .upload-list").length) {
            $.post(G_BASE_URL + '/publish/ajax/' + PUBLISH_TYPE + '_attach_edit_list/', PUBLISH_TYPE + '_id=' + ITEM_ID, function (data) {
                if (data['err']) {
                    return false;
                } else {
                    $.each(data['rsm']['attachs'], function (i, v) {
                        fileupload.setFileList(v);
                    });
                }
            }, 'json');
        }
    }

    AWS.Dropdown.bind_dropdown_list($('.aw-mod-publish #question_contents'), 'publish');

    //初始化分类
	if ($('#category_id').length)
	{
		var category_data = '', category_id;

		$.each($('#category_id option').toArray(), function (i, field) {
			if ($(field).attr('selected') == 'selected')
			{
				category_id = $(this).attr('value');
			}
			if (i > 0)
			{
				if (i > 1)
				{
					category_data += ',';
				}

				category_data += "{'title':'" + $(field).text() + "', 'id':'" + $(field).val() + "'}";
			}
		});

		if(category_id == undefined)
		{
			category_id = CATEGORY_ID;
		}

		$('#category_id').val(category_id);

		AWS.Dropdown.set_dropdown_list('.aw-publish-title .dropdown', eval('[' + category_data + ']'), category_id);

		$('.aw-publish-title .dropdown li a').click(function() {
			$('#category_id').val($(this).attr('data-value'));
		});

		$.each($('.aw-publish-title .dropdown .aw-dropdown-list li a'),function(i, e)
		{
			if ($(e).attr('data-value') == $('#category_id').val())
			{
				$('#aw-topic-tags-select').html($(e).html());
			}
		});
	}

	//自动展开话题选择
	$('.aw-edit-topic').click();

    // 自动保存草稿
	$('textarea.wmd-input').bind('blur', function() {
		if ($(this).val() != '')
		{
			$.post(G_BASE_URL + '/account/ajax/save_draft/item_id-1__type-' +　PUBLISH_TYPE, 'message=' + $(this).val(), function (result) {
				$('#question_detail_message').html(result.err + ' <a href="#" onclick="$(\'textarea#advanced_editor\').attr(\'value\', \'\'); AWS.User.delete_draft(1, \'' + PUBLISH_TYPE + '\'); $(this).parent().html(\' \'); return false;">' + _t('删除草稿') + '</a>');
			}, 'json');
		}
	});

	// 难度控件

	$('#question-difficulty-control').barrating('show', {
        wrapperClass: 'br-wrapper',
        initialRating: $('#question_difficulty').val(),
        showSelectedRating: true,
        onSelect: function(value, text) {
            $('#question_difficulty').val(value);
        }
	});

	// 解析答案

	var icheckOption = {
		checkboxClass: 'icheckbox_square-blue',
		radioClass: 'iradio_square-blue',
		increaseArea: '20%'
	};

	var IS_JSON = true;
	try {
		var quizContent = $.parseJSON($('#quiz_content').val());	
	}
	catch (err) {
		IS_JSON = false;
	}

	if(IS_JSON) {
		var quizType = '';
		if(quizContent.type == 'singleSelection') {
			quizType = '单选';
		}
		else if(quizContent.type == 'multipleSelection') {
			quizType = '多选';
		}
		else if(quizContent.type == 'crossword') {
			quizType = '字谜';
		}
		else if(quizContent.type == 'textInput') {
			quizType = '填空';
		}

		quizSummary  = '<div><i class="glyphicon glyphicon-tags"></i>题型：<span class="label label-info">' + 
			quizType + '</span></div>';
		if(quizContent.countdown > 0) {
			quizSummary += '<div><i class="glyphicon glyphicon-time"></i>限时：<strong>' + quizContent.countdown + '</strong> 秒</div>';
		}

		$('#quiz-summary').html(quizSummary);

		$('.quiz-preview').nkrQuiz({
			'mode' : 'single',
			'data' : quizContent
		});
	}

	$('#edit-quiz-options').on('click', function(e) {
		DismissErrorMessage();
		
		var IS_JSON = true;
		try {
			var quizContent = $.parseJSON($('#quiz_content').val());	
		}
		catch (err) {
			IS_JSON = false;
		}
		
		if(IS_JSON) {

			// countdown

			if(quizContent.countdown > 0) {
				$('#enable-quiz-countdown input[name="enable-quiz-countdown"]').prop('checked', true);
				$('#enable-quiz-countdown').iCheck('update');

				$('#quiz-countdown-input').val(quizContent.countdown);
				$('#quiz-countdown-input').show();
			}

			var quizTypeControl = $('#quiz-type');
			var quizTypeId = 0;
			if(quizContent.type == 'singleSelection') {
				quizTypeId = 1;
				
				var quizOptions = '';
				if(quizContent.options.length >= 2) {
					$('.quiz-option-single').remove();
					for (var i = 0; i < quizContent.options.length; i++) {
						quizOptions += '<tr class="quiz-option-single">' + 
							'<td><input type="text" class="form-control" placeholder="输入答题选项" value="' + 
							quizContent.options[i].content + '"></td>' +
							'<td><input class="dlg-control" type="radio" name="quiz-option-single-answer"' + 
							(quizContent.answers[i].answer ? 'checked' : '') + '></td>' +
							'<td><a href="#" class="btn btn-danger btn-sm delete">删除</a></td>' +
							'</tr>';
					};
					$(quizOptions).insertBefore('.quiz-option-single-list .quiz-option-single-add');
					$('.quiz-option-single-list').iCheck(icheckOption);
				}
			}
			else if(quizContent.type == 'multipleSelection') {
				quizTypeId = 2;

				var quizOptions = '';
				if(quizContent.options.length >= 2) {
					$('.quiz-option-multiple').remove();
					for (var i = 0; i < quizContent.options.length; i++) {
						quizOptions += '<tr class="quiz-option-multiple">' + 
							'<td><input type="text" class="form-control" placeholder="输入答题选项" value="' + 
							quizContent.options[i].content + '"></td>' +
							'<td><input class="dlg-control" type="checkbox" name="quiz-option-single-answer"' + 
							(quizContent.answers[i].answer ? 'checked' : '') + '></td>' +
							'<td><a href="#" class="btn btn-danger btn-sm delete">删除</a></td>' +
							'</tr>';
					};
					$(quizOptions).insertBefore('.quiz-option-multiple-list .quiz-option-multiple-add');
					$('.quiz-option-multiple-list').iCheck(icheckOption);
				}
			}
			else if(quizContent.type == 'crossword') {
				quizTypeId = 3;

				var quizOptions = '';
				if(quizContent.options.length == 1) {
					$('#quiz-option-crossword-words').val(quizContent.options[0].content);
					$('#quiz-option-crossword-answer').val(quizContent.answers[0].answer);
					UpdateCrosswordWordsHint();
				}
			}
			else if(quizContent.type == 'textInput') {
				quizTypeId = 4;

				var quizOptions = '';
				if(quizContent.options.length >= 1 ) {
					$('.quiz-option-textinput').remove();

					for (var i = 0; i < quizContent.options.length; i++) {
						quizOptions += '<tr class="quiz-option-textinput">' +
							'<td><input class="form-control" type="text" placeholder="填空标签" data-textinput-field="label" value ="' + quizContent.options[i].content + 
							'"></td><td><input class="form-control" type="text" placeholder="填空答案" data-textinput-field="answer" value="' + 
							quizContent.answers[i].answer + 
							'"></td><td><a href="#" class="btn btn-danger btn-sm delete">删除</a></td></tr>';
					};
				}

				$(quizOptions).insertBefore('.quiz-option-textinput-list .quiz-option-textinput-add');
			}

			if(quizTypeId > 0) {
				quizTypeControl.attr('data-quiz-type', quizTypeId);
				quizTypeControl.find('.quiz-type-select')
					.text(quizTypeControl.find('.dropdown-menu li a[data-quiz-type="' + quizTypeId + '"]').text());
				var optionPage = $('.quiz-option-pages li.quiz-option-page[data-quiz-type="' + quizTypeId + '"]');
				if(optionPage.length) {
		  			$(optionPage)
		  				.removeClass('hidden')
		  				.siblings('.quiz-option-page').addClass('hidden');
		  		}
		  		else
		  		{
					$('.quiz-option-pages li.quiz-option-page.default-page')
						.removeClass('hidden')
						.siblings('.quiz-option-page').addClass('hidden');  			
		  		}
			}
		}
	});

	// 答题选项编辑对话框

	function ShowErrorMessage(msg) {
		$('#quiz-option-error-message')
			.removeClass('hidden')
			.fadeIn()
			.find('em').text(msg);
	}

	function DismissErrorMessage() {
		$('#quiz-option-error-message')
			.fadeOut()
			.addClass('hidden')
			.find('em').text('');
	}

	$('input.dlg-control').iCheck(icheckOption);

	$('#enable-quiz-countdown').on('ifChecked', function(){
		$('#quiz-countdown-input').show().find('input').focus();
	});

	$('#enable-quiz-countdown').on('ifUnchecked', function(){
		$('#quiz-countdown-input').hide();
	});	

	$('#quiz-type .dropdown-menu li a').on('click', function(e){
		var sel = $(this);
		var selText = sel.text();
		var quizType = sel.attr('data-quiz-type');
  		
  		sel.parents('.btn-group').find('.dropdown-toggle span.quiz-type-select').text(selText);
  		$('#quiz-type').attr('data-quiz-type', quizType);

  		var optionPage = $('.quiz-option-pages li.quiz-option-page[data-quiz-type="' + quizType + '"]');
  		if(optionPage.length) {
  			$(optionPage)
  				.removeClass('hidden')
  				.siblings('.quiz-option-page').addClass('hidden');
  		}
  		else
  		{
			$('.quiz-option-pages li.quiz-option-page.default-page')
				.removeClass('hidden')
				.siblings('.quiz-option-page').addClass('hidden');  			
  		}

  		e.preventDefault();
	});

	// 单项选择题

	$('.quiz-option-single-add a.add').on('click', function(e){
		var options = $('.quiz-option-single-list .quiz-option-single');
		var optionCount = options.length;
		if(optionCount <= 15) {
			$('<tr class="quiz-option-single">' +
				'<td><input type="text" class="form-control" placeholder="输入答题选项"></td>' +
				'<td><input class="dlg-control" type="radio" name="quiz-option-single-answer"></td>' +
				'<td><a href="#" class="btn btn-danger btn-sm delete">删除</a></td>' +
				'</tr>')
				.insertAfter(options[optionCount - 1])
				.iCheck(icheckOption);
		}

		e.preventDefault();
	});

	$('.quiz-option-single-list').on('click', 'a.delete', function(e) {
		var options = $('.quiz-option-single-list .quiz-option-single');

		if(options.length > 2) {
			$(this).closest('.quiz-option-single').remove();
		}

		e.preventDefault();
	});

	// 多项选择题

	$('.quiz-option-multiple-add a.add').on('click', function(e){
		var options = $('.quiz-option-multiple-list .quiz-option-multiple');
		var optionCount = options.length;
		if(optionCount < 15) {
			$('<tr class="quiz-option-multiple">' +
				'<td><input type="text" class="form-control" placeholder="输入答题选项"></td>' +
				'<td><input class="dlg-control" type="checkbox" name="quiz-option-multiple-answer"></td>' +
				'<td><a href="#" class="btn btn-danger btn-sm delete">删除</a></td>' +
				'</tr>')
				.insertAfter(options[optionCount - 1])
				.iCheck(icheckOption);
		}

		e.preventDefault();
	});

	$('.quiz-option-multiple-list').on('click', 'a.delete', function(e) {
		var options = $('.quiz-option-multiple-list .quiz-option-multiple');

		if(options.length > 2) {
			$(this).closest('.quiz-option-multiple').remove();
		}

		e.preventDefault();
	});

	// 成语字谜

	var wordDB = '的一是了我不人在他有这个上们来到时大地为子中你说生国年着就那和要她出也得里后自以会家可下而过天去能对小多然于心学么之都好看起发当没成只如事把还用第样道想作种开美总从无情己面最女但现前些所同日手又行意动方期它头经长儿回位分爱老因很给名法间斯知世什两次使身者被高已亲其进此话常与活正感见明问力理尔点文几定本公特做外孩相西果走将月十实向声车全信重三机工物气每并别真打太新比才便夫再书部水像眼等体却加电主界门利海受听表德少克代员许稜先口由死安写性马光白或住难望教命花结乐色更拉东神记处让母父应直字场平报友关放至张认接告入笑内英军候民岁往何度山觉路带万男边风解叫任金快原吃妈变通师立象数四失满战远格士音轻目条呢病始达深完今提求清王化空业思切怎非找片罗钱紶吗语元喜曾离飞科言干流欢约各即指合反题必该论交终林请医晚制球决传画保读运及则房早院量苦火布品近坐产答星精视五连司巴奇管类未朋且婚台夜青北队久乎越观落尽形影红爸百令周吧识步希亚术留市半热送兴造谈容极随演收首根讲整式取照办强石古华拿计您装似足双妻尼转诉米称丽客南领节衣站黑刻统断福城故历惊脸选包紧争另建维绝树系伤示愿持千史谁准联妇纪基买志静阿诗独复痛消社算义竟确酒需单治卡幸兰念举仅钟怕共毛句息功官待究跟穿室易游程号居考突皮哪费倒价图具刚脑永歌响商礼细专黄块脚味灵改据般破引食仍存众注笔甚某沉血备习校默务土微娘须试怀料调广苏显赛查密议底列富梦错座参八除跑亮假印设线温虽掉京初养香停际致阳纸李纳验助激够严证帝饭忘趣支春集丈木研班普导顿睡展跳获艺六波察群皇段急庭创区奥器谢弟店否害草排背止组州朝封睛板角况曲馆育忙质河续哥呼若推境遇雨标姐充围案伦护冷警贝著雪索剧啊船险烟依斗值帮汉慢佛肯闻唱沙局伯族低玩资屋击速顾泪洲团圣旁堂兵七露园牛哭旅街劳型烈姑陈莫鱼异抱宝权鲁简态级票怪寻杀律胜份汽右洋范床舞秘午登楼贵吸责例追较职属渐左录丝牙党继托赶章智冲叶胡吉卖坚喝肉遗救修松临藏担戏善卫药悲敢靠伊村戴词森耳差短祖云规窗散迷油旧适乡架恩投弹铁博雷府压超负勒杂醒洗采毫嘴毕九冰既状乱景席珍童顶派素脱农疑练野按犯拍征坏骨余承置臓彩灯巨琴免环姆暗换技翻束增忍餐洛塞缺忆判欧层付阵玛批岛项狗休懂武革良恶恋委拥娜妙探呀营退摇弄桌熟诺宣银势奖宫忽套康供优课鸟喊降夏困刘罪亡鞋健模败伴守挥鲜财孤枪禁恐伙杰迹妹藸遍盖副坦牌江顺秋萨菜划授归浪听凡预奶雄升碃编典袋莱含盛济蒙棋端腿招释介烧误';
	
	String.prototype.shuffle = function () {
	    var a = this.split(''),
	        n = a.length;

	    for(var i = n - 1; i > 0; i--) {
	        var j = Math.floor(Math.random() * (i + 1));
	        var tmp = a[i];
	        a[i] = a[j];
	        a[j] = tmp;
	    }
	    return a.join('');
	}

	function UpdateCrosswordWordsHint() {
		var crosswordAnswer = $('#quiz-option-crossword-answer').val().trim().replace(/\s+/g, '');
		if(crosswordAnswer.length > 0) {
			var crosswordWords = $('#quiz-option-crossword-words').val().trim().replace(/\s+/g, '');
			var match = (crosswordWords.length == crosswordAnswer.length * 8);
			var hint = '可选汉字个数<em class="' + (match ? 'match' : 'mismatch') + '">' + crosswordWords.length + 
				'</em>/<em class="' + (match ? 'match' : 'mismatch') + '">' + crosswordAnswer.length * 8 + '</em>';
			$('#quiz-option-crossword-words-hint').html(hint);
		}
	}

	$('#quiz-option-crossword-words').on('input', function(){
		UpdateCrosswordWordsHint();
	});

	$('#quiz-option-crossword-auto').on('click', function(e){
		var answer = $('.quiz-option-crossword-answer input[name="quiz-option-crossword-answer"]')
			.val().trim().replace(/\s+/g, '');

		if(answer.length) {
			var words = answer;
			var wordsCount = answer.length * 8;

			for (var i = 0; i < wordsCount - answer.length; i++) {
				words += wordDB[Math.floor(Math.random() * (wordDB.length - 1))];
			};

			$('.quiz-option-crossword-words textarea[name="quiz-option-crossword-words"]').val(words.shuffle());
			UpdateCrosswordWordsHint();

			DismissErrorMessage();
		} else {
			ShowErrorMessage('请先输入字谜答案');
		}

		e.preventDefault();
	});

	$('#quiz-option-crossword-shuffle').on('click', function(e){
		var wordsInput = $('.quiz-option-crossword-words textarea[name="quiz-option-crossword-words"]');
		var words = wordsInput.val().trim().replace(/\s+/g, '');

		if(words.length) {
			wordsInput.val(words.shuffle());
			UpdateCrosswordWordsHint();
		}

		e.preventDefault();
	});

	$('#quiz-option-error-message i').on('click', function(e){
		$('#quiz-option-error-message')
			.fadeOut()
			.addClass('hidden');
	});

	// 完形填空

	$('.quiz-option-textinput-add a.add').on('click', function(e){
		var options = $('.quiz-option-textinput-list .quiz-option-textinput');
		var optionCount = options.length;
		if(optionCount < 6) {
			$('<tr class="quiz-option-textinput">' +
				'<td><input class="form-control" type="text" placeholder="填空标签" data-textinput-field="label"></td>' +
				'<td><input class="form-control" type="text" placeholder="填空答案" data-textinput-field="answer"></td>' +
				'<td><a href="#" class="btn btn-danger btn-sm delete">删除</a></td>' +
				'</tr>')
				.insertAfter(options[optionCount - 1]);
		}

		e.preventDefault();
	});

	$('.quiz-option-textinput-list').on('click', 'a.delete', function(e) {
		var options = $('.quiz-option-textinput-list .quiz-option-textinput');

		if(options.length > 1) {
			$(this).closest('.quiz-option-textinput').remove();
		}

		e.preventDefault();
	});

	// 确认保存设置

	$('#quiz-options-save').on('click', function(e) {

		// 检查答题类型

		var quizTypeId = $('#quiz-type').attr('data-quiz-type');
		var countdown = 0;
		if($('#quiz-countdown-input').is(':visible')) {
			var countdownInputControl = $('#quiz-countdown-input');
			var countdownInput = countdownInputControl.val().trim();

			if(Math.floor(countdownInput) == countdownInput && $.isNumeric(countdownInput)) {
				countdown = countdownInput;
				if(countdown < 10) {
					ShowErrorMessage('答题时间不能少于10秒');
					return;
				}
			}
			else {
				countdownInputControl.focus();
				ShowErrorMessage('请输入有效的答题时限');

				return;
			}
		}

		var quizType = '';
		var quizSummary = '';
		var isValidOption = true;
		var hasAnswer = false;
		var options = [];
		var answers = [];
		if(quizTypeId == 1) {
			// 单项选择
			$('.quiz-option-single').each(function(i, element){
				var $elements = $(element).children();
				var optionInput = $($elements[0]).find('input');
				var optionValue = optionInput.val().trim();
				
				if(!optionValue.length) {
					optionInput.focus();
					ShowErrorMessage('请输入答题选项');

					isValidOption = false;
					return;
				}

				// 检查重复选项

				for (var i = 0; i < options.length; i++) {
					if(optionValue == options[i]['content']) {
						optionInput.focus();
						ShowErrorMessage('请不要输入重复的答题选项');

						isValidOption = false;
						return;
					}
				};

				options = options.concat({'content' : optionValue});

				var isAnswer = $($elements[1]).find('.iradio_square-blue').hasClass('checked');
				answers = answers.concat({'answer':isAnswer, 'score' : isAnswer ? 10 : 0});

				hasAnswer |= isAnswer;
			});

			if(!isValidOption) {
				return;
			}

			if(!hasAnswer) {

				ShowErrorMessage('请设置一个答案');
				return;
			}

			quizSummary  = '<div><i class="glyphicon glyphicon-tags"></i>题型：<span class="label label-info">单选</span></div>';

			quizType = 'singleSelection';

		} else if(quizTypeId == 2) {
			// 多项选择
			$('.quiz-option-multiple').each(function(i, element){
				var $elements = $(element).children();
				var optionInput = $($elements[0]).find('input');
				var optionValue = optionInput.val().trim();
				
				if(!optionValue.length) {
					optionInput.focus();
					ShowErrorMessage('请输入答题选项');

					isValidOption = false;
					return;
				}

				// 检测重复选项

				for (var i = 0; i < options.length; i++) {
					if(optionValue == options[i]['content']) {
						optionInput.focus();
						ShowErrorMessage('请不要输入重复的答题选项');

						isValidOption = false;
						return;
					}
				};

				options = options.concat({'content' : optionValue});

				var isAnswer = $($elements[1]).find('.icheckbox_square-blue').hasClass('checked');
				answers = answers.concat({'answer':isAnswer, 'score' : isAnswer ? 10 : 0});

				hasAnswer |= isAnswer;
			});

			if(!isValidOption) {
				return;
			}

			if(!hasAnswer) {

				ShowErrorMessage('请至少设置一个答案');
				return;
			}

			quizSummary  = '<div><i class="glyphicon glyphicon-tags"></i>题型：<span class="label label-info">多选</span></div>';
			quizType = 'multipleSelection';
		} else if(quizTypeId == 3) {
			// 成语字谜
			var crosswordAnswerInput = $('#quiz-option-crossword-answer');
			var crosswordAnswer = crosswordAnswerInput.val().trim().replace(/\s+/g, '');
			if(!crosswordAnswer.length) {
				crosswordAnswerInput.val(crosswordAnswer);
				crosswordAnswerInput.focus();
				ShowErrorMessage('请输入字谜答案');

				return;
			} else if(crosswordAnswer.length > 5) {
				crosswordAnswerInput.focus();
				crosswordAnswerInput.val(crosswordAnswer);
				ShowErrorMessage('字谜长度请不要超过5');

				return;
			}

			var crosswordWordsInput = $('#quiz-option-crossword-words');
			var crosswordWords = crosswordWordsInput.val().trim().replace(/\s+/g, '');
			if(crosswordWords.length != crosswordAnswer.length * 8) {
				crosswordWordsInput.val(crosswordWords);
				crosswordWordsInput.focus();
				ShowErrorMessage('可选汉字长度必须为：' + crosswordAnswer.length + ' × 8 = ' + crosswordAnswer.length * 8);

				return;
			}

			options = [{'content' : crosswordWords, 'wordcount' : crosswordAnswer.length}];
			answers = [{'answer' : crosswordAnswer, 'score' : 10}];
			quizSummary  = '<div><i class="glyphicon glyphicon-tags"></i>题型：<span class="label label-info">字谜</span></div>';
			quizType = 'crossword';

		} else if(quizTypeId == 4) {
			// 完形填空
			$('.quiz-option-textinput').each(function(i, element){
				var $elements = $(element).children();
				var optionLabelInput = $($elements[0]).find('input');
				var optionLabelValue = optionLabelInput.val().trim();
				
				if(!optionLabelValue.length) {
					optionLabelInput.focus();
					ShowErrorMessage('请输入填空的说明文字');

					isValidOption = false;
					return;
				}

				var optionAnswerInput = $($elements[1]).find('input');
				var optionAnswerValue = optionAnswerInput.val().trim();

				if(!optionAnswerValue.length) {
					optionAnswerInput.focus();
					ShowErrorMessage('请输入相应的答案');

					isValidOption = false;
					return;
				}

				// 检测重复选项

				for (var i = 0; i < options.length; i++) {
					if(optionLabelValue == options[i]['content']) {
						optionLabelInput.focus();
						ShowErrorMessage('请不要输入重复的答题选项');

						isValidOption = false;
						return;
					}
				};

				options = options.concat({'content' : optionLabelValue});
				answers = answers.concat({'answer': optionAnswerValue, 'score' : 10});
			});

			if(!isValidOption) {
				return;
			}

			quizSummary  = '<div><i class="glyphicon glyphicon-tags"></i>题型：<span class="label label-info">填空</span></div>';
			quizType = 'textInput';

		} else {
			ShowErrorMessage('请选择答题类型');

			return;
		}

		DismissErrorMessage();

		if(countdown > 0) {
			quizSummary += '<div><i class="glyphicon glyphicon-time"></i>限时：<strong>' + countdown + '</strong> 秒</div>';
		}

		var quizItem = {
			'type' : quizType,
			'countdown' : countdown,
			'description' : '',
			'options' : options,
			'answers' : answers
		};

		$('#quiz_content').val(JSON.stringify(quizItem));
		$('#quiz-summary').html(quizSummary);
		$('.quiz-preview').nkrQuiz({
	        "mode" : "single",
	        "showSubmit" : false,
	        "data" : $.parseJSON($('#quiz_content').val())
	    });
		$('#dlg-quiz-options').modal('hide');

		e.preventDefault();
	})

});

$(function(){

	// 发表问题成功检测 

	if(PUBLISH_SUCCESS_HINT) {
		var textInfo = '<p>非常感谢你分享问题</p>';
		textInfo += '<p><span class="c-green">+ ' + PUBLISH_SUCCESS_INTEGRAL  + '</span> 积分</p>';
		textInfo += '<p>你当前剩余 <strong>' + USER_INTEGRAL + '</strong> 积分</p>';
		textInfo += '<div class="alert-edit-solution m-t-20"><p>点击<span class="hidden-xs">问题页面卡右上</span><span class="help-button"><i class="md md-create"></i></span>按钮，<span class="hidden-xs">你可以</span>编辑修改问题</p><p>点击<span class="hidden-xs">问题页面卡右上</span><span class="help-button"><i class="md md-spellcheck"></i></span>按钮，<span class="hidden-xs">你可以</span><strong>撰写答案解析</strong></p></div>';
		swal({   
        	title: '发表问题成功',
        	text: textInfo,   
        	html: true,
        	confirmButtonText: "确定",
        	type: 'success'
        });
	}

	// 问题标签

	$('.question-loader').on('click', '.question-tag',  function(e){
		var tag = $(this);
		window.location.href = G_BASE_URL + '/question/' + tag.attr('data-type') + '-' + tag.attr('data-id');
	});

	// 答题记录

	$('.question-loader').on('click', '.user-quiz-record-message', function(e){
		var icon = $(this).find('i');
		var recordList = $(this).siblings('.user-quiz-record-items');

		if(recordList.is(":visible")) {
			recordList.slideUp();
			icon.removeClass('md-expand-more').addClass('md-chevron-right');
		} else {
			icon.removeClass('md-chevron-right').addClass('md-expand-more');
			recordList.slideDown();
		}
	});

	// 问题内容

	function formatCountdownInfo() {
		// 限时答题，限时时间

		$('.countdown-value').each(function() {
			var countdownElement = $(this);
			var seconds = parseInt(countdownElement.attr('data-countdown'));

			var hour = parseInt(seconds / 3600);
	        var minute = parseInt(seconds / 60);
	        var second = parseInt(seconds % 60);

	        var hourDial = countdownElement.find('.dial.hour');
	        var minuteDial = countdownElement.find('.dial.minute');
	        var secondDial = countdownElement.find('.dial.second');

	        var hourElement = countdownElement.find('.countdown-hour');

	        countdownElement.find('.dial').knob({
		        'width' : 90,
		        'height' : 90,
		        'min' : 0,
		        'max' : 60,
		        'readOnly' : true,
		        'fgColor': '#039AF4',
		        'bgColor': '#e0e0e0',
		        'inputColor': '#039AF4',
		        'thickness': 0.1,

		        format: function (v) {return (60 - v) % 60;}
		    });

	       
            hourDial.trigger(
                'configure',
                {
                    'max': hour
                }
            );
            hourDial.val(hour).trigger('change');
	        if(hour > 0) {
	            hourElement.show();
	        } else {
	            hourElement.hide();
	        }

            minuteDial.val((60 - minute) % 60).trigger('change');
            if(minute == 0) {
            	minuteDial.trigger(
	                'configure',
	                {
	                    'fgColor': '#f44336',
		        		'bgColor': '#f44336',
		        		'inputColor': '#f44336'
	                }
	            );
            }
            
            secondDial.trigger(
                'configure',
                {
                    'max': 60
                }
            );
            secondDial.val((60 - second) % 60).trigger('change');
            if(second == 0) {
            	secondDial.trigger(
	                'configure',
	                {
	                    'fgColor': '#039AF4',
		        		'bgColor': '#039AF4',
		        		'inputColor': '#039AF4'
	                }
	            );
            }
		});
	}

	// 加载问题内容

	function loadQuestionContent() {
		// 加载动画

		var spiner = '<div class="sk-circle">' +
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
		$('.question-loader').html(spiner);

		$.get(G_BASE_URL + '/question/ajax/init_question_content/id-' + QUESTION_ID, function (response) {
			$('.question-loader').html(response);

			var QUIZ_RETRY_COUNT = $('input[name=question-quiz-record-try-count]').val();
			var PASSED_QUIZ = $('input[name=question-quiz-record-passed]').val();
			var takenQuiz = (QUIZ_RETRY_COUNT > 0);

			formatCountdownInfo();
			parseQuestionQuiz(!takenQuiz);

			// 提示信息

			if ($('[data-toggle="tooltip"]')[0]) {
		        $('[data-toggle="tooltip"]').tooltip({container: 'body'});
		    }

			// 提示添加答案解析

			var addSolutionShowHintKey = 'add_solution_hint_shown_' + QUESTION_ID; 
			var addQuestionSolutionControl = $('.add-question-solution');
			if(localStorage.getItem(addSolutionShowHintKey) != 'true' && addQuestionSolutionControl.attr('data-toggle') == 'popover')
			{
				addQuestionSolutionControl.popover({html: true, placement: 'top'}).popover('show');
			}
		});
	}

	// 限时答题问题开始答题

	function beginCountdownQuestion() {
		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		// 倒计时问题开始答题

		$.get(G_BASE_URL + '/question/ajax/begin_question_quiz_countdown/id-' + QUESTION_ID, function (response) {
			$('.question-loader').html(response).css('opacity',0).animate({opacity:1}, 300);
			parseQuestionQuiz();
		});
	}

	function submitAnswerHandle(answer, spendTime) {
		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		// 检查问题答案

		$.get(G_BASE_URL + '/question/ajax/question_quiz_submit_answer/question_id-' + QUESTION_ID + '__answer-' + answer + '__spend_time-' + spendTime + '__record_id-' + QUESTION_QUIZ_RECORD_ID, function (quiz_result) {

			// 检查是否为特殊用户

			if(quiz_result['is_special_user']) {
				return;
			}

			// 检查是否为有效答案

			if(quiz_result['internal_error']) {
				sweetAlert("系统错误", "答题无效！", "error");
				window.location.href = G_BASE_URL;
				return;
			}

			// 检查是否为正确答案

			var textInfo = '';
			if(spendTime > 0)
			{
				textInfo = '<p><i class="md md-timer"></i> 答题用时 <span><strong>' + spendTime + '</strong> 秒</span></p>';
			}

            if (quiz_result['correct']) {
            	if(quiz_result.integral)
				{
					textInfo += '<p><span class="c-green"> +' + quiz_result.integral + '</span> 积分</p>';
					textInfo += '<p>你当前剩余 <span><strong>' + (quiz_result.user_integral + quiz_result.integral) + '</strong></span> 积分</p>';
				}

                swal({   
                	title: '回答正确',
                	text: textInfo,   
                	html: true,
                	confirmButtonText: "确定",
                	type: 'success'
                	},
                	function() {
                		loadQuestionContent();
                	}
                );

            } else {
            	if(quiz_result.integral)
				{
					textInfo += '<p><span class="c-red"> -' + quiz_result.integral + '</span> 积分</p>';
					textInfo += '<p>你当前剩余 <span><strong>' + (quiz_result.user_integral - quiz_result.integral) + '</strong></span> 积分</p>';
				}
				swal({   
                	title: '回答错误',   
                	text: textInfo,   
                	html: true,
                	confirmButtonText: "确定",
                	type: 'error'
                	},
                	function() {
                		loadQuestionContent();
                	}
                );
            }
        }, 'json');
	}

	function answerTimeoutHandle() {
		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		// 提交超时请求

		$.get(G_BASE_URL + '/question/ajax/question_quiz_timeout/record_id-' + QUESTION_QUIZ_RECORD_ID + '__question_id-' + QUESTION_ID, function (result) {
			
			// 特殊用户

			if(result.is_special_user)
			{
				return;
			}
			
			// 超时提示

			textInfo = '<p class="c-deeporange"><i class="md md-timer-off"></i> 答题超时</p>';
			if(result.required_integral)
			{
				textInfo += '<p><span class="c-red"> -' + result.required_integral + '</span> 积分</p>';
				textInfo += '<p>你当前剩余 <span><strong>' + (result.user_integral - result.required_integral) + '</strong></span> 积分</p>';
			}

			swal({
            	title: '答题失败',
            	text: textInfo,   
            	html: true,
            	confirmButtonText: "确定",
            	type: 'error'
            	},
            	function() {
            		loadQuestionContent();
            	}
            );
		}, 'json');
	}

	function parseQuestionQuiz(enabled) {

		if(typeof enabled == 'undefined') {
			enabled = true;
		}

		QUESTION_QUIZ = $('input[name=question-quiz-content]').val();
		QUESTION_QUIZ_ID = $('input[name=question-quiz-id]').val();
		QUESTION_QUIZ_RECORD_ID = $('input[name=question-quiz-record-id]').val();

		var IS_JSON = true;
		try {
			var quizContent = $.parseJSON(QUESTION_QUIZ);	
		}
		catch (err) {
			IS_JSON = false;
		}

		if (IS_JSON) {
			$('.question-quiz-content').nkrQuiz({
				'mode' : 'single',
				'showSubmit' : true,
				'enableCountdown' : true,
				'data' : quizContent,
				'enabled' : enabled,
				'onSubmitAnswer' : submitAnswerHandle,
				'onTimeout' : answerTimeoutHandle
			});
		}
	}

	loadQuestionContent();

	// 重新答题积分操作

	function retryQuizIntegralAction(callback)
	{
		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		// 获取答题积分

		$.get(G_BASE_URL + '/question/ajax/get_question_quiz_retry_integral/question_id-' + QUESTION_ID, function (result){
			if(result.err)
			{
				AWS.alert(result.err);

				return;
			}

			if(result.not_enough_integral)
			{
				textInfo = '<p>你当前剩余 <strong>' + result.user_integral + '</strong> 积分</p>' + '<p>重新答题需要 <span class="c-red">' + result.required_integral + '</span> 积分</p>';
				textInfo += '<div class="m-t-20 alert-get-integral"><i class="md md-help"></i> 你可以通过<a class="c-lightblue" href="' + G_BASE_URL + '/publish/' + '">分享题目</a>来获取额外积分，更多获取积分的方式可以查看<a class="c-lightblue" href="' + G_BASE_URL + '/integral/rule/' + '">积分规则</a></div>';
				swal({
					title: '积分不足',
		        	text: textInfo,
		        	html: true,
		        	confirmButtonText: "获取积分",
		        	showCancelButton: true,
				    cancelButtonText: "返回问题",
		        	type: 'info'
		        	},
		        	function() {
		        		window.location.href = G_BASE_URL + '/integral/rule/';
						return;	
		        	}
		        );

				return;
			}
				
			if(result.required_integral > 0)
			{
				swal({   
		        	title: '积分提示',
		        	text: '<p>你当前剩余 <strong>' + result.user_integral + '</strong> 积分</p>' + '<p>重新答题需要 <span class="c-red">' + result.required_integral + '</span> 积分</p>',   
		        	html: true,
		        	confirmButtonText: "继续",
		        	showCancelButton: true,
		        	cancelButtonText: "取消",
		        	type: 'info'
		        	},
		        	function() {
		        		// 添加积分记录

		        		$.get(G_BASE_URL + '/question/ajax/save_question_quiz_retry_integral/question_id-' + QUESTION_ID, function (result) {
							if (result.err)
							{
								AWS.alert(result.err);

								return;
							}
							
							callback();
						}, 'json');
		        	}
		        );
			}
		}, 'json');
	}

	//////////////////////////////////////////
	// 答题操作
	//////////////////////////////////////////

	// 限时答题：开始答题

	$('.question-loader').on('click', '#question-action-countdown-start', function(e) {
		e.preventDefault();
		beginCountdownQuestion();
	});

	// 限时答题：重新答题

	$('.question-loader').on('click', '#question-action-countdown-retry', function(e) {
		e.preventDefault();

		retryQuizIntegralAction(function(){
			beginCountdownQuestion();
		});
	});

	// 普通答题： 重新答题

	$('.question-loader').on('click', '#question-action-retry', function (e) {
		e.preventDefault();

		retryQuizIntegralAction(function(){
			$('.question-quiz-board').hide();
			parseQuestionQuiz();
			// $('.question-quiz-content').fadeIn();
		});
	});

	// 开放答题：答题讨论

	function QuestionDiscuss() {
		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		// 滚动到回复框

		$('html, body').animate({
			scrollTop: $('.comment-box').offset().top - 150
		}, {
			duration: 600,
			queue: true
		});

		//获取焦点

		if(EDITOR) {
			EDITOR.focus();
		}
	}

	// 我要回答问题／继续参与讨论

	$('.question-loader').on('click', '.question-action-discuss', function (e) {
		e.preventDefault();

		QuestionDiscuss();
	});
	
	// 查看答案解析

	function getQuestionSolution() {
		$.get(G_BASE_URL + '/question/ajax/get_question_solution/question_id-' + QUESTION_ID, function (response) {
			if (!response)
			{
				AWS.alert('获取答案解析失败！');

				return;
			}

			$('.question-quiz-board').hide();
			$('.question-solution').html(response).fadeIn();
		});
	}

	$('.question-quiz-actions').on('click', 'li.question-quiz-action-item.view-solution a', function (e) {
		e.preventDefault();
		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		// 获取答案及相关的记录信息

		$.get(G_BASE_URL + '/question/ajax/get_question_solution_record/question_id-' + QUESTION_ID, function (result){
			
			if(result.err) {
				AWS.alert(result.err);

				return;
			}

			// 没有答案

			if(result.solution_not_exist)
			{
				swal({   
		        	title: '系统提示',
		        	text: '该问题目前还没有答案解析！',   
		        	html: true,
		        	confirmButtonText: "确定",
		        	type: 'info'
		        });

				return;
			}

			if(result.record_exist) {
				// 直接获取答案

				getQuestionSolution();
			} else {

				// 发送购买答案请求

				$.get(G_BASE_URL + '/question/ajax/get_question_view_solution_integral/question_id-' + QUESTION_ID, function (result){
					if(result.err)
					{
						AWS.alert(result.err);

						return;
					}

					if(result.not_enough_integral)
					{
						textInfo =  '<p>你当前剩余 <strong>' + result.user_integral + '</strong> 积分</p>' + '<p>查看答案解析需要 <span class="required-integral c-red">' + result.required_integral + '</span> 积分</p>'; 
						textInfo += '<div class="m-t-20 alert-get-integral"><i class="md md-help"></i> 你可以通过<a class="c-lightblue" href="' + G_BASE_URL + '/publish/' + '">分享题目</a>来获取额外积分，更多获取积分的方式可以查看<a class="c-lightblue" href="' + G_BASE_URL + '/integral/rule/' + '">积分规则</a></div>';
						swal({
							title: '积分不足',
				        	text: textInfo,
				        	html: true,
				        	confirmButtonText: "获取积分",
				        	showCancelButton: true,
				        	cancelButtonText: "返回问题",
				        	type: 'info'
				        	},
				        	function() {
				        		window.location.href = G_BASE_URL + '/integral/rule/';
								return;	
				        	}
				        );

						return;
					}

					// 提示并查看答案

					if(result.required_integral > 0)
					{
						swal({   
				        	title: '积分提示',
				        	text: '<p>你当前剩余 <strong>' + result.user_integral + '</strong> 积分</p>' + '<p>查看答案解析需要 <span class="c-red">' + result.required_integral + '</span> 积分</p>',
				        	html: true,
				        	confirmButtonText: "继续",
				        	showCancelButton: true,
				        	cancelButtonText: "取消",
				        	type: 'info'
				        	},
				        	function() {
				        		$.get(G_BASE_URL + '/question/ajax/save_question_view_solution_integral/question_id-' + QUESTION_ID, function (result) {
				        			if(result.err) {
				        				AWS.alert(result.err);
				        			}

				        			// 添加答案解析查看记录

					        		$.get(G_BASE_URL + '/question/ajax/save_question_solution_record/question_id-' + QUESTION_ID, function (result) {
										if (result.err)
										{
											AWS.alert(result.err);
										}
										else
										{
											// 获取答案

					        				getQuestionSolution();
										}
									}, 'json');
				        		});
				        	}
				        );
					}
				}, 'json');
			}
		}, 'json');
	});

	// 关闭答案解析

	$('.question-loader').on('click', '.question-solution .close-question-solution', function (e) {
		$('.question-solution').hide();
		$('.question-quiz-board').fadeIn();
		
		e.preventDefault();
	});

	// 参与答题讨论

	$('.question-quiz-actions').on('click', 'li.question-quiz-action-item.question-discuss a', function(e) {
		e.preventDefault();
		QuestionDiscuss();
	});

	// 出题

	$('.question-content').on('click', '.action-publish-question', function (e){
		window.location.href = G_BASE_URL + '/publish/';
		e.preventDefault();
	});

	// 答案详解添加提示框按钮

	$('body').on('click', '.add-solution-popover-close', function (e) {
		e.preventDefault();
		var addSolutionShowHintKey = 'add_solution_hint_shown_' + QUESTION_ID;
		localStorage.setItem(addSolutionShowHintKey, true);
		$('.add-question-solution').popover('hide').popover('destroy');
	});

	// 展开评论框

	function reloadAnswers() {
		$.get(G_BASE_URL + '/question/ajax/load_answers/question_id-' + QUESTION_ID, function (response) {
			$('.answer-items').hide().html(response).fadeIn();

			// 提示框

			if ($('[data-toggle="tooltip"]')[0]) {
		        $('[data-toggle="tooltip"]').tooltip({container: 'body'});
		    }
		});
	}

	if($('.answer-items').attr('data-load-answer') == 1) {
		reloadAnswers();
	}

	$('.load-question-answers').on('click', function (e){
		e.preventDefault();

		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		reloadAnswers();
		$('.load-question-answers-board').hide();
	});
});
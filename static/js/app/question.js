$(function(){

	// 加载问题内容

	function initQuestionContent() {
		$.get(G_BASE_URL + '/question/ajax/init_question_content/id-' + QUESTION_ID, function (response) {
			$('.question-content').html(response);

			var QUIZ_RETRY_COUNT = $('input[name=question-quiz-record-try-count]').val();
			var PASSED_QUIZ = $('input[name=question-quiz-record-passed]').val();
			var enableQuiz = true;

			if(QUIZ_RETRY_COUNT && QUIZ_RETRY_COUNT > 0) {

				// 关闭答题区

				enableQuiz = false;

				if(PASSED_QUIZ > 0) {
					$('.post-answer-action.retry').hide();
				}

				hideQuizContent();
			}

			parseQuestionQuiz(enableQuiz);
		});
	}

	initQuestionContent();

	// 限时答题开始

	$('.question-content').on('click', '#begin-question-quiz-countdown', function (e) {
		e.preventDefault();

		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		beginCountdownQuestion();
	});

	$('.question-content').on('click', '#retry-question-quiz-countdown', function (e) {
		e.preventDefault();

		// 积分操作请求

		// 重新加载题目

		beginCountdownQuestion();
	});

	// 重新加载限时答题

	function beginCountdownQuestion() {
		$.get(G_BASE_URL + '/question/ajax/begin_question_quiz_countdown/id-' + QUESTION_ID, function (response) {
			$('.countdown-question-welcome').fadeOut(400, function() {
				$('.question-content').html(response).css('opacity',0).animate({opacity:1}, 400);
				parseQuestionQuiz();
			});
		});
	}

	// 解析答题选项

	function hideQuizContent() {
		                    		
		// 隐藏答题选项

		question_quiz_container = $('.question-quiz-content');
		if(question_quiz_container.height() < 200) {
			question_quiz_container.height(200);
		}

    	$('.question-quiz-content')
        	.addClass('quiz-blur');
        $('.question-quiz-content-overlay')
        	.fadeIn(1000);
	}

	function updateQuizStats() {
		var retryCountControl = $('#quiz-retry-action span.retry-count');
		if(retryCountControl.length) {
			retryCountControl.text($('input[name=question-quiz-record-try-count]').val());
		} else {
			$('#quiz-retry-action').append('<span class="retry-count badge">' + $('input[name=question-quiz-record-try-count]').val() + '</span>');
		}

		$('.question-quiz-total-count .quiz-stats-count').text(QUESTION_QUIZ_STATS_TOTAL);
		var rate = 0;
		if(QUESTION_QUIZ_STATS_TOTAL > 0) {
			rate = QUESTION_QUIZ_STATS_PASSED * 100 / QUESTION_QUIZ_STATS_TOTAL;
		}

		$('.question-quiz-pass-rate .quiz-stats-rate').text(rate.toFixed(0));
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
				'onSubmitAnswer' : function (answer, spendTime) {

					// 检查用户是否登录

					if(G_USER_ID <= 0) {
						window.location.href = G_BASE_URL + '/account/login/';
						return;
					}

					// 检查问题答案

					$.get(G_BASE_URL + '/question/ajax/question_quiz_check_answer/question_id-' + QUESTION_ID + '__answer-' + answer + '__spend_time-' + spendTime + '__record_id-' + QUESTION_QUIZ_RECORD_ID, function (quiz_result) {

						// 检查是否为有效答案

						if(!quiz_result['is_valid_answer']) {
							sweetAlert("系统错误", "答案无效！", "error");
							window.location.href = G_BASE_URL;
							return;
						}

						// 检查是否为正确答案

						spendTimeInfo = '';
						if(spendTime > 0)
						{
							spendTimeInfo = '<div>用时 <span>' + spendTime + ' 秒</span></div>';
						}

		                if (quiz_result['correct']) {
		                    swal({   
		                    	title: '回答正确！',   
		                    	text: spendTimeInfo + '<div><span style="color:#A5DC86">+30<span> 积分</div>',   
		                    	html: true,
		                    	confirmButtonText: "确定",
		                    	type: 'success'
		                    	},
		                    	function() {
		                    		$('.post-answer-action.retry').hide();
		                    		hideQuizContent();
		                    	}
		                    );

		                } else {
							swal({   
		                    	title: '回答错误！',   
		                    	text: spendTimeInfo + '<div><span style="color:#F27474">-30<span> 积分</div>',   
		                    	html: true,
		                    	confirmButtonText: "确定",
		                    	type: 'error'
		                    	},
		                    	function() {
		                    		if(quiz_result['is_countdown']) {
		                    			window.location.href = G_BASE_URL + '/question/' + QUESTION_ID;
		                    		} else {
										hideQuizContent();
		                    		}
		                    	}
		                    );
		                }

		                // 更新答题统计

		                if(quiz_result['try_count']) {
		                	$('input[name=question-quiz-record-try-count]').val(quiz_result['try_count']);
		                }

		                if(quiz_result['passed_quiz']) {
		                	$('input[name=question-quiz-record-passed]').val(quiz_result['passed_quiz']);
		                }

		                if(quiz_result['quiz_stats_total']) {
		                	QUESTION_QUIZ_STATS_TOTAL = quiz_result['quiz_stats_total'];
		                	QUESTION_QUIZ_STATS_PASSED = quiz_result['quiz_stats_passed'];
		                }

		                updateQuizStats();

		            }, 'json');
				},
				'onTimeout' : function () {
					// 检查用户是否登录

					if(G_USER_ID <= 0) {
						window.location.href = G_BASE_URL + '/account/login/';
						return;
					}

					// 提交超时请求

					$.post(G_BASE_URL + '/question/ajax/question_quiz_timeout/quiz_id=' + QUESTION_QUIZ_ID, function (result) {
						// 超时提示

						swal({
	                    	title: '时间到',   
	                    	text: '<div>答题失败！</div><div><span style="color:#F27474">-30<span> 积分</div>',   
	                    	html: true,
	                    	confirmButtonText: "确定",
	                    	type: 'error'
	                    	},
	                    	function() {
	                    		initQuestionContent();
	                    	}
	                    );

						// 更新答题统计
					}, 'json');
				}
			});
		}
	}

	// 答题完成

	$('.question-content').on('click', '#quiz-retry-action', function (e) {
		e.preventDefault();


	});
});
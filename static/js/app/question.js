$(function(){

	// 加载问题内容

	function initQuestionContent() {
		$.get(G_BASE_URL + '/question/ajax/init_question_content/id-' + QUESTION_ID, function (response) {
			$('.question-content').html(response);

			var QUIZ_RETRY_COUNT = $('input[name=question-quiz-record-try-count]').val();
			var PASSED_QUIZ = $('input[name=question-quiz-record-passed]').val();
			var takenQuiz = (QUIZ_RETRY_COUNT > 0);

			parseQuestionQuiz(!takenQuiz);

			if(takenQuiz) {
				question_quiz_container = $('.question-quiz-content');
				if(question_quiz_container.height() < 200) {
					question_quiz_container.height(200);
				}

				$('.question-quiz-content')
			    	.addClass('quiz-blur');
			    $('.question-quiz-content-overlay')
			    	.fadeIn(1000);
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
			$('.countdown-question-welcome').fadeOut(400, function() {
				$('.question-content').html(response).css('opacity',0).animate({opacity:1}, 400);
				parseQuestionQuiz();
			});
		});
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

						var spendTimeInfo = '';
						if(spendTime > 0)
						{
							spendTimeInfo = '<p>用时 <span>' + spendTime + ' 秒</span></p>';
						}

		                if (quiz_result['correct']) {
		                    swal({   
		                    	title: '回答正确！',
		                    	text: spendTimeInfo + '<p><span style="color:#A5DC86">+30<span> 积分</p>',   
		                    	html: true,
		                    	confirmButtonText: "确定",
		                    	type: 'success'
		                    	},
		                    	function() {
		                    		initQuestionContent();
		                    	}
		                    );

		                } else {
							swal({   
		                    	title: '回答错误！',   
		                    	text: spendTimeInfo + '<p><span style="color:#F27474">-30<span> 积分</p>',   
		                    	html: true,
		                    	confirmButtonText: "确定",
		                    	type: 'error'
		                    	},
		                    	function() {
		                    		initQuestionContent();
		                    	}
		                    );
		                }
		            }, 'json');
				},
				'onTimeout' : function () {
					// 检查用户是否登录

					if(G_USER_ID <= 0) {
						window.location.href = G_BASE_URL + '/account/login/';
						return;
					}

					// 提交超时请求

					$.post(G_BASE_URL + '/question/ajax/question_quiz_timeout/record_id=' + QUESTION_QUIZ_RECORD_ID, function (result) {
					
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
					}, 'json');
				}
			});
		}
	}

	initQuestionContent();

	// 限时答题开始

	$('.question-content').on('click', '#begin-question-quiz-countdown', function (e) {
		e.preventDefault();

		beginCountdownQuestion();
	});

	// 限时答题：重新答题

	$('.question-content').on('click', '#retry-question-quiz-countdown', function (e) {
		e.preventDefault();

		// 积分操作请求



		// 重新加载题目

		beginCountdownQuestion();
	});

	// 普通答题： 重新答题

	$('.question-content').on('click', '#retry-question-quiz-normal', function (e) {
		e.preventDefault();

		// 重新尝试积分操作请求

		// 开发答题区

		parseQuestionQuiz();
		$('.question-quiz-content')
	    	.removeClass('quiz-blur');
	    $('.question-quiz-content-overlay')
	    	.fadeOut(300);
	});
});
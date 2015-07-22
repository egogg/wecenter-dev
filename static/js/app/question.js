$(function(){

	// 加载问题内容

	$.get(G_BASE_URL + '/question/ajax/init_question_content/id-' + QUESTION_ID, function (response) {
		$('.question-content').html(response);
		parseQuestionQuiz();
	});

	// 限时答题开始

	$('.question-content').on('click', '#begin-question-quiz-countdown', function (e) {
		loadCountdownQuestion();
		e.preventDefault();
	});

	// 重新加载限时答题

	function loadCountdownQuestion() {
		$.get(G_BASE_URL + '/question/ajax/begin_question_quiz_countdown/id-' + QUESTION_ID, function (response) {
			$('.countdown-question-welcome').fadeOut(400, function() {
				$('.question-content').html(response).hide().fadeIn(400);
				parseQuestionQuiz();
			});
		});
	}

	// 解析答题选项

	function parseQuestionQuiz() {
		var questionQuizControl = $('.question-quiz');
		QUESTION_QUIZ = questionQuizControl.attr('data-quiz-content');
		QUESTION_QUIZ_ID = questionQuizControl.attr('data-quiz-id');

		questionQuizControl.removeAttr('data-quiz-content');
		questionQuizControl.removeAttr('data-quiz-id');
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
				'onSubmitAnswer' : function (answer, spendTime) {

					// 检查用户是否登录

					if(G_USER_ID <= 0) {
						window.location.href = G_BASE_URL + '/account/login/';
						return;
					}

					// 检查问题答案

					$.get(G_BASE_URL + '/question/ajax/question_quiz_check_answer/quiz_id-' + QUESTION_QUIZ_ID + '__answer-' + answer + '__spend_time-' + spendTime, function (quiz_result) {

						// 检查是否为有效用户

						if(!quiz_result['is_valid_user']) {
							window.location.href = G_BASE_URL + '/account/login/';
							return;
						}

						// 检查是否为有效答案

						if(!quiz_result['is_valid_answer']) {
							sweetAlert("系统错误", "答案无效！", "error");
							return;
						}

						// 检查是否为正确答案

						spendTimeInfo = '';
						if(spendTime > 0)
						{
							spendTimeInfo = '<div>用时 <span>' + spendTime + ' 秒</span></div>';
						}

						function hideQuizContent(){
		                    		
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

		                if (quiz_result['correct']) {

		                	// 回答正确

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
		                    		hideQuizContent();	
		                    	}
		                    );
		                }

		                // 更新答题统计
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
	                    });

						// 更新答题统计
					}, 'json');
				}
			});
		}
	}
});
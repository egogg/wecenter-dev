$(function(){

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

	                if (quiz_result['correct']) {
	                    swal({   
	                    	title: '回答正确！',   
	                    	text: spendTimeInfo + '<div><span style="color:#A5DC86">+30<span> 积分</div>',   
	                    	html: true,
	                    	confirmButtonText: "确定",
	                    	type: 'success'
	                    });
	                } else {
						swal({   
	                    	title: '回答错误！',   
	                    	text: spendTimeInfo + '<div><span style="color:#F27474">-30<span> 积分</div>',   
	                    	html: true,
	                    	confirmButtonText: "确定",
	                    	type: 'error'
	                    });
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
});
$(function(){

	function showQuizContentOverlay()
	{
		$('.question-quiz-content').animate({opacity: 0}, 300);
		question_quiz_container = $('.question-quiz-content');
		if(question_quiz_container.height() < 200) {
			question_quiz_container.height(200);
		}
		
		$('.question-quiz-content-overlay').animate({opacity:1}, 300).show();
	}

	function showQuizContent()
	{
		$('.question-quiz-content-overlay')
	    	.animate({opacity:0}, 300).hide();
	    $('.question-quiz-content')
	    	.animate({opacity:1}, 300);

	    $('.question-quiz-content').removeAttr('style');
	}

	// 加载问题内容

	function initQuestionContent() {
		$.get(G_BASE_URL + '/question/ajax/init_question_content/id-' + QUESTION_ID, function (response) {
			$('.question-content').html(response);

			var QUIZ_RETRY_COUNT = $('input[name=question-quiz-record-try-count]').val();
			var PASSED_QUIZ = $('input[name=question-quiz-record-passed]').val();
			var takenQuiz = (QUIZ_RETRY_COUNT > 0);

			parseQuestionQuiz(!takenQuiz);

			if(takenQuiz) {
				showQuizContentOverlay();
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
			$('.countdown-question-welcome').animate({opacity:1}, 300);
			$('.question-content').html(response).css('opacity',0).animate({opacity:1}, 300);
			parseQuestionQuiz();
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

						var textInfo = '';
						if(spendTime > 0)
						{
							textInfo = '<p>用时 <span>' + spendTime + ' 秒</span></p>';
						}

						

		                if (quiz_result['correct']) {
		                	if(quiz_result.integral)
							{
								textInfo += '<p><span style="color:#A5DC86">+' + quiz_result.integral + '<span> 积分</p>';
							}

		                    swal({   
		                    	title: '回答正确！',
		                    	text: textInfo,   
		                    	html: true,
		                    	confirmButtonText: "确定",
		                    	type: 'success'
		                    	},
		                    	function() {
		                    		initQuestionContent();
		                    	}
		                    );

		                } else {
		                	if(quiz_result.integral)
							{
								textInfo += '<p><span style="color:#F27474"> -' + quiz_result.integral + '<span> 积分</p>';
							}
							swal({   
		                    	title: '回答错误！',   
		                    	text: textInfo,   
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

					$.get(G_BASE_URL + '/question/ajax/question_quiz_timeout/record_id-' + QUESTION_QUIZ_RECORD_ID + '__question_id-' + QUESTION_ID, function (result) {
						
						// 特殊用户

						if(result.is_special_user)
						{
							return;
						}
						
						// 超时提示

						textInfo = '<p>答题失败！</p>';
						if(result.required_integral)
						{
							textInfo += '<p><span style="color:#F27474">-' + result.required_integral + '<span> 积分</p>';
						}

						swal({
	                    	title: '时间到',
	                    	text: textInfo,   
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
				swal({
					title: '积分不足',
		        	text: '<p>您拥有 <span class="user_integral">' + result.user_integral + '</span> 积分</p>' + '<p>重新答题需要 <span class="required_integral">' + result.required_integral + '</span> 积分</p>',
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
		        	text: '<p>您拥有 <span class="user_integral">' + result.user_integral + '</span> 积分</p>' + '<p>重新答题需要消耗您 <span style="color:#F27474">' + result.required_integral + '<span> 积分</p>',   
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

	// 限时答题开始

	$('.question-content').on('click', '#begin-question-quiz-countdown', function (e) {
		e.preventDefault();

		beginCountdownQuestion();
	});

	// 限时答题：重新答题

	$('.question-content').on('click', '#retry-question-quiz-countdown', function (e) {
		e.preventDefault();
		retryQuizIntegralAction(function(){
			beginCountdownQuestion();
		});
	});

	// 普通答题： 重新答题

	$('.question-content').on('click', '#retry-question-quiz-normal', function (e) {
		e.preventDefault();

		retryQuizIntegralAction(function(){
			parseQuestionQuiz();
		    showQuizContent();
		});
	});
	
	// 关闭选项框

	$('.question-content').on('click', '.close-question-quiz-content-overlay', function (e) {
		showQuizContent();
		e.preventDefault();
	});

	// 查看问题解析

	function getQuestionSolution() {
		$.get(G_BASE_URL + '/question/ajax/get_question_solution/question_id-' + QUESTION_ID, function (response) {
			if (!response)
			{
				AWS.alert('查看答案解析失败，您没有足够的权限！');

				return;
			}

			var countdown_question = $('.countdown-question-welcome');
			if(countdown_question.length) {
				countdown_question.hide();
			} else {
				showQuizContent();
			}
			
			$('.question-solution').html(response).fadeIn();
		});
	}

	$('.question-content').on('click', '.action-view-solution', function (e) {
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
						swal({
							title: '积分不足',
				        	text: '<p>您拥有 <span class="user-integral">' + result.user_integral + '</span> 积分</p>' + '<p>查看答案解析需要 <span class="required-integral">' + result.required_integral + '</span> 积分</p>',
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
				        	text: '<p>您拥有 <span class="user_integral">' + result.user_integral + '</span> 积分</p>' + '<p>查看答案解析需要消耗您 <span style="color:#F27474">' + result.required_integral + '<span> 积分</p>',
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

	$('.question-solution').on('click', '.close-question-solution', function (e) {
		$('.question-solution').hide();
		var countdown_question = $('.countdown-question-welcome');
		if(countdown_question.length)
		{
			countdown_question.fadeIn();
		} else {
			showQuizContentOverlay();
		}
		
		e.preventDefault();
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

	$('.comment-tool-toggle').on('click', function (e){
		e.preventDefault();

		var commentSection = $('.question-comment-section');

		if(!commentSection.is(':visible'))
		{
			// 获取答题结论和讨论数

			if(ANSWER_COUNT > 0){
				swal({
					title: '友情提示',
		        	text: '评论中可能透露答案信息，不再思考思考？',
		        	html: true,
		        	confirmButtonText: "继续查看",
		        	showCancelButton: true,
					cancelButtonText: "我再想想",
		        	type: 'info'
		        	},
		        	function() {
		        		commentSection.slideDown();
					    $('html, body').animate({
					        scrollTop: commentSection.position().top
					    }, 600);
		        	}
		        );
			} else {
				commentSection.slideDown();
				$('html, body').animate({
			        scrollTop: commentSection.position().top
			    }, 600);
			}
		} else {
			commentSection.slideUp();
		}
			
	});
});
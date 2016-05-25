$(function(){

	// 答题记录popover

	function initPopover(sel) {
		sel.popover({
        		html : true, 
        		container: 'body',
        		trigger: 'manual'
        }).on("mouseenter", function () {
        	var _this = this;
        	$(this).popover("show");
        	$(".popover").on("mouseleave", function () {
            	$(_this).popover('hide');
        	});
    	}).on("mouseleave", function () {
        	var _this = this;
	        setTimeout(function () {
	            if (!$(".popover:hover").length) {
	                $(_this).popover("hide");
	            }
	    	}, 300)
	    });
	}

	function setupPopover()
	{
		if ($('.content-popover')[0]) {
	        initPopover($('.content-popover'));
	    }
	}

    // 邀请用户列表

    function updateInvitedUsers() {
    	$.get(G_BASE_URL + '/question/ajax/invited_users/question_id-' + QUESTION_ID + '__page-1', function (response) {
    		if(response != '') {
    			$('#invited-user-list').html(response);
    			initPopover($('#invited-user-list .content-popover'));

    			$('.invited-users.load-more').html('<a href="javascript:void(0);" auto-load="false" id="load-more-invited-users"><i class="md md-refresh"></i> 加载更多</a>').show();
    			AWS.load_list_view(G_BASE_URL + "/question/ajax/invited_users/question_id-" + QUESTION_ID, $('#load-more-invited-users'), $('#invited-user-list'), 2, setupPopover);

    		} else {
    			$('#invited-user-list').html('<div class="text-center no-invited-users"><p class="c-gray">目前还没有被邀请的用户</p><a href="javascript:void(0);" class="c-blue add-user-invitations"><i class="md md-person-add"></i> 邀请答题</a></div>');
    			$('.invited-users.load-more').hide();
    		}
		});
    }

    updateInvitedUsers();

    $('#invited-user-list').on('click', '.add-user-invitations', function(e) {
    	e.preventDefault();
    	$('#add-user-invitation-tab').click();
    });

    // 邀请答题按钮

    $('.question-invite').click(function(){
    	$('#add-user-invitation-tab').click();

    	$('html, body').animate({
			scrollTop: $('.question-invitation').offset().top - 150
		}, {
			duration: 600,
			queue: true
		});
    });

    // 加载更多邀请用户

   	AWS.load_list_view(G_BASE_URL + "/question/ajax/invited_users/question_id-" + QUESTION_ID, $('#load-more-invited-users'), $('#invited-user-list'), 2, setupPopover);

    // 邀请用户分页

    //邀请初始化
    var pgnum = $('.help-user-invite-list').attr('data-page');
    var perpg = $('.help-user-invite-list').attr('data-perpage');
    var helpuserlist = $('.help-user-invite-list .help-user-item');
    var maxpg = parseInt(helpuserlist.length / perpg);
    function updateHelpUserItems(pagenum, perpage) {
    	helpuserlist.hide();

    	$('.help-user-invite-list').attr('data-page', pagenum);
    	$('.help-user-invite-list-nav .prev').prop("disabled", pagenum <= 1);
    	$('.help-user-invite-list-nav .next').prop("disabled", pagenum >= maxpg);

    	for (var i = (pagenum - 1) * perpage; i < pagenum * perpage; i++)
	    {
	    	helpuserlist.eq(i).show();
	    }
    }
   	
	updateHelpUserItems(pgnum, perpg);

    //邀请上一页

    $('.help-user-invite-list-nav .prev').click(function()
    {
    	pgnum--;
    	helpuserlist.removeClass('slideInLeft slideInRight').addClass('slideInLeft');

    	updateHelpUserItems(pgnum, perpg);
    });

    //邀请下一页
    $('.help-user-invite-list-nav .next').click(function()
    {
    	pgnum++;
    	helpuserlist.removeClass('slideInLeft slideInRight').addClass('slideInRight');

    	updateHelpUserItems(pgnum, perpg);
    });

    // 用户邀请的用户列表

    function updateUserInvitations() {
    	$.get(G_BASE_URL + '/question/ajax/user_invited_users/uid-' + G_USER_ID + '__question_id-' + QUESTION_ID, function (response) {
			$('#user-invited-users-list').html(response);
		});
    }

    updateUserInvitations();

    $('.question-invitation').on('click', '.toggle-invitation', function(e) {
    	e.preventDefault();

    	if(G_USER_ID <= 0) {
			window.location = G_BASE_URL + '/account/login/';
			return;
		}

    	var $this = $(e.target);
    	if($this.hasClass('active')) {
    		$.post(G_BASE_URL + '/question/ajax/save_invite/question_id-' + QUESTION_ID + '__uid-' + $this.attr('data-id'), function (result) {
				if(result.errno == -1) {
					AWS.alert(result.err);
				} else {
					updateInvitedUsers();
					updateUserInvitations();
					$this.removeClass('active').text('取消邀请');
				}
			}, 'json');
    	} else {
    		$.post(G_BASE_URL + '/question/ajax/cancel_question_invite/question_id-' + QUESTION_ID + "__recipients_uid-" + $this.attr('data-id'), function (result) {
				if(result.errno == -1) {
					AWS.alert(result.err);
				} else {
					updateInvitedUsers();
					updateUserInvitations();
					$this.addClass('active').text('发送邀请');
				}
			}, 'json');
    	}
    });

    // 用户邀请的用户列表取消邀请

    $('#user-invited-users-list').on('click', '.cancel-user-invitation', function(e) {
    	e.preventDefault();

    	var $this = $(this);
    	var invitedUser = $('.toggle-invitation[data-id="' + $this.attr('data-id') + '"]')[0];
    	if(invitedUser) {
    		
    		// 如果用户还在推荐列表中，直接通过点击取消	

    		invitedUser.click();
    	} else {

    		// 直接发送删除用户请求

    		$.post(G_BASE_URL + '/question/ajax/cancel_question_invite/question_id-' + QUESTION_ID + "__recipients_uid-" + $this.attr('data-id'), function (result) {
				if(result.errno == -1) {
					AWS.alert(result.err);
				} else {
					updateInvitedUsers();
					updateUserInvitations();
				}
			}, 'json');
    	}

    	$this.closest('li').remove();
    	$this.closest('.popover').remove();
    	if(!$this.parents('.cancel-user-invitations li')[0]) {
    		$this.closest('.popover').remove();
    	}
    });

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

 	function initCountdownTimer(size) {
 		var seconds = $('#countdown-timer').attr('data-countdown');
 		timer = '<li>' +
					'<div class="easy-pie countdown-hour" data-percent="100">' +
						'<div class="dial hour">60</div>' +
						'<div class="dial-title">时</div>' +
					'</div>' +
				'</li>' +
				'<li>' +
					'<div class="easy-pie countdown-minute" data-percent="100">' +
						'<div class="dial minute">60</div>' +
						'<div class="dial-title">分</div>' +
					'</div>' +
				'</li>' +
				'<li>' +
					'<div class="easy-pie countdown-second" data-percent="100">' +
						'<div class="dial second">60</div>' +
						'<div class="dial-title">秒</div>' +
					'</div>' +
				'</li>';
		$('#countdown-timer').html(timer);

 		var hour = parseInt(seconds / 3600);
        var minute = parseInt(seconds / 60);
        var second = parseInt(seconds % 60);

        var hourElement = $('#countdown-timer .countdown-hour').attr('data-percent', (60 - hour) * 5 / 3);
        $('#countdown-timer .dial.hour').text(hour);
        $('#countdown-timer .countdown-minute').attr('data-percent', (60 - minute) * 5 / 3);
        $('#countdown-timer .dial.minute').text(minute);
       	$('#countdown-timer .countdown-second').attr('data-percent', (60 - second) * 5 / 3);
        $('#countdown-timer .dial.second').text(second);

        if(hour > 0) {
		    hourElement.show();
		} else {
		    hourElement.hide();
		}

		var trackColor = '#eee';
    	var scaleColor = 'rgba(255,255,255,0)';
    	var barColor = '#03A9F4';

		$('#countdown-timer .easy-pie').easyPieChart({
            trackColor: trackColor,
            scaleColor: scaleColor,
            barColor: barColor,
            lineWidth: 2,
            animate: {duration: 100},
            lineCap: 'butt',
            size: size
        });
 	}

	function updateCountdownTimer(countdown) {
		// 限时答题，限时时间

		$('#countdown-timer').attr('data-countdown', countdown);

		var hour = parseInt(countdown / 3600);
        var minute = parseInt(countdown / 60);
        var second = parseInt(countdown % 60);

        var hourElement = $('#countdown-timer .countdown-hour');
        var hourDial = $('#countdown-timer .dial.hour');
        var minuteElement = $('#countdown-timer .countdown-minute');
        var minuteDial = $('#countdown-timer .dial.minute');
        var secondElement = $('#countdown-timer .countdown-second');
        var secondDial = $('#countdown-timer .dial.second');

        if(hour > 0) {
		    hourElement.show();
		} else {
		    hourElement.hide();
		}

		var minuteBarColor = '#03A9F4';
		minuteDial.removeClass('c-red');
		if(hour == 0 && minute == 0) {
			minuteBarColor = '#f44336';
			minuteDial.addClass('c-red');
		}

		var secondBarColor = '#03A9F4';
		secondDial.removeClass('c-orange').removeClass('c-red');
		if(hour == 0 && minute == 0 && second <= 10 && second > 0) {
			secondBarColor = '#FFC107';
			secondDial.addClass('c-orange');
		} else if(hour == 0 && minute == 0 && second == 0) {
			secondBarColor = '#f44336';
			secondDial.addClass('c-red');
		}
		minuteElement.data('easyPieChart').options.barColor = minuteBarColor;
		secondElement.data('easyPieChart').options.barColor = secondBarColor;

		hourElement.data('easyPieChart').update((60 - hour) * 5 / 3);
		hourDial.text(hour);
		minuteElement.data('easyPieChart').update((60 - minute) * 5 / 3);
		minuteDial.text(minute);
		secondElement.data('easyPieChart').update((60 - second) * 5 / 3);
		secondDial.text(second);
	}

	function setupCoundownTimerAffix() {
		var timerElement = $('#countdown-timer');
	    if(timerElement.length != 0)
	    {
	        $('.countdown-timer-wrap').height(timerElement.height());

	        timerElement.on('affixed.bs.affix', function () {
	            // $('#nav-question-list-header > .card').css({"padding-left": navBarPadding, "padding-right": navBarPadding});
	            // $('#nav-question-list-header .card-body').addClass('container p-l-0');
	            initCountdownTimer(60);
	        });

	        timerElement.on('affixed-top.bs.affix', function() {
	            // $('#nav-question-list-header > .card').css({"padding-left": 0, "padding-right": 0});
	            // $('#nav-question-list-header .card-body').removeClass('container p-l-0');
	            // header.css({"box-shadow": "0px 1px 4px rgba(0, 0, 0, 0.3)"});
	            initCountdownTimer(100);
	        });

	        timerElement.affix({
	            offset: { top: timerElement.offset().top}
	        });
	    }
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

			// var QUIZ_RETRY_COUNT = $('input[name=question-quiz-record-try-count]').val();
			var PASSED_QUIZ = $('input[name=question-quiz-record-passed]').val();
			// var takenQuiz = (QUIZ_RETRY_COUNT > 0);

			initCountdownTimer(100);
			setupCoundownTimerAffix();
			parseQuestionQuiz(PASSED_QUIZ);

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
			initCountdownTimer(100);
			setupCoundownTimerAffix();
			parseQuestionQuiz();
		});
	}

	function checkAnswer(answer, spendTime) {
		var spiner = '<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>';
		var checking = swal({   
			title: '',   
			text: spiner + '<p class="m-t-10 f-12 c-gray">正在检查答案</p>', 
			html: true, 
			allowEscapeKey: false,
			showConfirmButton: false 
		});

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

            checking.close();
        }, 'json');
	}

	function submitAnswerHandle(answer, spendTime) {
		
		// 检查用户是否登录

		if(G_USER_ID <= 0) {
			window.location.href = G_BASE_URL + '/account/login/';
			return;
		}

		// 如果已经通过了答题，提示是否继续尝试

		var PASSED_QUIZ = $('input[name=question-quiz-record-passed]').val();
		if(PASSED_QUIZ) {
			textInfo = '你已经通过了答题，回答正确<strong class="c-red">不加分</strong>，回答错误<strong class="c-red">仍然扣分</strong>';
			swal(
				{   
                	title: '答题提示',
                	text: textInfo,   
                	html: true,
                	confirmButtonText: "继续答题",
                	showCancelButton: true,
				    cancelButtonText: "返回问题",
                	type: 'warning'
            	},
            	function(isConfirm) {
            		if(isConfirm) {
            			setTimeout(function(){     
            				checkAnswer(answer, spendTime);  
            			}, 10);	
            		}
            	}
            );
		} else {
			checkAnswer(answer, spendTime);
		}	
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

	function parseQuestionQuiz(passed) {
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

		var enableCountdown = true;
		if(passed) {
			enableCountdown = false;
		}

		if (IS_JSON) {
			$('.question-quiz-content').nkrQuiz({
				'mode' : 'single',
				'showSubmit' : true,
				'enableCountdown' : enableCountdown,
				'data' : quizContent,
				'enabled' : true,
				'onSubmitAnswer' : submitAnswerHandle,
				'onCountdown': updateCountdownTimer,
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
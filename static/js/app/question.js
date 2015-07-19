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
			'data' : quizContent
		});
	}
});
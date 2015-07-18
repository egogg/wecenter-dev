/**
 * Created by www.naokr.com
 * quiz jQuery plugin
 */

/**
 * wrapping the codes, avoid conflict
 */
(function ($) {
    /**
     * define a plugin
     */
    var nkrQuiz,
        privateMethod;  // private methods for the plugin

    /**
     * The main part of the plugin
     * in singlar mode
     * PS：prepend "_" to private methods
     */
    nkrQuiz = (function () {

        /**
         * The initialize codes for the plugin goes here
         * @param element selector passed to the jquery, example: $("#J_plugin").plugin(), then $(element) = $("#J_plugin")
         * @param options option parameters for the plugin
         * @constructor
         */
        function nkrQuiz(element, options) {
            // assign the dom object
            this.$element = $(element);

            // merge the defaults and options
            this.settings = $.extend({}, $.fn.nkrQuiz.defaults, options);

            this.init();

            if (typeof this.settings.data != 'undefined') {
                quizMode = this.settings.mode;
                if(quizMode == 'single') {
                    this.parseQuizItem(this.$element, this.settings.data, this.settings);    
                }
                else if(quizMode == 'set') {
                    this.parseQuizSet();
                }
                else if(quizMode == 'training') {
                    this.parseQuizTraining();
                }
                
            }
        }

        /**
         * plugin publice method, accessible by the users
         */

        nkrQuiz.prototype.init = function () {
            // Clear target element content

            this.$element.html('');

            // initialize answer list

            this.savedAnswers = [];
        };

        nkrQuiz.prototype.parseQuizOptionCrossWord = function (element, quizOptions) {
            var quizContent = '<div class="quiz-options"><div class="quiz-word-board"><div class="btn-group quiz-word-group">';
            for (var i = 0; i < quizOptions[0].content.length; i++) {
                    quizContent += '<button type="button" class="btn btn-default quiz-word-key" data-index="' + i + '" data-word="' 
                        + quizOptions[0].content[i] + '">' 
                        + quizOptions[0].content[i] + '</button>';
            }

            quizContent += '</div><div class="quiz-answer-board"><div class="btn-group quiz-word-group">';
            for (var i = 0; i < quizOptions[0].wordcount; i++) {
                quizContent += '<button type="button" class="btn btn-default quiz-answer-key blank"></button>';
            }
            quizContent += '</div></div></div>';
            element.append(quizContent);
            
            function updateSubmitButtonStatus(element) {
                element.find('.quiz-submit-answer').prop('disabled', 
                    element.find('.quiz-answer-board .quiz-answer-key.blank').length);
            };

            var getQuizResult = function(element) {
                var result = [];
                element.find('.quiz-answer-key').each(function(index) {
                    result[index] = $(this).attr('data-word-index');
                });

                return result;
            };

            var lockQuiz = function(element) {
                element.find('.quiz-submit-answer').off('click').prop('disabled', true);
                element.find('.quiz-word-board').off('click', '.quiz-word-key');
                element.find('.quiz-answer-board').off('click', '.quiz-answer-key');
            };

            var setupInitAnswer = function(element, initAnswer) {
                var wordKeys = element.find('.quiz-word-board .quiz-word-key');
                var answerKeys = element.find('.quiz-answer-board .quiz-answer-key');

                for (var i = 0; i < initAnswer.length; i++) {
                    var index = initAnswer[i];
                    if(typeof index != 'undefined') {
                        $(wordKeys[index])
                            .addClass('checked')
                            .text('');
                        $(answerKeys[i])
                            .removeClass('blank')
                            .attr('data-word-index', index)
                            .text($(wordKeys[index]).attr('data-word'));
                    }    
                };
            };

            this.settings = $.extend({}, this.settings, { 
                "getQuizResult" : getQuizResult,
                "lockQuiz" : lockQuiz,
                "setupInitAnswer" : setupInitAnswer
            });

            element.find('.quiz-word-board').on('click', '.quiz-word-key', $.proxy(function(e) {
                var wordKey = $(e.currentTarget);
                if(!wordKey.hasClass('checked')) {
                    var answerKey = this.$element.find('.quiz-answer-board .quiz-answer-key.blank:first');

                    if(answerKey.length) {
                        wordKey
                            .addClass('checked')
                            .text('');
                        answerKey
                            .removeClass('blank')
                            .attr('data-word-index', wordKey.attr('data-index'))
                            .text(wordKey.attr('data-word'));
                    }

                    updateSubmitButtonStatus(element);
              }

              e.preventDefault();
            }, this));

            element.find('.quiz-answer-board').on('click', '.quiz-answer-key', $.proxy(function(e){
                var answerKey = $(e.currentTarget);
                if(!answerKey.hasClass('blank')) {
                    var wordKey = element.find('.quiz-word-board .quiz-word-key[data-index="' + 
                        answerKey.attr('data-word-index') + '"]');

                    if(wordKey.length) {
                        wordKey
                            .removeClass('checked')
                            .text(wordKey.attr('data-word'));
                        answerKey
                            .addClass('blank')
                            .removeAttr('data-word-index')
                            .text('');

                        updateSubmitButtonStatus(element);
                    }
                }

                e.preventDefault();
            }, this));
        };

        nkrQuiz.prototype.parseQuizOptionSingleSelection = function (element, quizOptions) {
            var quizContent = '<div class="quiz-options"><div class="list-group quiz-single-selection">'
            for (var i = 0; i < quizOptions.length; i++) {
                var indexTag = this.settings.alphabet[i % this.settings.alphabet.length];
                quizContent += '<a class="list-group-item quiz-option" data-index="' + i + '"><span class="quiz-option-tag">' + 
                    indexTag + '、</span><em>' + quizOptions[i].content + '</em><i class="quiz-option-mark glyphicon glyphicon-ok"></i></a>';
            };
            quizContent += '</div></div>';
            element.append(quizContent);

            function updateSubmitButtonStatus(element) {
                element.find('.quiz-submit-answer').prop('disabled', 
                    !element.find('.quiz-single-selection .quiz-option.checked').length);
            };

            var getQuizResult = function(element) {
                return $(element.find('.quiz-single-selection .quiz-option.checked')[0]).attr('data-index');
            };

            var lockQuiz = function(element) {
                element.find('.quiz-submit-answer').off('click').prop('disabled', true);
                element.find('.quiz-single-selection').off('click', '.quiz-option');
            };

            var setupInitAnswer = function(element, initAnswer) {
                var optionElements = element.find('.quiz-single-selection .quiz-option');

                if(typeof initAnswer != 'undefined') {
                    $(optionElements[initAnswer])
                        .addClass('checked')
                        .siblings('.quiz-option').removeClass('checked');    
                }
            };

            this.settings = $.extend({}, this.settings, {
                "getQuizResult" : getQuizResult,
                "lockQuiz" : lockQuiz,
                "setupInitAnswer" : setupInitAnswer
            });

            element.find('.quiz-single-selection').on('click', '.quiz-option', $.proxy(function (e) {
                $(e.currentTarget)
                    .addClass('checked')
                    .siblings('.quiz-option').removeClass('checked');

                updateSubmitButtonStatus(element);

                e.preventDefault();
            }, this));
        };

        nkrQuiz.prototype.parseQuizOptionMultipleSelection = function (element, quizOptions) {
            var quizContent = '<div class="quiz-options"><div class="list-group quiz-multiple-selection">'
            for (var i = 0; i < quizOptions.length; i++) {
                var indexTag = this.settings.alphabet[i % this.settings.alphabet.length];
                quizContent += '<a class="list-group-item quiz-option" data-index="' + i + '"><span class="quiz-option-tag">' + 
                    indexTag + '、</span><em>' + quizOptions[i].content + '</em><i class="quiz-option-mark glyphicon glyphicon-ok"></i></a>';;
            };
            quizContent += '</div></div>';
            element.append(quizContent);

            function updateSubmitButtonStatus(element) {
                element.find('.quiz-submit-answer').prop('disabled', 
                    !element.find('.quiz-multiple-selection .quiz-option.checked').length);
            };

            var getQuizResult = function(element) {
                var result = [];
                element.find('.quiz-multiple-selection .quiz-option.checked').each(function(index) {
                    result[index] = $(this).attr('data-index');
                });

                return result;
            };

            var lockQuiz = function(element) {
                element.find('.quiz-submit-answer').off('click').prop('disabled', true);
                element.find('.quiz-multiple-selection').off('click', '.quiz-option');
            };

            var setupInitAnswer = function(element, initAnswer) {
                var optionElements = element.find('.quiz-multiple-selection .quiz-option');

                for (var i = 0; i < initAnswer.length; i++) {
                    var index = initAnswer[i];
                    if(typeof index != 'undefined') {
                        $(optionElements[index]).addClass('checked');
                    }
                };
            };

            this.settings = $.extend({}, this.settings, { 
                "getQuizResult" : getQuizResult,
                "lockQuiz" : lockQuiz,
                "setupInitAnswer" : setupInitAnswer
            });

            element.find('.quiz-multiple-selection').on('click', '.quiz-option', $.proxy(function (e) {
                $(e.currentTarget).toggleClass('checked');
                updateSubmitButtonStatus(element);

                e.preventDefault();
            }, this));
        };

        nkrQuiz.prototype.parseQuizOptionTextInput = function (element, quizOptions) {
            var quizContent = '<div class="quiz-options"><div class="quiz-text-input">'
            for (var i = 0; i < quizOptions.length; i++) {
                quizContent += '<div class="form-group"><label>' + (i + 1) + '、' + quizOptions[i].content + '</label>' + 
                    '<input type="text" class="quiz-input form-control" data-index="' + i + 
                    '"></input></div>';
            };
            quizContent += '</div></div>';
            element.append(quizContent);

            function isAllFieldsSet(element) {
                var allSet = true;
                element.find('.quiz-text-input input.quiz-input').each(function(){
                    allSet &= ($(this).val().trim().length > 0);
                });

                return allSet;
            }

            function updateSubmitButtonStatus(element) {
                element.find('.quiz-submit-answer').prop('disabled', !isAllFieldsSet(element));
            };

            var getQuizResult = function(element) {
                var result = [];
                element.find('.quiz-text-input input.quiz-input').each(function(index){
                    result[index] = $(this).val().trim();
                });

                return result;
            };

            var lockQuiz = function(element) {
                element.find('.quiz-submit-answer').off('click').prop('disabled', true);
                element.find('.quiz-text-input')
                    .off('input', '.quiz-input')
                    .find('input.quiz-input').prop('disabled', true);
            };

            var setupInitAnswer = function(element, initAnswer) {
                var inputElements = element.find('.quiz-text-input input.quiz-input');

                for (var i = 0; i < initAnswer.length; i++) {
                    var inputText = initAnswer[i];
                    if(typeof inputText != 'undefined') {
                        $(inputElements[i]).val(inputText.trim());
                    }
                };
            };

            this.settings = $.extend({}, this.settings, { 
                "getQuizResult" : getQuizResult,
                "lockQuiz" : lockQuiz,
                "setupInitAnswer" : setupInitAnswer
            });

            element.find('.quiz-text-input').on('input', '.quiz-input', $.proxy(function (e) {
                updateSubmitButtonStatus(element);

                e.preventDefault();
            }, this));
        };

        nkrQuiz.prototype.parseQuizItem = function (element, quizItem, options) {
            element = (typeof element == 'undefined' ? this.$element : element);
            var isValidQuizOption = false;

            // quiz description

            var quizContent = '';
            if(typeof quizItem.description != 'undefined') {
                quizContent += '<div class="quiz-description">';
                quizContent += quizItem.description;
                quizContent += '</div>';
                element.append(quizContent);
            }

            // quiz options

            var quizOptions = quizItem.options;
            if(quizItem.type === 'crossword') {
                this.parseQuizOptionCrossWord(element, quizOptions);
                isValidQuizOption = true;
            }
            else if(quizItem.type === 'singleSelection') {
                this.parseQuizOptionSingleSelection(element, quizOptions);
                isValidQuizOption = true;
            }
            else if(quizItem.type === 'multipleSelection') {
                this.parseQuizOptionMultipleSelection(element, quizOptions);
                isValidQuizOption = true;
            }
            else if(quizItem.type === 'textInput') {
                this.parseQuizOptionTextInput(element, quizOptions);
                isValidQuizOption = true;
            }

            if(isValidQuizOption) {

                // init answer

                if(typeof options.initAnswer != 'undefined' && 
                    typeof this.settings.setupInitAnswer == 'function') {
                    this.settings.setupInitAnswer(this.$element, initAnswer);
                }

                // countdown

                var timer = setInterval(countdownUpdate, 1000);
                if(typeof options.enableCountdown != 'undefined' && options.enableCountdown) {
                    if(quizItem.countdown > 0) {
                        quizContent = '<div class="quiz-countdown">';
                        quizContent += '<div class="quiz-countdown-title">' + options.quizRemainingTime + '</div>';
                        quizContent += '<div class="timer">' + 
                            _timeFormat(quizItem.countdown) + '</div></div>';
                        this.$element.append(quizContent);

                        // setup countdown timer

                        var countdown = quizItem.countdown;
                        var timerControl = this.$element.find('.quiz-countdown .timer');
                        function countdownUpdate() {
                            countdown--;
                            timerControl.html(_timeFormat(countdown));
                            timerControl.attr('data-time-spend', countdown);
                            if(countdown == 0) {
                                clearInterval(timer);
                                if(typeof this.settings.lockQuiz == 'function') {
                                    this.settings.lockQuiz(element);
                                }

                                if(typeof options.onTimeup == 'function') {
                                    options.onTimeup();
                                }
                            }
                        }
                    }
                }

                // submit button

                if(typeof options.showSubmit != 'undefined' && options.showSubmit) {
                    quizContent = '<div class="quiz-submit">';
                    quizContent += '<button class="btn btn-success btn-normal quiz-submit-answer" disabled>' 
                        + options.submitAnswerText + '</button>';
                    quizContent += '</div>';
                    this.$element.append(quizContent);

                    this.$element.find('.quiz-submit-answer').on('click', $.proxy(function(){
                        // stop timer 

                        if(typeof timer != 'undefined') {
                            clearInterval(timer);
                        }

                        if(typeof options.getQuizResult == 'function') {
                            var answer = options.getQuizResult(this.$element);
                            var time = this.$element.find('.quiz-countdown .timer').attr('data-time-spend');
                            if(typeof options.onSubmitAnswer == 'function') {
                                options.onSubmitAnswer(answer, time);
                            }
                        }

                        if(typeof this.settings.lockQuiz == 'function') {
                            this.settings.lockQuiz(element);
                        }
                    }, this));
                }
            }
        };

        nkrQuiz.prototype.parseQuizSet = function () {
            var updateQuizQuestion = $.proxy(function () {
                var quizQuestion = this.$element.find('.quiz-question');
                var quizContent = '';
                var questionIndex = this.$element.attr('data-qid');

                quizQuestion.html('');
                if(typeof questionIndex == 'undefined' || questionIndex < 0) {
                    // startup page

                    quizContent += '<div class="quiz-description">';
                    if(typeof this.settings.data.startupContent != 'undefined') {
                        quizContent += this.settings.data.startupContent;    
                    }
                    quizContent += '<button class="quiz-nav-startup">' + this.settings.startupQuizText + '</button>';
                    quizContent += '</div>';

                    quizQuestion.append(quizContent);
                }
                else {
                    this.parseQuizItem(quizQuestion, 
                        this.settings.data.questions[questionIndex], 
                        {
                            'initAnswer' : this.savedAnswers[questionIndex],
                            'showSubmit' : false
                        });

                    quizContent += '<div class="quiz-navigation">';
                    if(questionIndex > 0) {
                        quizContent += '<button class="quiz-nav-previous">' +
                            this.settings.quizNavPreviousText + '</button>';    
                    }
                    
                    if(questionIndex < this.settings.data.questions.length - 1) {
                        quizContent += '<button class="quiz-nav-next">' + 
                            this.settings.quizNavNextText + '</button>';    
                    }

                    if(questionIndex == this.settings.data.questions.length - 1) {
                        quizContent += '<button class="quiz-nav-complete">' +
                            this.settings.quizNavCompleteText + '</button>';
                    }
                    
                    quizContent += '</div>';
                    quizQuestion.append(quizContent);
                }
            }, this);

            var saveQuizResult = $.proxy(function () {
                var questionIndex = this.$element.attr('data-qid');

                if(typeof questionIndex != 'undefined') {
                    this.savedAnswers[questionIndex] = this.settings.getQuizResult(this.$element);
                }
            }, this);
            
            var quizContent = '<div class="quiz-question"></div>';
            this.$element.append(quizContent);
            updateQuizQuestion();
            
            quizContent = '<div class="quiz-question-num-list">';
            for (var i = 0; i < this.settings.data.questions.length; i++) {
                quizContent += '<button class="quiz-question-num">' + (i + 1) + '</button>';
            };
            quizContent += '</div>';
            this.$element.append(quizContent);

            this.$element.on('click', 'button.quiz-nav-startup', $.proxy(function(e) {
                this.$element.attr('data-qid', '0');
                updateQuizQuestion();
                e.preventDefault();
            }, this));

            this.$element.on('click', 'button.quiz-nav-previous', $.proxy(function(e){
                var questionIndex = this.$element.attr('data-qid');
                if(questionIndex > 0) {
                    saveQuizResult();

                    questionIndex--;
                    this.$element.attr('data-qid', questionIndex);

                    updateQuizQuestion();
                }
                e.preventDefault();
            }, this));

            this.$element.on('click', 'button.quiz-nav-next', $.proxy(function(e){
                var questionIndex = this.$element.attr('data-qid');
                if(questionIndex < this.settings.data.questions.length - 1) {

                    saveQuizResult();

                    questionIndex++;
                    this.$element.attr('data-qid', questionIndex);

                    updateQuizQuestion();
                }
                e.preventDefault();
            }, this));

            this.$element.on('click', 'button.quiz-nav-complete', $.proxy(function(e){

            }, this));
        };

        return nkrQuiz;
    })();

    /**
     * private methods
     */
    
    _timeFormat = function(seconds) {
        var hour = parseInt(seconds / 3600);
        var minute = parseInt(seconds / 60);
        var second = parseInt(seconds % 60);
        var hasHour = false;
        var hasMinute = false;

        var timeStr = '';
        if(hour > 0) {
            timeStr += '<span class="hour label label-primary">' + ('00' + hour).slice(-2) + ' 时</span>';
            hasHour = true;
        }

        if(minute > 0) {
            timeStr += '<span class="minute label label-primary">' + ('00' + minute).slice(-2) + ' 分</span>';
            hasMinute = true;
        }

        var alarmClass = 'label-primary';
        if(!hasHour && !hasMinute && (second < 10)) {
            alarmClass = 'label-danger';
        }

        timeStr += '<span class="second label ' + alarmClass + '">' + (('00' + second).slice(-2) + ' 秒') + '</span>';

        return timeStr;
    }

    privateMethod = function () {
        
    };

    /**
     * define an object of the plugin class 
     */
    $.fn.nkrQuiz = function (options) {
        return this.each(function () {
            var $me = $(this),
                instance = $me.data('plugin');
            // cache the object
            // if(!instance){
            //     // attatch the plugin object to the dom
            //     $me.data('plugin', (instance = new nkrQuiz(this, options)) );
            // }

            $me.data('plugin', (instance = new nkrQuiz(this, options)) );

            /**
             * call the plugin's method by string name
             * example: $('#id').plugin('doSomething') equal to $('#id).plugin.doSomething();
             */
            if ($.type(options) === 'string') return instance[options]();
        });
    };

    /**
     * default options for the plugin
     */
    $.fn.nkrQuiz.defaults = {
        mode : 'single',
        alphabet : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        submitAnswerText : '提交答案',
        startupQuizText: '开始测试',
        quizNavPreviousText : '上一题',
        quizNavNextText : '下一题',
        quizNavCompleteText : '完成测试',
        quizRemainingTime : '答题剩余时间：',

        onSubmitAnswer : function (answer) {

        }
    };

})(jQuery);
$(document).ready(function ()
{
    /* 注册页面验证 */
        
    function verify_register_form(element)
    {
        $(element).find('[type=text], [type=password]').on({
            focus : function()
            {
                var _this = $(this);
                var inputWrap = $(this).parent();
                if (typeof _this.attr('tips') != 'undefined' && _this.attr('tips') != '')
                {
                    inputWrap.find('.help-block').detach();
                    inputWrap.find('.form-control-feedback').detach();
                    inputWrap.addClass('has-warning').removeClass('has-error').removeClass('has-success').removeClass('has-feedback');
                    inputWrap.append('<small class="help-block">' + _this.attr('tips') + '</small>');
                }
            },
            blur : function()
            {
                var _this = $(this);
                var inputWrap = _this.parent();
                var helpBlock = inputWrap.find('.help-block');
                var feedbackBlock = inputWrap.find('.form-control-feedback');
                var inputValue = _this.val();
                var errorTips = _this.attr('errortips');
                var isValidValue = false;

                function updateCheckResult() {
                    helpBlock.detach();
                    feedbackBlock.detach();
                    inputWrap.removeClass('has-error').removeClass('has-warning').removeClass('has-success').removeClass('has-feedback');

                    if(!isValidValue) {
                        inputWrap.addClass('has-error');
                        inputWrap.append('<small class="help-block">' + errorTips + '</small>');
                    } else {
                        inputWrap.addClass('has-success').addClass('has-feedback');
                        inputWrap.append('<span class="md md-check form-control-feedback"></span>');
                    }
                }

                switch ($(this).attr('name'))
                {
                    case 'user_name' :
                        isValidValue = (inputValue.length >= 2 && inputValue.length <= 16);
                        if(isValidValue)
                        {
                            $.get(G_BASE_URL + '/account/ajax/check_username/username' + '-' + encodeURIComponent(inputValue), function (result)
                            {
                                if (result.errno == -1)
                                {
                                    isValidValue = false;
                                    errorTips = result.err;
                                    updateCheckResult();
                                }
                            }, 'json');
                        }
                    break;

                    case 'email' :
                        var emailreg = /^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/;
                        isValidValue = emailreg.test(inputValue);
                    break;

                    case 'password' :
                        isValidValue = (inputValue.length >= 6 && inputValue.length <= 17);
                    break;
                    default :
                        errorTips = '';
                }

                updateCheckResult();
            }
        });
    }

    verify_register_form('#register_form');
});

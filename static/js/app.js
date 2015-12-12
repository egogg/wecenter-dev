var document_title = document.title;

$(document).ready(function ()
{
    // 响应式头部搜索按钮

    $('#navbar-mobile-search-icon').on('click', function(e) {
        $('.navbar-mobile-search').addClass('open');
        e.preventDefault();
    });

    $('.navbar-mobile-search-close').on('click', function(e) {
        $('.navbar-mobile-search').removeClass('open');
        e.preventDefault();
    });

    // List view
    if ($('.dropdown')[0]) {
        //Propagate
        $('body').on('click', '.dropdown.open .dropdown-menu ', function(e) {
            e.stopPropagation();
        });

        $(document).on('click', function (e) {
            if(!$(e.target).is('.navbar-search-input.dropdown.open .dropdown-menu, #aw-search-query')) {
                $('.navbar-search-input.dropdown').removeClass('open');
            }
        });


        $('.dropdown').on('shown.bs.dropdown', function(e) {
            if ($(this).attr('data-animation')) {
                $animArray = [];
                $animation = $(this).data('animation');
                $animArray = $animation.split(',');
                $animationIn = 'animated ' + $animArray[0];
                $animationOut = 'animated ' + $animArray[1];
                $animationDuration = ''
                if (!$animArray[2]) {
                    $animationDuration = 500; //if duration is not defined, default is set to 500ms
                } else {
                    $animationDuration = $animArray[2];
                }

                $(this).find('.dropdown-menu').removeClass($animationOut)
                $(this).find('.dropdown-menu').addClass($animationIn);
            }
        });

        $('.dropdown').on('hide.bs.dropdown', function(e) {
            if ($(this).attr('data-animation')) {
                e.preventDefault();
                $this = $(this);
                $dropdownMenu = $this.find('.dropdown-menu');

                $dropdownMenu.addClass($animationOut);
                setTimeout(function() {
                    $this.removeClass('open')

                }, $animationDuration);
            }
        });
    }


    //全部设置为已读
    
    $('body').on('click', '[data-clear="notification"]', function(e) {
        e.preventDefault();

        var x = $(this).closest('.listview');
        var y = x.find('.lv-item');
        var z = y.size();

        $(this).parent().fadeOut();

        x.find('.list-group').prepend('<i class="grid-loading hide-it"></i>');
        x.find('.grid-loading').fadeIn(1500);


        var w = 0;
        y.each(function() {
            var z = $(this);
            setTimeout(function() {
                z.addClass('animated fadeOutRightBig').delay(1000).queue(function() {
                    z.remove();
                });
            }, w += 150);
        })

        //Popup empty message
        setTimeout(function() {
            $('#notifications').addClass('empty');
        }, (z * 150) + 200);
    });

    // 问题分类筛选

    function getFilter(type) {
        var filterItem = $('.question-filter-' + type + '.active');
        if(filterItem.length != 1) {
            return('');
        }

        var data_id = filterItem.attr('data-id');
        if(typeof data_id == 'undefined') {
            return('');
        } else {
            return(type + '-' + data_id);
        }
    }


    function getSort() {
        var sortItem = $('#question-list-sort-type');
        if(sortItem.length != 1) {
            return('');
        }

        var sort_type = sortItem.attr('data-sort-type');
        if(typeof sort_type == 'undefined' || sort_type == '') {
            return ('');
        } else {
            return('sort_type-' + sort_type + '__');
        }
    }

    function getFilterTokens() {
        var filterTokens = '';
        var filters = ['category', 'quiztype', 'difficulty', 'countdown'];
        for (var i = 0; i < filters.length; i++) {
            var token = getFilter(filters[i]);
            if(token.length) {
                filterTokens += (token + '__');
            }
        };

        // 用户记录选项 

        var filter_urecord = $('#question-list-filter-urecord').attr('data-filter-type');
        if(typeof filter_urecord != 'undefined' && filter_urecord != '' ) {
            filterTokens += ('urecord-' + filter_urecord + '__');
        }

        // 日期范围

        var filter_date = $('#question-list-filter-date').attr('data-filter-type');
        if(typeof filter_date != 'undefined' && filter_date != '') {
            filterTokens += ('date-' + filter_date + '__');
        }

        filterTokens += getSort();
        filterTokens = filterTokens.replace(/(__$)/g, '');
        filterTokens = filterTokens.replace(/(^__)/g, '');

        return filterTokens;
    }

    // filter items

    $('.question-filters').on('click', '.question-filter-items li', function(e){
        e.preventDefault();
        $(this).addClass('active').siblings().removeClass('active');        

        window.location.href = G_BASE_URL + $('#question-filters').attr('data-url-base') + getFilterTokens();
    });

    $('.question-list-filter-item.sort .dropdown-menu li').on('click', function(e){
        e.preventDefault();

        var currentSel = $(this).find('a');
        var currentSelText = currentSel.html();
        var linkElement = $('#question-list-sort-type');
        linkElement.attr('data-sort-type', currentSel.attr('data-sort-type'));

        window.location.href = G_BASE_URL + linkElement.attr('data-url-base') + getFilterTokens();
    });

    $('.question-list-filter-item.urecord .dropdown-menu li').on('click', function(e){
        e.preventDefault();
        
        if(G_USER_ID <= 0) {
            window.location.href = G_BASE_URL + '/account/login/';
            return;
        }

        var currentSel = $(this).find('a');
        var currentSelText = currentSel.html();
        var linkElement = $('#question-list-filter-urecord');
        linkElement.attr('data-filter-type', currentSel.attr('data-filter-type'));

        window.location.href = G_BASE_URL + linkElement.attr('data-url-base') + getFilterTokens();
    });

    $('.question-list-filter-item.date .dropdown-menu li').on('click', function(e){
        e.preventDefault();

        var currentSel = $(this).find('a');
        var currentSelText = currentSel.html();
        var linkElement = $('#question-list-filter-date');
        linkElement.attr('data-filter-type', currentSel.attr('data-filter-type'));

        window.location.href = G_BASE_URL + linkElement.attr('data-url-base') + getFilterTokens();
    });

    function clearFilters() {
        $('.question-filter-items li.active').removeClass('active');
    }

    $('.question-list').on('click', '.question-tag', function(e){
        var questionTag = $(this);
        var targetFilter = $('.question-filters .question-filter-items li.question-filter-' + questionTag.attr('data-type') + '[data-id=' + questionTag.attr('data-id') + ']');

        clearFilters();
        targetFilter.click();
    });

    // 筛选移动布局

    function mGetFilter(type) {
        var filterItem = $('.m-question-filter-' + type + '.active');
        if(filterItem.length != 1) {
            return('');
        }

        var data_id = filterItem.attr('data-id');
        if(typeof data_id == 'undefined') {
            return('');
        } else {
            return(type + '-' + data_id);
        }
    }


    function mGetSort() {
        var sortItem = $('.m-question-sort-type');

        var sort_type = sortItem.attr('data-sort-type');
        if(typeof sort_type == 'undefined' || sort_type == '') {
            return ('');
        } else {
            return('sort_type-' + sort_type + '__');
        }
    }

    function mGetFilterTokens() {
        var filterTokens = '';
        var filters = ['category', 'quiztype', 'difficulty', 'countdown'];
        for (var i = 0; i < filters.length; i++) {
            var token = mGetFilter(filters[i]);
            if(token.length) {
                filterTokens += (token + '__');
            }
        };

        filterTokens += mGetSort();

        // 答题记录筛选

        var filter_urecord = $('#m-question-filter-urecord').attr('data-filter-type');
        if(typeof filter_urecord != 'undefined' && filter_urecord != '' ) {
            filterTokens += ('urecord-' + filter_urecord + '__');
        }

        filterTokens = filterTokens.replace(/(__$)/g, '');
        filterTokens = filterTokens.replace(/(^__)/g, '');

        return filterTokens;
    }

    $('.m-question-filters').on('click', '.m-question-filter-items li', function(e){
        e.preventDefault();
        $(this).addClass('active').siblings().removeClass('active');
    });

    $('#m-question-filters-apply').on('click', function(e){
        window.location.href = G_BASE_URL + $('#m-question-filter').attr('data-url-base') + mGetFilterTokens();
    });

    $('.m-question-sort-items').on('click', 'li', function(e){
        e.preventDefault();

        var currentSel = $(this).find('a');
        var currentSelText = currentSel.html();
        $('#m-question-sort-type').attr('data-sort-type', currentSel.attr('data-sort-type')).html(currentSelText + ' <i class="caret"></i>');

        window.location.href = G_BASE_URL + $('#m-question-sort-type').attr('data-url-base') + mGetFilterTokens();
    });

    $('.m-question-urecord-filter-items').on('click', 'li', function(e){
        e.preventDefault();

        if(G_USER_ID <= 0) {
            window.location.href = G_BASE_URL + '/account/login/';
            return;
        }

        var currentSel = $(this).find('a');
        var currentSelText = currentSel.html();
        $('#m-question-filter-urecord').attr('data-filter-type', currentSel.attr('data-filter-type')).html(currentSelText + ' <i class="caret"></i>');

        window.location.href = G_BASE_URL + $('#m-question-filter-urecord').attr('data-url-base') + mGetFilterTokens();
    });

    // 排序bar sticky

    var navBar = $('#nav-question-list-header');
    if(navBar.length != 0)
    {
        var navBarPadding = navBar.offset().left + 15;
        
        $('#nav-question-list-header-wrap').height(navBar.height());
        
        navBar.on('affixed.bs.affix', function () {
            $('#nav-question-list-header > .card').css({"padding-left": navBarPadding, "padding-right": navBarPadding});
        });

        navBar.on('affixed-top.bs.affix', function() {
            $('#nav-question-list-header > .card').css({"padding-left": 0, "padding-right": 0});
        });

        navBar.affix({
            offset: { top: navBar.offset().top}
        });
    }

    // 移动版排序栏

    var mNavBar = $('#m-question-list-heading');
    if(mNavBar.length != 0)
    {
        $('#m-question-list-heading-wrap').height(mNavBar.height());
        mNavBar.affix({
            offset: { top: mNavBar.offset().top}
        });
    }
        
    // fix form bug...
    $("form[action='']").attr('action', window.location.href);

    // 验证码
    $('img#captcha').attr('src', G_BASE_URL + '/account/captcha/');

    // 输入框自动增高
    $('.autosize').autosize();

    //响应式导航条效果
    $('.aw-top-nav .navbar-toggle').click(function(e)
    {
        e.preventDefault();
        if ($(this).parents('.aw-top-nav').find('.navbar-collapse').hasClass('active'))
        {
            $(this).parents('.aw-top-nav').find('.navbar-collapse').removeClass('active');
        }
        else
        {
            $(this).parents('.aw-top-nav').find('.navbar-collapse').addClass('active');
        }
    });

    //检测通知
    if (typeof (G_NOTIFICATION_INTERVAL) != 'undefined')
    {
        AWS.Message.check_notifications();
        AWS.G.notification_timer = setInterval('AWS.Message.check_notifications()', G_NOTIFICATION_INTERVAL);
    }

    //文章列表样式调整
    $.each($('.aw-common-list .aw-item.article'), function (i, e)
    {
        if ($(this).find('img').length > 1)
        {
            if ($.trim($(this).find('.markitup-box').text()) == '')
            {
                $(this).find('.aw-upload-img-list, .markitup-box img').css({
                    'right': 'auto',
                    'left': 0,
                    'top': 10
                });
            }
            $(this).find('.aw-upload-img-list').next().detach();
            $(this).find('.markitup-box img').eq(0).css({'z-index':'999'});
        }
        else
        {
            $(this).find('.img.pull-right').hide();
        }
    });

    $('a[rel=lightbox]').fancybox(
    {
        openEffect: 'none',
        closeEffect: 'none',
        prevEffect: 'none',
        nextEffect: 'none',
        centerOnScroll : true,
        closeBtn: false,
        helpers:
        {
            buttons:
            {
                position: 'bottom'
            }
        },
        afterLoad: function ()
        {
            this.title = '第 ' + (this.index + 1) + ' 张, 共 ' + this.group.length + ' 张' + (this.title ? ' - ' + this.title : '');
        }
    });

    if (window.location.hash.indexOf('#!') != -1)
    {
        if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
        {
            $.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
        }
    }

    /*用户头像提示box*/
    AWS.show_card_box('.aw-user-name, .aw-user-img', 'user');

    AWS.show_card_box('.topic-tag, .aw-topic-name, .aw-topic-img', 'topic');

    //文章页添加评论, 话题添加 绑定事件
    AWS.Init.init_article_comment_box('.aw-article-content .aw-article-comment');

    AWS.Init.init_topic_edit_box('.aw-edit-topic');

    //话题编辑下拉菜单click事件
    $(document).on('click', '.aw-edit-topic-box .aw-dropdown-list li', function ()
    {
        $(this).parents('.aw-edit-topic-box').find('#aw_edit_topic_title').val($(this).text());
        $(this).parents('.aw-edit-topic-box').find('.add').click();
        $(this).parents('.aw-edit-topic-box').find('.aw-dropdown').hide();
    });

    //话题删除按钮
    $(document).on('click', '.topic-tag .close',  function()
    {
        var data_type = $(this).parents('.aw-topic-bar').attr('data-type'),
            data_id = $(this).parents('.aw-topic-bar').attr('data-id'),
            data_url = '',
            topic_id = $(this).parents('.topic-tag').attr('data-id');

        switch (data_type)
        {
            case 'question':
                data_url = G_BASE_URL + '/topic/ajax/remove_topic_relation/';
                break;

            case 'topic':
                data_url = G_BASE_URL + '/topic/ajax/remove_related_topic/related_id-' + $(this).parents('.topic-tag').attr('data-id') + '__topic_id-' + data_id;
                break;

            case 'favorite':
                data_url = G_BASE_URL + '/favorite/ajax/remove_favorite_tag/';
                break

            case 'article':
                data_url = G_BASE_URL + '/topic/ajax/remove_topic_relation/';
                break;
        }

        if ($(this).parents('.aw-topic-bar').attr('data-url'))
        {
            data_url = $(this).parents('.aw-topic-bar').attr('data-url');
        }

        if (data_type == 'topic')
        {
            $.get(data_url);
        }
        else if (data_type == 'favorite')
        {
            $.post(data_url, 
            {
                'item_type': data_type,
                'topic_id': topic_id,
                'item_id' : data_id,
                'tags' : $.trim($(this).parents('.topic-tag').text())
            }, function (result)
            {
            }, 'json');
        }
        else
        {
            $.post(data_url, 
            {
                'type': data_type,
                'topic_id': topic_id,
                'item_id' : data_id
            }, function (result)
            {
                $('#aw-ajax-box').empty();
            }, 'json');
        }

        $(this).parents('.topic-tag').remove();

        return false;
    });

    //小卡片mouseover
    $(document).on('mouseover', '#aw-card-tips', function ()
    {
        clearTimeout(AWS.G.card_box_hide_timer);

        $(this).show();
    });

    //小卡片mouseout
    $(document).on('mouseout', '#aw-card-tips', function ()
    {
        $(this).hide();
    });

    //用户小卡片关注更新缓存
    $(document).on('click', '.aw-card-tips-user .follow', function ()
    {
        var uid = $(this).parents('.aw-card-tips').find('.name').attr('data-id');

        $.each(AWS.G.cashUserData, function (i, a)
        {
            if (a.match('data-id="' + uid + '"'))
            {
                if (AWS.G.cashUserData.length == 1)
                {
                    AWS.G.cashUserData = [];
                }
                else
                {
                    AWS.G.cashUserData[i] = '';
                }
            }
        });
    });

    //话题小卡片关注更新缓存
    $(document).on('click', '.aw-card-tips-topic .follow', function ()
    {
        var topic_id = $(this).parents('.aw-card-tips').find('.name').attr('data-id');

        $.each(AWS.G.cashTopicData, function (i, a)
        {
            if (a.match('data-id="' + topic_id + '"'))
            {
                if (AWS.G.cashTopicData.length == 1)
                {
                    AWS.G.cashTopicData = [];
                }
                else
                {
                    AWS.G.cashTopicData[i] = '';
                }
            }
        });
    });

    /*icon tooltips提示*/
    $(document).on('mouseover', '.follow, .voter, .aw-icon-thank-tips, .invite-list-user', function ()
    {
        $(this).tooltip('show');
    });

    //搜索下拉
    AWS.Dropdown.bind_dropdown_list('#aw-search-query', 'search');

    //编辑器@人
    AWS.at_user_lists('#wmd-input, .aw-article-replay-box #comment_editor', 5);

    //ie浏览器下input,textarea兼容
    if (document.all)
    {
        AWS.check_placeholder($('input, textarea'));

        // 每隔1s轮询检测placeholder
        setInterval(function()
        {
            AWS.check_placeholder($('input[data-placeholder!="true"], textarea[data-placeholder!="true"]'));
        }, 1000);
    }

    // 重新加载用户答题动态
    if (typeof (G_USER_QUIZ_MESSAGE_INTERVAL) != 'undefined')
    {
        AWS.Message.update_user_quiz_message();
        AWS.G.user_quiz_message_timer = setInterval('AWS.Message.update_user_quiz_message()', G_USER_QUIZ_MESSAGE_INTERVAL);
    }
});

$(window).on('hashchange', function() {
    if (window.location.hash.indexOf('#!') != -1)
    {
        if ($('a[name=' + window.location.hash.replace('#!', '') + ']').length)
        {
            $.scrollTo($('a[name=' + window.location.hash.replace('#!', '') + ']').offset()['top'] - 20, 600, {queue:true});
        }
    }
});

if ($('.aw-back-top').length)
{
    $(window).scroll(function ()
    {
        if ($(window).scrollTop() > ($(window).height() / 2))
        {
            $('.aw-back-top').fadeIn();
        }
        else
        {
            $('.aw-back-top').fadeOut();
        }
    });
}

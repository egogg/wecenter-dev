;( function( $, window, document, undefined ) {

    "use strict";

        // Create the defaults once
        var pluginName = "nkrShare",
            defaults = {
                url : window.location.href,
                title : '',
                description : '',
                pic : '',

                shareId : {
                    sina : {
                        id : '', //sina weibo appID
                        ralateUid : '' //sina weibo @userID
                    }
                }
            };

        // The actual plugin constructor
        function Plugin ( element, options ) {
            this.element = element;
            this.settings = $.extend( {}, defaults, options );
            this._defaults = defaults;
            this._name = pluginName;
            this.init();
        }

        $.extend( Plugin.prototype, {
            init: function() {

                //微博专用
                this.__title = this.settings.title;
                this.__pic = this.settings.pic;
                this.__url = this.settings.url;
                this.__description = this.settings.description;

                //set the shareID
                this.__shareId = this.settings.shareId;

                this.buildShares();
            },
            buildShares : function() {
                var shares = {
                    'qzone' : this.shareQzone,
                    'qq' : this.shareQQ,
                    'douban' : this.shareDouban,
                    'weibo' : this.shareWeibo,
                    'yixin' : this.shareYixin,
                    'wechat' : this.shareWechat
                };
                
                for(var s in shares) {
                    var shareLink = $(this.element).find('a.' + s);
                    if(shareLink.length) {
                        shares[s](this, shareLink);
                    }
                }
            },
            shareWechat: function(obj, element) {
                element.attr('href', 'javascript:void(0);');
                
                // generate qrcode

                var qrContainer = $('<div></div>');
                qrContainer.qrcode({render : 'image', text : obj.__url, ecLevel:'H', size:250, background:'#fff'});
                var qrImg = qrContainer.find('img');

                element.bind('click', function(){
                    swal({   
                        title: '分享到微信朋友圈',   
                        text: '<img src="' + qrImg.attr('src') + '" alt="微信分享二维码" /><div class="share-description">使用微信“扫一扫”，打开网页后点击微信屏幕右上角分享按钮分享到朋友圈</div>', 
                        html: true, 
                        confirmButtonText: "返回"
                    });
                });
            },
            shareQzone: function(obj, element){
                var _param = {
                    url: obj.__url,
                    title: obj.__title,
                    pics: obj.__pic,
                    summary: obj.__description,
                    site : "脑壳网"
                };
                var arr = [];

                for( var p in _param ){
                    arr.push(p + '=' + encodeURIComponent( _param[p] || '' ) )
                }

                element.attr('href', 'http://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?' + arr.join('&'));
            },
            shareQQ: function(obj, element){
                var _param = {
                    url: obj.__url,
                    title: obj.__title,
                    pics: obj.__pic,
                    summary: obj.__description,
                    site: "脑壳网"
                }
                var arr = [];

                for( var p in _param ){
                    arr.push(p + '=' + encodeURIComponent( _param[p] || '' ) )
                }

               element.attr('href', 'http://connect.qq.com/widget/shareqq/index.html?' + arr.join('&'));
            },
            shareDouban: function(obj, element){
                var _param = {
                    href: obj.__url,
                    name: obj.__title,
                    image: obj.__pic,
                    text: obj.__description
                }
                var arr = [];

                for( var p in _param ){
                    arr.push(p + '=' + encodeURIComponent( _param[p] || '' ) )
                }

                element.attr('href', 'https://www.douban.com/share/service?' + arr.join('&'));
            },
            shareWeibo: function(obj, element){
                var param = {
                    url:obj.__url,
                    appkey:obj.__shareId.sina.id || '', /**你申请的应用appkey,显示分享来源(可选)*/
                    title:obj.__title, /**分享的文字内容(可选，默认为所在页面的title)*/
                    pic:obj.__pic, /**分享图片的路径(可选)*/
                    /**关联用户的UID，分享微博会@该用户(可选)*/
                    ralateUid:obj.__shareId.sina.ralateUid || '', 
                    language:'zh_cn' /**设置语言，zh_cn|zh_tw(可选)*/
                }
                var arr = [];

                for( var p in param ){
                    arr.push(p + '=' + encodeURIComponent( param[p] || '' ) )
                }

                element.attr('href', 'http://service.weibo.com/share/share.php?' + arr.join('&'));
            },
            shareYixin: function(obj, element){
                var _param = {
                    appkey : '',
                    type : 'webpage',
                    title : '网易云课堂',
                    desc : obj.__title,
                    userdesc : '',
                    pic : obj.__pic,
                    url : obj.__url
                }
                var arr = [];

                for(var p in _param){
                    arr.push(p + '=' + encodeURIComponent( _param[p] || '' ));
                }

                element.attr('href', 'http://open.yixin.im/share?' + arr.join('&'));
            }
        });

        // A really lightweight plugin wrapper around the constructor,
        // preventing against multiple instantiations
        $.fn[ pluginName ] = function( options ) {
            return this.each( function() {
                if ( !$.data( this, "plugin_" + pluginName ) ) {
                    $.data( this, "plugin_" +
                        pluginName, new Plugin( this, options ) );
                }
            } );
        };
})( jQuery, window, document );
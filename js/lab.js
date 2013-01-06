/**
 * new feature related script
 */

(function ($) {
    var href = document.location.href,
        isSupportLocalstorage = window.localStorage,
        isSupportNotification = window.webkitNotifications;
    // message notifications
    //@todo render the messages
    $.get('api/user/0/message', {
        type:1,
        count:1,
        start_time:+new Date()
    }, function (res) {
        var no = res.count;
        if (isSupportLocalstorage && isSupportNotification) {
            if (localStorage.getItem('enableNotification') === '1') {
                var notice = window.webkitNotifications.createNotification('', '提醒', '您收到新的站内消息');
                notice.show();
                setTimeout(function () {
                    notice.close();
                }, 2000);
            }
        }
    }, 'json');
})(jQuery);
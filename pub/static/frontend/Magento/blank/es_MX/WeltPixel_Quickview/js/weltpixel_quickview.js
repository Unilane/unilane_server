define([
    'jquery',
    'magnificPopup'
    ], function ($, magnificPopup) {
    "use strict";

    var quickview = {
        displayContent: function(prodUrl) {
            if (!prodUrl.length) {
                return false;
            }

            var url = window.weltpixel_quickview.baseUrl + 'weltpixel_quickview/index/updatecart';
            var showMiniCart = parseInt(window.weltpixel_quickview.showMiniCart);

            window.weltpixel_quickview.showMiniCartFlag = false;

            $.magnificPopup.open({
                items: {
                  src: prodUrl
                },
                type: 'iframe',
                closeOnBgClick: false,
                preloader: true,
                tLoading: '',
                callbacks: {
                    open: function() {
                      $('.mfp-preloader').css('display', 'block');
                    },
                    beforeClose: function() {
                        if (window.weltpixel_quickview.showMiniCartFlag) {
                            $('[data-block="minicart"]').trigger('contentLoading');
                            $.ajax({
                                url: url,
                                global: false,
                                method: "POST"
                            });
                        }
                    },
                    close: function() {
                      $('.mfp-preloader').css('display', 'none');
                    },
                    afterClose: function() {
                        /* Show only if product was added to cart and enabled from admin */
                        if (window.weltpixel_quickview.showMiniCartFlag && showMiniCart) {
                            $("html, body").animate({ scrollTop: 0 }, "slow");
                            setTimeout(function(){
                                if (!jQuery('.block-minicart').is(":visible")) {
                                    $('.action.showcart').trigger('click');
                                }
                            }, 1000);
                        }
                    }
                  }
            });
        }
    };

    window.quickview = quickview;
    return quickview;
});

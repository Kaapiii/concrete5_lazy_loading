;
(function($, dpr) {
    if (dpr>1)
        $.lazyLoadXT.srcAttr = (dpr > 2 ? 'data-src-2x' : 'data-src');
})(jQuery, window.devicePixelRatio || 1);
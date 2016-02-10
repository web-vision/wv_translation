/**
 * @param  {object} window    The window object of the browser.
 * @param  {object} document  The document object of the browser.
 * @param  {function} $       jQuery
 * @param  {undefined} undefined Just undefined
 *
 * @author Daniel Siepmann <d.siepmann@web-vision.de>
 */
;
(function(window, document, $) {
    $(function() {
        $('input[type="checkbox"]').click(function() {
            var $this = $(this),
                $tr = $this.closest('tr');
            $tr.removeClass('info');
            if ($this.is(':checked')) {
                $tr.addClass('info');
            }
        });
    });
})(window, document, TYPO3.jQuery);

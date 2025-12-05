jQuery(document).ready(function($) {
    $('.apivro-variable-code').on('click', function() {
        var variable = $(this).data('variable') || $(this).text();
        var $original = $(this);
        var originalText = $original.text();
        var copiedText = (typeof window.apivroWoonotifyI18n !== 'undefined' && window.apivroWoonotifyI18n.copied) ? window.apivroWoonotifyI18n.copied : 'Copied!';
        var errorText = (typeof window.apivroWoonotifyI18n !== 'undefined' && window.apivroWoonotifyI18n.error) ? window.apivroWoonotifyI18n.error : 'Error!';

        function applyCopiedUI(textToShow) {
            $original.text(textToShow);
            $original.addClass('is-copied');
            setTimeout(function() {
                $original.text(originalText);
                $original.removeClass('is-copied');
            }, 1000);
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(variable).then(function() {
                applyCopiedUI(copiedText);
            }).catch(function() {
                fallbackCopy(variable, $original, originalText, copiedText, errorText);
            });
        } else {
            fallbackCopy(variable, $original, originalText, copiedText, errorText);
        }
    });

    function fallbackCopy(text, $element, originalText, copiedText, errorText) {
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        try {
            document.execCommand('copy');
            $element.text(copiedText).addClass('is-copied');
            setTimeout(function() {
                $element.text(originalText).removeClass('is-copied');
            }, 1000);
        } catch (err) {
            $element.text(errorText).addClass('is-copied');
            setTimeout(function() {
                $element.text(originalText).removeClass('is-copied');
            }, 1000);
        }
        $temp.remove();
    }
});


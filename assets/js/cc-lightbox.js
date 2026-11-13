/* Comic Calendar: accessible, dependency-free single-comic lightbox. */
(function () {
    'use strict';
    var overlay = null;
    var previousFocus = null;
    var previousOverflow = '';

    function closeLightbox() {
        if (!overlay) return;
        overlay.remove();
        overlay = null;
        document.body.classList.remove('cc-lightbox-open');
        document.body.style.overflow = previousOverflow;
        document.removeEventListener('keydown', onKeydown);
        if (previousFocus && document.contains(previousFocus)) previousFocus.focus();
        previousFocus = null;
    }

    function onKeydown(event) {
        if (!overlay) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeLightbox();
        } else if (event.key === 'Tab') {
            // The close button is the dialog's sole keyboard control.
            event.preventDefault();
            overlay.querySelector('.cc-lightbox-close').focus();
        }
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest && event.target.closest('a[data-cc-lightbox]');
        if (!trigger) return;
        var source = trigger.getAttribute('href');
        if (!source) return;
        event.preventDefault();
        closeLightbox();
        previousFocus = trigger;
        previousOverflow = document.body.style.overflow;

        overlay = document.createElement('div');
        overlay.className = 'cc-lightbox-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', trigger.getAttribute('aria-label') || 'Enlarged comic');
        var image = document.createElement('img');
        image.src = source;
        image.alt = trigger.querySelector('img') ? trigger.querySelector('img').alt : 'Comic';
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'cc-lightbox-close';
        button.setAttribute('aria-label', 'Close enlarged comic');
        button.textContent = '\u00d7';
        button.addEventListener('click', closeLightbox);
        overlay.appendChild(image);
        overlay.appendChild(button);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeLightbox();
        });
        document.body.appendChild(overlay);
        document.body.classList.add('cc-lightbox-open');
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', onKeydown);
        button.focus();
    });
}());

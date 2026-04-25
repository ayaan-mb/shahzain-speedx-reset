(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('speedx-reset-confirmation');
        var button = document.getElementById('speedx-reset-submit');
        var form = document.getElementById('speedx-site-reset-form');

        if (!input || !button || !form) {
            return;
        }

        var toggleButtonState = function () {
            var isConfirmed = input.value === 'reset';

            button.disabled = !isConfirmed;
            button.hidden = !isConfirmed;
            button.classList.toggle('speedx-reset-button--active', isConfirmed);
        };

        input.addEventListener('input', toggleButtonState);
        toggleButtonState();

        form.addEventListener('submit', function (event) {
            if (button.disabled) {
                event.preventDefault();
                return;
            }

            var confirmMessage = (window.SpeedXSiteReset && window.SpeedXSiteReset.confirmMessage)
                ? window.SpeedXSiteReset.confirmMessage
                : 'Final warning: this will permanently reset your site. Do you want to continue?';

            if (!window.confirm(confirmMessage)) {
                event.preventDefault();
            }
        });
    });
})();

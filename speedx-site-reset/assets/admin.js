(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('speedx-reset-confirmation');
        var button = document.getElementById('speedx-reset-submit');
        var form = document.getElementById('speedx-site-reset-form');
        var modeRadios = document.querySelectorAll('input[name="speedx_reset_mode"]');

        if (!input || !button || !form || !modeRadios.length) {
            return;
        }

        var updateCards = function () {
            modeRadios.forEach(function (radio) {
                var card = radio.closest('.speedx-option-card');
                if (!card) {
                    return;
                }
                card.classList.toggle('speedx-option-card--selected', radio.checked);
            });
        };

        var toggleButtonState = function () {
            var selectedMode = !!document.querySelector('input[name="speedx_reset_mode"]:checked');
            var isConfirmed = input.value === 'RESET';
            var enabled = selectedMode && isConfirmed;

            button.disabled = !enabled;
            button.hidden = !enabled;
            button.classList.toggle('speedx-reset-button--active', enabled);
            updateCards();
        };

        input.addEventListener('input', toggleButtonState);
        modeRadios.forEach(function (radio) {
            radio.addEventListener('change', toggleButtonState);
        });
        toggleButtonState();

        form.addEventListener('submit', function (event) {
            if (button.disabled) {
                event.preventDefault();
                return;
            }

            var confirmMessage = (window.SpeedXSiteReset && window.SpeedXSiteReset.confirmMessage)
                ? window.SpeedXSiteReset.confirmMessage
                : 'Final warning: this destructive action cannot be undone. Continue?';

            if (!window.confirm(confirmMessage)) {
                event.preventDefault();
            }
        });
    });
})();

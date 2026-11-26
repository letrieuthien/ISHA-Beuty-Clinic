(function () {
    'use strict';
    function updatePicker(picker) {
        if (!picker) return;
        picker.querySelectorAll('.net360qr-shape-card').forEach(function (card) {
            var radio = card.querySelector('input[type=\"radio\"][name=\"qr_shape\"]');
            card.classList.toggle('is-selected', !!(radio && radio.checked));
        });
    }
    document.addEventListener('change', function (event) {
        if (event.target && event.target.matches('.net360qr-shape-picker input[type=\"radio\"][name=\"qr_shape\"]')) {
            updatePicker(event.target.closest('.net360qr-shape-picker'));
        }
    });
    document.addEventListener('click', function (event) {
        var card = event.target.closest && event.target.closest('.net360qr-shape-card');
        if (card) {
            var radio = card.querySelector('input[type=\"radio\"][name=\"qr_shape\"]');
            if (radio && event.target !== radio) { radio.checked = true; radio.dispatchEvent(new Event('change', { bubbles: true })); }
            updatePicker(card.closest('.net360qr-shape-picker'));
        }
    });
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.net360qr-shape-picker').forEach(updatePicker);
    });
}());

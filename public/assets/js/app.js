(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var add = event.target.closest('[data-add]');
        if (add) {
            var container = document.getElementById(add.dataset.add);
            var first = container && container.querySelector('[data-row]');
            if (!container || !first) return;
            var index = container.querySelectorAll('[data-row]').length;
            var clone = first.cloneNode(true);
            clone.querySelectorAll('input, select, textarea').forEach(function (field) {
                field.name = field.name.replace(/\[\d+\]/, '[' + index + ']');
                if (field.type !== 'hidden') field.value = '';
            });
            container.appendChild(clone);
            return;
        }
        if (event.target.closest('[data-remove]')) {
            var row = event.target.closest('[data-row]');
            if (row && row.parentElement.querySelectorAll('[data-row]').length > 1) row.remove();
        }
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var submit = form.querySelector('button[type="submit"]');
            if (submit) { submit.disabled = true; submit.setAttribute('aria-busy', 'true'); }
        });
    });
})();

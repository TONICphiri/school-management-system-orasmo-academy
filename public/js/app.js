document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-toggle]');
    document.querySelectorAll('.dropdown.open').forEach(function (d) {
        if (!toggle || d.id !== toggle.getAttribute('data-toggle')) {
            if (!d.contains(e.target)) d.classList.remove('open');
        }
    });
    if (toggle) {
        e.preventDefault();
        var target = document.getElementById(toggle.getAttribute('data-toggle'));
        if (target) target.classList.toggle('open');
    }
    var menu = e.target.closest('.menu-toggle');
    if (menu) document.querySelector('.sidebar').classList.toggle('open');
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') document.querySelectorAll('.dropdown.open').forEach(function (d) { d.classList.remove('open'); });
});

// Confirm before destructive or final actions
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
});

// Show and hide dependent form fields
function syncDepends() {
    document.querySelectorAll('[data-show-when]').forEach(function (el) {
        var parts = el.getAttribute('data-show-when').split('=');
        var input = document.querySelector('[name="' + parts[0] + '"]:checked') || document.querySelector('select[name="' + parts[0] + '"]');
        var values = parts[1].split('|');
        el.classList.toggle('hidden', !input || values.indexOf(input.value) === -1);
    });
}
document.addEventListener('change', syncDepends);
document.addEventListener('DOMContentLoaded', syncDepends);

// Keyboard friendly mark entry: Enter moves to the next learner
document.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && e.target.classList.contains('mark-input')) {
        e.preventDefault();
        var inputs = Array.prototype.slice.call(document.querySelectorAll('.mark-input'));
        var next = inputs[inputs.indexOf(e.target) + 1];
        if (next) { next.focus(); next.select(); }
    }
});

// Filter district and zone lists by the selected division or district
document.addEventListener('change', function (e) {
    var name = e.target.name;
    if (name === 'division_id' || name === 'district_id') {
        var childName = name === 'division_id' ? 'district_id' : 'zone_id';
        var child = e.target.form && e.target.form.querySelector('select[name="' + childName + '"]');
        if (!child) return;
        Array.prototype.forEach.call(child.options, function (o) {
            var parent = o.getAttribute('data-parent');
            o.hidden = parent && e.target.value && parent !== e.target.value;
        });
        if (child.selectedOptions[0] && child.selectedOptions[0].hidden) child.value = '';
    }
});

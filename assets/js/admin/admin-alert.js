// Alert helpers
// Use browser dialogs as fallback
window.AdminAlert = (function () {
    function accentColor() {
        return (window.SquirAdminTheme && window.SquirAdminTheme.swalColor) ? window.SquirAdminTheme.swalColor() : '#6b3f2a';
    }
    var RED = '#c0392b';

    // Confirm admin action
    function confirm(options) {
        if (!window.Swal) {
            return Promise.resolve(window.confirm(options.text || options.title));
        }
        return Swal.fire({
            icon: options.icon || 'warning',
            title: options.title,
            text: options.text,
            showCancelButton: true,
            confirmButtonText: options.confirmText || 'Yes',
            cancelButtonText: 'Cancel',
            confirmButtonColor: options.danger ? RED : accentColor(),
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) { return result.isConfirmed; });
    }

    // Show success notification
    function success(title, text) {
        if (!window.Swal) {
            window.alert(title + (text ? '\n' + text : ''));
            return Promise.resolve();
        }
        return Swal.fire({
            icon: 'success',
            title: title,
            text: text,
            confirmButtonColor: accentColor(),
            timer: 2000,
            timerProgressBar: true
        });
    }

    function error(message) {
        if (!window.Swal) {
            window.alert(message || 'Something went wrong.');
            return Promise.resolve();
        }
        return Swal.fire({
            icon: 'error',
            title: 'Oops',
            text: message || 'Something went wrong.',
            confirmButtonColor: accentColor()
        });
    }

    return { confirm: confirm, success: success, error: error };
})();
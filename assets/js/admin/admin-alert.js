// Admin alert helpers
// Use browser dialogs as fallback
window.AdminAlert = (function () {
    var BROWN = '#6b3f2a';
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
            confirmButtonColor: options.danger ? RED : BROWN,
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
            confirmButtonColor: BROWN,
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
            confirmButtonColor: BROWN
        });
    }

    return { confirm: confirm, success: success, error: error };
})();
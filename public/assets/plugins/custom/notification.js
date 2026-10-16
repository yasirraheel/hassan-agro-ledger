function Notify(type, res = null, msg = null) {
    var message = '';

    switch (type) {
        case "error":
            message = msg ?? res?.responseJSON?.message ?? (typeof res?.responseText === 'string' ? res.responseText : null) ?? (typeof res === 'string' ? res : null) ?? 'Oops! Something went wrong';
            toastr.error(message);
            break;
        case "success":
            message = msg ?? res?.message ?? (typeof res === 'string' ? res : null) ?? 'Operation Successful.';
            toastr.success(message);
            break;
        case "warning":
            message = msg ?? res?.message ?? res?.responseJSON?.message ?? (typeof res === 'string' ? res : null) ?? 'Warning! Operation Failed.';
            toastr.warning(message);
            break;
        default:
            message = msg ?? (typeof res === 'string' ? res : '');
            if (message) toastr.info(message);
    }
}

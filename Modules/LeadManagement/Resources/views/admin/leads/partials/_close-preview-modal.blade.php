<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ translate('Lead_Details') }}</title>
</head>
<body>
<script>
    (function () {
        var payload = {
            type: 'lead-deleted-close-modal',
            message: @json($message)
        };

        if (window.parent && window.parent !== window) {
            window.parent.postMessage(payload, window.location.origin);
            return;
        }

        window.location.replace(@json(route('admin.lead.index')));
    })();
</script>
</body>
</html>

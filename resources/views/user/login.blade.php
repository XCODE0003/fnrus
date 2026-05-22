<html lang="{{ app()->getLocale() }}">
<head>
    <title>{{ __('site.modal_auth_title') }}</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<script>
    $(document).ready(function() {

        var expirationDate = new Date();
        expirationDate.setDate(expirationDate.getDate() + 15);

        document.cookie = "session_token={{ $session_token }}; expires=" + expirationDate.toUTCString() + "; path=/";

        location.href = '/my/profile';
    });
</script>
</body>
</html>

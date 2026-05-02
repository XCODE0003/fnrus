<html>
<head>
    <title>{{ __('site.auth_title') }}</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<script>
    $(document).ready(function() {

        var expirationDate = new Date();
        expirationDate.setDate(expirationDate.getDate() + 7);

        document.cookie = "daccb43ac082fc527924d21b25f8210e=1; expires=" + expirationDate.toUTCString() + "; path=/";

        location.href = '/';
    });
</script>
</body>
</html>

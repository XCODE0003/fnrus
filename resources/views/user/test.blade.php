<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.2/dist/min/dropzone.min.css">
    <script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.2/dist/min/dropzone.min.js"></script>
    <title>Dropzone Example</title>
</head>
<body>
<div id="uploader" class="dropzone">
    <input name="file" type="file" class="d-none" />
</div>

<script>
    Dropzone.autoDiscover = false;

    window.onload = function () {
        var api_url = "/api"; // Replace with your API endpoint
        var dropzoneOptions = {
            dictDefaultMessage: 'Drop Here!',
            url:  api_url + "/attachments/image/upload",
            addRemoveLinks: true,
            maxFilesize: 5,
            acceptedFiles: ".png, .jpg, .gif",
            headers: {'Authorization': 'Bearer ' + getCookie('session_token')},
            init: function() {
                this.on('success', function(file, resp) {
                    alert('File uploaded successfully: ' + file.name);
                });
                this.on('error', function(file, errorMessage) {
                    alert('Error uploading file: ' + errorMessage);
                });
            }
        };

        var uploader = document.querySelector('#uploader');
        var newDropzone = new Dropzone(uploader, dropzoneOptions);

        console.log("Loaded");

        function getCookie(name) {
            // Implement your getCookie function here
        }
    };
</script>
</body>
</html>

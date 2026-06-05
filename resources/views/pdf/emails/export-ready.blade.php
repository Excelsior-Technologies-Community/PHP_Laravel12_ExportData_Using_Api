<!-- resources/views/emails/export-ready.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Export Ready</title>
</head>
<body>
    <h2>Your Export is Ready!</h2>
    <p>Export Type: {{ $exportLog->export_type }}</p>
    <p>Format: {{ $exportLog->format }}</p>
    <p>Records: {{ $exportLog->records_count }}</p>
    <p>
        <a href="{{ url($exportLog->download_url) }}">Download File</a>
    </p>
    <p>This link will expire in 24 hours.</p>
</body>
</html>
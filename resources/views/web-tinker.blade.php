<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Web Tinker</title>

    <!-- Style sheets-->
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    @if(config('web-tinker.dark_theme'))
        <link href='{{asset(mix('app-dark.css', 'vendor/web-tinker'))}}' rel='stylesheet' type='text/css'>
    @else
        <link href='{{asset(mix('app.css', 'vendor/web-tinker'))}}' rel='stylesheet' type='text/css'>
    @endif
</head>
<body>

Here comes the tinker

<div id="web-tinker" v-cloak>
    Vue loaded!
</div>


<script src="{{asset(mix('app.js', 'vendor/web-tinker'))}}"></script>
</body>
</html>

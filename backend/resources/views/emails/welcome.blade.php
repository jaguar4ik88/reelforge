<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1a1a1a;">
    <p>{{ __('emails.welcome.greeting', ['name' => $user->name]) }}</p>
    <p>{{ __('emails.welcome.body') }}</p>
    <p style="margin-top: 1.5rem;">{{ __('emails.welcome.closing', ['app' => config('app.name')]) }}</p>
</body>
</html>

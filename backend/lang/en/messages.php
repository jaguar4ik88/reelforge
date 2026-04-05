<?php

return [
    'auth' => [
        'registered'       => 'Registration successful.',
        'login_success'    => 'Login successful.',
        'logout_success'   => 'Logged out successfully.',
        'invalid_credentials' => 'Invalid credentials.',
        'reset_link_sent_generic' => 'If an account exists for that email, we sent password reset instructions.',
        'reset_throttled' => 'Please wait before requesting another reset link.',
        'password_reset_success' => 'Your password has been reset. You can sign in now.',
        'invalid_reset_token' => 'This reset link is invalid or has expired. Request a new one.',
        'invalid_reset_user' => 'We could not find an account for this email.',
        'password_reset_failed' => 'Could not reset the password. Try again.',
    ],
    'project' => [
        'created'          => 'Project created.',
        'deleted'          => 'Project deleted.',
        'not_found'        => 'Project not found.',
        'forbidden'        => 'Forbidden.',
    ],
    'image' => [
        'uploaded'         => 'Images uploaded successfully.',
        'deleted'          => 'Image deleted.',
        'upload_locked'    => 'Cannot upload images to a project that is already processing or done.',
    ],
    'video' => [
        'started'          => 'Video generation started. Check back in a moment.',
        'limit_reached'    => 'Monthly video limit reached. Upgrade your plan.',
        'already_processing' => 'Video generation is already in progress.',
        'need_images'      => 'Upload at least 3 images before generating.',
        'insufficient_credits' => 'Not enough credits to generate a video.',
        'photo_guided_use_photo_flow' => 'This project was created from a product photo. Use photo-guided generation instead of video export.',
    ],
    'photo_guided' => [
        'default_title'       => 'Product (photo)',
        'default_description' => 'Created via photo-guided flow. Reference image is stored as project photos.',
        'project_created'     => 'Project created from your product photo.',
        'started'             => 'Generation job queued. Prompt saved — connect your model when ready.',
        'not_photo_project'   => 'This action is only for photo-guided projects.',
        'project_locked'      => 'Cannot start generation while the project is not in draft state.',
        'need_reference_image' => 'Upload a product reference image first.',
        'already_running'     => 'A generation is already pending or processing for this project.',
        'insufficient_credits' => 'Not enough credits for photo-guided generation.',
        'stub_quality_1'      => 'Product is visible in the frame',
        'stub_quality_2'      => 'Add an image-analysis API key for automatic descriptions',
    ],
    'credits' => [
        'stub_purchase' => 'Credits added (stub purchase; enable only in debug).',
    ],
    'profile' => [
        'updated'             => 'Profile updated.',
        'password_changed'    => 'Password changed.',
        'wrong_password'      => 'Current password is incorrect.',
        'password_oauth_only' => 'You signed in with Google or Apple. Set a password from account settings when that option is available, or continue using social login.',
    ],
];

<?php

test('youtube config file is loaded', function () {
    $config = config('youtube');

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['credentials', 'scopes', 'admin', 'upload', 'routes', 'defaults']);
});

test('youtube client credentials are configured', function () {
    $credentials = config('youtube.credentials');

    expect($credentials)->toBeArray()
        ->and($credentials['client_id'])->toBeString()
        ->and($credentials['client_secret'])->toBeString()
        ->and($credentials['redirect_uri'])->toBeString();
});

test('youtube scopes are configured', function () {
    $scopes = config('youtube.scopes');

    expect($scopes)->toBeArray()
        ->and($scopes)->toContain('https://www.googleapis.com/auth/youtube.upload');
});

test('youtube admin config is valid', function () {
    $admin = config('youtube.admin');

    expect($admin)->toBeArray()
        ->and($admin)->toHaveKeys(['enabled', 'prefix', 'middleware', 'auth_middleware']);
});

test('youtube upload config is valid', function () {
    $upload = config('youtube.upload');

    expect($upload)->toBeArray()
        ->and($upload)->toHaveKeys(['chunk_size', 'timeout', 'max_file_size', 'temp_path']);
});

test('youtube defaults config is valid', function () {
    $defaults = config('youtube.defaults');

    expect($defaults)->toBeArray()
        ->and($defaults)->toHaveKeys(['privacy_status', 'category_id', 'language'])
        ->and($defaults['privacy_status'])->toBeIn(['private', 'unlisted', 'public']);
});

@extends('youtube::layouts.admin')

@section('title', 'YouTube Authentication')
@section('header', 'YouTube Authentication')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Introduction Card -->
    <div class="glass rounded-xl p-8 glow-blue-hover transition-all duration-300">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-12 w-12 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
            </div>
            <div class="ml-6 flex-1">
                <h2 class="text-2xl font-bold text-white">Connect Your YouTube Channel</h2>
                <p class="mt-2 text-gray-300">
                    Authenticate with Google to enable video uploads and manage your YouTube channel directly from this application.
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="glass rounded-xl border-2 border-green-500/50 bg-green-900/20 p-6">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-200">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="glass rounded-xl border-2 border-red-500/50 bg-red-900/20 p-6">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-200">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Connected Accounts -->
    @if($hasTokens)
    <div class="glass rounded-xl p-8">
        <h3 class="text-xl font-semibold text-white mb-6">Connected YouTube Channels</h3>
        <div class="space-y-4">
            @foreach($tokens as $token)
            <div class="glass rounded-lg p-6 flex items-center justify-between hover:glow-purple-hover transition-all duration-300">
                <div class="flex items-center space-x-4">
                    @if($token->channel_thumbnail)
                    <img src="{{ $token->channel_thumbnail }}" alt="{{ $token->channel_title }}" class="w-16 h-16 rounded-full border-2 border-purple-500/50">
                    @else
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                        <span class="text-2xl font-bold text-white">{{ substr($token->channel_title, 0, 1) }}</span>
                    </div>
                    @endif

                    <div>
                        <h4 class="text-lg font-semibold text-white">{{ $token->channel_title }}</h4>
                        @if($token->channel_handle)
                        <p class="text-sm text-gray-400">{{ $token->channel_handle }}</p>
                        @endif
                        <div class="flex items-center mt-2 space-x-4">
                            @if($token->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Active
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Inactive
                            </span>
                            @endif

                            <span class="text-xs text-gray-400">
                                Expires {{ $token->expires_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                </div>

                <form action="{{ route('youtube.revoke') }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to disconnect this channel?');">
                    @csrf
                    <input type="hidden" name="token_id" value="{{ $token->id }}">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Disconnect
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="glass rounded-xl p-12 text-center">
        <div class="mx-auto w-24 h-24 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
            </svg>
        </div>
        <h3 class="text-2xl font-bold text-white mb-2">No Connected Channels</h3>
        <p class="text-gray-400 mb-6">Connect your YouTube channel to start uploading videos.</p>
    </div>
    @endif

    <!-- Connect New Channel Button -->
    <div class="glass rounded-xl p-8 text-center">
        <h3 class="text-xl font-semibold text-white mb-4">
            @if($hasTokens)
            Connect Another Channel
            @else
            Get Started
            @endif
        </h3>
        <p class="text-gray-400 mb-6">
            Click the button below to authenticate with Google and grant access to your YouTube channel.
        </p>
        <a href="{{ route('youtube.auth') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-105 glow-purple">
            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
            </svg>
            Connect YouTube Channel
        </a>
        <p class="mt-4 text-xs text-gray-500">
            You will be redirected to Google to authorize this application
        </p>
    </div>

    <!-- Permissions Info -->
    <div class="glass rounded-xl p-8">
        <h3 class="text-lg font-semibold text-white mb-4">What permissions are required?</h3>
        <ul class="space-y-3 text-gray-300">
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span><strong>Upload videos</strong> - Upload new videos to your YouTube channel</span>
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span><strong>Manage videos</strong> - Update, delete, and manage your video metadata</span>
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span><strong>View channel information</strong> - Access your channel details and statistics</span>
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span><strong>Offline access</strong> - Refresh authentication tokens automatically</span>
            </li>
        </ul>
    </div>
</div>
@endsection

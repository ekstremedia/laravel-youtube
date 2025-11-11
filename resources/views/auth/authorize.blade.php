@extends('youtube::layouts.app')

@section('title', 'YouTube Authorization')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
        <svg class="mx-auto h-16 w-16 text-red-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
        </svg>
        <h1 class="mt-4 text-3xl font-bold text-white">YouTube Authorization</h1>
        <p class="mt-2 text-gray-400">Connect your YouTube channel to enable video uploads</p>
    </div>

    @if(session('success'))
    <div class="bg-green-900/50 border border-green-500 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <p class="text-sm text-green-200">{{ session('success') }}</p>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-900/50 border border-red-500 rounded-lg p-4">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="ml-3">
                <p class="text-sm text-red-200">{{ session('error') }}</p>
            </div>
        </div>
    </div>
    @endif

    <!-- Configuration Check -->
    @if(!$credentialsConfigured)
    <div class="bg-yellow-900/50 border border-yellow-500 rounded-lg p-6">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="ml-3 flex-1">
                <h3 class="text-lg font-medium text-yellow-200">Configuration Required</h3>
                <p class="mt-2 text-sm text-yellow-300">Please add your Google OAuth credentials to your .env file:</p>
                <div class="mt-4 bg-gray-800 rounded p-4 font-mono text-sm text-gray-300">
                    <div>YOUTUBE_CLIENT_ID=your-client-id</div>
                    <div>YOUTUBE_CLIENT_SECRET=your-client-secret</div>
                    <div>YOUTUBE_REDIRECT_URI={{ url('/youtube/callback') }}</div>
                </div>
                <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="mt-4 inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Get Credentials from Google Cloud Console
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Authentication Status -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h2 class="text-xl font-semibold text-white mb-4">Status</h2>

        @if($hasToken)
            <div class="flex items-center space-x-4">
                @if($token->channel_thumbnail)
                <img src="{{ $token->channel_thumbnail }}" alt="{{ $token->channel_title }}" class="w-16 h-16 rounded-full border-2 border-green-500">
                @endif

                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-white">{{ $token->channel_title }}</h3>
                    @if($token->channel_handle)
                    <p class="text-sm text-gray-400">{{ $token->channel_handle }}</p>
                    @endif
                    <div class="mt-2 flex items-center space-x-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900 text-green-200 border border-green-700">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Connected
                        </span>
                        <span class="text-xs text-gray-400">
                            Token expires {{ $token->expires_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <form action="{{ route('youtube.revoke') }}" method="POST" onsubmit="return confirm('Are you sure you want to disconnect?');">
                    @csrf
                    <input type="hidden" name="token_id" value="{{ $token->id }}">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Disconnect
                    </button>
                </form>
            </div>
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <p class="mt-4 text-gray-400">Not connected to YouTube</p>
            </div>
        @endif
    </div>

    <!-- Connect Button -->
    @if($credentialsConfigured)
    <div class="text-center">
        <a href="{{ route('youtube.auth') }}" class="inline-flex items-center px-8 py-4 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg shadow-lg transition-all transform hover:scale-105">
            <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
            </svg>
            @if($hasToken)
                Re-authorize YouTube
            @else
                Authorize YouTube
            @endif
        </a>
        <p class="mt-4 text-sm text-gray-500">
            You will be redirected to Google to authorize this application
        </p>
    </div>
    @endif

    <!-- Permissions Info -->
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-white mb-4">Required Permissions</h3>
        <ul class="space-y-3 text-gray-300 text-sm">
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Upload videos to your YouTube channel</span>
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Manage your video metadata (title, description, etc.)</span>
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>View your channel information</span>
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-green-400 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>Automatic token refresh (offline access)</span>
            </li>
        </ul>
    </div>
</div>
@endsection

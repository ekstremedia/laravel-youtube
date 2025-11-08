@extends('youtube::layouts.admin')

@section('title', 'Channels')
@section('header', 'YouTube Channels')

@section('content')
<div class="space-y-6">
    @if(count($channels) === 0)
    <!-- Empty State -->
    <div class="glass rounded-xl p-12 text-center">
        <div class="flex justify-center mb-4">
            <div class="rounded-full bg-purple-900/50 p-6">
                <svg class="w-16 h-16 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>
        <h3 class="text-xl font-semibold text-purple-100 mb-2">No Channels Connected</h3>
        <p class="text-purple-300 mb-6">Connect your YouTube channel to start managing your videos</p>
        <a href="{{ route('youtube.auth') }}" class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Connect YouTube Channel
        </a>
    </div>
    @else
    <!-- Channels List -->
    <div class="glass rounded-xl overflow-hidden">
        <div class="p-6 border-b border-purple-700/30">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-purple-100">Connected Channels</h2>
                <a href="{{ route('youtube.auth') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Channel
                </a>
            </div>
        </div>

        <div class="divide-y divide-purple-700/30">
            @foreach($channels as $channel)
            <div class="p-6 hover:bg-purple-900/20 transition-colors duration-200">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4">
                        @if(!empty($channel['thumbnails']['default']['url']))
                        <img src="{{ $channel['thumbnails']['default']['url'] }}" alt="{{ $channel['title'] }}" class="w-20 h-20 rounded-full border-2 border-purple-500/50">
                        @else
                        <div class="w-20 h-20 rounded-full bg-purple-900/50 flex items-center justify-center border-2 border-purple-500/50">
                            <svg class="w-10 h-10 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        @endif

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-purple-100">{{ $channel['title'] }}</h3>
                            <p class="text-sm text-purple-400 mt-1">{{ $channel['id'] }}</p>

                            @if(!empty($channel['description']))
                            <p class="text-sm text-purple-300 mt-2 line-clamp-2">{{ $channel['description'] }}</p>
                            @endif

                            <div class="flex items-center space-x-6 mt-4">
                                @if(isset($channel['subscriberCount']))
                                <div class="text-sm">
                                    <span class="text-purple-400">Subscribers:</span>
                                    <span class="text-purple-100 font-medium ml-1">{{ number_format($channel['subscriberCount']) }}</span>
                                </div>
                                @endif
                                @if(isset($channel['videoCount']))
                                <div class="text-sm">
                                    <span class="text-purple-400">Videos:</span>
                                    <span class="text-purple-100 font-medium ml-1">{{ number_format($channel['videoCount']) }}</span>
                                </div>
                                @endif
                                @if(isset($channel['viewCount']))
                                <div class="text-sm">
                                    <span class="text-purple-400">Views:</span>
                                    <span class="text-purple-100 font-medium ml-1">{{ number_format($channel['viewCount']) }}</span>
                                </div>
                                @endif
                            </div>

                            <div class="flex items-center space-x-2 mt-4">
                                @if($channel['expires_soon'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-900/50 text-yellow-300 border border-yellow-600/50">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    Token Expiring Soon
                                </span>
                                @endif
                                @if($channel['is_expired'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-300 border border-red-600/50">
                                    Expired
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 ml-4">
                        <a href="{{ route('youtube.admin.channels.show', $channel['id']) }}" class="inline-flex items-center px-3 py-2 bg-purple-700/50 hover:bg-purple-700 text-purple-100 text-sm font-medium rounded-lg transition-colors duration-200">
                            View Details
                        </a>
                        <form action="{{ route('youtube.admin.channels.sync') }}" method="POST" class="inline">
                            @csrf
                            <input type="hidden" name="channel_id" value="{{ $channel['id'] }}">
                            <button type="submit" class="inline-flex items-center px-3 py-2 bg-purple-600/50 hover:bg-purple-600 text-purple-100 text-sm font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Sync
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

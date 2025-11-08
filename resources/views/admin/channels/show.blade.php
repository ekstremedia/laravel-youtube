@extends('youtube::layouts.admin')

@section('title', 'Channel Details')
@section('header', isset($channel) ? $channel['title'] : 'Channel Details')

@section('content')
<div class="space-y-6">
    @if(isset($error))
    <!-- Error State -->
    <div class="glass rounded-xl border-2 border-red-500/50 bg-red-900/20 p-6">
        <div class="flex items-start">
            <svg class="h-6 w-6 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <h3 class="text-lg font-medium text-red-200">Error Loading Channel</h3>
                <p class="mt-2 text-sm text-red-300">{{ $error }}</p>
            </div>
        </div>
    </div>
    @else
    <!-- Channel Header -->
    <div class="glass rounded-xl p-6">
        <div class="flex items-start space-x-6">
            @if(!empty($channel['thumbnails']['default']['url']))
            <img src="{{ $channel['thumbnails']['default']['url'] }}" alt="{{ $channel['title'] }}" class="w-32 h-32 rounded-full border-4 border-purple-500/50">
            @endif

            <div class="flex-1">
                <h1 class="text-2xl font-bold text-purple-100">{{ $channel['title'] }}</h1>
                <p class="text-purple-400 mt-1">{{ $channel['id'] }}</p>

                @if(!empty($channel['description']))
                <p class="text-purple-300 mt-4">{{ $channel['description'] }}</p>
                @endif

                <div class="grid grid-cols-3 gap-4 mt-6">
                    @if(isset($channel['subscriberCount']))
                    <div class="text-center p-4 bg-purple-900/30 rounded-lg">
                        <p class="text-2xl font-bold text-purple-100">{{ number_format($channel['subscriberCount']) }}</p>
                        <p class="text-sm text-purple-400 mt-1">Subscribers</p>
                    </div>
                    @endif
                    @if(isset($channel['videoCount']))
                    <div class="text-center p-4 bg-purple-900/30 rounded-lg">
                        <p class="text-2xl font-bold text-purple-100">{{ number_format($channel['videoCount']) }}</p>
                        <p class="text-sm text-purple-400 mt-1">Videos</p>
                    </div>
                    @endif
                    @if(isset($channel['viewCount']))
                    <div class="text-center p-4 bg-purple-900/30 rounded-lg">
                        <p class="text-2xl font-bold text-purple-100">{{ number_format($channel['viewCount']) }}</p>
                        <p class="text-sm text-purple-400 mt-1">Total Views</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Videos -->
    @if(count($videos) > 0)
    <div class="glass rounded-xl overflow-hidden">
        <div class="p-6 border-b border-purple-700/30">
            <h2 class="text-xl font-semibold text-purple-100">Recent Videos</h2>
        </div>
        <div class="divide-y divide-purple-700/30">
            @foreach($videos as $video)
            <div class="p-6 hover:bg-purple-900/20 transition-colors duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-purple-100">{{ $video['title'] }}</h3>
                        <p class="text-sm text-purple-400 mt-1">{{ $video['publishedAt'] }}</p>
                    </div>
                    <a href="https://www.youtube.com/watch?v={{ $video['id'] }}" target="_blank" class="text-purple-400 hover:text-purple-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Playlists -->
    @if(count($playlists) > 0)
    <div class="glass rounded-xl overflow-hidden">
        <div class="p-6 border-b border-purple-700/30">
            <h2 class="text-xl font-semibold text-purple-100">Playlists</h2>
        </div>
        <div class="divide-y divide-purple-700/30">
            @foreach($playlists as $playlist)
            <div class="p-6 hover:bg-purple-900/20 transition-colors duration-200">
                <h3 class="text-lg font-medium text-purple-100">{{ $playlist['title'] }}</h3>
                <p class="text-sm text-purple-400 mt-1">{{ $playlist['itemCount'] ?? 0 }} videos</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif
</div>
@endsection

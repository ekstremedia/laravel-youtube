@extends('youtube::layouts.admin')

@section('title', 'Playlists')
@section('header', 'Playlists')

@section('content')
<div class="space-y-6">
    <div class="flex justify-end">
        <a href="{{ route('youtube.admin.playlists.create') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Create Playlist
        </a>
    </div>

    <div class="glass rounded-xl overflow-hidden">
        @forelse($playlists as $playlist)
        <div class="p-6 border-b border-purple-700/30 hover:bg-purple-900/20 transition-colors duration-200">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-purple-100">{{ $playlist['title'] }}</h3>
                    <p class="text-sm text-purple-400 mt-1">{{ $playlist['itemCount'] ?? 0 }} videos</p>
                    @if(isset($playlist['channel_title']))
                    <p class="text-xs text-purple-500 mt-1">{{ $playlist['channel_title'] }}</p>
                    @endif
                </div>
                <a href="{{ route('youtube.admin.playlists.show', $playlist['id']) }}" class="px-3 py-2 bg-purple-700/50 hover:bg-purple-700 text-purple-100 text-sm rounded-lg transition-colors duration-200">
                    View
                </a>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <p class="text-purple-300">No playlists found</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

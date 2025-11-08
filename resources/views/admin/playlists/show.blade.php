@extends('youtube::layouts.admin')

@section('title', $playlist['title'] ?? 'Playlist')
@section('header', $playlist['title'] ?? 'Playlist')

@section('content')
<div class="space-y-6">
    <div class="glass rounded-xl p-6">
        <h1 class="text-2xl font-bold text-purple-100">{{ $playlist['title'] }}</h1>
        @if(!empty($playlist['description']))
        <p class="text-purple-300 mt-2">{{ $playlist['description'] }}</p>
        @endif
        <p class="text-sm text-purple-400 mt-4">{{ count($videos) }} videos</p>
    </div>

    @if(count($videos) > 0)
    <div class="glass rounded-xl overflow-hidden">
        <div class="p-6 border-b border-purple-700/30">
            <h2 class="text-xl font-semibold text-purple-100">Videos</h2>
        </div>
        <div class="divide-y divide-purple-700/30">
            @foreach($videos as $video)
            <div class="p-6">
                <p class="text-purple-100">{{ $video['title'] ?? 'Video' }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

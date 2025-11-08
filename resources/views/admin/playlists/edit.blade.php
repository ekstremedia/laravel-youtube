@extends('youtube::layouts.admin')

@section('title', 'Edit Playlist')
@section('header', 'Edit Playlist')

@section('content')
<div class="max-w-3xl">
    <div class="glass rounded-xl p-6">
        <p class="text-purple-300">Playlist editing is not yet implemented</p>
        <a href="{{ route('youtube.admin.playlists.show', $playlist['id']) }}" class="text-purple-400 hover:text-purple-300 mt-4 inline-block">Back to Playlist</a>
    </div>
</div>
@endsection

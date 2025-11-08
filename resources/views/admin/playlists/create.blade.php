@extends('youtube::layouts.admin')

@section('title', 'Create Playlist')
@section('header', 'Create Playlist')

@section('content')
<div class="max-w-3xl">
    <div class="glass rounded-xl p-6">
        <form method="POST" action="{{ route('youtube.admin.playlists.store') }}">
            @csrf
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="150" required class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Description</label>
                    <textarea name="description" rows="4" maxlength="5000" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Privacy Status</label>
                    <select name="privacy_status" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="private">Private</option>
                        <option value="unlisted">Unlisted</option>
                        <option value="public">Public</option>
                    </select>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('youtube.admin.playlists.index') }}" class="text-purple-400 hover:text-purple-300">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200">Create Playlist</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('youtube::layouts.admin')

@section('title', 'Edit Video')
@section('header', 'Edit Video')

@section('content')
<div class="max-w-3xl">
    <div class="glass rounded-xl p-6">
        <form method="POST" action="{{ route('youtube.admin.videos.update', $video->id) }}">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Title</label>
                    <input type="text" name="title" value="{{ old('title', $video->title) }}" maxlength="100" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Description</label>
                    <textarea name="description" rows="6" maxlength="5000" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description', $video->description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Tags (comma-separated)</label>
                    <input type="text" name="tags" value="{{ old('tags', is_array($video->tags) ? implode(', ', $video->tags) : '') }}" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Privacy Status</label>
                    <select name="privacy_status" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="private" {{ $video->privacy_status === 'private' ? 'selected' : '' }}>Private</option>
                        <option value="unlisted" {{ $video->privacy_status === 'unlisted' ? 'selected' : '' }}>Unlisted</option>
                        <option value="public" {{ $video->privacy_status === 'public' ? 'selected' : '' }}>Public</option>
                    </select>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('youtube.admin.videos.show', $video->id) }}" class="text-purple-400 hover:text-purple-300">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200">
                        Update Video
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

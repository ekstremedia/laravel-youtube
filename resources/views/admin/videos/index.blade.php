@extends('youtube::layouts.admin')

@section('title', 'Videos')
@section('header', 'Videos')

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="glass rounded-xl p-6">
        <form method="GET" action="{{ route('youtube.admin.videos.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search videos..." class="bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">

            <select name="privacy" class="bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">All Privacy</option>
                <option value="public" {{ ($filters['privacy'] ?? '') === 'public' ? 'selected' : '' }}>Public</option>
                <option value="unlisted" {{ ($filters['privacy'] ?? '') === 'unlisted' ? 'selected' : '' }}>Unlisted</option>
                <option value="private" {{ ($filters['privacy'] ?? '') === 'private' ? 'selected' : '' }}>Private</option>
            </select>

            <select name="status" class="bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="">All Status</option>
                <option value="uploaded" {{ ($filters['status'] ?? '') === 'uploaded' ? 'selected' : '' }}>Uploaded</option>
                <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>

            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white rounded-lg px-4 py-2 transition-colors duration-200">
                Filter
            </button>
        </form>
    </div>

    <!-- Videos List -->
    <div class="glass rounded-xl overflow-hidden">
        @forelse($videos as $video)
        <div class="p-6 border-b border-purple-700/30 hover:bg-purple-900/20 transition-colors duration-200">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-purple-100">{{ $video->title }}</h3>
                    <p class="text-sm text-purple-400 mt-1">{{ $video->video_id }}</p>
                    <div class="flex items-center space-x-4 mt-2">
                        <span class="text-xs px-2 py-1 rounded bg-purple-900/50 text-purple-300">{{ ucfirst($video->privacy_status) }}</span>
                        <span class="text-xs text-purple-400">{{ $video->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('youtube.admin.videos.show', $video->id) }}" class="px-3 py-2 bg-purple-700/50 hover:bg-purple-700 text-purple-100 text-sm rounded-lg transition-colors duration-200">
                        View
                    </a>
                    <a href="{{ route('youtube.admin.videos.edit', $video->id) }}" class="px-3 py-2 bg-purple-600/50 hover:bg-purple-600 text-purple-100 text-sm rounded-lg transition-colors duration-200">
                        Edit
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <p class="text-purple-300">No videos found</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($videos->hasPages())
    <div class="flex justify-center">
        {{ $videos->links() }}
    </div>
    @endif
</div>
@endsection

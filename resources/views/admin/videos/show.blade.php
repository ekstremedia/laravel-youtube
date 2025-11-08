@extends('youtube::layouts.admin')

@section('title', $video->title ?? 'Video Details')
@section('header', $video->title ?? 'Video Details')

@section('content')
<div class="space-y-6">
    @if(isset($error))
    <div class="glass rounded-xl border-2 border-red-500/50 bg-red-900/20 p-6">
        <p class="text-red-300">{{ $error }}</p>
    </div>
    @else
    <div class="glass rounded-xl p-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <h1 class="text-2xl font-bold text-purple-100 mb-4">{{ $video->title }}</h1>
                @if($video->description)
                <p class="text-purple-300 mb-4">{{ $video->description }}</p>
                @endif

                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-purple-900/30 rounded-lg">
                        <p class="text-2xl font-bold text-purple-100">{{ number_format($video->view_count ?? 0) }}</p>
                        <p class="text-sm text-purple-400 mt-1">Views</p>
                    </div>
                    <div class="text-center p-4 bg-purple-900/30 rounded-lg">
                        <p class="text-2xl font-bold text-purple-100">{{ number_format($video->like_count ?? 0) }}</p>
                        <p class="text-sm text-purple-400 mt-1">Likes</p>
                    </div>
                    <div class="text-center p-4 bg-purple-900/30 rounded-lg">
                        <p class="text-2xl font-bold text-purple-100">{{ number_format($video->comment_count ?? 0) }}</p>
                        <p class="text-sm text-purple-400 mt-1">Comments</p>
                    </div>
                </div>
            </div>

            <div>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-purple-400">Video ID</p>
                        <p class="text-purple-100 font-mono text-sm">{{ $video->video_id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-purple-400">Privacy Status</p>
                        <p class="text-purple-100">{{ ucfirst($video->privacy_status) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-purple-400">Upload Status</p>
                        <p class="text-purple-100">{{ ucfirst($video->upload_status) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-purple-400">Published At</p>
                        <p class="text-purple-100">{{ $video->published_at?->format('M d, Y H:i') ?? 'Not published' }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ $video->watchUrl }}" target="_blank" class="flex-1 text-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors duration-200">
                            Watch on YouTube
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

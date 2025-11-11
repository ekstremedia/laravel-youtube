@extends('youtube::layouts.admin')

@section('title', 'Upload Video')
@section('header', 'Upload Video')

@section('content')
<div class="max-w-3xl">
    <div class="glass rounded-xl p-6">
        <form id="upload-form" method="POST" action="{{ route('youtube.admin.upload.store') }}" enctype="multipart/form-data" data-max-size="{{ $maxFileSize }}">
            @csrf

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Video File *</label>
                    <input type="file" name="video" accept="video/*" required class="w-full text-purple-100 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700">
                    <p class="text-xs text-purple-400 mt-1">Max size: {{ $maxFileSize }}MB</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="100" required class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Description</label>
                    <textarea name="description" rows="6" maxlength="5000" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Tags (comma-separated)</label>
                    <input type="text" name="tags" value="{{ old('tags') }}" placeholder="travel, vlog, adventure" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Privacy Status</label>
                    <select name="privacy_status" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="private" {{ ($defaultPrivacy ?? 'private') === 'private' ? 'selected' : '' }}>Private</option>
                        <option value="unlisted" {{ ($defaultPrivacy ?? 'private') === 'unlisted' ? 'selected' : '' }}>Unlisted</option>
                        <option value="public" {{ ($defaultPrivacy ?? 'private') === 'public' ? 'selected' : '' }}>Public</option>
                    </select>
                </div>

                @if($tokens->count() > 1)
                <div>
                    <label class="block text-sm font-medium text-purple-300 mb-2">Channel</label>
                    <select name="channel_id" class="w-full bg-purple-900/30 border border-purple-700/50 text-purple-100 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        @foreach($tokens as $token)
                        <option value="{{ $token->channel_id }}">{{ $token->channel_title }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('youtube.admin.videos.index') }}" class="text-purple-400 hover:text-purple-300">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors duration-200">
                        Upload Video
                    </button>
                </div>
            </div>
        </form>

        <!-- Upload Progress -->
        <div id="upload-progress" class="hidden mt-6">
            <div class="mb-2 flex justify-between items-center">
                <span id="progress-text" class="text-sm text-purple-300">Uploading...</span>
            </div>
            <div class="w-full bg-purple-900/30 rounded-full h-3 border border-purple-700/50">
                <div id="progress-bar" class="bg-gradient-to-r from-purple-600 to-purple-500 h-3 rounded-full text-xs text-white flex items-center justify-center transition-all duration-300" style="width: 0%">0%</div>
            </div>
        </div>

        <!-- Status Message -->
        <div id="status-message" class="hidden"></div>
    </div>

    @if($recentUploads->count() > 0)
    <div class="glass rounded-xl p-6 mt-6">
        <h2 class="text-lg font-semibold text-purple-100 mb-4">Recent Uploads</h2>
        <div class="space-y-3">
            @foreach($recentUploads as $upload)
            <div class="flex items-center justify-between p-3 bg-purple-900/20 rounded-lg">
                <div>
                    <p class="text-purple-100 font-medium">{{ $upload->title }}</p>
                    <p class="text-xs text-purple-400">{{ $upload->created_at->diffForHumans() }}</p>
                </div>
                <span class="text-xs px-2 py-1 rounded bg-purple-900/50 text-purple-300">{{ ucfirst($upload->upload_status) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/youtube/js/upload.js') }}"></script>
@endpush

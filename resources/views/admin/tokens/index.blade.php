@extends('youtube::layouts.admin')

@section('title', 'OAuth Tokens')
@section('header', 'OAuth Tokens')

@section('content')
<div class="space-y-6">
    <div class="glass rounded-xl overflow-hidden">
        @forelse($tokens as $token)
        <div class="p-6 border-b border-purple-700/30">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-medium text-purple-100">{{ $token->channel_title }}</h3>
                    <p class="text-sm text-purple-400 mt-1">{{ $token->channel_id }}</p>

                    <div class="flex items-center space-x-4 mt-3 text-sm">
                        <span class="text-purple-300">Expires: {{ $token->expires_at->diffForHumans() }}</span>
                        @if($token->is_expired)
                        <span class="px-2 py-1 rounded bg-red-900/50 text-red-300 text-xs">Expired</span>
                        @elseif($token->expires_soon)
                        <span class="px-2 py-1 rounded bg-yellow-900/50 text-yellow-300 text-xs">Expiring Soon</span>
                        @endif
                        @if(!$token->is_active)
                        <span class="px-2 py-1 rounded bg-gray-700 text-gray-300 text-xs">Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    @if($token->is_active)
                    <form action="{{ route('youtube.admin.tokens.refresh', $token->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-purple-600/50 hover:bg-purple-600 text-purple-100 text-sm rounded-lg transition-colors duration-200">Refresh</button>
                    </form>

                    <form action="{{ route('youtube.admin.tokens.deactivate', $token->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-yellow-600/50 hover:bg-yellow-600 text-yellow-100 text-sm rounded-lg transition-colors duration-200">Deactivate</button>
                    </form>
                    @else
                    <form action="{{ route('youtube.admin.tokens.activate', $token->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 bg-green-600/50 hover:bg-green-600 text-green-100 text-sm rounded-lg transition-colors duration-200">Activate</button>
                    </form>
                    @endif

                    <form action="{{ route('youtube.admin.tokens.destroy', $token->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this token?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 bg-red-600/50 hover:bg-red-600 text-red-100 text-sm rounded-lg transition-colors duration-200">Delete</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center">
            <p class="text-purple-300">No tokens found</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

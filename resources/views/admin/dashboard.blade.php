@extends('youtube::layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Configuration Warning -->
    @if($configurationWarning)
    <div class="glass rounded-xl border-2 border-red-500/50 bg-red-900/20 p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-medium text-red-200">Configuration Required</h3>
                <p class="mt-2 text-sm text-red-300">{{ $configurationWarning }}</p>
                <div class="mt-4">
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Get OAuth Credentials from Google Cloud Console
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Channels -->
        <div class="glass rounded-xl p-6 glow-purple-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-300 text-sm font-medium">Total Channels</p>
                    <p class="text-3xl font-bold text-purple-100 mt-2">{{ $stats['total_channels'] }}</p>
                    <p class="text-purple-400 text-sm mt-1">{{ $stats['active_channels'] }} active</p>
                </div>
                <div class="rounded-full bg-purple-900/50 p-3">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Videos -->
        <div class="glass rounded-xl p-6 glow-purple-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-300 text-sm font-medium">Total Videos</p>
                    <p class="text-3xl font-bold text-purple-100 mt-2">{{ $stats['total_videos'] }}</p>
                    <p class="text-purple-400 text-sm mt-1">{{ $stats['public_videos'] }} public</p>
                </div>
                <div class="rounded-full bg-purple-900/50 p-3">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Views -->
        <div class="glass rounded-xl p-6 glow-purple-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-300 text-sm font-medium">Total Views</p>
                    <p class="text-3xl font-bold text-purple-100 mt-2">{{ number_format($stats['total_views']) }}</p>
                    <p class="text-purple-400 text-sm mt-1">All time</p>
                </div>
                <div class="rounded-full bg-purple-900/50 p-3">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Likes -->
        <div class="glass rounded-xl p-6 glow-purple-hover transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-300 text-sm font-medium">Total Likes</p>
                    <p class="text-3xl font-bold text-purple-100 mt-2">{{ number_format($stats['total_likes']) }}</p>
                    <p class="text-purple-400 text-sm mt-1">All videos</p>
                </div>
                <div class="rounded-full bg-purple-900/50 p-3">
                    <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Connected Channels -->
    @if(count($channels) > 0)
        <div class="glass rounded-xl p-6">
            <h2 class="text-xl font-semibold text-purple-100 mb-4">Connected Channels</h2>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($channels as $channel)
                    <div class="bg-purple-950/30 rounded-lg p-4 border border-purple-800/30 hover:border-purple-600/50 transition-all duration-300">
                        <div class="flex items-center space-x-4">
                            @if($channel['thumbnail'])
                                <img src="{{ $channel['thumbnail'] }}" alt="{{ $channel['title'] }}" class="w-16 h-16 rounded-full">
                            @else
                                <div class="w-16 h-16 rounded-full bg-purple-800 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-purple-300" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1">
                                <h3 class="font-semibold text-purple-100">{{ $channel['title'] }}</h3>
                                @if($channel['handle'])
                                    <p class="text-sm text-purple-300">{{ $channel['handle'] }}</p>
                                @endif
                                @if(isset($channel['stats']))
                                    <div class="flex space-x-4 mt-2 text-sm text-purple-400">
                                        <span>{{ number_format($channel['stats']['subscriber_count'] ?? 0) }} subscribers</span>
                                        <span>{{ number_format($channel['stats']['video_count'] ?? 0) }} videos</span>
                                    </div>
                                @endif
                            </div>
                            <div class="text-right">
                                @if($channel['is_active'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-300">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900/50 text-red-300">
                                        Inactive
                                    </span>
                                @endif
                                <p class="text-xs text-purple-500 mt-1">
                                    Expires {{ \Carbon\Carbon::parse($channel['expires_at'])->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="glass rounded-xl p-8 text-center">
            <svg class="w-16 h-16 mx-auto text-purple-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            <h3 class="text-xl font-semibold text-purple-100 mb-2">No Channels Connected</h3>
            <p class="text-purple-300 mb-6">Connect your YouTube channel to start managing your content.</p>
            <a href="{{ route('youtube.auth') }}" class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-lg transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
                Connect YouTube Channel
            </a>
        </div>
    @endif

    <!-- Recent Videos -->
    @if($recentVideos->count() > 0)
        <div class="glass rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-purple-100">Recent Videos</h2>
                <a href="{{ route('youtube.admin.videos.index') }}" class="text-purple-400 hover:text-purple-300 text-sm">
                    View all →
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b border-purple-800/30">
                            <th class="pb-3 text-purple-300 font-medium">Video</th>
                            <th class="pb-3 text-purple-300 font-medium">Channel</th>
                            <th class="pb-3 text-purple-300 font-medium">Privacy</th>
                            <th class="pb-3 text-purple-300 font-medium">Views</th>
                            <th class="pb-3 text-purple-300 font-medium">Uploaded</th>
                            <th class="pb-3 text-purple-300 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentVideos as $video)
                            <tr class="border-b border-purple-800/20 hover:bg-purple-900/20 transition-colors">
                                <td class="py-3">
                                    <div class="flex items-center space-x-3">
                                        @if($video->thumbnail)
                                            <img src="{{ $video->thumbnail }}" alt="{{ $video->title }}" class="w-20 h-12 object-cover rounded">
                                        @else
                                            <div class="w-20 h-12 bg-purple-900/50 rounded flex items-center justify-center">
                                                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-purple-100 font-medium">{{ Str::limit($video->title, 40) }}</p>
                                            <p class="text-purple-400 text-sm">{{ $video->video_id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 text-purple-200">{{ $video->token->channel_title ?? 'Unknown' }}</td>
                                <td class="py-3">
                                    @if($video->privacy_status === 'public')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900/50 text-green-300">
                                            Public
                                        </span>
                                    @elseif($video->privacy_status === 'unlisted')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-900/50 text-yellow-300">
                                            Unlisted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-900/50 text-purple-300">
                                            Private
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 text-purple-200">{{ number_format($video->view_count) }}</td>
                                <td class="py-3 text-purple-200">{{ $video->created_at->diffForHumans() }}</td>
                                <td class="py-3">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ $video->watch_url }}" target="_blank" class="text-purple-400 hover:text-purple-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                        <a href="{{ route('youtube.admin.videos.edit', $video->id) }}" class="text-purple-400 hover:text-purple-300">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Upload Activity Chart -->
    @if(count($chartData) > 0)
        <div class="glass rounded-xl p-6">
            <h2 class="text-xl font-semibold text-purple-100 mb-4">Upload Activity (Last 30 Days)</h2>
            <div class="h-64" x-data="uploadChart" x-init="initChart">
                <canvas id="uploadChart"></canvas>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('uploadChart', () => ({
        initChart() {
            const ctx = document.getElementById('uploadChart').getContext('2d');
            const chartData = @json($chartData);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Object.keys(chartData),
                    datasets: [{
                        label: 'Videos Uploaded',
                        data: Object.values(chartData),
                        borderColor: 'rgb(153, 0, 255)',
                        backgroundColor: 'rgba(153, 0, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: 'rgb(214, 153, 255)',
                                stepSize: 1
                            },
                            grid: {
                                color: 'rgba(153, 0, 255, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: 'rgb(214, 153, 255)',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                color: 'rgba(153, 0, 255, 0.1)'
                            }
                        }
                    }
                }
            });
        }
    }));
});
</script>
@endpush
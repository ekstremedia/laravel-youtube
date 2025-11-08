<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'YouTube Admin') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        purple: {
                            950: '#1a0033',
                            900: '#2d0052',
                            850: '#3d0066',
                            800: '#4c0080',
                            750: '#5c0099',
                            700: '#6b00b3',
                            650: '#7a00cc',
                            600: '#8a00e6',
                            550: '#9900ff',
                            500: '#a31aff',
                            450: '#ad33ff',
                            400: '#b84dff',
                            350: '#c266ff',
                            300: '#cc80ff',
                            250: '#d699ff',
                            200: '#e0b3ff',
                            150: '#ebccff',
                            100: '#f5e6ff',
                            50: '#faf5ff',
                        },
                        dark: {
                            bg: '#0a0014',
                            card: '#140a1f',
                            hover: '#1f0f2e',
                            border: '#2d1a40',
                            text: {
                                primary: '#f5e6ff',
                                secondary: '#d699ff',
                                muted: '#9966cc',
                            }
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #1a0033;
        }

        ::-webkit-scrollbar-thumb {
            background: #6b00b3;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #8a00e6;
        }

        /* Glow effects */
        .glow-purple {
            box-shadow: 0 0 20px rgba(153, 0, 255, 0.3);
        }

        .glow-purple-hover:hover {
            box-shadow: 0 0 30px rgba(153, 0, 255, 0.5);
        }

        /* Background gradient animation */
        .animated-bg {
            background: linear-gradient(-45deg, #0a0014, #1a0033, #2d0052, #1f0f2e);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Glass effect */
        .glass {
            background: rgba(20, 10, 31, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(153, 0, 255, 0.2);
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased animated-bg min-h-screen">
    <div class="min-h-screen" x-data="{ sidebarOpen: false, userMenuOpen: false }">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-50 w-64 transform transition-transform duration-300 ease-in-out lg:translate-x-0 glass"
             :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
             @click.away="sidebarOpen = false">

            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-purple-800/30">
                <div class="flex items-center space-x-3">
                    <svg class="w-8 h-8 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span class="text-xl font-bold text-purple-100">YouTube Admin</span>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-purple-400 hover:text-purple-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="px-4 py-6 space-y-2">
                <a href="{{ route('youtube.admin.dashboard') }}"
                   class="flex items-center px-4 py-3 text-purple-200 rounded-lg transition-all duration-200 hover:bg-purple-900/30 hover:text-purple-100 {{ request()->routeIs('youtube.admin.dashboard') ? 'bg-purple-900/50 text-purple-100' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('youtube.admin.channels.index') }}"
                   class="flex items-center px-4 py-3 text-purple-200 rounded-lg transition-all duration-200 hover:bg-purple-900/30 hover:text-purple-100 {{ request()->routeIs('youtube.admin.channels*') ? 'bg-purple-900/50 text-purple-100' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    Channels
                </a>

                <a href="{{ route('youtube.admin.videos.index') }}"
                   class="flex items-center px-4 py-3 text-purple-200 rounded-lg transition-all duration-200 hover:bg-purple-900/30 hover:text-purple-100 {{ request()->routeIs('youtube.admin.videos*') ? 'bg-purple-900/50 text-purple-100' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4"></path>
                    </svg>
                    Videos
                </a>

                <a href="{{ route('youtube.admin.upload.index') }}"
                   class="flex items-center px-4 py-3 text-purple-200 rounded-lg transition-all duration-200 hover:bg-purple-900/30 hover:text-purple-100 {{ request()->routeIs('youtube.admin.upload*') ? 'bg-purple-900/50 text-purple-100' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Upload
                </a>

                <a href="{{ route('youtube.admin.playlists.index') }}"
                   class="flex items-center px-4 py-3 text-purple-200 rounded-lg transition-all duration-200 hover:bg-purple-900/30 hover:text-purple-100 {{ request()->routeIs('youtube.admin.playlists*') ? 'bg-purple-900/50 text-purple-100' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Playlists
                </a>

                <div class="pt-6 mt-6 border-t border-purple-800/30">
                    <a href="{{ route('youtube.admin.tokens.index') }}"
                       class="flex items-center px-4 py-3 text-purple-200 rounded-lg transition-all duration-200 hover:bg-purple-900/30 hover:text-purple-100 {{ request()->routeIs('youtube.admin.tokens*') ? 'bg-purple-900/50 text-purple-100' : '' }}">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                        API Tokens
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main content -->
        <div class="lg:ml-64">
            <!-- Top bar -->
            <header class="glass border-b border-purple-800/30">
                <div class="flex items-center justify-between h-16 px-6">
                    <!-- Mobile menu button -->
                    <button @click="sidebarOpen = true" class="lg:hidden text-purple-400 hover:text-purple-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <!-- Page title -->
                    <h1 class="text-xl font-semibold text-purple-100">@yield('header', 'Dashboard')</h1>

                    <!-- User menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-3 text-purple-200 hover:text-purple-100">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-purple-500 to-purple-700 flex items-center justify-center">
                                <span class="text-sm font-medium text-white">{{ substr(Auth::user()->name ?? 'U', 0, 1) }}</span>
                            </div>
                            <span class="hidden md:block">{{ Auth::user()->name ?? 'User' }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             @click.away="open = false"
                             x-cloak
                             class="absolute right-0 mt-2 w-48 rounded-lg glass overflow-hidden">
                            <a href="{{ route('youtube.auth') }}" class="block px-4 py-3 text-purple-200 hover:bg-purple-900/30 hover:text-purple-100">
                                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                </svg>
                                Connect Channel
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="block">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-purple-200 hover:bg-purple-900/30 hover:text-purple-100">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="p-6">
                <!-- Notifications -->
                @if (session('success'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         x-init="setTimeout(() => show = false, 5000)"
                         class="mb-6 p-4 rounded-lg bg-green-900/50 border border-green-500/30 text-green-200">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ session('success') }}
                            <button @click="show = false" class="ml-auto text-green-400 hover:text-green-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div x-data="{ show: true }"
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         x-init="setTimeout(() => show = false, 5000)"
                         class="mb-6 p-4 rounded-lg bg-red-900/50 border border-red-500/30 text-red-200">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ session('error') }}
                            <button @click="show = false" class="ml-auto text-red-400 hover:text-red-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
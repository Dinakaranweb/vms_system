<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard')</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        @keyframes scale {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-scale { animation: scale 0.2s ease-out; }
        .animate-slide-up { animation: slideUp 0.3s ease-out; }
    </style>
</head>
<body class="bg-gray-50">
    
    <div x-data="{ sidebarOpen: true }">
        <!-- Sidebar -->
        <aside class="fixed top-0 left-0 z-40 w-64 h-screen bg-white shadow-xl transition-transform sidebar-transition"
               :class="{ '-translate-x-full': !sidebarOpen }">
            <div class="p-6 border-b">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-building text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">VMS Portal</h1>
                        <p class="text-xs text-gray-500">Vendor Management</p>
                    </div>
                </div>
            </div>
            
            <div class="p-4 border-b">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
                        {{ substr(Auth::user()->name, 0, 2) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst(Auth::user()->getRoleNames()->first()) }}</p>
                    </div>
                </div>
            </div>
            
            <nav class="p-4 space-y-2 overflow-y-auto" style="height: calc(100% - 180px)">
                <!-- Dashboard Menu -->
                @if(Auth::user()->hasRole('super-admin'))
                    <a href="/super-admin/dashboard" class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('super-admin.dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
                
                @if(Auth::user()->hasRole('admin'))
                    <a href="/admin/dashboard" class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
                
                @if(Auth::user()->hasRole('vendor'))
                    <a href="/vendor/dashboard" class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('vendor.dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Dashboard</span>
                    </a>
                @endif

                <!-- Vendor Management Section -->
                @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                    <div class="pt-4 pb-2">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            Vendor Management
                        </div>
                        
                        <a href="{{ Auth::user()->hasRole('super-admin') ? route('super-admin.vendors') : route('admin.vendors') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('*.vendors*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-users w-5"></i>
                            <span>All Vendors</span>
                        </a>
                    </div>
                @endif

                <!-- Tender Management Section -->
                @if(Auth::user()->hasRole('admin'))
                    <div class="pt-2 pb-2">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            Tender Management
                        </div>
                        
                        <a href="{{ route('admin.tenders') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('admin.tenders*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-file-alt w-5"></i>
                            <span>All Tenders</span>
                        </a>
                        
                        <a href="{{ route('admin.tenders.create') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition">
                            <i class="fas fa-plus-circle w-5"></i>
                            <span>Create Tender</span>
                        </a>
                    </div>
                @endif

                <!-- Applications Section -->
                @if(Auth::user()->hasRole('admin'))
                    <div class="pt-2 pb-2">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            Applications
                        </div>
                        
                        <a href="{{ route('admin.applications') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('admin.applications*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-file-signature w-5"></i>
                            <span>All Applications</span>
                        </a>
                    </div>
                @endif

                <!-- Contract Management Section -->
                @if(Auth::user()->hasRole('admin'))
                    <div class="pt-2 pb-2">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            Contract Management
                        </div>
                        
                        <a href="{{ route('admin.contracts') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('admin.contracts*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-file-contract w-5"></i>
                            <span>All Contracts</span>
                        </a>
                    </div>
                @endif

                <!-- Vendor Portal Section -->
                @if(Auth::user()->hasRole('vendor'))
                    <div class="pt-4 pb-2">
                        <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                            Tenders
                        </div>
                        
                        <a href="{{ route('vendor.tenders') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('vendor.tenders*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-gavel w-5"></i>
                            <span>Open Tenders</span>
                        </a>
                        
                        <a href="{{ route('vendor.applications') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('vendor.applications*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-file-alt w-5"></i>
                            <span>My Applications</span>
                        </a>
                        
                        <a href="{{ route('vendor.contracts') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('vendor.contracts*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-file-contract w-5"></i>
                            <span>My Contracts</span>
                        </a>
                        
                        <a href="{{ route('vendor.profile') }}" 
                           class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('vendor.profile*') ? 'bg-blue-50 text-blue-600' : '' }}">
                            <i class="fas fa-building w-5"></i>
                            <span>Company Profile</span>
                        </a>
                    </div>
                @endif
@if(Auth::user()->hasRole('tender-owner'))
    <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
        Tender Owner Portal
    </div>
    
    <a href="{{ route('tender-owner.dashboard') }}" 
       class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('tender-owner.dashboard') ? 'bg-blue-50 text-blue-600' : '' }}">
        <i class="fas fa-chart-line w-5"></i>
        <span>Dashboard</span>
    </a>
    
    <a href="{{ route('tender-owner.tenders.index') }}" 
       class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('tender-owner.tenders*') ? 'bg-blue-50 text-blue-600' : '' }}">
        <i class="fas fa-file-alt w-5"></i>
        <span>My Tenders</span>
        @php $tenderCount = Auth::user()->ownedTenders()->count(); @endphp
        @if($tenderCount > 0)
            <span class="ml-auto bg-blue-500 text-white text-xs px-2 py-1 rounded-full">{{ $tenderCount }}</span>
        @endif
    </a>
    
    <a href="{{ route('tender-owner.applications.index') }}" 
       class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('tender-owner.applications*') ? 'bg-blue-50 text-blue-600' : '' }}">
        <i class="fas fa-file-signature w-5"></i>
        <span>Applications</span>
        @php 
            $applicationCount = App\Models\TenderApplication::whereIn('tender_id', Auth::user()->ownedTenders()->pluck('id'))->where('status', 'submitted')->count();
        @endphp
        @if($applicationCount > 0)
            <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ $applicationCount }}</span>
        @endif
    </a>
    
    <a href="{{ route('tender-owner.contracts.index') }}" 
       class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('tender-owner.contracts*') ? 'bg-blue-50 text-blue-600' : '' }}">
        <i class="fas fa-file-contract w-5"></i>
        <span>Contracts</span>
    </a>
    
    <a href="{{ route('tender-owner.tenders.create') }}" 
       class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition">
        <i class="fas fa-plus-circle w-5"></i>
        <span>Create Tender</span>
    </a>
@endif
                @if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('super-admin'))
                <div class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                    System Administration
                </div>
                <a href="{{ route('admin.users') }}" class="flex items-center space-x-3 px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition {{ request()->routeIs('admin.users*') ? 'bg-blue-50 text-blue-600' : '' }}">
                    <i class="fas fa-users-cog w-5"></i>
                    <span>User Management</span>
                </a>
            @endif
            </nav>
            
            <div class="absolute bottom-0 w-full p-4 border-t bg-white">
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="flex items-center space-x-3 w-full px-4 py-3 text-red-600 rounded-lg hover:bg-red-50 transition">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="ml-64 transition-all" :class="{ 'ml-0': !sidebarOpen }">
            <!-- Top Navbar -->
            <nav class="bg-white shadow-sm sticky top-0 z-30">
                <div class="px-6 py-4 flex justify-between items-center">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <i class="fas fa-bell text-gray-500 cursor-pointer"></i>
                            <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">{{ Auth::user()->name }}</span>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <main class="p-6">
                @yield('content')
                
            </main>
            

        </div>
    </div>
   
    
</body>
 @include('layouts.footer')
</html>

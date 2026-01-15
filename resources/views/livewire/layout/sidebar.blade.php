<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    public function logout(Logout $logout)
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="h-full flex flex-col bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
    <!-- Branding / Logo -->
    <div class="shrink-0 flex items-center h-16 px-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 group">
            <x-application-logo class="block h-8 w-auto fill-current text-indigo-600 dark:text-indigo-400 transition-transform group-hover:scale-105" />
            <span class="font-bold text-lg text-gray-900 dark:text-white tracking-tight">CRM</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
        <div class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ __('Main') }}
        </div>

        <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
            <x-slot:icon>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
            </x-slot:icon>
            {{ __('Dashboard') }}
        </x-sidebar-link>

        @if(auth()->user()->role === 'admin')
            <x-sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" wire:navigate>
                 <x-slot:icon>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                 </x-slot:icon>
                {{ __('Admin Panel') }}
            </x-sidebar-link>
        @endif

        <div class="px-3 mt-6 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ __('Management') }}
        </div>

        <x-sidebar-link :href="route('organizations.index')" :active="request()->routeIs('organizations.*')" wire:navigate>
            <x-slot:icon>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5M12 6.75h1.5M15 6.75h1.5M9 10.5h1.5M12 10.5h1.5M15 10.5h1.5M9 14.25h1.5M12 14.25h1.5M15 14.25h1.5M9 18h1.5M12 18h1.5M15 18h1.5" />
            </x-slot:icon>
            {{ __('Organizations') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('products.index')" :active="request()->routeIs('products.*')" wire:navigate>
            <x-slot:icon>
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </x-slot:icon>
            {{ __('Products') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('enquiries.index')" :active="request()->routeIs('enquiries.*')" wire:navigate>
             <x-slot:icon>
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
             </x-slot:icon>
            {{ __('Enquiries') }}
        </x-sidebar-link>

        <div class="px-3 mt-6 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
             {{ __('Sales') }}
        </div>

        <x-sidebar-link :href="route('quotations.index')" :active="request()->routeIs('quotations.*')" wire:navigate>
            <x-slot:icon>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </x-slot:icon>
            {{ __('Quotations') }}
        </x-sidebar-link>

        <x-sidebar-link :href="route('proformas.index')" :active="request()->routeIs('proformas.*')" wire:navigate>
            <x-slot:icon>
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
            </x-slot:icon>
            {{ __('Proformas') }}
        </x-sidebar-link>

         <div class="px-3 mt-6 mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
             {{ __('System') }}
        </div>

        <x-sidebar-link :href="route('dropdowns.index')" :active="request()->routeIs('dropdowns.*')" wire:navigate>
            <x-slot:icon>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </x-slot:icon>
            {{ __('Dropdowns') }}
        </x-sidebar-link>
    </nav>

    <!-- User Profile Footer -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
        <div class="flex items-center gap-3">
            <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-3 flex-1 min-w-0 group">
                <div class="flex-shrink-0">
                    <div class="h-9 w-9 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold shadow-sm group-hover:bg-indigo-600 transition-colors">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name">
                        {{ auth()->user()->name }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ auth()->user()->email }}
                    </p>
                </div>
            </a>
             <button wire:click="logout" class="flex-shrink-0 p-1 text-gray-400 hover:text-red-500 transition-colors" title="Log Out">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
            </button>
        </div>
    </div>
</div>
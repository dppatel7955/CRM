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

<div class="h-full flex flex-col bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700">
    <!-- Logo -->
    <div class="shrink-0 flex items-center justify-center h-16 border-b border-gray-100 dark:border-gray-700">
        <a href="{{ route('dashboard') }}" wire:navigate>
            <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
        </a>
    </div>

    <!-- Nav Links -->
    <div class="px-4 py-4 space-y-2 overflow-y-auto flex-1">
        <x-nav-link class="w-full flex" :href="route('dashboard')" :active="request()->routeIs('dashboard')"
            wire:navigate>
            {{ __('Dashboard') }}
        </x-nav-link>

        @if(auth()->user()->role === 'admin')
            <x-nav-link class="w-full flex" :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')"
                wire:navigate>
                {{ __('Admin') }}
            </x-nav-link>
        @endif

        <x-nav-link class="w-full flex" :href="route('organizations.index')"
            :active="request()->routeIs('organizations.*')" wire:navigate>
            {{ __('Organizations') }}
        </x-nav-link>

        <x-nav-link class="w-full flex" :href="route('products.index')" :active="request()->routeIs('products.*')"
            wire:navigate>
            {{ __('Products') }}
        </x-nav-link>

        <x-nav-link class="w-full flex" :href="route('enquiries.index')" :active="request()->routeIs('enquiries.*')"
            wire:navigate>
            {{ __('Enquiries') }}
        </x-nav-link>

        <x-nav-link class="w-full flex" :href="route('quotations.index')" :active="request()->routeIs('quotations.*')"
            wire:navigate>
            {{ __('Quotations') }}
        </x-nav-link>

        <x-nav-link class="w-full flex" :href="route('proformas.index')" :active="request()->routeIs('proformas.*')"
            wire:navigate>
            {{ __('Proformas') }}
        </x-nav-link>

        <x-nav-link class="w-full flex" :href="route('dropdowns.index')" :active="request()->routeIs('dropdowns.*')"
            wire:navigate>
            {{ __('Dropdowns') }}
        </x-nav-link>
    </div>

    <!-- Responsive Settings Options -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-600">
        <div class="px-4">
            <div class="font-medium text-base text-gray-800 dark:text-gray-200"
                x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                x-on:profile-updated.window="name = $event.detail.name"></div>
            <div class="font-medium text-sm text-gray-500 truncate">{{ auth()->user()->email }}</div>
        </div>

        <div class="mt-3 space-y-1">
            <x-responsive-nav-link :href="route('profile')" wire:navigate>
                {{ __('Profile') }}
            </x-responsive-nav-link>

            <!-- Authentication -->
            <button wire:click="logout" class="w-full text-start">
                <x-responsive-nav-link>
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </button>
        </div>
    </div>
</div>
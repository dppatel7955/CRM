<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\Enquiry;
use App\Models\Quotation;
use App\Models\ProformaInvoice;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new 
#[Layout('layouts.app')]
class extends Component {
    public function with(): array
    {
        return [
            'stats' => [
                'users' => User::count(),
                'organizations' => Organization::count(),
                'enquiries' => Enquiry::count(),
                'enquiries_new' => Enquiry::where('order_status', 'New')->count(),
                'quotations' => Quotation::count(),
                'proformas' => ProformaInvoice::count(),
            ],
            'recent_users' => User::latest()->take(5)->get(),
            'recent_enquiries' => Enquiry::with('organization')->latest()->take(5)->get(),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-gray-200">Admin Dashboard</h1>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Total Users</div>
                <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['users'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Organizations</div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['organizations'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Total Enquiries</div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">{{ $stats['enquiries'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">New Enquiries</div>
                <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ $stats['enquiries_new'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Quotations</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['quotations'] }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="text-gray-500 dark:text-gray-400 text-sm">Proformas</div>
                <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">{{ $stats['proformas'] }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Enquiries -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Recent Enquiries</h3>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recent_enquiries as $enquiry)
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $enquiry->subject }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $enquiry->organization->organization_name }}
                                    </p>
                                </div>
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $enquiry->order_status }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-4 text-right">
                        <a href="{{ route('enquiries.index') }}"
                            class="text-blue-600 hover:text-blue-500 text-sm font-medium">View All &rarr;</a>
                    </div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Recent Users</h3>
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($recent_users as $user)
                            <li class="py-3 flex justify-between items-center">
                                <div class="flex items-center">
                                    <div
                                        class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold mr-3">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->role }}</p>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
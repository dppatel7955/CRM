<?php

use App\Models\Organization;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

new
    #[Layout('layouts.app')]
    class extends Component {
    use WithPagination;

    public $search = '';

    public function delete($id)
    {
        $org = Organization::find($id);
        $org?->delete();
    }

    public function with(): array
    {
        return [
            'organizations' => Organization::query()
                ->where('organization_name', 'like', '%' . $this->search . '%')
                ->orWhere('phone', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%')
                ->latest()
                ->paginate(50),
        ];
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-4 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Organizations</h2>
                    <a href="{{ route('organizations.create') }}"
                        class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-500">
                        Add New
                    </a>
                </div>

                <div class="mb-4">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search..."
                        class="w-full px-3 py-2 text-sm border rounded dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:outline-none focus:border-blue-500">
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800 border dark:border-gray-700">
                        <thead>
                            <tr
                                class="w-full bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                <th class="px-3 py-2">Name</th>
                                <th class="px-3 py-2 hidden sm:table-cell">Contact</th>
                                <th class="px-3 py-2 hidden md:table-cell">Type</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($organizations as $org)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $org->organization_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $org->gst_number }}</div>
                                    </td>
                                    <td class="px-3 py-2 hidden sm:table-cell">
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $org->contact_person_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $org->phone }}</div>
                                    </td>
                                    <td class="px-3 py-2 hidden md:table-cell">
                                        @if($org->is_dealer)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Dealer</span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">Customer</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        @if($org->active)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('organizations.edit', $org) }}"
                                                class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400">Edit</a>
                                            <button wire:click="delete({{ $org->id }})" wire:confirm="Are you sure?"
                                                class="text-red-600 hover:text-red-900 dark:hover:text-red-400">Del</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">No organizations
                                        found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $organizations->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
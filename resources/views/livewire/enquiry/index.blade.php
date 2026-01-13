<?php

use App\Models\Enquiry;
use App\Models\User;
use App\Models\Dropdown;
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
        $enquiry = Enquiry::find($id);
        $enquiry?->delete();
    }

    public function updateStatus($id, $status)
    {
        $enquiry = Enquiry::find($id);
        if ($enquiry) {
            $enquiry->update(['order_status' => $status]);
        }
    }

    public function assignUser($id, $userId)
    {
        $enquiry = Enquiry::find($id);
        if ($enquiry) {
            $enquiry->update(['assigned_to' => $userId ?: null]);
        }
    }

    public function with(): array
    {
        return [
            'enquiries' => Enquiry::query()
                ->with(['organization', 'assignee'])
                ->where('subject', 'like', '%' . $this->search . '%')
                ->orWhereHas('organization', function ($q) {
                    $q->where('organization_name', 'like', '%' . $this->search . '%');
                })
                ->latest()
                ->paginate(50),
            'users' => User::all(), // For assignment dropdown
        ];
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
            <div class="p-4 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold">Enquiries</h2>
                    <a href="{{ route('enquiries.create') }}"
                        class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-500">
                        New Enquiry
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
                                <th class="px-3 py-2">Subject / Org</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2 hidden md:table-cell">Assigned</th>
                                <th class="px-3 py-2 hidden lg:table-cell">Follow Up</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($enquiries as $enquiry)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-3 py-2">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $enquiry->subject }}
                                        </div>
                                        <div class="text-xs text-gray-500 truncate max-w-[150px]">
                                            <a href="{{ route('organizations.edit', $enquiry->organization_id) }}"
                                                class="hover:underline">
                                                {{ $enquiry->organization->organization_name }}
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select wire:change="updateStatus({{ $enquiry->id }}, $event.target.value)"
                                            class="text-xs border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white py-1 pl-2 pr-6">
                                            @foreach(['New', 'In Progress', 'Quotation Sent', 'Closed Won', 'Closed Lost'] as $status)
                                                <option value="{{ $status }}" {{ $enquiry->order_status == $status ? 'selected' : '' }}>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 hidden md:table-cell">
                                        <select wire:change="assignUser({{ $enquiry->id }}, $event.target.value)"
                                            class="text-xs border-gray-300 rounded shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white py-1 pl-2 pr-6">
                                            <option value="">Unassigned</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ $enquiry->assigned_to == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-gray-500 hidden lg:table-cell">
                                        {{ $enquiry->follow_up_date ? $enquiry->follow_up_date->format('Y-m-d') : '--' }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('enquiries.edit', $enquiry) }}"
                                                class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400">Edit</a>
                                            <button wire:click="delete({{ $enquiry->id }})" wire:confirm="Are you sure?"
                                                class="text-red-600 hover:text-red-900 dark:hover:text-red-400">Del</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">No enquiries found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $enquiries->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
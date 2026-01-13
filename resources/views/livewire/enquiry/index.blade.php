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
            'enquiry_statuses' => Dropdown::where('type', 'Order Status')->where('active', true)->orderBy('value')->get(),
        ];
    }
}; ?>

<div class="py-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Enquiries</h2>
            <div class="flex space-x-2">
                <a href="{{ route('enquiries.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-500 shadow-sm flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                            clip-rule="evenodd" />
                    </svg>
                    Add Enquiry
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <!-- Toolbar -->
            <div
                class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800 rounded-t-lg">
                <div class="relative w-full max-w-xs">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Search enquiries...">
                </div>
                <!-- Add Filter Button Placeholder if needed -->
            </div>

            <div class="overflow-x-auto">
                {{-- Table Layout Matching Reference --}}
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Created
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Title
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Organization
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Stage
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($enquiries as $enquiry)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150 ease-in-out">
                                <!-- Created -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $enquiry->created_at->diffForHumans() }}
                                </td>

                                <!-- Title -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        <a href="{{ route('enquiries.edit', $enquiry) }}"
                                            class="hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $enquiry->subject }}
                                        </a>
                                    </div>
                                    <!-- Optional Labels could go here -->
                                </td>

                                <!-- Customer (Contact Person) -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $enquiry->organization->contact_person_name ?? '--' }}
                                </td>

                                <!-- Organization -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    <a href="{{ route('organizations.edit', $enquiry->organization_id) }}"
                                        class="hover:text-blue-600 hover:underline">
                                        {{ $enquiry->organization->organization_name }}
                                    </a>
                                </td>

                                <!-- Stage (Status) -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <select wire:change="updateStatus({{ $enquiry->id }}, $event.target.value)"
                                        class="text-sm border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-white py-1 pl-2 pr-8">
                                        @foreach($enquiry_statuses as $status)
                                            <option value="{{ $status->value }}" {{ $enquiry->order_status == $status->value ? 'selected' : '' }}>{{ $status->value }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-3">
                                        <a href="{{ route('enquiries.edit', $enquiry) }}"
                                            class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path
                                                    d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                        </a>
                                        <button wire:click="delete({{ $enquiry->id }})" wire:confirm="Delete this enquiry?"
                                            class="text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="mt-2 text-base font-medium">No enquiries found</span>
                                        <p class="mt-1 text-sm text-gray-400">Get started by creating a new enquiry.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($enquiries->hasPages())
                <div
                    class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-b-lg">
                    {{ $enquiries->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
<?php

use App\Models\ProformaInvoice;
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
        $pi = ProformaInvoice::find($id);
        $pi?->delete();
    }

    public function with(): array
    {
        return [
            'invoices' => ProformaInvoice::query()
                ->with(['quotation.enquiry.organization'])
                ->whereHas('quotation.enquiry.organization', function ($q) {
                    $q->where('organization_name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('id', 'like', '%' . $this->search . '%')
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
                    <h2 class="text-lg font-semibold">Proforma Invoices</h2>
                    <a href="{{ route('proformas.create') }}"
                        class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-500">
                        Create New
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
                                <th class="px-3 py-2">PI ID</th>
                                <th class="px-3 py-2 hidden sm:table-cell">Quotation</th>
                                <th class="px-3 py-2">Organization</th>
                                <th class="px-3 py-2 hidden md:table-cell">PO Details</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($invoices as $pi)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-3 py-2 text-sm font-medium">PI-{{ $pi->id }}</td>
                                    <td class="px-3 py-2 text-sm hidden sm:table-cell">
                                        <a href="{{ route('quotations.edit', $pi->quotation_id) }}"
                                            class="text-blue-600 hover:underline">
                                            Q-{{ $pi->quotation_id }}
                                        </a>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $pi->quotation->enquiry->organization->organization_name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 hidden md:table-cell">
                                        <div class="text-xs">No: {{ $pi->po_number ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">Date:
                                            {{ $pi->po_date ? $pi->po_date->format('Y-m-d') : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('proformas.download', $pi) }}"
                                                class="text-green-600 hover:text-green-900 dark:hover:text-green-400"
                                                target="_blank">PDF</a>
                                            <a href="{{ route('proformas.edit', $pi) }}"
                                                class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400">Edit</a>
                                            <button wire:click="delete({{ $pi->id }})" wire:confirm="Are you sure?"
                                                class="text-red-600 hover:text-red-900 dark:hover:text-red-400">Del</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">No invoices found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
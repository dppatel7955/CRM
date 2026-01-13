<?php

use Livewire\Volt\Component;
use App\Models\Enquiry;
use App\Models\Dropdown;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    
    // Filters
    public $filterActive = '1'; // Default to Active
    public $filterStatus = '';
    public $filterTime = '';

    // PDF Modal State
    public $viewingPdfUrl = null;
    public $viewingPdfDownloadUrl = null;
    public $viewingPdfTitle = '';

    public function with(): array
    {
        $query = Enquiry::with(['organization', 'assignee', 'quotations.proformaInvoices', 'proformaInvoices'])
            ->orderBy('created_at', 'desc');

        // Filter: Active
        if ($this->filterActive !== '') {
            $query->where('active', (bool)$this->filterActive);
        }

        // Filter: Status
        if ($this->filterStatus) {
            $query->where('order_status', $this->filterStatus);
        }

        // Filter: Time (Follow Up Date)
        if ($this->filterTime) {
            $today = now()->startOfDay();
            
            if ($this->filterTime === 'overdue') {
                $query->whereDate('follow_up_date', '<', $today);
            } elseif ($this->filterTime === 'today') {
                $query->whereDate('follow_up_date', '=', $today);
            } elseif ($this->filterTime === 'this_week') {
                $query->whereBetween('follow_up_date', [now()->startOfWeek(), now()->endOfWeek()]);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('subject', 'like', '%' . $this->search . '%')
                  ->orWhere('order_status', 'like', '%' . $this->search . '%')
                  ->orWhereHas('organization', function ($q) {
                      $q->where('organization_name', 'like', '%' . $this->search . '%')
                        ->orWhere('contact_person_name', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'enquiries' => $query->paginate(10),
            'enquiry_statuses' => Dropdown::where('type', 'Order Status')->where('active', true)->orderBy('value')->get(),
            'enquiry_sources' => Dropdown::where('type', 'Enquiry Source')->where('active', true)->orderBy('value')->get(),
        ];
    }

    public function updateEnquiry($id, $field, $value)
    {
        $enquiry = Enquiry::find($id);
        if ($enquiry) {
            if ($value === '' && in_array($field, ['follow_up_date', 'follow_up_notes', 'products', 'enquiry_source'])) {
                $value = null;
            }
            
            $enquiry->$field = $value;
            $enquiry->save();
        }
    }

    public function openPdf($viewUrl, $downloadUrl, $title)
    {
        $this->viewingPdfUrl = $viewUrl;
        $this->viewingPdfDownloadUrl = $downloadUrl;
        $this->viewingPdfTitle = $title;
        $this->dispatch('open-modal', 'pdf-viewer');
    }
}; ?>

<div class="py-6 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Dashboard</h2>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <!-- Toolbar -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-4 bg-gray-50 dark:bg-gray-800 rounded-t-lg">
                <div class="relative w-full sm:max-w-xs">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" 
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" 
                        placeholder="Search enquiries...">
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <!-- Active Filter -->
                    <select wire:model.live="filterActive" class="block w-full sm:w-auto py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Active</option>
                        <option value="1">Active: Yes</option>
                        <option value="0">Active: No</option>
                    </select>

                    <!-- Time Filter -->
                    <select wire:model.live="filterTime" class="block w-full sm:w-auto py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Time</option>
                        <option value="overdue">Overdue</option>
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                    </select>

                    <!-- Status Filter -->
                    <select wire:model.live="filterStatus" class="block w-full sm:w-auto py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Statuses</option>
                        @foreach($enquiry_statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Created
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Organization
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Contact Info
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Products
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Status & Source
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Follow Up
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Assigned
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Active
                            </th>
                             <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($enquiries as $enquiry)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150 ease-in-out" wire:key="row-{{ $enquiry->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $enquiry->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <a href="{{ route('organizations.edit', $enquiry->organization) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $enquiry->organization->organization_name }}
                                        </a>
                                        <!-- Small Edit Button -->
                                        <a href="{{ route('organizations.edit', [
                                            'organization' => $enquiry->organization->id,
                                            'return_to' => route('dashboard', [
                                                'page' => $this->getPage(),
                                                'search' => $search,
                                                'filterActive' => $filterActive,
                                                'filterStatus' => $filterStatus,
                                                'filterTime' => $filterTime,
                                            ])
                                        ]) }}" 
                                           class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                           title="Edit Organization Details">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $enquiry->organization->contact_person_name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $enquiry->organization->phone }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[150px]">{{ $enquiry->organization->email }}</div>
                                </td>
                                <td class="px-6 py-4 min-w-[200px]">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $enquiry->products }}">
                                        {{ $enquiry->products ?: '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 min-w-[180px]">
                                    <div class="flex flex-col gap-2">
                                        <!-- Inline Edit Status -->
                                        <select 
                                            wire:change="updateEnquiry({{ $enquiry->id }}, 'order_status', $event.target.value)"
                                            class="block w-full py-1 px-2 text-xs border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        >
                                            <option value="">Status...</option>
                                            @foreach($enquiry_statuses as $status)
                                                <option value="{{ $status->value }}" {{ $enquiry->order_status == $status->value ? 'selected' : '' }}>
                                                    {{ $status->value }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <!-- Inline Edit Source -->
                                        <select 
                                            wire:change="updateEnquiry({{ $enquiry->id }}, 'enquiry_source', $event.target.value)"
                                            class="block w-full py-1 px-2 text-xs border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        >
                                            <option value="">Source...</option>
                                            @foreach($enquiry_sources as $source)
                                                <option value="{{ $source->value }}" {{ $enquiry->enquiry_source == $source->value ? 'selected' : '' }}>
                                                    {{ $source->value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap min-w-[180px]">
                                    <div class="flex flex-col gap-2">
                                        <!-- Inline Edit Follow Up Date -->
                                        <input 
                                            type="date" 
                                            value="{{ $enquiry->follow_up_date ? $enquiry->follow_up_date->format('Y-m-d') : '' }}"
                                            wire:change="updateEnquiry({{ $enquiry->id }}, 'follow_up_date', $event.target.value)"
                                            class="block w-full py-1 px-2 text-xs border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        >
                                        
                                        <!-- Inline Edit Follow Up Note -->
                                        <textarea 
                                            placeholder="Note..."
                                            wire:blur="updateEnquiry({{ $enquiry->id }}, 'follow_up_notes', $event.target.value)"
                                            class="block w-full py-1 px-2 text-xs border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            rows="2"
                                        >{{ $enquiry->follow_up_notes }}</textarea>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($enquiry->assignee)
                                        <div class="flex items-center">
                                            <div class="h-6 w-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300">
                                                {{ substr($enquiry->assignee->name, 0, 1) }}
                                            </div>
                                            <span class="ml-2 text-sm text-gray-900 dark:text-white">{{ $enquiry->assignee->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <select 
                                        wire:change="updateEnquiry({{ $enquiry->id }}, 'active', $event.target.value)"
                                        class="block w-24 py-1 px-2 text-xs border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    >
                                        <option value="1" {{ $enquiry->active ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ !$enquiry->active ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex gap-2 justify-end">
                                            @if($enquiry->quotations->isEmpty())
                                                <!-- Create Quotation (New) -->
                                                <a href="{{ route('quotations.create', ['enquiry_id' => $enquiry->id]) }}" 
                                                class="inline-flex items-center px-2 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-medium rounded hover:bg-blue-100 dark:hover:bg-blue-900/50 border border-blue-200 dark:border-blue-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                    Create Qt
                                                </a>
                                            @else
                                                <!-- Revise Quotation (Create New from Old) -->
                                                @php $latestQuote = $enquiry->quotations->sortByDesc('id')->first(); @endphp
                                                <a href="{{ route('quotations.create', ['revise_from' => $latestQuote->id]) }}" 
                                                class="inline-flex items-center px-2 py-1 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs font-medium rounded hover:bg-purple-100 dark:hover:bg-purple-900/50 border border-purple-200 dark:border-purple-800">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                                    Revise Qt
                                                </a>
                                            @endif
                                            
                                            <!-- Create Proforma (Directly from Enquiry) -->
                                            <a href="{{ route('proformas.create', ['enquiry_id' => $enquiry->id]) }}" 
                                            class="inline-flex items-center px-2 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-medium rounded hover:bg-green-100 dark:hover:bg-green-900/50 border border-green-200 dark:border-green-800">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                Create Pi
                                            </a>
                                        </div>

                                        <div class="flex gap-2 justify-end">
                                            @if($enquiry->quotations->isNotEmpty())
                                                @php $latestQuoteForView = $enquiry->quotations->sortByDesc('id')->first(); @endphp
                                                <!-- View Quotation PDF (Modal) -->
                                                <button 
                                                    wire:click="openPdf('{{ route('quotations.view_pdf', $latestQuoteForView) }}', '{{ route('quotations.download', $latestQuoteForView) }}', 'View Quotation')"
                                                    class="inline-flex items-center px-2 py-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium rounded hover:bg-indigo-100 dark:hover:bg-indigo-900/50 border border-indigo-200 dark:border-indigo-800"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                    View Qt
                                                </button>
                                            @endif

                                            @if($enquiry->proformaInvoices->isNotEmpty() || $enquiry->quotations->pluck('proformaInvoices')->flatten()->isNotEmpty())
                                                 @php 
                                                    // Get latest PI
                                                    $latestPi = $enquiry->proformaInvoices->isNotEmpty() 
                                                        ? $enquiry->proformaInvoices->sortByDesc('id')->first() 
                                                        : $enquiry->quotations->pluck('proformaInvoices')->flatten()->sortByDesc('id')->first();
                                                 @endphp
                                                 @if($latestPi)
                                                     <!-- View Proforma PDF (Modal) -->
                                                    <button 
                                                        wire:click="openPdf('{{ route('proformas.view_pdf', $latestPi) }}', '{{ route('proformas.download', $latestPi) }}', 'View Proforma Invoice')"
                                                        class="inline-flex items-center px-2 py-1 bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 text-xs font-medium rounded hover:bg-teal-100 dark:hover:bg-teal-900/50 border border-teal-200 dark:border-teal-800"
                                                    >
                                                         <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                                                        View Pi
                                                    </button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="mt-2 text-base font-medium">No enquiries found</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($enquiries->hasPages())
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-b-lg">
                    {{ $enquiries->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- PDF Viewer Modal -->
    <x-modal name="pdf-viewer" wire:model="showPdfModal" maxWidth="7xl">
        <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 rounded-lg h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $viewingPdfTitle }}
                </h3>
                <div class="flex gap-2 items-center">
                     <!-- Toolbar Buttons -->
                    <a href="mailto:?subject={{ $viewingPdfTitle }}&body=Please find attached the {{ $viewingPdfTitle }}." 
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-500 hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm transition ease-in-out duration-150">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        Send via Email
                    </a>
                    
                    <a href="https://wa.me/?text=Here is the {{ $viewingPdfTitle }}" target="_blank"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                         Send via WhatsApp
                    </a>

                    @if($viewingPdfDownloadUrl)
                        <a href="{{ $viewingPdfDownloadUrl }}" 
                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm transition ease-in-out duration-150">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download PDF
                        </a>
                    @endif
                    
                    <button x-on:click="$dispatch('close')" class="text-gray-400 hover:text-gray-500 focus:outline-none ml-2">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- PDF Iframe -->
            @if($viewingPdfUrl)
                <div class="flex-grow rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900">
                    <iframe src="{{ $viewingPdfUrl }}" class="w-full h-full" frameborder="0"></iframe>
                </div>
            @endif
        </div>
    </x-modal>
</div>
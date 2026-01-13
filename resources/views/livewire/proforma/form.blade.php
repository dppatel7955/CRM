<?php

use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\Enquiry;
use App\Models\ProformaInvoiceProduct;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

new
    #[Layout('layouts.app')]
    class extends Component {
    public ?ProformaInvoice $proforma = null;
    public $quotation_id = '';
    public $enquiry_id = '';
    public $returnUrl = '';

    // Fields
    public $invoice_percentage = "100%";
    public $po_date = '';
    public $po_number = '';
    public $shipping_address = '';

    // Charges [['title' => '', 'amount' => 0]]
    public $charges_list = [];

    // Products
    public $items = [];

    // Internal tracking
    public $reviseFromId = null;
    public $isReadOnly = false;

    public function mount(?ProformaInvoice $proforma = null): void
    {
        $quotationId = request()->query('quotation_id');
        $enquiryId = request()->query('enquiry_id');
        $this->reviseFromId = request()->query('revise_from');
        $this->returnUrl = request()->query('return_to', route('dashboard'));

        if ($proforma && $proforma->exists) {
            // VIEW MODE (Read Only)
            $this->proforma = $proforma;
            $this->isReadOnly = true; 
            
            $this->quotation_id = $proforma->quotation_id;
            $this->enquiry_id = $proforma->enquiry_id;
            $this->invoice_percentage = $proforma->invoice_percentage;
            $this->po_date = $proforma->po_date ? $proforma->po_date->format('Y-m-d') : '';
            $this->po_number = $proforma->po_number ?? '';
            $this->shipping_address = $proforma->shipping_address ?? '';

            $this->charges_list = $proforma->charges ?? [['title' => '', 'amount' => 0]];

            foreach ($proforma->products as $item) {
                $snapshot = $item->product_snapshot;
                $this->items[] = [
                    'product_name' => $snapshot['product_name'] ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'price' => $item->custom_price,
                    'snapshot' => $snapshot
                ];
            }
        } elseif ($this->reviseFromId) {
            // REVISE MODE (From existing Proforma)
            $sourcePi = ProformaInvoice::with(['products'])->find($this->reviseFromId);
            if ($sourcePi) {
                // Pre-fill from existing PI
                $this->quotation_id = $sourcePi->quotation_id;
                $this->enquiry_id = $sourcePi->enquiry_id;
                $this->invoice_percentage = $sourcePi->invoice_percentage;
                $this->po_date = $sourcePi->po_date ? $sourcePi->po_date->format('Y-m-d') : '';
                $this->po_number = $sourcePi->po_number;
                $this->shipping_address = $sourcePi->shipping_address;
                $this->charges_list = $sourcePi->charges ?? [['title' => '', 'amount' => 0]];

                 foreach ($sourcePi->products as $item) {
                    $snapshot = $item->product_snapshot;
                    $this->items[] = [
                        'product_name' => $snapshot['product_name'] ?? 'Unknown',
                        'quantity' => $item->quantity,
                        'price' => $item->custom_price,
                        'snapshot' => $snapshot
                    ];
                }
            } else {
                 $this->charges_list = [['title' => '', 'amount' => 0]];
            }
            
        } elseif ($quotationId) {
            // Creating from Quotation (Pre-fill logic)
            $this->quotation_id = $quotationId;
            $quotation = Quotation::with(['products', 'enquiry'])->find($quotationId);
            if ($quotation) {
                // Also link to enquiry_id for consistency
                $this->enquiry_id = $quotation->enquiry_id;
                $this->charges_list = [['title' => '', 'amount' => 0]];

                // Populate items from quotation
                foreach ($quotation->products as $qItem) {
                    $snapshot = $qItem->product_snapshot;
                    $this->items[] = [
                        'product_name' => $snapshot['product_name'] ?? 'Unknown',
                        'quantity' => $qItem->quantity,
                        'price' => $qItem->custom_price,
                        'snapshot' => $snapshot
                    ];
                }
            }
        } elseif ($enquiryId) {
            // Creating from Enquiry
            $this->enquiry_id = $enquiryId;

            // Auto-detect latest quotation to pre-fill
            $latestQuote = Quotation::where('enquiry_id', $enquiryId)->with(['products'])->latest()->first();

            if ($latestQuote) {
                $this->quotation_id = $latestQuote->id;
                $this->charges_list = [['title' => '', 'amount' => 0]];

                foreach ($latestQuote->products as $qItem) {
                    $snapshot = $qItem->product_snapshot;
                    $this->items[] = [
                        'product_name' => $snapshot['product_name'] ?? 'Unknown',
                        'quantity' => $qItem->quantity,
                        'price' => $qItem->custom_price,
                        'snapshot' => $snapshot
                    ];
                }
            } else {
                // Truly new/blank
                $this->items = []; 
                $this->charges_list = [['title' => '', 'amount' => 0]];
            }
        } else {
            $this->charges_list = [['title' => '', 'amount' => 0]];
        }
    }

    public function addCharge()
    {
        if($this->isReadOnly) return;
        $this->charges_list[] = ['title' => '', 'amount' => 0];
    }

    public function removeCharge($index)
    {
        if($this->isReadOnly) return;
        unset($this->charges_list[$index]);
        $this->charges_list = array_values($this->charges_list);
    }
    
    // Add Item Manually (since we might not have products)
    public function addItem()
    {
         if($this->isReadOnly) return;
         $this->items[] = [
            'product_name' => '',
            'quantity' => 1,
            'price' => 0,
            'snapshot' => ['product_name' => 'Manual Item'] 
        ];
    }
    
    public function removeItem($index)
    {
        if($this->isReadOnly) return;
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    // Logic to select quotation if not set?
    public function updatedQuotationId($value)
    {
        if ($this->isReadOnly) return;
        if ($value && !$this->proforma && !$this->reviseFromId) {
            $quotation = Quotation::with(['products', 'enquiry'])->find($value);
            if ($quotation) {
                $this->enquiry_id = $quotation->enquiry_id;
                $this->items = [];
                foreach ($quotation->products as $qItem) {
                    $snapshot = $qItem->product_snapshot;
                    $this->items[] = [
                        'product_name' => $snapshot['product_name'] ?? 'Unknown',
                        'quantity' => $qItem->quantity,
                        'price' => $qItem->custom_price,
                        'snapshot' => $snapshot
                    ];
                }
            }
        }
    }

    public function save(): void
    {
        if($this->isReadOnly) return;
        
        $validated = $this->validate([
            'quotation_id' => 'nullable|exists:quotations,id',
            'enquiry_id' => 'required_without:quotation_id|exists:enquiries,id',
            'invoice_percentage' => 'required|string',
            'po_date' => 'nullable|date',
            'po_number' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'charges_list.*.title' => 'nullable|string',
            'charges_list.*.amount' => 'nullable|numeric',
            'items' => 'array',
            'items.*.product_name' => 'required',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        // Clean charges
        $charges = [];
        foreach ($this->charges_list as $c) {
            if (!empty($c['title'])) {
                $charges[] = $c;
            }
        }
        
        // Determine Organization Snapshot Source
        $orgSnapshot = [];
        if ($this->quotation_id) {
             $quotation = Quotation::with('enquiry.organization')->find($this->quotation_id);
             $orgSnapshot = $quotation->enquiry->organization->toArray();
        } elseif ($this->enquiry_id) {
             $enquiry = Enquiry::with('organization')->find($this->enquiry_id);
             $orgSnapshot = $enquiry->organization->toArray();
        }

        // --- Custom Proforma Number Logic ---
        // Reuse prefix logic from Quotation (User prefix)
        $user = auth()->user();
        $prefix = $user->prefix; 
        if (!$prefix) {
            $parts = explode(' ', $user->name);
            $prefix = '';
            foreach ($parts as $part) { $prefix .= strtoupper(substr($part, 0, 1)); }
            $prefix = substr($prefix, 0, 3); 
        }

        $proformaNo = 0;
        $revisionNo = 0;

        if ($this->reviseFromId) {
            // Revision
            $sourcePi = ProformaInvoice::find($this->reviseFromId);
            if ($sourcePi) {
                $proformaNo = $sourcePi->proforma_no;
                if (!$proformaNo) {
                    // Fallback for legacy items without number
                }
                $revisionNo = $sourcePi->revision_no + 1;
            }
        }
        
        if (!$proformaNo) {
            // New Sequence
            $proformaNo = (int) DB::table('proforma_invoices')->max('proforma_no') + 1;
            $revisionNo = 0; 
        }

        $customId = $prefix . 'PI' . $proformaNo; // Add PI marker? Or just Prefix + No?
        // User said: "same kindof custom_quotation_id"
        // Quotation was: SV1, SV1R1. 
        // For Proforma, maybe simpler to also use prefix but distinctive? 
        // If user prefix is SV, then SV1 might confuse if both use same sequence but different tables.
        // Usually Proforma logic: SV-PI-1 ? Or just PI-SV-1?
        // User didn't specify format exactly, just "same kind". 
        // Let's use Prefix + 'PI' + No + R + Rev for clarity: SVPI1R1 OR SV1 (if shared).
        // BUT Proformas are separate table. 
        // Let's go with: PREFIX + 'PI' + NO. Example: SVPI1, SVPI1R1.
        
        $customId = $prefix . 'PI' . $proformaNo;
        
        if ($revisionNo > 0) {
            $customId .= 'R' . $revisionNo;
        }
        // ------------------------------------

        // Data to save (Always create NEW)
        $data = [
            'quotation_id' => $this->quotation_id ?: null,
            'enquiry_id' => $this->enquiry_id,
            'organization_snapshot' => $orgSnapshot,
            'invoice_percentage' => $this->invoice_percentage,
            'po_date' => $this->po_date ?: null,
            'po_number' => $this->po_number,
            'shipping_address' => $this->shipping_address,
            'charges' => $charges,
            'proforma_no' => $proformaNo,
            'revision_no' => $revisionNo,
            'custom_proforma_id' => $customId,
        ];

        // Ensure we are creating new, not updating $this->proforma if it existed (though we blocked saving in View mode)
        $this->proforma = ProformaInvoice::create($data);

        // Save products
        foreach ($this->items as $item) {
            // Handle manual items without full snapshot
             $snapshot = $item['snapshot'] ?? ['product_name' => $item['product_name']];
             // Ensure product_name is in snapshot
             $snapshot['product_name'] = $item['product_name'];
             
            $this->proforma->products()->create([
                'product_snapshot' => $snapshot,
                'custom_price' => $item['price'],
                'quantity' => $item['quantity'],
            ]);
        }

        session()->flash('message', "Proforma Invoice {$customId} saved successfully.");
        $this->redirect($this->returnUrl);
    }

    public function with(): array
    {
        return [
            'quotations' => Quotation::where('status', 'Accepted')->orWhere('status', 'Sent')->latest()->get(),
            'enquiries' => Enquiry::latest()->get(), // Load enquiries for fallback selection if needed
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between mb-6">
                    <h2 class="text-xl font-semibold">
                         @if($isReadOnly)
                             View Proforma Invoice #{{ $proforma->custom_proforma_id ?? $proforma->id }}
                         @else
                            {{ request()->query('revise_from') ? 'Revise Proforma Invoice' : 'Create Proforma Invoice' }}
                         @endif
                    </h2>
                    <a href="{{ $returnUrl }}"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        &larr; Back to Dashboard
                    </a>
                </div>
                
                 @if($isReadOnly)
                    <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md text-sm text-yellow-700 dark:text-yellow-300">
                        This Proforma is in <strong>View Only</strong> mode.
                    </div>
                @endif

                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Quotation Selection (Optional now) -->
                        <div class="md:col-span-2">
                             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quotation Source (Optional)</label>
                             <select wire:model.live="quotation_id" {{ $isReadOnly || $quotation_id ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                                <option value="">No Quotation (Direct from Enquiry)</option>
                                @foreach($quotations as $q)
                                    <option value="{{ $q->id }}">
                                        {{ $q->custom_quotation_id ?? 'Q-'.$q->id }} (Enq: {{ $q->enquiry->subject }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">If "No Quotation" is selected, this Proforma will be linked directly to the Enquiry.</p>
                        </div>
                        
                         <!-- Enquiry Display (Read-onlyish or Selectable if needed) -->
                        @if(!$quotation_id && $enquiry_id)
                             <div class="md:col-span-2 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-md border border-blue-200 dark:border-blue-800">
                                <label class="block text-sm font-medium text-blue-800 dark:text-blue-300">Linked Enquiry</label>
                                @php $linkedEnquiry = \App\Models\Enquiry::find($enquiry_id); @endphp
                                <div class="text-sm text-blue-900 dark:text-blue-100">
                                    {{ $linkedEnquiry ? $linkedEnquiry->subject . ' (' . $linkedEnquiry->organization->organization_name . ')' : 'Unknown Enquiry' }}
                                </div>
                            </div>
                        @elseif(!$quotation_id && !$enquiry_id)
                             <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Enquiry (Since no Quotation)</label>
                                <select wire:model.live="enquiry_id" required {{ $isReadOnly ? 'disabled' : '' }}
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                                    <option value="">Select Enquiry</option>
                                    @foreach($enquiries as $e)
                                        <option value="{{ $e->id }}">{{ $e->subject }} - {{ $e->organization->organization_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif


                        <!-- Invoice % -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Percentage
                                %</label>
                            <input wire:model="invoice_percentage" type="text" required {{ $isReadOnly ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                        </div>

                        <!-- PO Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">PO Date</label>
                            <input wire:model="po_date" type="date" {{ $isReadOnly ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                        </div>

                        <!-- PO Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">PO Number</label>
                            <input wire:model="po_number" type="text" {{ $isReadOnly ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                        </div>

                        <!-- Shipping Address -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shipping
                                Address</label>
                            <textarea wire:model="shipping_address" rows="3" {{ $isReadOnly ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60"></textarea>
                        </div>
                    </div>

                    <!-- Items Preview (Computed from Quotation) -->
                    <div class="border-t pt-6 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Items / Products
                        </h3>
                        <div class="space-y-4">
                            @foreach ($items as $index => $item)
                                <div class="flex gap-4 items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex-1">
                                         <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Product Description</label>
                                        <input type="text" wire:model="items.{{ $index }}.product_name" required {{ $isReadOnly ? 'disabled' : '' }}
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm disabled:opacity-60">
                                    </div>
                                    <div class="w-32">
                                        <label
                                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Quantity</label>
                                        <input type="number" wire:model="items.{{ $index }}.quantity" min="1" required {{ $isReadOnly ? 'disabled' : '' }}
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm disabled:opacity-60">
                                    </div>
                                    <div class="w-40">
                                        <label
                                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Price</label>
                                        <input type="number" wire:model="items.{{ $index }}.price" step="0.01" min="0" {{ $isReadOnly ? 'disabled' : '' }}
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm disabled:opacity-60">
                                    </div>
                                     @if(!$isReadOnly)
                                     <div class="pb-0">
                                        <button type="button" wire:click="removeItem({{ $index }})"
                                            class="text-red-600 hover:text-red-800 p-2 mt-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if(!$isReadOnly)
                         <button type="button" wire:click="addItem"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-500 font-medium flex items-center">
                            + Add Custom Item
                        </button>
                        @endif
                    </div>

                    <!-- Extra Charges -->
                    <div class="border-t pt-6 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Additional Charges
                            (Freight, etc)</h3>
                        <div class="space-y-3">
                            @foreach ($charges_list as $index => $charge)
                                <div class="flex gap-4 items-start">
                                    <div class="flex-1">
                                        <input type="text" wire:model="charges_list.{{ $index }}.title" {{ $isReadOnly ? 'disabled' : '' }}
                                            placeholder="Charge Title"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                                    </div>
                                    <div class="w-40">
                                        <input type="number" wire:model="charges_list.{{ $index }}.amount" {{ $isReadOnly ? 'disabled' : '' }}
                                            placeholder="Amount" step="0.01"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                                    </div>
                                    @if(!$isReadOnly)
                                    <button type="button" wire:click="removeCharge({{ $index }})"
                                        class="text-red-600 hover:text-red-800 p-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if(!$isReadOnly)
                        <button type="button" wire:click="addCharge"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-500 font-medium flex items-center">
                            + Add Charge
                        </button>
                        @endif
                    </div>
                    
                    @if(!$isReadOnly)
                    <div class="flex items-center justify-end pt-4 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ $reviseFromId ? 'Create Revision' : 'Create Proforma' }}
                        </button>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
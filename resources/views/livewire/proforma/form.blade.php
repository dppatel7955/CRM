<?php

use App\Models\ProformaInvoice;
use App\Models\Quotation;
use App\Models\ProformaInvoiceProduct;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
    #[Layout('layouts.app')]
    class extends Component {
    public ?ProformaInvoice $proforma = null;
    public $quotation_id = '';

    // Fields
    public $invoice_percentage = 100;
    public $po_date = '';
    public $po_number = '';
    public $shipping_address = '';

    // Charges [['title' => '', 'amount' => 0]]
    public $charges_list = [];

    // Products (Read-onlyish from Quotation, or editable?)
    // "Proforma Invoice Products" table exists. So editable copies.
    public $items = [];

    public function mount(?ProformaInvoice $proforma = null): void
    {
        $quotationId = request()->query('quotation_id');

        if ($proforma && $proforma->exists) {
            $this->proforma = $proforma;
            $this->quotation_id = $proforma->quotation_id;
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
        } elseif ($quotationId) {
            // Creating from Quotation
            $this->quotation_id = $quotationId;
            $quotation = Quotation::with('products')->find($quotationId);
            if ($quotation) {
                // Populate items from quotation
                foreach ($quotation->products as $qItem) {
                    $snapshot = $qItem->product_snapshot;
                    $this->items[] = [
                        'product_name' => $snapshot['product_name'] ?? 'Unknown',
                        'quantity' => $qItem->quantity, // Maybe apply percentage? No, usually percentage applies to total amount, not quantity.
                        'price' => $qItem->custom_price,
                        'snapshot' => $snapshot
                    ];
                }
            }
        } else {
            $this->charges_list = [['title' => '', 'amount' => 0]];
        }
    }

    public function addCharge()
    {
        $this->charges_list[] = ['title' => '', 'amount' => 0];
    }

    public function removeCharge($index)
    {
        unset($this->charges_list[$index]);
        $this->charges_list = array_values($this->charges_list);
    }

    // Logic to select quotation if not set?
    public function updatedQuotationId($value)
    {
        if ($value && !$this->proforma) {
            $quotation = Quotation::with('products')->find($value);
            if ($quotation) {
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
        $validated = $this->validate([
            'quotation_id' => 'required|exists:quotations,id',
            'invoice_percentage' => 'required|numeric|min:0|max:100',
            'po_date' => 'nullable|date',
            'po_number' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string',
            'charges_list.*.title' => 'nullable|string',
            'charges_list.*.amount' => 'nullable|numeric',
        ]);

        // Clean charges
        $charges = [];
        foreach ($this->charges_list as $c) {
            if (!empty($c['title'])) {
                $charges[] = $c;
            }
        }

        $quotation = Quotation::with('enquiry.organization')->find($this->quotation_id);
        $orgSnapshot = $quotation->enquiry->organization->toArray();

        // Data to save
        $data = [
            'quotation_id' => $this->quotation_id,
            'organization_snapshot' => $orgSnapshot,
            'invoice_percentage' => $this->invoice_percentage,
            'po_date' => $this->po_date ?: null,
            'po_number' => $this->po_number,
            'shipping_address' => $this->shipping_address,
            'charges' => $charges,
        ];

        if ($this->proforma) {
            $this->proforma->update($data);
            $this->proforma->products()->delete(); // Re-create products
        } else {
            $this->proforma = ProformaInvoice::create($data);
        }

        // Save products
        foreach ($this->items as $item) {
            $this->proforma->products()->create([
                'product_snapshot' => $item['snapshot'],
                'custom_price' => $item['price'],
                'quantity' => $item['quantity'],
            ]);
        }

        session()->flash('message', 'Proforma Invoice saved successfully.');
        $this->redirect(route('proformas.index'));
    }

    public function with(): array
    {
        return [
            'quotations' => Quotation::where('status', 'Accepted')->orWhere('status', 'Sent')->latest()->get(),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between mb-6">
                    <h2 class="text-xl font-semibold">
                        {{ $proforma ? 'Edit Proforma Invoice' : 'Create Proforma Invoice' }}
                    </h2>
                    <a href="{{ route('proformas.index') }}"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        &larr; Back to List
                    </a>
                </div>

                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Quotation Selection -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quotation Source
                                *</label>
                            <select wire:model.live="quotation_id" required {{ $proforma ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select Quotation</option>
                                @foreach($quotations as $q)
                                    <option value="{{ $q->id }}">Q-{{ $q->id }} (Enq: {{ $q->enquiry->subject }})</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Invoice % -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice Percentage
                                %</label>
                            <input wire:model="invoice_percentage" type="number" step="0.01" min="0" max="100" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- PO Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">PO Date</label>
                            <input wire:model="po_date" type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- PO Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">PO Number</label>
                            <input wire:model="po_number" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- Shipping Address -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Shipping
                                Address</label>
                            <textarea wire:model="shipping_address" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>

                    <!-- Items Preview (Computed from Quotation) -->
                    <div class="border-t pt-6 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Items (from Quotation)
                        </h3>
                        <div class="space-y-4">
                            @foreach ($items as $index => $item)
                                <div class="flex gap-4 items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium dark:text-white">{{ $item['product_name'] }}</div>
                                    </div>
                                    <div class="w-32">
                                        <label
                                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Quantity</label>
                                        <input type="number" wire:model="items.{{ $index }}.quantity" min="1" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                    </div>
                                    <div class="w-40">
                                        <label
                                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Price</label>
                                        <input type="number" wire:model="items.{{ $index }}.price" step="0.01" min="0"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Extra Charges -->
                    <div class="border-t pt-6 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Additional Charges
                            (Freight, etc)</h3>
                        <div class="space-y-3">
                            @foreach ($charges_list as $index => $charge)
                                <div class="flex gap-4 items-start">
                                    <div class="flex-1">
                                        <input type="text" wire:model="charges_list.{{ $index }}.title"
                                            placeholder="Charge Title"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div class="w-40">
                                        <input type="number" wire:model="charges_list.{{ $index }}.amount"
                                            placeholder="Amount" step="0.01"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <button type="button" wire:click="removeCharge({{ $index }})"
                                        class="text-red-600 hover:text-red-800 p-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                            fill="currentColor">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addCharge"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-500 font-medium flex items-center">
                            + Add Charge
                        </button>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ $proforma ? 'Update Proforma' : 'Create Proforma' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
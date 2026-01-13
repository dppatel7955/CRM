<?php

use App\Models\Quotation;
use App\Models\Enquiry;
use App\Models\Product;
use Livewire\Volt\Component;

use App\Models\QuotationProduct;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\DB;

new
    #[Layout('layouts.app')]
    class extends Component {
    public ?Quotation $quotation = null;

    public $enquiry_id = '';
    public $valid_till = '';
    public $status = 'Draft';
    public $terms_and_conditions = '';

    // Line Items
    public $items = []; // [['product_id' => '', 'quantity' => 1, 'price' => 0, 'product_name' => '']]

    public $isReadOnly = false; // Add read-only flag
    public $returnUrl = ''; // Store the return URL
    
    // Revision tracking (Internal)
    public $reviseFromId = null; 

    public function mount(?Quotation $quotation = null): void
    {
        $this->reviseFromId = request()->query('revise_from');
        $this->returnUrl = request()->query('return_to', route('dashboard')); // Default to dashboard if not set

        if ($quotation && $quotation->exists) {
            // VIEW MODE (Read Only)
            $this->quotation = $quotation;
            $this->enquiry_id = $quotation->enquiry_id;
            $this->valid_till = $quotation->valid_till ? $quotation->valid_till->format('Y-m-d') : '';
            $this->status = $quotation->status;
            $this->terms_and_conditions = $quotation->terms_and_conditions;
            $this->isReadOnly = true; // Set Read Only

            foreach ($quotation->products as $item) {
                $snapshot = $item->product_snapshot;
                $this->items[] = [
                    'product_id' => $snapshot['id'] ?? null,
                    'quantity' => $item->quantity,
                    'price' => $item->custom_price,
                    'product_name' => $snapshot['product_name'] ?? 'Unknown Product',
                    'snapshot' => $snapshot,
                ];
            }
        } elseif ($this->reviseFromId) {
             // REVISE MODE (Create new from old)
             $sourceQuote = Quotation::with('products')->find($this->reviseFromId);
             
             if ($sourceQuote) {
                 $this->enquiry_id = $sourceQuote->enquiry_id;
                 // $this->status = 'Draft'; // Reset status for new revision?
                 $this->terms_and_conditions = $sourceQuote->terms_and_conditions;
                 // Don't copy valid_till, maybe? Or keep it? Let's keep empty or default.
                 // $this->valid_till = ...
                 
                 foreach ($sourceQuote->products as $item) {
                    $snapshot = $item->product_snapshot;
                    $this->items[] = [
                        'product_id' => $snapshot['id'] ?? null,
                        'quantity' => $item->quantity,
                        'price' => $item->custom_price,
                        'product_name' => $snapshot['product_name'] ?? 'Unknown Product',
                        'snapshot' => $snapshot,
                    ];
                }
             } else {
                 $this->items = [['product_id' => '', 'quantity' => 1, 'price' => 0, 'product_name' => '', 'snapshot' => []]];
             }

        } else {
            // CREATE MODE
            if (request()->query('enquiry_id')) {
                $this->enquiry_id = request()->query('enquiry_id');
            }
            $this->items = [['product_id' => '', 'quantity' => 1, 'price' => 0, 'product_name' => '', 'snapshot' => []]];
        }
    }

    public function addItem()
    {
        if ($this->isReadOnly) return;
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'price' => 0, 'product_name' => '', 'snapshot' => []];
    }

    public function removeItem($index)
    {
        if ($this->isReadOnly) return;
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateItemProduct($index, $productId)
    {
        if ($this->isReadOnly) return;
        $product = Product::find($productId);
        if ($product) {
            $this->items[$index]['product_id'] = $product->id;
            $this->items[$index]['product_name'] = $product->product_name;
            $this->items[$index]['price'] = $product->price; // Default to base price
            $this->items[$index]['snapshot'] = $product->toArray();
        }
    }

    public function save(): void
    {
        if ($this->isReadOnly) return; // Prevent saving in View mode

        $validated = $this->validate([
            'enquiry_id' => 'required|exists:enquiries,id',
            'valid_till' => 'nullable|date',
            'status' => 'required|string',
            'terms_and_conditions' => 'nullable|string',
            'items' => 'array|min:1',
            'items.*.product_id' => 'required', // Ensure product selected (or snapshot logic)
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $enquiry = Enquiry::with('organization')->find($this->enquiry_id);
        $orgSnapshot = $enquiry->organization->toArray();

        // --- Custom Quotation Number Logic ---
        // Prefix: User.prefix OR User Initials
        $user = auth()->user();
        $prefix = $user->prefix; 
        if (!$prefix) {
            // Derive initials if no prefix set
            $parts = explode(' ', $user->name);
            $prefix = '';
            foreach ($parts as $part) { $prefix .= strtoupper(substr($part, 0, 1)); }
            $prefix = substr($prefix, 0, 3); // Limit length
        }

        $quotationNo = 0;
        $revisionNo = 0;
        
        if ($this->reviseFromId) {
            // Revision of existing quote
            $sourceQuote = Quotation::find($this->reviseFromId);
            if ($sourceQuote) {
                $quotationNo = $sourceQuote->quotation_no;
                // If the source didn't have a quotation_no (legacy), assign one? 
                // Better to just start new logic. But assume new flow.
                if (!$quotationNo) {
                     // Fallback for legacy: treat as new but try to preserve sequence? 
                     // Safe: Treat as new global number if unknown.
                     // Or: $quotationNo = DB::table('quotations')->max('quotation_no') + 1;
                     // But user wants same Q number. Let's just create new if null.
                }

                $revisionNo = $sourceQuote->revision_no + 1;
            }
        }

        if (!$quotationNo) {
            // New Sequence (Create Mode or Fallback)
             $quotationNo = (int) DB::table('quotations')->max('quotation_no') + 1;
             $revisionNo = 0; // First version is 0 (SV1) or user implies SV1 (R0 hidden) ?
             // Example: "first quotation SV1", "second quotation SV1R1".
             // So base revision is 0, stored as 0, displayed without 'R0'.
        }

        $customId = $prefix . $quotationNo;
        if ($revisionNo > 0) {
            $customId .= 'R' . $revisionNo;
        }
        // -------------------------------------

        // Always CREATE new quotation (Revision logic)
        $this->quotation = Quotation::create([
            'enquiry_id' => $this->enquiry_id,
            'organization_snapshot' => $orgSnapshot,
            'terms_and_conditions' => $this->terms_and_conditions,
            'valid_till' => $this->valid_till ?: null,
            'status' => $this->status,
            'quotation_no' => $quotationNo,
            'revision_no' => $revisionNo,
            'custom_quotation_id' => $customId, 
        ]);
        
        foreach ($this->items as $item) {
            $snapshot = $item['snapshot'] ?? [];
            if (empty($snapshot) && !empty($item['product_id'])) {
                $product = Product::find($item['product_id']);
                $snapshot = $product ? $product->toArray() : [];
            }

            $this->quotation->products()->create([
                'product_snapshot' => $snapshot,
                'custom_price' => $item['price'],
                'quantity' => $item['quantity'],
            ]);
        }

        session()->flash('message', "Quotation {$customId} saved successfully.");
        $this->redirect($this->returnUrl);
    }

    public function with(): array
    {
        return [
            'enquiries' => Enquiry::with('organization')->orderBy('id', 'desc')->get(), // Should filter active
            'products_list' => Product::where('active', true)->get(),
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
                            View Quotation #{{ $quotation->custom_quotation_id ?? $quotation->id }}
                         @else
                            {{ request()->query('revise_from') ? 'Revise Quotation' : 'Create Quotation' }}
                         @endif
                    </h2>
                    <a href="{{ $returnUrl }}"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        &larr; Back to Dashboard
                    </a>
                </div>
                
                @if($isReadOnly)
                    <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md text-sm text-yellow-700 dark:text-yellow-300">
                        This quotation is in <strong>View Only</strong> mode. To make changes, please use the <strong>Revise</strong> option from the dashboard to create a new version.
                    </div>
                @endif

                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Enquiry Selection -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enquiry *</label>
                            <select wire:model="enquiry_id" required {{ $isReadOnly || $enquiry_id ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                                <option value="">Select Enquiry</option>
                                @foreach($enquiries as $enq)
                                    <option value="{{ $enq->id }}">
                                        {{ $enq->organization->organization_name }} - {{ $enq->subject }}
                                        ({{ $enq->created_at ? $enq->created_at->format('d M') : '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('enquiry_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Terms -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Terms &
                                Conditions</label>
                            <textarea wire:model="terms_and_conditions" rows="3" {{ $isReadOnly ? 'disabled' : '' }}
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60"></textarea>
                        </div>
                    </div>

                    <!-- Line Items -->
                    <div class="border-t pt-6 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Products</h3>
                        <div class="space-y-4">
                            @foreach ($items as $index => $item)
                                <div class="flex gap-4 items-end p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex-1">
                                        <label
                                            class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Product</label>
                                        <select wire:model="items.{{ $index }}.product_id" {{ $isReadOnly ? 'disabled' : '' }}
                                            wire:change="updateItemProduct({{ $index }}, $event.target.value)" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm disabled:opacity-60">
                                            <option value="">Select Product</option>
                                            @foreach($products_list as $prod)
                                                <option value="{{ $prod->id }}">{{ $prod->product_name }} - ${{ $prod->price }}
                                                </option>
                                            @endforeach
                                        </select>
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
                                    <div class="pb-1">
                                        <button type="button" wire:click="removeItem({{ $index }})"
                                            class="text-red-600 hover:text-red-800 p-2">
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
                            + Add Item
                        </button>
                        @endif
                    </div>

                    @if(!$isReadOnly)
                    <div class="flex items-center justify-end pt-4 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ request()->query('revise_from') ? 'Create Revision' : 'Create Quotation' }}
                        </button>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
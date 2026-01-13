<?php

use App\Models\Quotation;
use App\Models\Enquiry;
use App\Models\Product;
use Livewire\Volt\Component;

use App\Models\QuotationProduct;
use Livewire\Attributes\Layout;

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

    public function mount(?Quotation $quotation = null): void
    {
        if ($quotation && $quotation->exists) {
            $this->quotation = $quotation;
            $this->enquiry_id = $quotation->enquiry_id;
            $this->valid_till = $quotation->valid_till ? $quotation->valid_till->format('Y-m-d') : '';
            $this->status = $quotation->status;
            $this->terms_and_conditions = $quotation->terms_and_conditions;

            foreach ($quotation->products as $item) {
                // If product_snapshot exists use it, otherwise fallback (though snapshot should exist)
                $snapshot = $item->product_snapshot;
                $this->items[] = [
                    'product_id' => $snapshot['id'] ?? null, // Tracking original ID might be useful but snapshot is key
                    'quantity' => $item->quantity,
                    'price' => $item->custom_price,
                    'product_name' => $snapshot['product_name'] ?? 'Unknown Product',
                    'snapshot' => $snapshot, // store full snapshot
                ];
            }
        } else {
            // If creating, check if enquiry_id is passed in query string?
            // Since we use route parameters, if passed, we can catch it.
            // But for now, basic init.
            $this->items = [['product_id' => '', 'quantity' => 1, 'price' => 0, 'product_name' => '', 'snapshot' => []]];
        }
    }

    public function addItem()
    {
        $this->items[] = ['product_id' => '', 'quantity' => 1, 'price' => 0, 'product_name' => '', 'snapshot' => []];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateItemProduct($index, $productId)
    {
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

        if ($this->quotation) {
            $this->quotation->update([
                'enquiry_id' => $this->enquiry_id,
                'organization_snapshot' => $orgSnapshot, // Update snapshot on edit? Typically snapshots shouldn't change on edit unless explicitly requested, but for Draft it's okay. For Sent/Accepted, maybe block edits? Assuming edit allowed.
                'terms_and_conditions' => $this->terms_and_conditions,
                'valid_till' => $this->valid_till ?: null,
                'status' => $this->status,
            ]);
            $this->quotation->products()->delete(); // Re-create items
        } else {
            $this->quotation = Quotation::create([
                'enquiry_id' => $this->enquiry_id,
                'organization_snapshot' => $orgSnapshot,
                'terms_and_conditions' => $this->terms_and_conditions,
                'valid_till' => $this->valid_till ?: null,
                'status' => $this->status,
            ]);
        }

        foreach ($this->items as $item) {
            // Ensure snapshot is populated. If new item, it's in 'snapshot' key.
            // If we just loaded from DB, it's also in 'snapshot'.
            // If user just selected product, updateItemProduct populated 'snapshot'.
            // Verify snapshot exists.
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

        session()->flash('message', 'Quotation saved successfully.');
        $this->redirect(route('quotations.index'));
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
                        {{ $quotation ? 'Edit Quotation' : 'Create Quotation' }}
                    </h2>
                    <a href="{{ route('quotations.index') }}"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        &larr; Back to List
                    </a>
                </div>

                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Enquiry Selection -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Enquiry *</label>
                            <select wire:model="enquiry_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select Enquiry</option>
                                @foreach($enquiries as $enq)
                                    <option value="{{ $enq->id }}">
                                        {{ $enq->organization->organization_name }} - {{ $enq->subject }}
                                        ({{ $enq->created_at->format('d M') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('enquiry_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Terms -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Terms &
                                Conditions</label>
                            <textarea wire:model="terms_and_conditions" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
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
                                        <select wire:model="items.{{ $index }}.product_id"
                                            wire:change="updateItemProduct({{ $index }}, $event.target.value)" required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm">
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
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addItem"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-500 font-medium flex items-center">
                            + Add Item
                        </button>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ $quotation ? 'Update Quotation' : 'Create Quotation' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
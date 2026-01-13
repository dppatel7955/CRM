<?php

use App\Models\Product;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

new
    #[Layout('layouts.app')]
    class extends Component {
    use WithFileUploads;

    public ?Product $product = null;

    public string $product_name = '';
    public string $model_name = '';
    public string $hsn_code = '';
    public $price = '';
    public $dealer_price = '';
    public $purchase_price = '';
    public $max_discount = '';
    public string $short_description = '';
    public string $description = '';
    public string $key_features = '';
    public string $application = '';
    public bool $active = true;

    // Images
    public $existingImages = []; // Paths of existing images
    public $newImages = []; // Temporary uploaded files (max 3 total combined)

    // JSON Attributes
    public array $attributes_list = [['key' => '', 'value' => '']];

    public function mount(?Product $product = null): void
    {
        if ($product && $product->exists) {
            $this->product = $product;
            $this->product_name = $product->product_name;
            $this->model_name = $product->model_name ?? '';
            $this->hsn_code = $product->hsn_code ?? '';
            $this->price = $product->price;
            $this->dealer_price = $product->dealer_price;
            $this->purchase_price = $product->purchase_price;
            $this->max_discount = $product->max_discount;
            $this->short_description = $product->short_description ?? '';
            $this->description = $product->description ?? '';
            $this->key_features = $product->key_features ?? '';
            $this->application = $product->application ?? '';
            $this->active = $product->active;

            $this->existingImages = $product->images ?? [];

            if (!empty($product->attributes)) {
                $this->attributes_list = [];
                foreach ($product->attributes as $key => $value) {
                    $this->attributes_list[] = ['key' => $key, 'value' => $value];
                }
                if (empty($this->attributes_list)) {
                    $this->attributes_list[] = ['key' => '', 'value' => ''];
                }
            }
        }
    }

    public function addAttributeRow()
    {
        $this->attributes_list[] = ['key' => '', 'value' => ''];
    }

    public function removeAttributeRow($index)
    {
        unset($this->attributes_list[$index]);
        $this->attributes_list = array_values($this->attributes_list);
    }

    public function removeExistingImage($index)
    {
        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    public function removeNewImage($index)
    {
        unset($this->newImages[$index]);
        $this->newImages = array_values($this->newImages);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'product_name' => 'required|string|max:255',
            'model_name' => 'nullable|string|max:255',
            'hsn_code' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'dealer_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'short_description' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'key_features' => 'nullable|string',
            'application' => 'nullable|string',
            'active' => 'boolean',
            'newImages.*' => 'nullable|image|max:2048', // 2MB Max
            'attributes_list.*.key' => 'nullable|string',
            'attributes_list.*.value' => 'nullable|string',
        ]);

        // Convert attributes list back to associative array
        $attributes = [];
        foreach ($this->attributes_list as $item) {
            if (!empty($item['key'])) {
                $attributes[$item['key']] = $item['value'];
            }
        }

        // Handle Images
        $finalImages = $this->existingImages;

        // Store new images
        foreach ($this->newImages as $image) {
            // Basic limit check (optimistic based on UI)
            if (count($finalImages) < 3) {
                $path = $image->store('products', 'public');
                $finalImages[] = $path;
            }
        }

        // Ensure max 3 (backend clamp)
        $finalImages = array_slice($finalImages, 0, 3);

        $data = [
            'product_name' => $this->product_name,
            'model_name' => $this->model_name,
            'hsn_code' => $this->hsn_code,
            'price' => $this->price,
            'dealer_price' => $this->dealer_price !== '' ? $this->dealer_price : null,
            'purchase_price' => $this->purchase_price !== '' ? $this->purchase_price : null,
            'max_discount' => $this->max_discount !== '' ? $this->max_discount : null,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'key_features' => $this->key_features,
            'application' => $this->application,
            'active' => $this->active,
            'images' => $finalImages,
            'attributes' => $attributes,
        ];

        if ($this->product) {
            $this->product->update($data);
            session()->flash('message', 'Product updated successfully.');
        } else {
            Product::create($data);
            session()->flash('message', 'Product created successfully.');
        }

        $this->redirect(route('products.index'));
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between mb-6">
                    <h2 class="text-xl font-semibold">
                        {{ $product ? 'Edit Product' : 'Create Product' }}
                    </h2>
                    <a href="{{ route('products.index') }}"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        &larr; Back to List
                    </a>
                </div>

                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Product Name -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name
                                *</label>
                            <input wire:model="product_name" type="text" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('product_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Image Upload Section -->
                        <div class="md:col-span-2 border-t pt-6 dark:border-gray-700">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Product
                                Images (Max 3)</label>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <!-- Display Existing Images -->
                                @foreach($existingImages as $index => $path)
                                    <div class="relative group">
                                        <img src="{{ Storage::url($path) }}"
                                            class="h-32 w-full object-cover rounded-md border border-gray-300 dark:border-gray-600">
                                        <button type="button" wire:click="removeExistingImage({{ $index }})"
                                            class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 opacity-75 hover:opacity-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach

                                <!-- Display New Images Preview -->
                                @foreach($newImages as $index => $image)
                                    <div class="relative group">
                                        <img src="{{ $image->temporaryUrl() }}"
                                            class="h-32 w-full object-cover rounded-md border border-gray-300 dark:border-gray-600">
                                        <button type="button" wire:click="removeNewImage({{ $index }})"
                                            class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 opacity-75 hover:opacity-100 transition">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach

                                <!-- Upload Button (Show if total < 3) -->
                                @if(count($existingImages) + count($newImages) < 3)
                                    <div
                                        class="flex items-center justify-center h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md hover:border-blue-500 dark:hover:border-blue-500 transition cursor-pointer">
                                        <label for="file-upload"
                                            class="cursor-pointer flex flex-col items-center justify-center w-full h-full">
                                            <svg class="h-8 w-8 text-gray-400" stroke="currentColor" fill="none"
                                                viewBox="0 0 48 48" aria-hidden="true">
                                                <path
                                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            <span class="mt-2 text-sm text-gray-500">Upload Image</span>
                                            <input id="file-upload" type="file" wire:model="newImages" class="hidden"
                                                accept="image/*" multiple>
                                        </label>
                                    </div>
                                @endif
                            </div>
                            @error('newImages.*') <span class="text-red-500 text-sm block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Model Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Model Name</label>
                            <input wire:model="model_name" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- HSN Code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">HSN Code</label>
                            <input wire:model="hsn_code" type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Selling Price
                                *</label>
                            <input wire:model="price" type="number" step="0.01" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Dealer Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dealer
                                Price</label>
                            <input wire:model="dealer_price" type="number" step="0.01"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- Purchase Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Purchase
                                Price</label>
                            <input wire:model="purchase_price" type="number" step="0.01"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- Max Discount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Max
                                Discount</label>
                            <input wire:model="max_discount" type="number" step="0.01"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- Active -->
                        <div class="flex items-center pt-6">
                            <input wire:model="active" type="checkbox" id="active"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                            <label for="active"
                                class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Active</label>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Short
                            Description</label>
                        <textarea wire:model="short_description" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full
                            Description</label>
                        <textarea wire:model="description" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    </div>

                    <!-- Key Features -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Key Features</label>
                        <textarea wire:model="key_features" rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Enter key features..."></textarea>
                    </div>

                    <!-- Application -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Application</label>
                        <textarea wire:model="application" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Enter applications..."></textarea>
                    </div>

                    <!-- Attributes Editor -->
                    <div class="border-t pt-6 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Technical Specifications /
                            Attributes</h3>
                        <div class="space-y-3">
                            @foreach ($attributes_list as $index => $attribute)
                                <div class="flex gap-4 items-start">
                                    <div class="flex-1">
                                        <input type="text" wire:model="attributes_list.{{ $index }}.key"
                                            placeholder="Key (e.g. Color)"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <div class="flex-1">
                                        <input type="text" wire:model="attributes_list.{{ $index }}.value"
                                            placeholder="Value (e.g. Red)"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    </div>
                                    <button type="button" wire:click="removeAttributeRow({{ $index }})"
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
                        <button type="button" wire:click="addAttributeRow"
                            class="mt-3 text-sm text-blue-600 hover:text-blue-500 font-medium flex items-center">
                            + Add Attribute
                        </button>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ $product ? 'Update Product' : 'Create Product' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php

use App\Models\Product;
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
        $product = Product::find($id);
        $product?->delete();
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->where('product_name', 'like', '%' . $this->search . '%')
                ->orWhere('model_name', 'like', '%' . $this->search . '%')
                ->orWhere('hsn_code', 'like', '%' . $this->search . '%')
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
                    <h2 class="text-lg font-semibold">Products</h2>
                    <a href="{{ route('products.create') }}"
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
                                <th class="px-3 py-2">Product</th>
                                <th class="px-3 py-2">Price</th>
                                <th class="px-3 py-2 hidden sm:table-cell">HSN</th>
                                <th class="px-3 py-2 hidden sm:table-cell">Status</th>
                                <th class="px-3 py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($products as $product)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-3 py-2">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $product->product_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $product->model_name }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $product->price }}
                                        @if($product->dealer_price)
                                            <div class="text-xs text-gray-400">D: {{ $product->dealer_price }}</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-sm text-gray-500 hidden sm:table-cell">
                                        {{ $product->hsn_code }}
                                    </td>
                                    <td class="px-3 py-2 hidden sm:table-cell">
                                        @if($product->active)
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Active</span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <a href="{{ route('products.edit', $product) }}"
                                                class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400">Edit</a>
                                            <button wire:click="delete({{ $product->id }})" wire:confirm="Are you sure?"
                                                class="text-red-600 hover:text-red-900 dark:hover:text-red-400">Del</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">No products found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
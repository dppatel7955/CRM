<?php

use App\Models\Dropdown;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new
    #[Layout('layouts.app')]
    class extends Component {
    public $type = '';
    public $value = '';
    public $types = ['Order Status', 'Enquiry Source', 'Unit', 'Tax']; // Predefined or dynamic?

    public function save()
    {
        $this->validate([
            'type' => 'required|string',
            'value' => 'required|string',
        ]);

        Dropdown::create([
            'type' => $this->type,
            'value' => $this->value,
            'active' => true,
        ]);

        $this->value = ''; // Keep type selected
        $this->dispatch('dropdown-saved');
    }

    public function delete($id)
    {
        Dropdown::find($id)?->delete();
    }

    public function with(): array
    {
        return [
            'dropdowns' => Dropdown::orderBy('type')->orderBy('value')->get()->groupBy('type'),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-xl font-semibold mb-6">Dropdown Master</h2>

                <!-- Add New -->
                <form wire:submit="save"
                    class="mb-8 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg flex gap-4 items-end">
                    <div class="w-1/3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                        <select wire:model="type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Select Type</option>
                            @foreach($types as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                            <option value="Other">Other (Custom)</option>
                        </select>
                    </div>
                    @if($type === 'Other')
                        <div class="w-1/3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Custom Type
                                Name</label>
                            <!-- Ideally handle custom type logic -->
                            <input type="text" wire:model="type" class="mt-1 block w-full rounded-md dark:bg-gray-700" />
                        </div>
                    @endif

                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Value</label>
                        <input type="text" wire:model="value" required placeholder="e.g. Sent, Email, kg"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500">Add</button>
                </form>

                <!-- List -->
                <div class="space-y-6">
                    @foreach($dropdowns as $type => $items)
                        <div>
                            <h3 class="font-bold text-lg mb-2 text-gray-800 dark:text-gray-200">{{ $type }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($items as $item)
                                    <div class="flex justify-between items-center p-3 border rounded dark:border-gray-700">
                                        <span>{{ $item->value }}</span>
                                        <button wire:click="delete({{ $item->id }})"
                                            class="text-red-500 hover:text-red-700 px-2">&times;</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
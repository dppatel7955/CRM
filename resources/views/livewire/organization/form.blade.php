<?php

use App\Models\Organization;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new 
#[Layout('layouts.app')]
class extends Component {
    public ?Organization $organization = null;

    public string $organization_name = '';
    public string $contact_person_name = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $gst_number = '';
    public bool $is_dealer = false;
    public bool $active = true;

    public function mount(?Organization $organization = null): void
    {
        if ($organization && $organization->exists) {
            $this->organization = $organization;
            $this->organization_name = $organization->organization_name;
            $this->contact_person_name = $organization->contact_person_name ?? '';
            $this->phone = $organization->phone ?? '';
            $this->email = $organization->email ?? '';
            $this->address = $organization->address ?? '';
            $this->gst_number = $organization->gst_number ?? '';
            $this->is_dealer = $organization->is_dealer;
            $this->active = $organization->active;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'organization_name' => 'required|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20|unique:organizations,phone,' . ($this->organization->id ?? 'NULL'),
            'email' => 'nullable|email|max:255|unique:organizations,email,' . ($this->organization->id ?? 'NULL'),
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:20',
            'is_dealer' => 'boolean',
            'active' => 'boolean',
        ]);

        if ($this->organization) {
            $this->organization->update($validated);
            session()->flash('message', 'Organization updated successfully.');
        } else {
            Organization::create($validated);
            session()->flash('message', 'Organization created successfully.');
        }

        $this->redirect(route('organizations.index'));
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between mb-6">
                    <h2 class="text-xl font-semibold">
                        {{ $organization ? 'Edit Organization' : 'Create Organization' }}
                    </h2>
                    <a href="{{ route('organizations.index') }}"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        &larr; Back to List
                    </a>
                </div>

                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Organization Name -->
                        <div>
                            <label for="organization_name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Organization Name
                                *</label>
                            <input wire:model="organization_name" type="text" id="organization_name" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('organization_name') <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Contact Person -->
                        <div>
                            <label for="contact_person_name"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Person
                                Name</label>
                            <input wire:model="contact_person_name" type="text" id="contact_person_name"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('contact_person_name') <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input wire:model="phone" type="text" id="phone"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input wire:model="email" type="email" id="email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- GST Number -->
                        <div>
                            <label for="gst_number"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">GST Number</label>
                            <input wire:model="gst_number" type="text" id="gst_number"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('gst_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Address -->
                        <div class="col-span-1 md:col-span-2">
                            <label for="address"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                            <textarea wire:model="address" id="address" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                            @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Options -->
                        <div class="col-span-1 md:col-span-2 flex space-x-6">
                            <div class="flex items-center">
                                <input wire:model="is_dealer" type="checkbox" id="is_dealer"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                <label for="is_dealer" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                    Is Dealer?
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input wire:model="active" type="checkbox" id="active"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                <label for="active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ $organization ? 'Update Organization' : 'Create Organization' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
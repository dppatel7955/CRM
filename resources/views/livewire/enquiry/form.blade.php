<?php

use App\Models\Enquiry;
use App\Models\Organization;
use App\Models\User;
use Livewire\Volt\Component;
use App\Models\Dropdown;
use Livewire\Attributes\Layout;

new
    #[Layout('layouts.app')]
    class extends Component {
    public ?Enquiry $enquiry = null;

    public $organization_search = '';
    public $organization_id = ''; // ID for selection
    public $show_results = false;
    public $create_new_org = false; // Toggle state

    // New Organization Fields
    public $new_org_name = '';
    public $new_org_phone = '';
    public $new_org_email = '';
    public $new_org_contact_person = '';

    public $assigned_to = '';
    public $subject = '';
    public $message = '';
    public $products = '';
    public $order_status = '';
    public $enquiry_source = '';
    public $active = true;
    public $follow_up_date = null;
    public $follow_up_notes = '';

    public function mount(?Enquiry $enquiry = null): void
    {
        if ($enquiry && $enquiry->exists) {
            $this->enquiry = $enquiry;
            $this->organization_id = $enquiry->organization_id;
            $this->organization_search = $enquiry->organization->organization_name ?? '';
            $this->assigned_to = $enquiry->assigned_to;
            $this->subject = $enquiry->subject;
            $this->message = $enquiry->message ?? '';
            $this->products = $enquiry->products ?? '';
            $this->order_status = $enquiry->order_status;
            $this->enquiry_source = $enquiry->enquiry_source ?? '';
            $this->active = $enquiry->active;
            $this->follow_up_date = $enquiry->follow_up_date ? $enquiry->follow_up_date->format('Y-m-d') : '';
            $this->follow_up_notes = $enquiry->follow_up_notes ?? '';
        } else {
            $this->assigned_to = auth()->id(); // Default to creator
        }
    }

    public function updatedOrganizationSearch()
    {
        $this->show_results = true;
        $this->create_new_org = false; // Reset if typing in search
    }

    public function selectResult($id, $name)
    {
        $this->organization_id = $id;
        $this->organization_search = $name;
        $this->show_results = false;
        $this->create_new_org = false;
    }

    public function createNewOrganization()
    {
        $this->create_new_org = true;
        $this->new_org_name = $this->organization_search; // Pre-fill with search term
        $this->organization_id = ''; // Clear selected ID
        $this->show_results = false;
    }

    public function cancelNewOrganization()
    {
        $this->create_new_org = false;
        $this->new_org_name = '';
        $this->new_org_phone = '';
        $this->new_org_email = '';
        $this->new_org_contact_person = '';
        // $this->organization_search = ''; // Optional: clear or keep
    }

    public function with(): array
    {
        $searched_organizations = [];
        // Only search if not creating new and search term is long enough
        if (!$this->create_new_org && strlen($this->organization_search) >= 2) {
            $searched_organizations = Organization::query()
                ->where('organization_name', 'like', '%' . $this->organization_search . '%')
                ->orWhere('phone', 'like', '%' . $this->organization_search . '%')
                ->orWhere('email', 'like', '%' . $this->organization_search . '%')
                ->limit(10)
                ->get();
        }

        return [
            'searched_organizations' => $searched_organizations,
            'users' => User::all(),
            'enquiry_statuses' => Dropdown::where('type', 'Order Status')->where('active', true)->orderBy('value')->get(),
            'enquiry_sources' => Dropdown::where('type', 'Enquiry Source')->where('active', true)->orderBy('value')->get(),
        ];
    }

    public function save(): void
    {
        // Validation rules
        $rules = [
            'assigned_to' => 'nullable|exists:users,id',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string',
            'products' => 'nullable|string|max:255',
            'order_status' => 'required|string',
            'enquiry_source' => 'nullable|string',
            'active' => 'boolean',
            'follow_up_date' => 'nullable|date',
            'follow_up_notes' => 'nullable|string',
        ];

        // Conditional validation for Organization
        if ($this->create_new_org) {
            $rules['new_org_name'] = 'required|string|max:255';
            $rules['new_org_phone'] = 'nullable|string|max:20';
            $rules['new_org_email'] = 'nullable|email|max:255';
            $rules['new_org_contact_person'] = 'nullable|string|max:255';
        } else {
            $rules['organization_id'] = 'required|exists:organizations,id';
        }

        $validated = $this->validate($rules);

        // Handle Organization Creation
        if ($this->create_new_org) {
            $org = Organization::create([
                'organization_name' => $this->new_org_name,
                'phone' => $this->new_org_phone,
                'email' => $this->new_org_email,
                'contact_person_name' => $this->new_org_contact_person,
                'active' => true,
                // Add defaults for other required fields if any, e.g. address
            ]);
            $this->organization_id = $org->id;
        }

        // Create the enquiry data array
        $enquiryData = [
            'organization_id' => $this->organization_id,
            'assigned_to' => $validated['assigned_to'] ?: null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'products' => $validated['products'],
            'order_status' => $validated['order_status'],
            'enquiry_source' => $validated['enquiry_source'],
            'active' => $validated['active'],
            'follow_up_date' => $validated['follow_up_date'] ?: null, // Ensure null if empty
            'follow_up_notes' => $validated['follow_up_notes'],
        ];

        if ($this->enquiry) {
            $this->enquiry->update($enquiryData);
            session()->flash('message', 'Enquiry updated successfully.');
        } else {
            $enquiryData['created_by'] = auth()->id();
            Enquiry::create($enquiryData);
            session()->flash('message', 'Enquiry created successfully.');
        }

        $this->redirect(route('enquiries.index'));
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="flex justify-between mb-6">
                    <h2 class="text-xl font-semibold">
                        {{ $enquiry ? 'Edit Enquiry' : 'Create Enquiry' }}
                    </h2>
                    <a href="{{ route('enquiries.index') }}"
                        class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200">
                        &larr; Back to List
                    </a>
                </div>

                <form wire:submit="save" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Organization Selection / Creation -->
                        <div class="md:col-span-2 relative">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Organization
                                *</label>

                            @if(!$create_new_org)
                                <!-- Search Mode -->
                                <div class="relative">
                                    <input type="text" wire:model.live.debounce.300ms="organization_search"
                                        placeholder="Search by Name, Phone, or Email..."
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        autocomplete="off" wire:focus="$set('show_results', true)">
                                    @error('organization_id') <span class="text-red-500 text-sm block mt-1">Please select an
                                    organization or create a new one.</span> @enderror

                                    <!-- Dropdown Results -->
                                    @if($show_results && !empty($searched_organizations))
                                        <div
                                            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                            @foreach($searched_organizations as $result)
                                                <div wire:click="selectResult({{ $result->id }}, '{{ addslashes($result->organization_name) }}')"
                                                    class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 text-gray-900 dark:text-white border-b dark:border-gray-600 last:border-0">
                                                    <div class="flex flex-col">
                                                        <span class="font-medium truncate">{{ $result->organization_name }}</span>
                                                        <span
                                                            class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $result->phone }}
                                                            | {{ $result->email }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                            <!-- Create New Option in Dropdown -->
                                            <div wire:click="createNewOrganization"
                                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 bg-gray-50 dark:bg-gray-800 hover:bg-blue-600 hover:text-white text-blue-600 dark:text-blue-400 font-medium border-t dark:border-gray-600">
                                                + Create New Organization
                                            </div>
                                        </div>
                                    @elseif($show_results && strlen($organization_search) >= 2)
                                        <div
                                            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 shadow-lg rounded-md py-2 px-3 text-sm text-gray-500 dark:text-gray-400">
                                            No organizations found.
                                            <button type="button" wire:click="createNewOrganization"
                                                class="text-blue-600 hover:underline font-bold ml-1">
                                                Create "{{ $organization_search }}"?
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <!-- Create New Organization Mode -->
                                <div
                                    class="bg-gray-50 dark:bg-gray-700 p-4 rounded-md border dark:border-gray-600 relative">
                                    <button type="button" wire:click="cancelNewOrganization"
                                        class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">New Organization
                                        Details</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label
                                                class="block text-xs font-medium text-gray-500 dark:text-gray-400">Organization
                                                Name *</label>
                                            <input wire:model="new_org_name" type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                            @error('new_org_name') <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-500 dark:text-gray-400">Contact
                                                Person</label>
                                            <input wire:model="new_org_contact_person" type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-medium text-gray-500 dark:text-gray-400">Phone</label>
                                            <input wire:model="new_org_phone" type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label
                                                class="block text-xs font-medium text-gray-500 dark:text-gray-400">Email</label>
                                            <input wire:model="new_org_email" type="email"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Subject -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject *</label>
                            <input wire:model="subject" type="text" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('subject') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Message -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                            <textarea wire:model="message" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        <!-- Products of Interest -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Interested
                                Products (Summary)</label>
                            <input wire:model="products" type="text" placeholder="e.g. Generators, Solar Panels"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select wire:model="order_status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select Status</option>
                                @foreach($enquiry_statuses as $status)
                                    <option value="{{ $status->value }}">{{ $status->value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Source -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Source</label>
                            <select wire:model="enquiry_source"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Select Source</option>
                                @foreach($enquiry_sources as $source)
                                    <option value="{{ $source->value }}">{{ $source->value }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Assigned To -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Assigned
                                To</label>
                            <select wire:model="assigned_to"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">Unassigned</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Follow Up Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Follow Up
                                Date</label>
                            <input wire:model="follow_up_date" type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <!-- Follow Up Notes -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Follow Up
                                Notes</label>
                            <textarea wire:model="follow_up_notes" rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>

                        <!-- Active -->
                        <div class="flex items-center">
                            <input wire:model="active" type="checkbox" id="active"
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                            <label for="active"
                                class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Active</label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t dark:border-gray-700">
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            {{ $enquiry ? 'Update Enquiry' : 'Create Enquiry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
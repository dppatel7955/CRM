<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified']);

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Livewire\Volt\Volt::route('organizations', 'organization.index')->name('organizations.index');
    Livewire\Volt\Volt::route('organizations/create', 'organization.form')->name('organizations.create');
    Livewire\Volt\Volt::route('organizations/{organization}/edit', 'organization.form')->name('organizations.edit');

    Livewire\Volt\Volt::route('products', 'product.index')->name('products.index');
    Livewire\Volt\Volt::route('products/create', 'product.form')->name('products.create');
    Livewire\Volt\Volt::route('products/{product}/edit', 'product.form')->name('products.edit');

    Livewire\Volt\Volt::route('enquiries', 'enquiry.index')->name('enquiries.index');
    Livewire\Volt\Volt::route('enquiries/create', 'enquiry.form')->name('enquiries.create');
    Livewire\Volt\Volt::route('enquiries/{enquiry}/edit', 'enquiry.form')->name('enquiries.edit');

    Livewire\Volt\Volt::route('quotations', 'quotation.index')->name('quotations.index');
    Livewire\Volt\Volt::route('quotations/create', 'quotation.form')->name('quotations.create');
    Livewire\Volt\Volt::route('quotations/{quotation}/edit', 'quotation.form')->name('quotations.edit');

    Livewire\Volt\Volt::route('proformas', 'proforma.index')->name('proformas.index');
    Livewire\Volt\Volt::route('proformas/create', 'proforma.form')->name('proformas.create');
    Livewire\Volt\Volt::route('proformas/{proforma}/edit', 'proforma.form')->name('proformas.edit');
});

Route::get('/quotations/{quotation}/download', [App\Http\Controllers\PdfController::class, 'downloadQuotation'])
    ->name('quotations.download')
    ->middleware(['auth']);

Route::get('/quotations/{quotation}/view', [App\Http\Controllers\PdfController::class, 'streamQuotation'])
    ->name('quotations.view_pdf')
    ->middleware(['auth']);

Route::get('/proformas/{proforma}/download', [App\Http\Controllers\PdfController::class, 'downloadProforma'])
    ->name('proformas.download')
    ->middleware(['auth']);

Route::get('/proformas/{proforma}/view', [App\Http\Controllers\PdfController::class, 'streamProforma'])
    ->name('proformas.view_pdf')
    ->middleware(['auth']);

Livewire\Volt\Volt::route('dropdowns', 'dropdown.index')->name('dropdowns.index')->middleware(['auth']);

Route::middleware(['auth', 'admin'])->group(function () {
    Livewire\Volt\Volt::route('admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
});

require __DIR__ . '/auth.php';

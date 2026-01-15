@props(['active', 'icon'])

@php
$classes = ($active ?? false)
            ? 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg bg-indigo-50 text-indigo-600 dark:bg-gray-700 dark:text-white transition-all duration-200'
            : 'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-white transition-all duration-200';

$iconClasses = ($active ?? false)
            ? 'mr-3 h-5 w-5 flex-shrink-0 text-indigo-600 dark:text-white'
            : 'mr-3 h-5 w-5 flex-shrink-0 text-gray-400 group-hover:text-indigo-600 dark:text-gray-400 dark:group-hover:text-white transition-colors duration-200';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    <svg class="{{ $iconClasses }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        {{ $icon }}
    </svg>
    <span class="truncate">
        {{ $slot }}
    </span>
</a>

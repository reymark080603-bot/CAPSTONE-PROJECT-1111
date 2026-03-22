@php
    $headerColor = match(strtolower(str_replace(' ', '', $book->category ?? ''))) {
        'programming' => 'bg-blue-600',
        'mathematics' => 'bg-green-600',
        'literature' => 'bg-purple-600',
        'science' => 'bg-red-600',
        'business' => 'bg-amber-600',
        'technology' => 'bg-indigo-600',
        'education' => 'bg-pink-600',
        'reference' => 'bg-gray-600',
        default => 'bg-blue-600'
    };
    
    $bgColor = match(strtolower(str_replace(' ', '', $book->category ?? ''))) {
        'programming' => 'bg-blue-100',
        'mathematics' => 'bg-green-100',
        'literature' => 'bg-purple-100',
        'science' => 'bg-red-100',
        'business' => 'bg-amber-100',
        'technology' => 'bg-indigo-100',
        'education' => 'bg-pink-100',
        'reference' => 'bg-gray-100',
        default => 'bg-blue-100'
    };
    
    $textColor = match(strtolower(str_replace(' ', '', $book->category ?? ''))) {
        'programming' => 'text-blue-600',
        'mathematics' => 'text-green-600',
        'literature' => 'text-purple-600',
        'science' => 'text-red-600',
        'business' => 'text-amber-600',
        'technology' => 'text-indigo-600',
        'education' => 'text-pink-600',
        'reference' => 'text-gray-600',
        default => 'text-blue-600'
    };
    
    $categoryIcons = [
        'programming' => 'fa-code',
        'mathematics' => 'fa-calculator',
        'literature' => 'fa-feather-alt',
        'science' => 'fa-flask',
        'business' => 'fa-chart-line',
        'technology' => 'fa-microchip',
        'education' => 'fa-graduation-cap',
        'reference' => 'fa-bookmark'
    ];
    $categoryClass = strtolower(str_replace(' ', '', $book->category ?? ''));
    $iconClass = $categoryIcons[$categoryClass] ?? 'fa-book';
@endphp

<!-- Book cover design -->
<div class="absolute inset-0 bg-white">
    <!-- Header band -->
    <div class="h-16 {{ $headerColor }} relative">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
    </div>
    
    <!-- Main content area -->
    <div class="p-6 h-full flex flex-col justify-between">
        <!-- Title section -->
        <div class="text-center mt-4">
            <h3 class="text-lg font-bold text-gray-900 leading-tight mb-2 break-words">
                {{ $book->title }}
            </h3>
            <p class="text-sm text-gray-600 font-medium">
                {{ $book->author }}
            </p>
        </div>
        
        <!-- Center illustration -->
        <div class="flex-1 flex items-center justify-center my-4">
            <div class="w-20 h-20 {{ $bgColor }} rounded-full flex items-center justify-center">
                <i class="fas {{ $iconClass }} text-2xl {{ $textColor }}"></i>
            </div>
        </div>
        
        <!-- Bottom section -->
        <div class="text-center">
            <div class="text-xs text-gray-500 uppercase tracking-wider font-semibold mb-2">
                {{ $book->category ?? 'General' }}
            </div>
            @if($book->published_year)
            <div class="text-xs text-gray-400">
                {{ $book->published_year }}
            </div>
            @endif
        </div>
    </div>
    
    <!-- Footer band -->
    <div class="absolute bottom-0 left-0 right-0 h-8 {{ $headerColor }}">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
    </div>
</div>

<!-- Book spine shadow -->
<div class="absolute left-0 top-0 w-2 h-full bg-gradient-to-r from-black/20 to-transparent"></div>

<!-- Highlight effect -->
<div class="absolute top-0 left-2 w-px h-full bg-white/40"></div>

<!-- Page depth effect -->
<div class="absolute right-0 top-1 bottom-1 w-1 bg-gray-300 rounded-r-xl"></div>
<div class="absolute right-1 top-2 bottom-2 w-px bg-gray-400"></div>

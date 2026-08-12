@extends('layouts.librarian')

@section('title', 'Edit Book')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Edit Book</h1>
            <p class="text-gray-600 mt-2">Update the information for this book</p>
        </div>
    </div>
</div>

<!-- Alerts -->
<div id="alertBox" class="hidden mb-4"></div>

<div class="bg-white rounded-xl shadow-sm border p-6">
    <form id="editBookForm" class="space-y-6" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="_method" value="PUT">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Cover -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cover Photo</label>
                <div class="aspect-[3/4] w-full max-w-[260px] rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center border mb-3 mx-auto lg:mx-0">
                    <img id="coverPreview" src="{{ $book->cover_photo ? $book->display_cover_url : '' }}" alt="Cover preview" class="w-full h-full object-cover {{ $book->cover_photo ? '' : 'hidden' }}">
                    @unless($book->cover_photo)
                    <div id="coverPlaceholder" class="text-gray-400 flex flex-col items-center">
                        <i class="fas fa-book text-5xl mb-2"></i>
                        <span>No cover selected</span>
                    </div>
                    @endunless
                </div>
                <input type="file" name="cover_photo" id="cover_photo" accept="image/*" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">JPEG, PNG, GIF up to 2MB</p>
            </div>

            <!-- Right: Fields -->
            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" id="title" name="title" value="{{ $book->title }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 mb-1">Author</label>
                    <input type="text" id="author" name="author" value="{{ $book->author }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="course" class="block text-sm font-medium text-gray-700 mb-1">Program</label>
                    @php($crs = isset($courses) ? $courses : ['BSE','BSHM','BSIT','BSN','BSTM'])
                    <select id="course" name="course" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Programs</option>
                        @foreach($crs as $course)
                            <option value="{{ $course }}" {{ (string)$book->course === (string)$course ? 'selected' : '' }}>{{ $course }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="resource_type" class="block text-sm font-medium text-gray-700 mb-1">Resource Type</label>
                    @php($resourceType = old('resource_type', $book->resource_type ?: 'book'))
                    <select id="resource_type" name="resource_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="book" {{ $resourceType === 'book' ? 'selected' : '' }}>Book</option>
                        <option value="e_journal" {{ $resourceType === 'e_journal' ? 'selected' : '' }}>E-Journal</option>
                        <option value="thesis" {{ $resourceType === 'thesis' ? 'selected' : '' }}>Thesis</option>
                        <option value="homegrown" {{ $resourceType === 'homegrown' ? 'selected' : '' }}>Homegrown / Unpublished</option>
                    </select>
                </div>
                <div>
                    <label for="subcategory" class="block text-sm font-medium text-gray-700 mb-1">Subcategory</label>
                    @php($subCat = old('subcategory', $book->subcategory ?? ''))
                    <select id="subcategory" name="subcategory" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Subcategory (Optional)</option>
                        <option value="Thesis & Dissertation" {{ $subCat === 'Thesis & Dissertation' ? 'selected' : '' }}>Thesis & Dissertation</option>
                        <option value="Capstone Project" {{ $subCat === 'Capstone Project' ? 'selected' : '' }}>Capstone Project</option>
                        <option value="Institutional Research" {{ $subCat === 'Institutional Research' ? 'selected' : '' }}>Institutional & Faculty Research</option>
                        <option value="Course Module" {{ $subCat === 'Course Module' ? 'selected' : '' }}>Course Module / Learning Material</option>
                        <option value="Institutional Publication" {{ $subCat === 'Institutional Publication' ? 'selected' : '' }}>Institutional Publication / Journal</option>
                    </select>
                </div>
                <div>
                    <label for="published_year" class="block text-sm font-medium text-gray-700 mb-1">Published Year</label>
                    <input type="number" id="published_year" name="published_year" value="{{ $book->published_year }}" min="1000" max="{{ date('Y') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <input type="text" id="language" name="language" value="{{ $book->language }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="borrow_days" class="block text-sm font-medium text-gray-700 mb-1">
                        <i class="fas fa-clock text-blue-600 mr-1"></i>Max Borrowing Duration (Days)
                    </label>
                    @php($bDays = old('borrow_days', $book->borrow_days ?? 5))
                    <select id="borrow_days" name="borrow_days" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium text-gray-900 bg-blue-50/50">
                        <option value="1" {{ (int)$bDays === 1 ? 'selected' : '' }}>1 Day (Overnight)</option>
                        <option value="3" {{ (int)$bDays === 3 ? 'selected' : '' }}>3 Days</option>
                        <option value="5" {{ (int)$bDays === 5 ? 'selected' : '' }}>5 Days (Standard)</option>
                        <option value="7" {{ (int)$bDays === 7 ? 'selected' : '' }}>7 Days (1 Week)</option>
                        <option value="14" {{ (int)$bDays === 14 ? 'selected' : '' }}>14 Days (2 Weeks)</option>
                        <option value="30" {{ (int)$bDays === 30 ? 'selected' : '' }}>30 Days (1 Month)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('librarian.books.show', $book->id) }}" class="px-5 py-3 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200">Cancel</a>
            <button type="submit" class="px-5 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 flex items-center gap-2" id="saveBtn">
                <span>Save Changes</span>
                <i class="fas fa-spinner fa-spin hidden" id="saveSpinner"></i>
            </button>
        </div>
    </form>
</div>

<script>
    // Preview cover when file changes
    const fileInput = document.getElementById('cover_photo');
    const coverPreview = document.getElementById('coverPreview');
    const coverPlaceholder = document.getElementById('coverPlaceholder');
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            coverPreview.src = url;
            coverPreview.classList.remove('hidden');
            if (coverPlaceholder) coverPlaceholder.classList.add('hidden');
        });
    }

    const form = document.getElementById('editBookForm');
    const alertBox = document.getElementById('alertBox');
    const saveBtn = document.getElementById('saveBtn');
    const saveSpinner = document.getElementById('saveSpinner');

    function showAlert(message, type = 'success') {
        alertBox.className = '';
        alertBox.classList.add('mb-4', 'p-4', 'rounded-lg', 'border');
        if (type === 'success') {
            alertBox.classList.add('bg-green-50', 'border-green-200');
        } else {
            alertBox.classList.add('bg-red-50', 'border-red-200');
        }
        alertBox.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle text-green-600' : 'fa-exclamation-circle text-red-600'} text-xl"></i>
                <div class="ml-3 text-sm ${type === 'success' ? 'text-green-800' : 'text-red-800'}">${message}</div>
            </div>
        `;
        alertBox.classList.remove('hidden');
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const url = `{{ route('librarian.books.update', $book->id) }}`;
        const data = new FormData(form);
        saveBtn.setAttribute('disabled', 'disabled');
        saveSpinner.classList.remove('hidden');

        try {
            const res = await fetch(url, {
                method: 'POST', // method spoofed via _method=PUT
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: data
            });
            const json = await res.json();
            if (!res.ok || json.success === false) {
                const msg = json.message || (json.errors ? Object.values(json.errors).flat().join('\n') : 'Failed to update book');
                showAlert(msg, 'error');
            } else {
                showAlert('Book updated successfully', 'success');
                // Redirect back to book details page after 1 second
                setTimeout(() => {
                    window.location.href = `{{ route('librarian.books.show', $book->id) }}`;
                }, 1000);
            }
        } catch (err) {
            console.error(err);
            showAlert('An error occurred while saving. Please try again.', 'error');
        } finally {
            saveBtn.removeAttribute('disabled');
            saveSpinner.classList.add('hidden');
        }
    });
</script>
@endsection

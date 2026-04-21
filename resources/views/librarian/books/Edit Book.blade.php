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
                <div class="aspect-[3/4] w-full rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center border mb-3">
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
                <div>
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
                    <label for="publisher" class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                    <input type="text" id="publisher" name="publisher" value="{{ $book->publisher_name }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="published_year" class="block text-sm font-medium text-gray-700 mb-1">Published Year</label>
                    <input type="number" id="published_year" name="published_year" value="{{ $book->published_year }}" min="1000" max="{{ date('Y') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 mb-1">Language</label>
                    <input type="text" id="language" name="language" value="{{ $book->language }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="5" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $book->description }}</textarea>
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

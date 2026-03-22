<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'librarian';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'published_year' => 'nullable|integer|min:1000|max:' . (date('Y') + 1),
            'isbn' => 'nullable|string|max:20|unique:books,isbn',
            'language' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:100',
            'course' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ebook_file' => 'nullable|file|mimes:pdf,epub|max:10240', // 10MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The book title is required.',
            'title.max' => 'The book title must not exceed 255 characters.',
            'author.required' => 'The author name is required.',
            'author.max' => 'The author name must not exceed 255 characters.',
            'publisher.max' => 'The publisher name must not exceed 255 characters.',
            'published_year.integer' => 'The published year must be a valid year.',
            'published_year.min' => 'The published year must be at least 1000.',
            'published_year.max' => 'The published year cannot be in the future.',
            'isbn.max' => 'The ISBN must not exceed 20 characters.',
            'isbn.unique' => 'This ISBN is already registered in the system.',
            'language.max' => 'The language must not exceed 50 characters.',
            'category.max' => 'The category must not exceed 100 characters.',
            'course.max' => 'The course must not exceed 100 characters.',
            'description.max' => 'The description must not exceed 2000 characters.',
            'cover_photo.image' => 'The cover photo must be an image file.',
            'cover_photo.mimes' => 'The cover photo must be a JPEG, PNG, JPG, or GIF file.',
            'cover_photo.max' => 'The cover photo must not exceed 2MB in size.',
            'ebook_file.file' => 'The ebook file must be a valid file.',
            'ebook_file.mimes' => 'The ebook file must be a PDF or EPUB file.',
            'ebook_file.max' => 'The ebook file must not exceed 10MB in size.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'title' => 'book title',
            'author' => 'author name',
            'publisher' => 'publisher',
            'published_year' => 'published year',
            'isbn' => 'ISBN',
            'language' => 'language',
            'category' => 'category',
            'course' => 'course',
            'description' => 'description',
            'cover_photo' => 'cover photo',
            'ebook_file' => 'ebook file',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up ISBN by removing spaces and hyphens
        if ($this->isbn) {
            $this->merge([
                'isbn' => preg_replace('/[\s\-]/', '', $this->isbn)
            ]);
        }

        // Set default language if not provided
        if (!$this->language) {
            $this->merge([
                'language' => 'English'
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate book by title and author combination
            if ($this->title && $this->author) {
                $existingBook = \App\Models\Book::where('title', $this->title)
                    ->where('author', $this->author)
                    ->first();

                if ($existingBook) {
                    $validator->errors()->add('title', 'A book with this title and author already exists in the system.');
                }
            }
        });
    }
}

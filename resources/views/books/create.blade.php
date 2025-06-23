@extends('layouts.app')

@section('title', 'Add New Book - CEO Routine Tracker')

@section('content')
    <div class="create-book-page">
        <!-- Breadcrumb -->
        <nav class="breadcrumb mb-4">
            <a href="{{ route('books.index') }}" class="breadcrumb-link">
                <i class="fas fa-book"></i> Library
            </a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Add New Book</span>
        </nav>

        <!-- Page Header -->
        <div class="page-header mb-6">
            <h1 class="page-title">
                <i class="fas fa-plus"></i>
                Add New Book
            </h1>
            <p class="text-muted">Add a new book to your reading library</p>
        </div>

        <div class="grid grid-2 gap-6">
            <!-- Main Form -->
            <div class="book-form-section">
                <form method="POST" action="{{ route('books.store') }}" id="bookForm">
                    @csrf

                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </div>
                        <div class="card-content">
                            <!-- Title -->
                            <div class="form-group">
                                <label class="form-label required">Title</label>
                                <input type="text" name="title" class="form-input @error('title') error @enderror"
                                    value="{{ old('title') }}" placeholder="Enter book title" required>
                                @error('title')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Author -->
                            <div class="form-group">
                                <label class="form-label required">Author</label>
                                <input type="text" name="author" class="form-input @error('author') error @enderror"
                                    value="{{ old('author') }}" placeholder="Enter author name" required>
                                @error('author')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- ISBN -->
                            <div class="form-group">
                                <label class="form-label">ISBN</label>
                                <input type="text" name="isbn" class="form-input @error('isbn') error @enderror"
                                    value="{{ old('isbn') }}" placeholder="ISBN-10 or ISBN-13">
                                @error('isbn')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-textarea @error('description') error @enderror" rows="4"
                                    placeholder="Brief description or synopsis of the book">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-tags"></i>
                            Categorization
                        </div>
                        <div class="card-content">
                            <div class="grid grid-2 gap-4">
                                <!-- Category -->
                                <div class="form-group">
                                    <label class="form-label required">Category</label>
                                    <select name="category" class="form-select @error('category') error @enderror" required>
                                        <option value="">Select category...</option>
                                        <option value="business" {{ old('category') === 'business' ? 'selected' : '' }}>
                                            Business</option>
                                        <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>
                                            Technical</option>
                                        <option value="personal_development"
                                            {{ old('category') === 'personal_development' ? 'selected' : '' }}>Personal
                                            Development</option>
                                        <option value="leadership"
                                            {{ old('category') === 'leadership' ? 'selected' : '' }}>Leadership</option>
                                        <option value="strategy" {{ old('category') === 'strategy' ? 'selected' : '' }}>
                                            Strategy</option>
                                        <option value="biography" {{ old('category') === 'biography' ? 'selected' : '' }}>
                                            Biography</option>
                                        <option value="fiction" {{ old('category') === 'fiction' ? 'selected' : '' }}>
                                            Fiction</option>
                                        <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Other
                                        </option>
                                    </select>
                                    @error('category')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Priority -->
                                <div class="form-group">
                                    <label class="form-label required">Priority</label>
                                    <select name="priority" class="form-select @error('priority') error @enderror" required>
                                        <option value="">Select priority...</option>
                                        <option value="1" {{ old('priority') == '1' ? 'selected' : '' }}>1 - Very Low
                                        </option>
                                        <option value="2" {{ old('priority') == '2' ? 'selected' : '' }}>2 - Low
                                        </option>
                                        <option value="3" {{ old('priority') == '3' ? 'selected' : '' }}>3 - Medium
                                        </option>
                                        <option value="4" {{ old('priority') == '4' ? 'selected' : '' }}>4 - High
                                        </option>
                                        <option value="5" {{ old('priority') == '5' ? 'selected' : '' }}>5 - Very High
                                        </option>
                                    </select>
                                    @error('priority')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tags -->
                            <div class="form-group">
                                <label class="form-label">Tags</label>
                                <input type="text" name="tags" class="form-input @error('tags') error @enderror"
                                    value="{{ old('tags') }}"
                                    placeholder="Enter tags separated by commas (e.g., productivity, entrepreneurship, AI)">
                                <div class="form-help">Separate multiple tags with commas</div>
                                @error('tags')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-book-open"></i>
                            Book Details
                        </div>
                        <div class="card-content">
                            <div class="grid grid-2 gap-4">
                                <!-- Total Pages -->
                                <div class="form-group">
                                    <label class="form-label required">Total Pages</label>
                                    <input type="number" name="total_pages"
                                        class="form-input @error('total_pages') error @enderror"
                                        value="{{ old('total_pages') }}" min="1" placeholder="e.g., 320"
                                        required>
                                    @error('total_pages')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Format -->
                                <div class="form-group">
                                    <label class="form-label required">Format</label>
                                    <select name="format" class="form-select @error('format') error @enderror" required>
                                        <option value="">Select format...</option>
                                        <option value="physical" {{ old('format') === 'physical' ? 'selected' : '' }}>
                                            Physical Book</option>
                                        <option value="ebook" {{ old('format') === 'ebook' ? 'selected' : '' }}>E-book
                                        </option>
                                        <option value="audiobook" {{ old('format') === 'audiobook' ? 'selected' : '' }}>
                                            Audiobook</option>
                                        <option value="pdf" {{ old('format') === 'pdf' ? 'selected' : '' }}>PDF
                                        </option>
                                    </select>
                                    @error('format')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Cover Image URL -->
                            <div class="form-group">
                                <label class="form-label">Cover Image URL</label>
                                <input type="url" name="cover_image_url"
                                    class="form-input @error('cover_image_url') error @enderror"
                                    value="{{ old('cover_image_url') }}" placeholder="https://example.com/book-cover.jpg"
                                    onchange="previewCover(this.value)">
                                @error('cover_image_url')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Purchase Information -->
                            <div class="grid grid-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Purchase URL</label>
                                    <input type="url" name="purchase_url"
                                        class="form-input @error('purchase_url') error @enderror"
                                        value="{{ old('purchase_url') }}" placeholder="https://amazon.com/...">
                                    @error('purchase_url')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Price</label>
                                    <input type="number" name="price"
                                        class="form-input @error('price') error @enderror" value="{{ old('price') }}"
                                        step="0.01" min="0" placeholder="29.99">
                                    @error('price')
                                        <div class="form-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="form-group">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-textarea @error('notes') error @enderror" rows="3"
                                    placeholder="Any additional notes about this book">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Book
                        </button>
                        <button type="button" onclick="startReading()" class="btn btn-success" style="display: none;"
                            id="startReadingBtn">
                            <i class="fas fa-play"></i> Add & Start Reading
                        </button>
                        <a href="{{ route('books.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>

            <!-- Preview Section -->
            <div class="book-preview-section">
                <div class="card sticky">
                    <div class="card-header">
                        <i class="fas fa-eye"></i>
                        Preview
                    </div>
                    <div class="card-content">
                        <div class="book-preview">
                            <div class="preview-cover" id="previewCover">
                                <div class="cover-placeholder">
                                    <i class="fas fa-book"></i>
                                </div>
                            </div>

                            <div class="preview-info">
                                <h3 class="preview-title" id="previewTitle">Book Title</h3>
                                <p class="preview-author" id="previewAuthor">by Author Name</p>

                                <div class="preview-meta">
                                    <span class="preview-category" id="previewCategory">Category</span>
                                    <span class="preview-pages" id="previewPages">0 pages</span>
                                    <span class="preview-format" id="previewFormat">Format</span>
                                </div>

                                <div class="preview-priority" id="previewPriority">
                                    Priority: <span class="priority-value">Medium</span>
                                </div>

                                <div class="preview-description" id="previewDescription">
                                    Book description will appear here...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-lightbulb"></i>
                        Quick Tips
                    </div>
                    <div class="card-content">
                        <div class="tips-list">
                            <div class="tip-item">
                                <i class="fas fa-star text-warning"></i>
                                <span>Use <strong>High Priority (4-5)</strong> for books you want to read soon</span>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-clock text-info"></i>
                                <span>The system tracks your <strong>30-minute daily reading</strong> sessions</span>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-chart-line text-success"></i>
                                <span>Progress tracking helps maintain your <strong>CEO excellence targets</strong></span>
                            </div>
                            <div class="tip-item">
                                <i class="fas fa-tags text-primary"></i>
                                <span>Use tags to organize books by topics or themes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .breadcrumb-link {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb-separator {
            color: var(--text-muted);
        }

        .book-form-section {
            max-width: none;
        }

        .book-preview-section .card.sticky {
            position: sticky;
            top: 100px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-label.required::after {
            content: ' *';
            color: var(--danger-color);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: var(--transition);
            background-color: white;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-input.error,
        .form-select.error,
        .form-textarea.error {
            border-color: var(--danger-color);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-error {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .form-help {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .book-preview {
            text-align: center;
        }

        .preview-cover {
            width: 100%;
            max-width: 200px;
            height: 280px;
            margin: 0 auto 1.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .preview-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cover-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .preview-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .preview-author {
            color: var(--text-secondary);
            font-style: italic;
            margin-bottom: 1rem;
        }

        .preview-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .preview-category {
            background-color: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
        }

        .preview-pages {
            color: var(--text-muted);
        }

        .preview-format {
            color: var(--text-secondary);
        }

        .preview-priority {
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .priority-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .preview-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.5;
            text-align: left;
        }

        .tips-list {
            space-y: 1rem;
        }

        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }

        .tip-item:last-child {
            margin-bottom: 0;
        }

        .tip-item i {
            margin-top: 0.125rem;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .book-preview-section .card.sticky {
                position: static;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview functionality
            const titleInput = document.querySelector('input[name="title"]');
            const authorInput = document.querySelector('input[name="author"]');
            const categorySelect = document.querySelector('select[name="category"]');
            const pagesInput = document.querySelector('input[name="total_pages"]');
            const formatSelect = document.querySelector('select[name="format"]');
            const prioritySelect = document.querySelector('select[name="priority"]');
            const descriptionTextarea = document.querySelector('textarea[name="description"]');

            // Update preview in real-time
            if (titleInput) {
                titleInput.addEventListener('input', function() {
                    document.getElementById('previewTitle').textContent = this.value || 'Book Title';
                });
            }

            if (authorInput) {
                authorInput.addEventListener('input', function() {
                    document.getElementById('previewAuthor').textContent = 'by ' + (this.value ||
                        'Author Name');
                });
            }

            if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    const category = this.value || 'Category';
                    const categoryLabel = this.options[this.selectedIndex].text || 'Category';
                    document.getElementById('previewCategory').textContent = categoryLabel;
                });
            }

            if (pagesInput) {
                pagesInput.addEventListener('input', function() {
                    const pages = this.value || '0';
                    document.getElementById('previewPages').textContent = pages + ' pages';
                });
            }

            if (formatSelect) {
                formatSelect.addEventListener('change', function() {
                    const format = this.options[this.selectedIndex].text || 'Format';
                    document.getElementById('previewFormat').textContent = format;
                });
            }

            if (prioritySelect) {
                prioritySelect.addEventListener('change', function() {
                    const priority = this.options[this.selectedIndex].text || 'Priority';
                    document.querySelector('.priority-value').textContent = priority.replace(/^\d+ - /, '');
                });
            }

            if (descriptionTextarea) {
                descriptionTextarea.addEventListener('input', function() {
                    const description = this.value || 'Book description will appear here...';
                    document.getElementById('previewDescription').textContent = description;
                });
            }

            // Show "Add & Start Reading" button for high priority books
            if (prioritySelect) {
                prioritySelect.addEventListener('change', function() {
                    const startReadingBtn = document.getElementById('startReadingBtn');
                    if (this.value >= 4) {
                        startReadingBtn.style.display = 'inline-flex';
                    } else {
                        startReadingBtn.style.display = 'none';
                    }
                });
            }
        });

        // Preview cover image
        function previewCover(url) {
            const previewCover = document.getElementById('previewCover');

            if (url) {
                // Test if the URL is valid by creating an image
                const img = new Image();
                img.onload = function() {
                    previewCover.innerHTML = `<img src="${url}" alt="Book Cover">`;
                };
                img.onerror = function() {
                    // If image fails to load, show placeholder
                    previewCover.innerHTML = `
                <div class="cover-placeholder">
                    <i class="fas fa-book"></i>
                </div>
            `;
                };
                img.src = url;
            } else {
                previewCover.innerHTML = `
            <div class="cover-placeholder">
                <i class="fas fa-book"></i>
            </div>
        `;
            }
        }

        // Add and start reading
        function startReading() {
            const form = document.getElementById('bookForm');
            const startReadingInput = document.createElement('input');
            startReadingInput.type = 'hidden';
            startReadingInput.name = 'start_reading';
            startReadingInput.value = '1';
            form.appendChild(startReadingInput);
            form.submit();
        }

        // Form validation enhancement
        document.getElementById('bookForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                CEOTracker.showError('Please fill in all required fields');

                // Scroll to first error
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstError.focus();
                }
            }
        });

        // Auto-suggest book information (mock implementation)
        let debounceTimer;
        document.querySelector('input[name="title"]').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const title = this.value.trim();

            if (title.length > 3) {
                debounceTimer = setTimeout(() => {
                    // This could integrate with a real book API like Google Books
                    console.log('Would search for book:', title);
                }, 500);
            }
        });
    </script>
@endpush

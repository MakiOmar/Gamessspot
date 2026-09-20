@extends('layouts.admin')
@section('plugins.Summernote', true)
@push('css')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
    #categoryModal .modal-dialog {
        max-width: min(1140px, 96vw);
    }
    #categoryModal .note-editable {
        min-height: 180px;
    }
</style>
@endpush

@push('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
@endpush

@section('content')
<div class="container">
    <h1 class="my-4">Card Categories</h1>

    <!-- Button to create a new category -->
    <button type="button" class="btn btn-success mb-3" onclick="openCreateModal()">Create New Category</button>

    <table id="categoriesTable" class="table table-striped table-hover card-categories-reponsive">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Price</th>
                <th>Poster Image</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="categoriesTableBody">
            @foreach($categories as $category)
            <tr id="categoryRow_{{ $category->id }}">
                <td>{{ $category->id }}</td>
                <td>{{ $category->name }}</td>
                <td>{{ $category->price }}</td>
                <td>
                    @if($category->poster_image)
                    <img src="{{ asset($category->poster_image) }}" alt="Poster" width="50" height="50">
                    @else
                    N/A
                    @endif
                </td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="openEditModal({{ $category->id }})">Edit</button>
                    <button class="btn btn-danger btn-sm" onclick="confirmDelete({{ $category->id }})">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal for Create/Edit Category -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form id="categoryForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Create Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="categoryId">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="6" placeholder="Write the category description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="poster_image" class="form-label">Poster Image</label>
                        <input type="file" class="form-control" id="poster_image" name="poster_image" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="gallery_images" class="form-label">Gallery Images</label>
                        <input type="file" class="form-control" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                        <small class="text-muted">Up to 8 images.</small>
                        <div id="existingCategoryGallery" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCategoryBtn" onclick="submitCategoryForm()">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('js')
<script>
    jQuery(document).ready(function($) {
        $('#categoriesTable').DataTable({
            responsive: false,
            autoWidth: false,
            pageLength: 10
        });

        function ensureCategoryDescriptionEditor() {
            if (typeof $.fn.summernote !== 'function') {
                return;
            }
            if (!$('#description').data('summernote')) {
                $('#description').summernote({
                    height: 200,
                    dialogsInBody: true,
                    placeholder: 'Write the category description...',
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link']],
                        ['view', ['codeview']]
                    ]
                });
            }
        }

        window.setCategoryDescription = function(html) {
            if (typeof $.fn.summernote === 'function') {
                ensureCategoryDescriptionEditor();
                $('#description').summernote('code', html || '');
                return;
            }
            $('#description').val(html || '');
        };

        window.renderCategoryGallery = function(images) {
            var $wrap = $('#existingCategoryGallery');
            $wrap.empty();
            (images || []).forEach(function (img) {
                var url = img.url || ('/' + img.path);
                $wrap.append(
                    '<label class="border rounded p-1 text-center" style="width:100px;">' +
                        '<img src="' + url + '" class="img-thumbnail mb-1" style="max-width:90px;max-height:90px;">' +
                        '<div class="form-check">' +
                            '<input class="form-check-input" type="checkbox" name="delete_gallery_ids[]" value="' + img.id + '">' +
                            '<span class="small">Remove</span>' +
                        '</div>' +
                    '</label>'
                );
            });
        };

        $('#categoryModal').on('shown.bs.modal', function () {
            ensureCategoryDescriptionEditor();
        });
    });

    function openCreateModal() {
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryId').value = '';
        document.getElementById('categoryModalLabel').innerText = 'Create Category';
        if (window.setCategoryDescription) {
            window.setCategoryDescription('');
        }
        if (window.renderCategoryGallery) {
            window.renderCategoryGallery([]);
        }
        new bootstrap.Modal(document.getElementById('categoryModal')).show();
    }

    function openEditModal(id) {
        fetch(`/manager/card-categories/${id}/edit`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('categoryId').value = data.id;
                document.getElementById('name').value = data.name;
                document.getElementById('price').value = data.price;
                document.getElementById('categoryModalLabel').innerText = 'Edit Category';
                if (window.setCategoryDescription) {
                    window.setCategoryDescription(data.description || '');
                }
                if (window.renderCategoryGallery) {
                    window.renderCategoryGallery(data.gallery || []);
                }
                new bootstrap.Modal(document.getElementById('categoryModal')).show();
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to load category details. Please try again later.'
                });
                console.error('Error:', error);
            });
    }

    function submitCategoryForm() {
        if (jQuery('#description').data('summernote')) {
            jQuery('#description').val(jQuery('#description').summernote('code'));
        }
        let formData = new FormData(document.getElementById('categoryForm'));
        let categoryId = document.getElementById('categoryId').value;
        let url = categoryId ? `/manager/card-categories/${categoryId}` : '/manager/card-categories';
        let method = categoryId ? 'PUT' : 'POST';
        formData.append('_method', method);
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: formData
        }).then(response => response.json()).then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message,
                    showConfirmButton: false,
                    timer: 1500
                });
                location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to save category'
                });
            }
        }).catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An unexpected error occurred. Please try again later.'
            });
            console.error('Error:', error);
        });
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deleteCategory(id);
            }
        });
    }

    function deleteCategory(id) {
        fetch(`/manager/card-categories/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Deleted', text: data.message, timer: 1200, showConfirmButton: false });
                document.getElementById(`categoryRow_${id}`).remove();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to delete category. Please try again later.'
            });
            console.error('Error:', error);
        });
    }
</script>
@endpush

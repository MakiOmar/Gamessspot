@extends('layouts.admin')

@section('content')
<div class="container">
    <h1 class="my-4">Card Categories</h1>

    <!-- Button to create a new category -->
    <button type="button" class="btn btn-success mb-3" onclick="openCreateModal()">Create New Category</button>

    <!-- Card Categories Table -->
    <table class="table table-striped table-hover">
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
    <div class="modal-dialog">
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
                        <label for="poster_image" class="form-label">Poster Image</label>
                        <input type="file" class="form-control" id="poster_image" name="poster_image" accept="image/*">
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
        // Open Create Modal
        function openCreateModal() {
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryModalLabel').innerText = 'Create Category';
            new bootstrap.Modal(document.getElementById('categoryModal')).show();
        }

        // Open Edit Modal
        function openEditModal(id) {
            fetch(`/manager/card-categories/${id}/edit`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('categoryId').value = data.id;
                document.getElementById('name').value = data.name;
                document.getElementById('price').value = data.price; // Use price instead of description
                document.getElementById('categoryModalLabel').innerText = 'Edit Category';
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

        // Submit the Create/Edit form using AJAX
        function submitCategoryForm() {
            let formData = new FormData(document.getElementById('categoryForm'));
            let categoryId = document.getElementById('categoryId').value;
            let url = categoryId ? `/manager/card-categories/${categoryId}` : '/manager/card-categories';
            let method = categoryId ? 'PUT' : 'POST';
            formData.append('_method', method);
            fetch(url, {
                method: 'POST',
                contentType: false, // Required for file uploads
                processData: false, // Required for file uploads
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
                    location.reload(); // Refresh the page to update the table (or manipulate the DOM to update without reload)
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

        // Confirm Delete with Swal and Delete Category
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

        // Delete category using AJAX
        function deleteCategory(id) {
            fetch(`/manager/card-categories/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
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

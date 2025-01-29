@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Cards List</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCardModal">
            Add New Card
        </button>
    </div>

    <!-- Table listing the cards -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Category Name</th>
                <th>Card Code</th>
                <th>Card Cost</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cards as $card)
                <tr>
                    <td>{{ $card->category->name ?? 'N/A' }}</td>
                    <td>{{ $card->code }}</td>
                    <td>${{ number_format($card->cost, 2) }}</td>
                    <td>
                        @if($card->status)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Sold</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No cards available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Add Card Modal -->
<div class="modal fade" id="addCardModal" tabindex="-1" aria-labelledby="addCardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCardModalLabel">Add New Card</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addCardForm">
                    @csrf
                    <div class="mb-3">
                        <label for="code" class="form-label">Card Code</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label for="cost" class="form-label">Card Cost</label>
                        <input type="number" class="form-control" id="cost" name="cost" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="card_category_id" class="form-label">Category</label>
                        <select class="form-control" id="card_category_id" name="card_category_id" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitCardForm()">Save Card</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
<script>
    function submitCardForm() {
        let formData = new FormData(document.getElementById('addCardForm'));
        jQuery.ajax({
            url: "{{ route('cards.store') }}", // Adjust route as needed
            type: 'POST',
            data: formData,
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            success: function(data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    jQuery('#addCardModal').modal('hide'); // Close the modal
                    location.reload(); // Refresh the page to update the table (you could also update the DOM without a reload)
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to save card. Please try again.'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An unexpected error occurred. Please try again later.'
                });
                console.error('Error:', error);
            }
        });
    }
</script>

@endpush
@extends('layouts.admin')

@section('title', 'Manager - Games')

@section('content')
    <div class="container mt-5">
        <h1 class="text-center mb-4">Games Management</h1>

        <!-- Scrollable table container -->
        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            <table class="table table-striped table-bordered" style="min-width: 800px;">
                <thead>
                    <tr>
                        <th style="width: 21px;">id</th>
                        <th style="width: 388px;">Game Name</th>
                        <th style="width: 286px;">Code</th>
                        <th style="width: 98px;">Reports</th>
                        <th style="width: 73px;">Edit</th>
                        <th style="width: 106px;">ps4 Poster Edit</th>
                        <th style="width: 106px;">ps5 Poster Edit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($games as $game)
                        <tr>
                            <td>{{ $game->id }}</td>
                            <td>{{ $game->title }}</td>
                            <td>{{ $game->code }}</td>
                            <td><a href="#">View Reports</a></td>
                            <td><a href="#" class="btn btn-primary edit-game" data-id="{{ $game->id }}" data-bs-toggle="modal" data-bs-target="#editGameModal">Edit</a></td>
                            <td><a href="#">Edit PS4 Poster</a></td>
                            <td><a href="#">Edit PS5 Poster</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination links -->
        <div class="d-flex justify-content-center mt-4">
            {{ $games->links('vendor.pagination.bootstrap-5') }}
        </div>
    </div>

    <!-- Edit Game Modal -->
    <div class="modal fade" id="editGameModal" tabindex="-1" aria-labelledby="editGameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGameModalLabel">Edit Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editGameForm">
                        @csrf
                        <input type="hidden" id="gameId" name="gameId">

                        <!-- Game Name -->
                        <label for="gameName">Game Name</label>
                        <input type="text" id="gameName" name="title" class="form-control" style="border-radius: 10px;" required>

                        <!-- Game Code -->
                        <label for="gameCode" class="mt-3">Game Code</label>
                        <input type="text" id="gameCode" name="code" class="form-control" style="border-radius: 10px;" required>

                        <!-- Full Price -->
                        <label for="fullPrice" class="mt-3">Full Price</label>
                        <input type="number" id="fullPrice" name="full_price" class="form-control" style="border-radius: 10px;" required>

                        <!-- Save Button -->
                        <button type="submit" class="btn btn-success mt-3">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

<script>
    $(document).ready(function() {
        // Set up AJAX to include CSRF token in every request
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // When the "Edit" button is clicked
        $('.edit-game').on('click', function() {
            var gameId = $(this).data('id');

            // Use AJAX to fetch the game data
            $.ajax({
                url: '/manager/games/' + gameId + '/edit',
                method: 'GET',
                success: function(response) {
                    // Populate the form fields with the game data
                    $('#gameId').val(response.id);
                    $('#gameName').val(response.title);
                    $('#gameCode').val(response.code);
                    $('#fullPrice').val(response.full_price);
                }
            });
        });

        // Handle form submission via AJAX
        $('#editGameForm').on('submit', function(e) {
            e.preventDefault();

            var gameId = $('#gameId').val();
            var formData = $(this).serialize();

            $.ajax({
                url: '/manager/games/' + gameId,
                method: 'PUT',
                data: formData,
                success: function(response) {
                    // Handle success (reload the page or update the table row)
                    alert('Game updated successfully!');
                    location.reload();
                },
                error: function(xhr) {
                    // Handle error
                    alert('An error occurred.');
                }
            });
        });
    });
</script>

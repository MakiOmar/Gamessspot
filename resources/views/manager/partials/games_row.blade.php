<table class="table table-striped table-bordered games-reponsive-table">
    <thead>
        <tr>
            <th>id</th>
            <th>Game Name</th>
            <th>Code</th>
            <th>Reports</th>
            <th>Edit</th>
        </tr>
    </thead>
    <tbody>
        @foreach($games as $game)
            <tr>
                <td>{{ $game->id }}</td>
                <td><span class="wraptext" style="max-width:90%;">{{ $game->title }}<span></td>
                <td>{{ $game->code }}</td>
                <td><a href="#">View Reports</a></td>
                <td><a href="#" class="btn btn-primary edit-game" data-id="{{ $game->id }}" data-bs-toggle="modal" data-bs-target="#editGameModal">Edit</a></td>
            </tr>
        @endforeach
    </tbody>
</table>
<!-- Pagination links -->
<div class="d-flex justify-content-center mt-4">
    {{ $games->links('vendor.pagination.bootstrap-5') }}
</div>

<table class="table table-striped table-bordered" style="min-width: 800px;">
    <thead>
        <tr>
            <th style="width: 21px;">id</th>
            <th style="width: 388px;">Game Name</th>
            <th style="width: 286px;">Code</th>
            <th style="width: 98px;">Reports</th>
            <th style="width: 73px;">Edit</th>
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
            </tr>
        @endforeach
    </tbody>
</table>
<!-- Pagination links -->
<div class="d-flex justify-content-center mt-4">
    {{ $games->links('vendor.pagination.bootstrap-5') }}
</div>

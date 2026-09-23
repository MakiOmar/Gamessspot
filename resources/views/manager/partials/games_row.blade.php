<table class="table table-striped table-bordered games-reponsive-table">
    <thead>
        <tr>
            <th>id</th>
            <th>Game Name</th>
            <th>Code</th>
            <th>Reports</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($games as $game)
            <tr>
                <td>{{ $game->id }}</td>
                <td>
                    <span class="wraptext" style="max-width:90%;">{{ $game->title }}</span>
                    @if(($game->product_type ?? 'game') === 'subscription')
                        <span class="badge bg-info text-dark ms-1">Subscription</span>
                    @endif
                </td>
                <td>{{ $game->code }}</td>
                <td><a href="#">View Reports</a></td>
                <td>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <!-- Toggle featured for public catalog -->
                        <button type="button"
                            class="btn btn-sm toggle-featured {{ $game->is_featured ? 'btn-warning' : 'btn-outline-secondary' }}"
                            data-id="{{ $game->id }}"
                            data-featured="{{ $game->is_featured ? '1' : '0' }}"
                            title="{{ $game->is_featured ? 'Remove from featured' : 'Set as featured' }}">
                            <i class="{{ $game->is_featured ? 'fas' : 'far' }} fa-star"></i>
                        </button>
                        <a href="#" class="btn btn-primary btn-sm edit-game" data-id="{{ $game->id }}" data-bs-toggle="modal" data-bs-target="#editGameModal">Edit</a>
                        @if(auth()->user()->roles->contains('name', 'admin'))
                            <button type="button"
                                class="btn btn-danger btn-sm delete-game"
                                data-id="{{ $game->id }}"
                                data-title="{{ $game->title }}">
                                Delete
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<!-- Pagination links -->
<div class="d-flex justify-content-center mt-4">
    {{ $games->links('vendor.pagination.bootstrap-5') }}
</div>

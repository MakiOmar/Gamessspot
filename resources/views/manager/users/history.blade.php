@extends('layouts.admin')

@section('title', 'User History - ' . $user->name)

@section('content')
<div class="container mt-5">
    {{-- Header: whose history this is, with phone for quick identification --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1">User History</h1>
            <div class="text-muted">
                {{ $user->name }}
                @if ($user->phone)
                    &middot; {{ $user->phone }}
                @endif
                @if ($user->email)
                    &middot; {{ $user->email }}
                @endif
            </div>
        </div>
        @can('view-sell-log')
            {{-- Shortcut to this customer's orders --}}
            @if ($user->phone)
                <a href="{{ route('manager.orders', ['search' => $user->phone, 'start_date' => '2000-01-01', 'end_date' => date('Y-m-d', strtotime('+1 year'))]) }}"
                   class="btn btn-outline-info btn-sm">
                    <i class="bi bi-receipt"></i> Orders
                </a>
            @endif
        @endcan
    </div>

    {{-- Add-note form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('manager.users.history.store', $user) }}" id="userNoteForm">
                @csrf
                <label for="userNoteInput" class="form-label">Add a note</label>
                <textarea name="note" id="userNoteInput" rows="3" maxlength="5000"
                          class="form-control @error('note') is-invalid @enderror"
                          placeholder="Any side info about this user..." required>{{ old('note') }}</textarea>
                @error('note')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-primary" id="userNoteSubmit">
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                        Save note
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Notes list, newest first --}}
    <div class="card">
        <div class="card-body">
            @forelse ($notes as $note)
                <div class="border-bottom py-3" id="userNote-{{ $note->id }}">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="small text-muted">
                            {{ $note->author?->name ?? 'Deleted user' }} &middot; {{ $note->created_at?->format('Y-m-d H:i') }}
                        </div>
                        @if ($note->canBeDeletedBy(auth('admin')->user()))
                            <button type="button" class="btn btn-outline-danger btn-sm delete-user-note"
                                    data-url="{{ route('manager.users.history.destroy', [$user, $note]) }}"
                                    data-note-id="{{ $note->id }}">
                                Delete
                            </button>
                        @endif
                    </div>
                    <div class="mt-1" style="white-space: pre-wrap;">{{ $note->note }}</div>
                </div>
            @empty
                {{-- Empty state --}}
                <div class="text-center text-muted py-4" id="userNotesEmpty">
                    No history notes yet. Add the first one above.
                </div>
            @endforelse

            <div class="mt-3">
                {{ $notes->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    jQuery(function ($) {
        function toast(icon, title) {
            Swal.fire({ toast: true, position: 'top-end', icon: icon, title: title, showConfirmButton: false, timer: 3000 });
        }

        @if (session('success'))
            toast('success', @json(session('success')));
        @endif

        // Loading indicator while the note is saved (normal form post)
        $('#userNoteForm').on('submit', function () {
            const $btn = $('#userNoteSubmit');
            $btn.prop('disabled', true).find('.spinner-border').removeClass('d-none');
        });

        $(document).on('click', '.delete-user-note', function () {
            const $btn = $(this);
            Swal.fire({
                title: 'Delete this note?',
                text: 'The note will be removed from this user\'s history.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) {
                    return;
                }
                $btn.prop('disabled', true);
                $.ajax({
                    url: $btn.data('url'),
                    method: 'POST',
                    data: { _token: @json(csrf_token()), _method: 'DELETE' }
                }).done(function (response) {
                    if (response.success) {
                        $('#userNote-' + $btn.data('note-id')).remove();
                        toast('success', 'Note deleted');
                    } else {
                        $btn.prop('disabled', false);
                        toast('error', response.message || 'Could not delete the note');
                    }
                }).fail(function (xhr) {
                    $btn.prop('disabled', false);
                    toast('error', (xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete the note');
                });
            });
        });
    });
</script>
@endpush

@extends('layouts.admin')
@push('css')
<style>
    @media screen and ( min-width:1200px ){
        #accounts-table{
            min-width: 1200px;
        }
    }
</style>
@endpush
@section('title', 'Manager - Accounts')

@section('content')
<!-- Region with Emoji -->
@php
    $regionEmojis = config('flags.flags');
@endphp
<div class="container mt-5">
    <h1 class="text-center mb-4">Accounts Management</h1>
    @if ( Auth::user()->roles->contains('name', 'admin') )
        <!-- Add Account Button (Bootstrap 5) -->
        <div class="d-flex justify-content-between mb-4 align-items-end">
            <div class="w-50">
                <div class="alert alert-warning" id="noResultsMessage" style="display: none;">
                    No results found.
                </div>
                <div class="d-flex">
                    <!-- Search Box -->
                    <input type="text" class="form-control" id="searchAccount" placeholder="Search accounts by email or game name">
                    <button id="searchButton" type="button" class="btn btn-primary ms-2">
                        Search
                    </button>
                </div>
            </div>
            <div class="d-flex justify-content-end align-items-center">
                <!-- Add Account Button -->
                <a type="button" id="addAccountButton" data-bs-toggle="modal" data-bs-target="#accountModal">
                    <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 512 512" width="32px" height="32px"><path fill="#32BEA6" d="M7.9,256C7.9,119,119,7.9,256,7.9C393,7.9,504.1,119,504.1,256c0,137-111.1,248.1-248.1,248.1C119,504.1,7.9,393,7.9,256z"/><path fill="#FFF" d="M391.5,214.5H297v-93.9c0-4-3.2-7.2-7.2-7.2h-68.1c-4,0-7.2,3.2-7.2,7.2v93.9h-93.9c-4,0-7.2,3.2-7.2,7.2v69.2c0,4,3.2,7.2,7.2,7.2h93.9v93.4c0,4,3.2,7.2,7.2,7.2h68.1c4,0,7.2-3.2,7.2-7.2v-93.4h94.5c4,0,7.2-3.2,7.2-7.2v-69.2C398.7,217.7,395.4,214.5,391.5,214.5z"/></svg>
                </a>
                @if( Auth::user()->roles->contains('name', 'admin') )
                    <!-- Export Button -->
                    <a href="{{ route('manager.accounts.export') }}" class="d-flex -4 float-right">
                        <svg xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 48 48" width="36px" height="36px"><path fill="#4CAF50" d="M41,10H25v28h16c0.553,0,1-0.447,1-1V11C42,10.447,41.553,10,41,10z"/><path fill="#FFF" d="M32 15H39V18H32zM32 25H39V28H32zM32 30H39V33H32zM32 20H39V23H32zM25 15H30V18H25zM25 25H30V28H25zM25 30H30V33H25zM25 20H30V23H25z"/><path fill="#2E7D32" d="M27 42L6 38 6 10 27 6z"/><path fill="#FFF" d="M19.129,31l-2.411-4.561c-0.092-0.171-0.186-0.483-0.284-0.938h-0.037c-0.046,0.215-0.154,0.541-0.324,0.979L13.652,31H9.895l4.462-7.001L10.274,17h3.837l2.001,4.196c0.156,0.331,0.296,0.725,0.42,1.179h0.04c0.078-0.271,0.224-0.68,0.439-1.22L19.237,17h3.515l-4.199,6.939l4.316,7.059h-3.74V31z"/></svg>
                    </a>
                @endif
            </div>
        </div>

        <!-- Scrollable table container -->
        <div style="overflow-x:auto; max-width: 100%; white-space: nowrap;">
            <table id="accounts-table" class="table table-striped table-bordered accounts-responsive-table">
                <thead>
                    <tr role="row">
                        <th style="width:30px">ID</th>
                        <th>Mail</th>
                        <th>Game Name</th>
                        <th>Region</th> <!-- Region will display an emoji -->
                        <th>Offline (PS4)</th>
                        <th>Primary (PS4)</th>
                        <th>Secondary (PS4)</th>
                        <th>Offline (PS5)</th>
                        <th>Primary (PS5)</th>
                        <th>Secondary (PS5)</th>
                        <th>Cost</th>
                        <th>Password</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="accountTableBody">
                    @include('manager.partials.account_rows', ['accounts' => $accounts])
                </tbody>
            </table>
        </div>

        <!-- Pagination links (if needed) -->
        <!-- Wrapper to be updated by AJAX -->
        <div id="paginationWrapper" class="d-flex justify-content-center mt-4">
            {{ $accounts->links('vendor.pagination.bootstrap-5') }}
        </div>
    @else
    <a type="button" class="btn btn-success" id="addAccountButton" data-bs-toggle="modal" data-bs-target="#accountModal">Add account</a>
    @endif
</div>
<!-- Bootstrap 5 Modal for Adding New Account -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="accountForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalLabel">Manage Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <!-- Hidden field for Account ID -->
                    <input type="hidden" id="accountId" name="id">

                    <!-- Mail -->
                    <div class="form-group">
                        <label for="mail">Mail</label>
                        <input type="email" class="form-control" id="mail" name="mail" required>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" class="form-control" id="password" name="password" required>
                    </div>

                    <!-- Game Dropdown -->
                    <div class="form-group">
                        <label for="game">Game</label>
                        <select class="form-control" id="game" name="game_id" required>
                            <option value="">Select a game</option>
                            @foreach($games as $game)
                                <option value="{{ $game->id }}">{{ $game->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Region -->
                    <div class="form-group">
                        <label for="region">Region</label>
                        <select class="form-control" id="region" name="region" required>
                            @foreach($regionEmojis as $code => $flag)
                                <option value="{{ $code }}">{{ $flag }} {{ $code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cost -->
                    <div class="form-group">
                        <label for="cost">Cost</label>
                        <input type="number" class="form-control" id="cost" name="cost" required>
                    </div>

                    <!-- Birth Date -->
                    <div class="form-group">
                        <label for="birthdate">Birth Date</label>
                        <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                    </div>

                    <!-- Login Code -->
                    <div class="form-group">
                        <label for="login_code">Login Code</label>
                        <textarea class="form-control" id="login_code" name="login_code" rows="3"></textarea>
                    </div>

                    <!-- PS4 and PS5 Stock Fields -->
                    <div id="stock-availability">
                        <!-- PS4 Availability -->
                    <div class="form-group">
                        <label for="ps4Availability">PS4 Availability</label>
                        <div class="checkbox-inline">
                            <label class="checkbox">
                                <input type="checkbox" name="ps4_primary" value="1">
                                <span></span> Primary
                            </label>
                            <label class="checkbox">
                                <input type="checkbox" name="ps4_secondary" value="1">
                                <span></span> Secondary
                            </label>
                            <label class="checkbox">
                                <input type="checkbox" name="ps4_offline1" value="1">
                                <span></span> Offline 1
                            </label>
                            <label class="checkbox">
                                <input type="checkbox" name="ps4_offline2" value="1">
                                <span></span> Offline 2
                            </label>
                        </div>
                    </div>

                    <!-- PS5 Availability -->
                    <div class="form-group">
                        <label for="ps5Availability">PS5 Availability</label>
                        <div class="checkbox-inline">
                            <label class="checkbox">
                                <input type="checkbox" name="ps5_primary" value="1">
                                <span></span> Primary
                            </label>
                            <label class="checkbox">
                                <input type="checkbox" name="ps5_secondary" value="1">
                                <span></span> Secondary
                            </label>
                            <label class="checkbox">
                                <input type="checkbox" name="ps5_offline" value="1">
                                <span></span> Offline
                            </label>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>



@endsection
@push('js')
<!-- JavaScript for handling AJAX form submission -->
<script>
    jQuery(document).ready(function($) {
        // Handle Add Account Button
        $(document).on('click','#addAccountButton',function() {
            $('#accountModalLabel').text('Add New Account');
            $('#accountForm').attr('action', "{{ route('manager.accounts.store') }}").attr('method', 'POST');
            $('#accountForm')[0].reset(); // Reset the form
            $('#stock-availability').show();
        });

        // Handle Edit Account Button
        $(document).on('click', '.editAccount',function() {
            $('#stock-availability').hide();
            $('#accountModalLabel').text('Edit Account');
            $('#accountForm').attr('action', `/manager/accounts/${$(this).data('id')}`).attr('data-method', 'PUT');

            // Populate form fields with account data
            $('#accountId').val($(this).data('id'));
            $('#mail').val($(this).data('mail'));
            $('#password').val($(this).data('password'));
            $('#game').val($(this).data('game_id')).trigger('change');
            $('#region').val($(this).data('region'));
            $('#cost').val($(this).data('cost'));
            $('#birthdate').val($(this).data('birthdate'));
            $('#login_code').val($(this).data('login_code'));
        });
        // Handle form submission
        $('#accountForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            let formAction = $(this).attr('action');
            if ( $(this).data('method') === 'PUT' ) {
                formData.append('_method', 'PUT');
            }
            $.ajax({
                url: formAction,
                method: 'POST',
                data: formData,
                contentType: false, // Required for FormData
                processData: false, // Required for FormData
                success: function(response) {
                    $('#accountModal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: response.success,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                    location.reload(); // Reload to reflect changes
                },
                error: function(xhr) {
                    let errors = xhr.responseJSON.errors;
                    for (let key in errors) {
                        Swal.fire({
                            title: 'Error',
                            text: errors[key][0],
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        });

        $('#searchButton').on('click', function () {
            let query = $('#searchAccount').val();
            if (query.length >= 3) {
                $.ajax({
                    url: "{{ route('manager.accounts.search') }}",
                    method: 'GET',
                    data: { search: query },
                    success: function (response) {
                        if (response.rows.trim() === '') {
                            $('#noResultsMessage').show();
                        } else {
                            $('#noResultsMessage').hide();
                            $('#accountTableBody').html(response.rows);
                            $('#paginationWrapper').html(response.pagination);
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Could not fetch data.', 'error');
                    }
                });
            } else {
                Swal.fire('Info', 'Please enter at least 3 characters to search.', 'info');
            }
        });
        $(document).on('click', '#search-pagination .pagination a', function(e) {
            e.preventDefault();
            let url = $(this).attr('href');
            let query = $('#searchAccount').val();

            $.ajax({
                url: url,
                method: 'GET',
                data: { search: query },
                success: function (response) {
                    $('#accountTableBody').html(response.rows);
                    $('#paginationWrapper').html(response.pagination);
                }
            });
        });

    });
    jQuery(document).ready(function ($) {
        $('.accounts-responsive-table').mobileTableToggle({
            maxVisibleColsDesktop: 5,
            enableOnDesktop: true
        });
    });
</script>
@endpush
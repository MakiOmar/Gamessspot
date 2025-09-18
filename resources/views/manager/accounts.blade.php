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
                    <!-- Import Button -->
                    <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#importModal" title="Import Accounts">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px" fill="white">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        Import
                    </button>
                    <!-- Export Button -->
                    <a href="{{ route('manager.accounts.export') }}" class="btn btn-success me-2" title="Export Accounts">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px" fill="white">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        Export
                    </a>
                    <!-- Template Button -->
                    <a href="{{ route('manager.accounts.template') }}" class="btn btn-outline-secondary" title="Download Template">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px" fill="currentColor">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        Template
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
                            
                            <!-- PS5 Only Checkbox -->
                            <div class="mb-2">
                                <label class="checkbox">
                                    <input type="checkbox" id="ps5_only" value="1">
                                    <span></span> <strong>PS5 Only</strong>
                                </label>
                            </div>
                            
                            <div class="checkbox-inline">
                                <label class="checkbox">
                                    <input type="checkbox" name="ps4_primary" value="1" class="ps4-checkbox">
                                    <span></span> Primary
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="ps4_secondary" value="1" class="ps4-checkbox">
                                    <span></span> Secondary
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="ps4_offline1" value="1" class="ps4-checkbox">
                                    <span></span> Offline 1
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="ps4_offline2" value="1" class="ps4-checkbox">
                                    <span></span> Offline 2
                                </label>
                            </div>
                        </div>

                        <!-- PS5 Availability -->
                        <div class="form-group">
                            <label for="ps5Availability">PS5 Availability</label>
                            
                            <div class="checkbox-inline">
                                <label class="checkbox">
                                    <input type="checkbox" name="ps5_primary" value="1" class="ps5-checkbox">
                                    <span></span> Primary
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="ps5_secondary" value="1" class="ps5-checkbox">
                                    <span></span> Secondary
                                </label>
                                <label class="checkbox">
                                    <input type="checkbox" name="ps5_offline" value="1" class="ps5-checkbox">
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="importForm" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="importModalLabel">Import Accounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <div class="mb-3">
                        <label for="importFile" class="form-label">Select Excel/CSV File</label>
                        <input type="file" class="form-control" id="importFile" name="file" 
                               accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">
                            <strong>Supported formats:</strong> Excel (.xlsx, .xls) and CSV files<br>
                            <strong>Max size:</strong> 10MB<br>
                            <strong>Note:</strong> Stock values are set automatically - you only need to provide the basic account information.
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Import Format</h6>
                        <p class="mb-1">Your Excel/CSV file should have these columns:</p>
                        <ul class="mb-0">
                            <li><strong>Mail</strong> - Email address (must be unique)</li>
                            <li><strong>Password</strong> - Account password</li>
                            <li><strong>Game</strong> - Game title (must exist in system)</li>
                            <li><strong>Region</strong> - Region code (e.g., US, EU)</li>
                            <li><strong>Cost</strong> - Account cost</li>
                            <li><strong>Birthdate</strong> - Birth date (YYYY-MM-DD)</li>
                            <li><strong>Login Code</strong> - Login code</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ route('manager.accounts.template') }}" class="btn btn-outline-primary me-auto">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import Accounts
                    </button>
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

        // Handle Import Form Submission
        $('#importForm').on('submit', function(e) {
            e.preventDefault();
            
            let formData = new FormData(this);
            let fileInput = $('#importFile')[0];
            
            // Validate file selection
            if (!fileInput.files.length) {
                Swal.fire('Error', 'Please select a file to import.', 'error');
                return;
            }
            
            // Show loading state
            let submitBtn = $(this).find('button[type="submit"]');
            let originalText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');
            
            $.ajax({
                url: "{{ route('manager.accounts.import') }}",
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#importModal').modal('hide');
                    Swal.fire({
                        title: 'Success!',
                        text: response.success,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload(); // Reload to show new accounts
                    });
                },
                error: function(xhr) {
                    let errorMessage = 'Import failed. Please check your file format.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errors = xhr.responseJSON.errors;
                        errorMessage = Object.values(errors).flat().join('<br>');
                    }
                    
                    Swal.fire({
                        title: 'Import Failed',
                        html: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                },
                complete: function() {
                    // Reset button state
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Handle PS5 Only Checkbox
        $('#ps5_only').on('change', function() {
            const isChecked = $(this).is(':checked');
            
            if (isChecked) {
                // When PS5 Only is checked, check all PS4 checkboxes and disable them
                $('.ps4-checkbox').prop('checked', true).prop('disabled', true);
            } else {
                // When PS5 Only is unchecked, enable PS4 checkboxes
                $('.ps4-checkbox').prop('disabled', false);
            }
        });

        // Handle individual PS4 checkbox changes (only when not disabled)
        $('.ps4-checkbox').on('change', function() {
            // Only process if the checkbox is not disabled
            if (!$(this).prop('disabled')) {
                // No master checkbox logic needed anymore
            }
        });

        // Special PS5 Offline Logic: If only PS5 offline is checked, add two offline stocks
        $('input[name="ps5_offline"]').on('change', function() {
            const ps5OfflineChecked = $(this).is(':checked');
            const ps5PrimaryChecked = $('input[name="ps5_primary"]').is(':checked');
            const ps5SecondaryChecked = $('input[name="ps5_secondary"]').is(':checked');
            
            // If only PS5 offline is checked (and no primary/secondary), add a second offline checkbox
            if (ps5OfflineChecked && !ps5PrimaryChecked && !ps5SecondaryChecked) {
                // Add a second PS5 offline checkbox if it doesn't exist
                if ($('input[name="ps5_offline2"]').length === 0) {
                    const ps5OfflineContainer = $(this).closest('.checkbox-inline');
                    ps5OfflineContainer.append(`
                        <label class="checkbox">
                            <input type="checkbox" name="ps5_offline2" value="1" class="ps5-checkbox">
                            <span></span> Offline 2
                        </label>
                    `);
                    
                    // Re-bind the change event for the new checkbox
                    $('input[name="ps5_offline2"]').on('change', function() {
                        // No master checkbox logic needed anymore
                    });
                }
            } else if (!ps5OfflineChecked || ps5PrimaryChecked || ps5SecondaryChecked) {
                // Remove the second offline checkbox if it exists
                $('input[name="ps5_offline2"]').closest('label').remove();
            }
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
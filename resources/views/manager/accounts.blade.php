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
    
    {{-- Cache Indicator --}}
    @include('components.cache-indicator')
    
    @if ( Auth::user()->roles->contains('name', 'admin') )
        <!-- Search and Action Buttons -->
        <div class="mb-4">
            <!-- Search Row -->
            <div class="row mb-3">
                <div class="col-12">
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
            </div>
            
            <!-- Action Buttons Row -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2 justify-content-start">
                        <!-- Add Account Button -->
                        <a type="button" id="addAccountButton" data-bs-toggle="modal" data-bs-target="#accountModal" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20px" height="20px" class="me-1">
                                <path fill="#FFF" d="M391.5,214.5H297v-93.9c0-4-3.2-7.2-7.2-7.2h-68.1c-4,0-7.2,3.2-7.2,7.2v93.9h-93.9c-4,0-7.2,3.2-7.2,7.2v69.2c0,4,3.2,7.2,7.2,7.2h93.9v93.4c0,4,3.2,7.2,7.2,7.2h68.1c4,0,7.2-3.2,7.2-7.2v-93.4h94.5c4,0,7.2-3.2,7.2-7.2v-69.2C398.7,217.7,395.4,214.5,391.5,214.5z"/>
                            </svg>
                            Add Account
                        </a>
                        @if( Auth::user()->roles->contains('name', 'admin') )
                            <!-- Import Button -->
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal" title="Import Accounts">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px" fill="white" class="me-1">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                                Import
                            </button>
                            <!-- Export Button -->
                            <a href="{{ route('manager.accounts.export') }}" class="btn btn-success" title="Export Accounts">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px" fill="white" class="me-1">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                                Export
                            </a>
                            <!-- Template Button -->
                            <a href="{{ route('manager.accounts.template') }}" class="btn btn-outline-secondary" title="Download Template">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20px" height="20px" fill="currentColor" class="me-1">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                                Template
                            </a>
                        @endif
                    </div>
                </div>
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
                        <div class="input-group">
                            <input type="email" class="form-control" id="mail" name="mail" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(event, 'mail')" title="Copy email">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="password" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(event, 'password')" title="Copy password">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
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
                                    <input type="checkbox" id="ps5_only" name="ps5_only" value="1">
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

<!-- Stock Edit Modal -->
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stockForm" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockModalLabel">Edit Account Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="_method" value="PATCH">

                    <div class="form-group mb-3">
                        <label for="ps4PrimaryStock">PS4 Primary Stock</label>
                        <input type="number" min="0" class="form-control" id="ps4PrimaryStock" name="ps4_primary_stock" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="ps4SecondaryStock">PS4 Secondary Stock</label>
                        <input type="number" min="0" class="form-control" id="ps4SecondaryStock" name="ps4_secondary_stock" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="ps4OfflineStock">PS4 Offline Stock</label>
                        <input type="number" min="0" class="form-control" id="ps4OfflineStock" name="ps4_offline_stock" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="ps5PrimaryStock">PS5 Primary Stock</label>
                        <input type="number" min="0" class="form-control" id="ps5PrimaryStock" name="ps5_primary_stock" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="ps5SecondaryStock">PS5 Secondary Stock</label>
                        <input type="number" min="0" class="form-control" id="ps5SecondaryStock" name="ps5_secondary_stock" required>
                    </div>

                    <div class="form-group mb-0">
                        <label for="ps5OfflineStock">PS5 Offline Stock</label>
                        <input type="number" min="0" class="form-control" id="ps5OfflineStock" name="ps5_offline_stock" required>
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
    // Copy to clipboard function
    function copyToClipboard(event, fieldId) {
        const field = document.getElementById(fieldId);
        const value = field.value;
        
        if (value) {
            navigator.clipboard.writeText(value).then(function() {
                // Change icon to checkmark temporarily
                const button = event.target.closest('button');
                const icon = button.querySelector('i');
                const originalClass = icon.className;
                
                icon.className = 'bi bi-check-lg text-success';
                
                setTimeout(function() {
                    icon.className = originalClass;
                }, 1500);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy to clipboard');
            });
        }
    }

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

        // Handle Stock Edit Button
        $(document).on('click', '.editStock', function() {
            const button = $(this);
            const form = $('#stockForm');
            const row = button.closest('tr'); // Get the row containing this button
            const accountId = button.data('id') || button.attr('data-id'); // Get account ID from data attribute

            console.log('Edit Stock clicked, accountId:', accountId);
            console.log('Row found:', row.length);

            form[0].reset();
            form.attr('action', button.data('update-url'));
            form.attr('data-account-id', accountId); // Store as attribute for persistence
            form.data('account-id', accountId); // Store account ID for row update
            form.data('row-reference', row); // Store reference to the row for easier access

            $('#ps4PrimaryStock').val(button.data('ps4_primary_stock'));
            $('#ps4SecondaryStock').val(button.data('ps4_secondary_stock'));
            $('#ps4OfflineStock').val(button.data('ps4_offline_stock'));
            $('#ps5PrimaryStock').val(button.data('ps5_primary_stock'));
            $('#ps5SecondaryStock').val(button.data('ps5_secondary_stock'));
            $('#ps5OfflineStock').val(button.data('ps5_offline_stock'));
        });

        // Handle Stock Form Submission
        $('#stockForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitButton = form.find('button[type=\"submit\"]');
            const originalText = submitButton.html();
            // Try multiple ways to get accountId
            let accountId = form.data('account-id') || form.attr('data-account-id');
            if (!accountId) {
                // Fallback: extract from form action URL
                const actionUrl = form.attr('action');
                const match = actionUrl.match(/\/(\d+)$/);
                if (match) {
                    accountId = match[1];
                }
            }
            console.log('Form submit, accountId:', accountId);

            submitButton.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Saving...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log('Stock update success, accountId:', accountId);
                    
                    // Get the updated stock values from the form
                    const ps4PrimaryStock = $('#ps4PrimaryStock').val();
                    const ps4SecondaryStock = $('#ps4SecondaryStock').val();
                    const ps4OfflineStock = $('#ps4OfflineStock').val();
                    const ps5PrimaryStock = $('#ps5PrimaryStock').val();
                    const ps5SecondaryStock = $('#ps5SecondaryStock').val();
                    const ps5OfflineStock = $('#ps5OfflineStock').val();
                    
                    console.log('Stock values from form:', {
                        ps4Offline: ps4OfflineStock,
                        ps4Primary: ps4PrimaryStock,
                        ps4Secondary: ps4SecondaryStock,
                        ps5Offline: ps5OfflineStock,
                        ps5Primary: ps5PrimaryStock,
                        ps5Secondary: ps5SecondaryStock
                    });

                    // Try multiple methods to find the row
                    let row = null;
                    
                    // Method 1: Find by data-account-id attribute on the row (most reliable)
                    row = $(`tr[data-account-id="${accountId}"]`);
                    console.log('Method 1 (data-account-id):', row.length);
                    
                    // Method 2: Use stored reference
                    if (!row || !row.length) {
                        const storedRow = form.data('row-reference');
                        if (storedRow && storedRow.length) {
                            row = storedRow;
                            console.log('Method 2 (stored reference):', row.length);
                        }
                    }
                    
                    // Method 3: Find by button with the account ID and traverse to row
                    if (!row || !row.length) {
                        row = $(`button.editStock[data-id="${accountId}"]`).closest('tr');
                        console.log('Method 3 (button closest):', row.length);
                    }
                    
                    // Method 4: Find by selector with :has
                    if (!row || !row.length) {
                        row = $(`tr:has(button.editStock[data-id="${accountId}"])`);
                        console.log('Method 4 (:has selector):', row.length);
                    }
                    
                    if (row && row.length) {
                        console.log('Row found! Updating cells...');
                        // Update table cells (columns are: ID, Mail, Game, Region, PS4 Offline, PS4 Primary, PS4 Secondary, PS5 Offline, PS5 Primary, PS5 Secondary, Cost, Password, Actions)
                        const cells = row.find('td');
                        console.log('Found cells:', cells.length);
                        
                        // Update stock values - use both text() and html() to ensure update
                        if (cells.length > 4) {
                            const cell4 = cells.eq(4);
                            cell4.text(ps4OfflineStock);
                            cell4.html(ps4OfflineStock);
                            console.log('Updated cell 4 (PS4 Offline):', ps4OfflineStock, 'New value:', cell4.text());
                        }
                        if (cells.length > 5) {
                            const cell5 = cells.eq(5);
                            cell5.text(ps4PrimaryStock);
                            cell5.html(ps4PrimaryStock);
                            console.log('Updated cell 5 (PS4 Primary):', ps4PrimaryStock, 'New value:', cell5.text());
                        }
                        if (cells.length > 6) {
                            const cell6 = cells.eq(6);
                            cell6.text(ps4SecondaryStock);
                            cell6.html(ps4SecondaryStock);
                            console.log('Updated cell 6 (PS4 Secondary):', ps4SecondaryStock, 'New value:', cell6.text());
                        }
                        if (cells.length > 7) {
                            const cell7 = cells.eq(7);
                            cell7.text(ps5OfflineStock);
                            cell7.html(ps5OfflineStock);
                            console.log('Updated cell 7 (PS5 Offline):', ps5OfflineStock, 'New value:', cell7.text());
                        }
                        if (cells.length > 8) {
                            const cell8 = cells.eq(8);
                            cell8.text(ps5PrimaryStock);
                            cell8.html(ps5PrimaryStock);
                            console.log('Updated cell 8 (PS5 Primary):', ps5PrimaryStock, 'New value:', cell8.text());
                        }
                        if (cells.length > 9) {
                            const cell9 = cells.eq(9);
                            cell9.text(ps5SecondaryStock);
                            cell9.html(ps5SecondaryStock);
                            console.log('Updated cell 9 (PS5 Secondary):', ps5SecondaryStock, 'New value:', cell9.text());
                        }
                        
                        // Force a reflow to ensure the browser updates the display
                        row[0].offsetHeight;
                        
                        console.log('All cells updated successfully');

                        // Update the mobile-detail-row if it exists (responsive view)
                        const mobileDetailRow = row.next('.mobile-detail-row');
                        if (mobileDetailRow.length) {
                            console.log('Found mobile-detail-row, updating...');
                            const detailContent = mobileDetailRow.find('td');
                            
                            // Update stock values in mobile detail row
                            // The format is: <div><strong>Header:</strong> Value</div>
                            const stockMappings = [
                                { header: 'Offline (PS4)', value: ps4OfflineStock },
                                { header: 'Primary (PS4)', value: ps4PrimaryStock },
                                { header: 'Secondary (PS4)', value: ps4SecondaryStock },
                                { header: 'Offline (PS5)', value: ps5OfflineStock },
                                { header: 'Primary (PS5)', value: ps5PrimaryStock },
                                { header: 'Secondary (PS5)', value: ps5SecondaryStock }
                            ];
                            
                            stockMappings.forEach(function(mapping) {
                                // Try to find the div by matching the header text
                                const detailDiv = detailContent.find('div').filter(function() {
                                    const strongText = $(this).find('strong').text().trim();
                                    return strongText === mapping.header + ':' || strongText === mapping.header;
                                });
                                
                                if (detailDiv.length) {
                                    detailDiv.html('<strong>' + mapping.header + ':</strong> ' + mapping.value);
                                    console.log('Updated mobile detail:', mapping.header, '=', mapping.value);
                                } else {
                                    console.log('Could not find mobile detail div for:', mapping.header);
                                }
                            });
                            
                            // Force update by reading from the actual table cells if direct update didn't work
                            const tableHeaders = $('#accounts-table thead th');
                            const maxVisible = tableHeaders.length > 5 ? 5 : tableHeaders.length; // Assuming maxVisibleCols is 5
                            const hiddenIndexes = [];
                            tableHeaders.each(function(i) {
                                if (i >= maxVisible) {
                                    hiddenIndexes.push(i);
                                }
                            });
                            
                            // Rebuild the detail row content from current cell values
                            let newDetailHTML = '';
                            hiddenIndexes.forEach(function(i) {
                                const headerText = tableHeaders.eq(i).text().trim();
                                const cellValue = cells.eq(i).html();
                                newDetailHTML += '<div><strong>' + headerText + ':</strong> ' + cellValue + '</div>';
                            });
                            
                            if (newDetailHTML) {
                                detailContent.html(newDetailHTML);
                                console.log('Rebuilt mobile detail row with current values');
                            }
                        } else {
                            console.log('Mobile detail row not found, may need to be created by mobileTableToggle');
                        }

                        // Update data attributes on table cells for responsive views
                        // These might be used by mobile/responsive table plugins
                        cells.eq(4).attr('data-label', 'Offline (PS4)');
                        cells.eq(5).attr('data-label', 'Primary (PS4)');
                        cells.eq(6).attr('data-label', 'Secondary (PS4)');
                        cells.eq(7).attr('data-label', 'Offline (PS5)');
                        cells.eq(8).attr('data-label', 'Primary (PS5)');
                        cells.eq(9).attr('data-label', 'Secondary (PS5)');

                        // Update the editStock button data attributes
                        const editStockButton = row.find('button.editStock');
                        editStockButton.attr('data-ps4_primary_stock', ps4PrimaryStock);
                        editStockButton.attr('data-ps4_secondary_stock', ps4SecondaryStock);
                        editStockButton.attr('data-ps4_offline_stock', ps4OfflineStock);
                        editStockButton.attr('data-ps5_primary_stock', ps5PrimaryStock);
                        editStockButton.attr('data-ps5_secondary_stock', ps5SecondaryStock);
                        editStockButton.attr('data-ps5_offline_stock', ps5OfflineStock);
                        editStockButton.data('ps4_primary_stock', ps4PrimaryStock);
                        editStockButton.data('ps4_secondary_stock', ps4SecondaryStock);
                        editStockButton.data('ps4_offline_stock', ps4OfflineStock);
                        editStockButton.data('ps5_primary_stock', ps5PrimaryStock);
                        editStockButton.data('ps5_secondary_stock', ps5SecondaryStock);
                        editStockButton.data('ps5_offline_stock', ps5OfflineStock);

                        // Also update the editAccount button data attributes to keep them in sync
                        const editAccountButton = row.find('button.editAccount');
                        if (editAccountButton.length) {
                            editAccountButton.attr('data-ps4_primary', ps4PrimaryStock);
                            editAccountButton.attr('data-ps4_secondary', ps4SecondaryStock);
                            editAccountButton.attr('data-ps4_offline', ps4OfflineStock);
                            editAccountButton.attr('data-ps5_primary', ps5PrimaryStock);
                            editAccountButton.attr('data-ps5_secondary', ps5SecondaryStock);
                            editAccountButton.attr('data-ps5_offline', ps5OfflineStock);
                            editAccountButton.data('ps4_primary', ps4PrimaryStock);
                            editAccountButton.data('ps4_secondary', ps4SecondaryStock);
                            editAccountButton.data('ps4_offline', ps4OfflineStock);
                            editAccountButton.data('ps5_primary', ps5PrimaryStock);
                            editAccountButton.data('ps5_secondary', ps5SecondaryStock);
                            editAccountButton.data('ps5_offline', ps5OfflineStock);
                        }

                        // Note: mobileTableToggle doesn't have a refresh method, so we manually update the detail row above
                        // If the table needs to be re-initialized, we would need to remove and re-add the plugin
                        
                        // Trigger a custom event for any listeners that might need to update views
                        $(document).trigger('accountStockUpdated', [accountId, {
                            ps4_primary_stock: ps4PrimaryStock,
                            ps4_secondary_stock: ps4SecondaryStock,
                            ps4_offline_stock: ps4OfflineStock,
                            ps5_primary_stock: ps5PrimaryStock,
                            ps5_secondary_stock: ps5SecondaryStock,
                            ps5_offline_stock: ps5OfflineStock
                        }]);
                    } else {
                        console.error('Row not found for account ID:', accountId);
                        console.log('Attempted selectors:', {
                            dataAccountId: $(`tr[data-account-id="${accountId}"]`).length,
                            storedReference: form.data('row-reference') ? form.data('row-reference').length : 0,
                            buttonClosest: $(`button.editStock[data-id="${accountId}"]`).closest('tr').length,
                            hasSelector: $(`tr:has(button.editStock[data-id="${accountId}"])`).length
                        });
                        console.log('All rows with data-account-id:', $('tr[data-account-id]').length);
                        console.log('All editStock buttons:', $('button.editStock').length);
                    }

                    // Hide modal and show success message
                    $('#stockModal').modal('hide');
                    
                    Swal.fire({
                        title: 'Success!',
                        text: response.success,
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                },
                error: function(xhr) {
                    let message = 'Failed to update stock. Please try again.';

                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join('\\n');
                        } else if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                    }

                    Swal.fire('Error', message, 'error');
                },
                complete: function() {
                    submitButton.prop('disabled', false).html(originalText);
                }
            });
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

        // Handle account deletion
        $(document).on('click', '.deleteAccount', function (e) {
            e.preventDefault();

            const accountId = $(this).data('id');
            const accountMail = $(this).data('mail');

            Swal.fire({
                title: 'Delete account?',
                text: `This will permanently remove ${accountMail}.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/manager/accounts/' + accountId,
                        method: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.message || 'Account deleted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function (xhr) {
                            const message = xhr.responseJSON && xhr.responseJSON.message
                                ? xhr.responseJSON.message
                                : 'Failed to delete the account. Please try again.';

                            Swal.fire({
                                title: 'Error',
                                text: message,
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
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

        // Listen for account stock updates to refresh responsive views
        $(document).on('accountStockUpdated', function(e, accountId, stockData) {
            // Refresh the responsive table view
            if (typeof $.fn.mobileTableToggle !== 'undefined') {
                $('#accounts-table').mobileTableToggle('refresh');
            }
            
            // Update any detail views or modals that might be showing this account's information
            // This ensures responsive/mobile views get updated
            const row = $(`tr:has(button.editStock[data-id="${accountId}"])`);
            if (row.length) {
                // Force a re-render by triggering a resize event (some responsive plugins listen to this)
                $(window).trigger('resize');
            }
        });
    });
</script>
@endpush
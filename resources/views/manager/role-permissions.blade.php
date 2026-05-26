@extends('layouts.admin')

@section('title', 'Manager - Roles & Permissions')

@section('content')
    <div class="mt-5">
        <h1 class="text-center mb-4">Roles &amp; Permissions</h1>

        <div class="alert alert-info">
            <strong>Note:</strong> Staff roles only. The customer role is managed separately.
            New custom roles appear on the Users page but not in the Users sidebar submenu until that menu is made dynamic.
        </div>

        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
            <div class="flex-grow-1" style="max-width: 420px;">
                <label for="roleSelect" class="form-label">Select role</label>
                <select id="roleSelect" class="form-select">
                    <option value="">— Choose a role —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" data-protected="{{ $role->name === 'admin' ? '1' : '0' }}">
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                    Create Role
                </button>
                <button type="button" class="btn btn-danger" id="deleteRoleBtn" disabled>
                    Delete Role
                </button>
            </div>
        </div>

        <div id="permissionsLoading" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <div id="permissionsEmpty" class="alert alert-secondary text-center">
            Select a role to view and edit its permissions.
        </div>

        <div id="permissionsPanel" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0" id="selectedRoleTitle"></h5>
                <button type="button" class="btn btn-primary" id="savePermissionsBtn">Save Permissions</button>
            </div>
            <div id="permissionsGroups"></div>
        </div>
    </div>

    {{-- Create role modal --}}
    <div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="createRoleForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createRoleModalLabel">Create Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newRoleName" class="form-label">Role name</label>
                            <input type="text" class="form-control" id="newRoleName" name="name" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="duplicateFromRole" class="form-label">Duplicate permissions from (optional)</label>
                            <select class="form-select" id="duplicateFromRole" name="duplicate_from_role_id">
                                <option value="">— No permissions (blank role) —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    jQuery(document).ready(function($) {
        const routes = {
            show: @json(route('manager.roles-permissions.show', ['role' => '__ROLE__'])),
            update: @json(route('manager.roles-permissions.update', ['role' => '__ROLE__'])),
            store: @json(route('manager.roles-permissions.store')),
            destroy: @json(route('manager.roles-permissions.destroy', ['role' => '__ROLE__'])),
            data: @json(route('manager.roles-permissions.data')),
        };

        let currentRoleId = null;
        let currentRoleProtected = false;

        function roleUrl(template, roleId) {
            return template.replace('__ROLE__', roleId);
        }

        function getCsrfToken() {
            return $('meta[name="csrf-token"]').attr('content');
        }

        function showSwal(title, text, icon) {
            Swal.fire({ title: title, text: text, icon: icon });
        }

        function resetPanel() {
            $('#permissionsPanel').hide();
            $('#permissionsEmpty').show();
            $('#deleteRoleBtn').prop('disabled', true);
            currentRoleId = null;
            currentRoleProtected = false;
        }

        function renderPermissions(payload) {
            const grouped = payload.grouped || {};
            let html = '';

            Object.keys(grouped).forEach(function(groupName) {
                html += '<div class="card mb-3"><div class="card-header"><strong>' + groupName + '</strong></div>';
                html += '<div class="card-body"><div class="row">';

                grouped[groupName].forEach(function(permission) {
                    const checked = permission.checked ? 'checked' : '';
                    html += '<div class="col-md-6 col-lg-4 mb-2">';
                    html += '<div class="form-check">';
                    html += '<input class="form-check-input permission-checkbox" type="checkbox" value="' + permission.key + '" id="perm_' + permission.key + '" ' + checked + '>';
                    html += '<label class="form-check-label" for="perm_' + permission.key + '">' + permission.label + '</label>';
                    html += '</div></div>';
                });

                html += '</div></div></div>';
            });

            $('#permissionsGroups').html(html);
            $('#selectedRoleTitle').text('Permissions for: ' + payload.role.name);
            $('#permissionsEmpty').hide();
            $('#permissionsPanel').show();
            $('#deleteRoleBtn').prop('disabled', currentRoleProtected);
        }

        function loadRolePermissions(roleId) {
            if (!roleId) {
                resetPanel();
                return;
            }

            currentRoleId = roleId;
            const $option = $('#roleSelect option:selected');
            currentRoleProtected = $option.data('protected') == 1;

            $('#permissionsLoading').show();
            $('#permissionsPanel').hide();
            $('#permissionsEmpty').hide();

            $.ajax({
                url: roleUrl(routes.show, roleId),
                method: 'GET',
                success: function(response) {
                    renderPermissions(response);
                },
                error: function(xhr) {
                    resetPanel();
                    showSwal('Error', xhr.responseJSON?.message || 'Failed to load permissions.', 'error');
                },
                complete: function() {
                    $('#permissionsLoading').hide();
                }
            });
        }

        function refreshRoleDropdown(selectRoleId) {
            return $.get(routes.data).then(function(response) {
                const $select = $('#roleSelect');
                const current = selectRoleId || $select.val();
                $select.find('option:not(:first)').remove();
                $('#duplicateFromRole option:not(:first)').remove();

                (response.roles || []).forEach(function(role) {
                    const protectedAttr = role.is_protected ? '1' : '0';
                    $select.append(
                        '<option value="' + role.id + '" data-protected="' + protectedAttr + '">' + role.name + '</option>'
                    );
                    $('#duplicateFromRole').append(
                        '<option value="' + role.id + '">' + role.name + '</option>'
                    );
                });

                if (current) {
                    $select.val(current);
                }
            });
        }

        $('#roleSelect').on('change', function() {
            loadRolePermissions($(this).val());
        });

        $('#savePermissionsBtn').on('click', function() {
            if (!currentRoleId) {
                return;
            }

            const permissions = [];
            $('.permission-checkbox:checked').each(function() {
                permissions.push($(this).val());
            });

            $.ajax({
                url: roleUrl(routes.update, currentRoleId),
                method: 'PUT',
                data: {
                    _token: getCsrfToken(),
                    permissions: permissions
                },
                success: function(response) {
                    showSwal('Saved', response.message || 'Permissions updated.', 'success');
                    if (response.data) {
                        renderPermissions(response.data);
                    }
                },
                error: function(xhr) {
                    let message = xhr.responseJSON?.message || 'Failed to save permissions.';
                    if (xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join(' ');
                    }
                    showSwal('Error', message, 'error');
                }
            });
        });

        $('#createRoleForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: routes.store,
                method: 'POST',
                data: {
                    _token: getCsrfToken(),
                    name: $('#newRoleName').val(),
                    duplicate_from_role_id: $('#duplicateFromRole').val() || null
                },
                success: function(response) {
                    $('#createRoleModal').modal('hide');
                    $('#createRoleForm')[0].reset();
                    showSwal('Created', response.message || 'Role created.', 'success');

                    refreshRoleDropdown(response.data?.role?.id).then(function() {
                        $('#roleSelect').val(response.data.role.id).trigger('change');
                    });
                },
                error: function(xhr) {
                    let message = xhr.responseJSON?.message || 'Failed to create role.';
                    if (xhr.responseJSON?.errors) {
                        const errors = xhr.responseJSON.errors;
                        message = Object.values(errors).flat().join(' ');
                    }
                    showSwal('Error', message, 'error');
                }
            });
        });

        $('#deleteRoleBtn').on('click', function() {
            if (!currentRoleId || currentRoleProtected) {
                return;
            }

            Swal.fire({
                title: 'Delete role?',
                text: 'This cannot be undone. Roles assigned to users cannot be deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Delete'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: roleUrl(routes.destroy, currentRoleId),
                    method: 'DELETE',
                    data: { _token: getCsrfToken() },
                    success: function(response) {
                        showSwal('Deleted', response.message || 'Role deleted.', 'success');
                        refreshRoleDropdown().then(function() {
                            $('#roleSelect').val('');
                            resetPanel();
                        });
                    },
                    error: function(xhr) {
                        showSwal('Error', xhr.responseJSON?.message || 'Failed to delete role.', 'error');
                    }
                });
            });
        });
    });
</script>
@endpush

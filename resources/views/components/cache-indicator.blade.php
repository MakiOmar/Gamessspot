@if(isset($cacheKey))
<div class="cache-indicator-wrapper" style="position: relative; margin-bottom: 15px;">
    <div class="d-flex justify-content-between align-items-center bg-light border rounded p-3">
        <div class="d-flex align-items-center">
            @if($fromCache ?? false)
                <span class="badge badge-success mr-2" style="font-size: 0.9rem;">
                    <i class="fas fa-bolt"></i> From Cache
                </span>
                <small class="text-muted">
                    <i class="fas fa-clock"></i>
                    Cached {{ isset($cacheMetadata['datetime']) ? \Carbon\Carbon::parse($cacheMetadata['datetime'])->diffForHumans() : 'recently' }}
                    | Expires in ~{{ \App\Services\CacheManager::TTL_SHORT }}s
                    @if(isset($cacheMetadata['execution_time_ms']))
                        | <strong class="text-success">⚡ {{ $cacheMetadata['execution_time_ms'] }}ms</strong>
                    @endif
                </small>
            @else
                <span class="badge badge-warning mr-2" style="font-size: 0.9rem;">
                    <i class="fas fa-database"></i> Fresh Query
                </span>
                <small class="text-muted">
                    Direct from database - now cached for {{ \App\Services\CacheManager::TTL_SHORT }}s
                    @if(isset($cacheMetadata['query_time_ms']))
                        | <strong class="text-warning">🐢 {{ $cacheMetadata['query_time_ms'] }}ms</strong>
                    @endif
                </small>
            @endif
        </div>
        
        <div class="d-flex align-items-center">
            @can('manage-options')
                <button type="button" 
                        class="btn btn-sm btn-outline-danger clear-cache-btn" 
                        data-cache-key="{{ $cacheKey }}"
                        title="Clear this page's cache">
                    <i class="fas fa-trash-alt"></i> Clear Cache
                </button>
            @endcan
            
            <button type="button" 
                    class="btn btn-sm btn-outline-secondary ml-2" 
                    data-toggle="collapse" 
                    data-target="#cache-details-{{ md5($cacheKey) }}"
                    title="Show cache details">
                <i class="fas fa-info-circle"></i>
            </button>
        </div>
    </div>
    
    <!-- Collapsible Cache Details -->
    <div class="collapse" id="cache-details-{{ md5($cacheKey) }}">
        <div class="card card-body mt-2">
            <h6 class="font-weight-bold mb-2"><i class="fas fa-key"></i> Cache Details</h6>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Cache Key:</strong></p>
                    <code class="d-block bg-light p-2 rounded text-sm">{{ $cacheKey }}</code>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Status:</strong></p>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fas fa-circle {{ ($fromCache ?? false) ? 'text-success' : 'text-warning' }}"></i> 
                            Cache {{ ($fromCache ?? false) ? 'Hit' : 'Miss' }}
                        </li>
                        <li><i class="fas fa-circle text-info"></i> TTL: {{ \App\Services\CacheManager::TTL_SHORT }} seconds (1 minute)</li>
                        <li><i class="fas fa-circle text-primary"></i> Driver: {{ strtoupper(config('cache.default')) }}</li>
                        @if(isset($cacheMetadata['datetime']))
                            <li><i class="fas fa-circle text-secondary"></i> 
                                Created: {{ \Carbon\Carbon::parse($cacheMetadata['datetime'])->format('Y-m-d H:i:s') }}
                            </li>
                        @endif
                        @if(isset($cacheMetadata['execution_time_ms']))
                            <li><i class="fas fa-circle {{ ($fromCache ?? false) ? 'text-success' : 'text-warning' }}"></i> 
                                <strong>Load Time: {{ $cacheMetadata['execution_time_ms'] }}ms</strong>
                                @if(isset($cacheMetadata['query_time_ms']) && isset($cacheMetadata['cache_saving_ms']))
                                    <span class="text-muted">(Query: {{ $cacheMetadata['query_time_ms'] }}ms)</span>
                                @endif
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
            
            <div class="alert alert-info mt-3 mb-0" style="font-size: 0.85rem;">
                <strong><i class="fas fa-lightbulb"></i> What does this mean?</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>From Cache:</strong> This page was loaded from {{ config('cache.default') }}, which is much faster than querying the database.</li>
                    <li><strong>Fresh Query:</strong> This page was loaded directly from the database and is now cached for future requests.</li>
                    <li><strong>Auto-Invalidation:</strong> Cache automatically clears when data is created, updated, or deleted.</li>
                    @if(isset($cacheMetadata['execution_time_ms']) && isset($cacheMetadata['query_time_ms']))
                        <li class="mt-2">
                            <strong>⚡ Performance Impact:</strong><br>
                            <span class="text-success">• Cache Hit: {{ $cacheMetadata['execution_time_ms'] }}ms</span><br>
                            <span class="text-warning">• Database Query: {{ $cacheMetadata['query_time_ms'] }}ms</span><br>
                            <span class="text-primary">• Speed Improvement: {{ round(($cacheMetadata['query_time_ms'] / max($cacheMetadata['execution_time_ms'], 0.1)), 1) }}x faster with cache!</span>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Handle clear cache button
    $('.clear-cache-btn').on('click', function() {
        var btn = $(this);
        var cacheKey = btn.data('cache-key');
        var originalHtml = btn.html();
        
        // Disable button and show loading
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Clearing...');
        
        // Send AJAX request
        $.ajax({
            url: '/cache/clear-key',
            method: 'POST',
            data: {
                key: cacheKey,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    btn.removeClass('btn-outline-danger')
                       .addClass('btn-success')
                       .html('<i class="fas fa-check"></i> Cleared!');
                    
                    // Show alert
                    Swal.fire({
                        icon: 'success',
                        title: 'Cache Cleared!',
                        text: 'Page will refresh to load fresh data.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function() {
                        // Reload page after showing success
                        location.reload();
                    });
                } else {
                    // Show error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to clear cache'
                    });
                    
                    btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to clear cache. Please try again.'
                });
                
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush

<style>
.cache-indicator-wrapper {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.cache-indicator-wrapper .badge {
    padding: 0.5em 0.8em;
}

.clear-cache-btn {
    transition: all 0.3s ease;
}

.clear-cache-btn:hover {
    transform: scale(1.05);
}
</style>
@endif


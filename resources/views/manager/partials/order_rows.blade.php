@php
    $readOnly = $readOnly ?? false;
    $isReportView = \App\Models\Report::isReportStatus($status ?? null);
@endphp
@foreach ($orders as $order)
    <tr id="orderRow-{{ $order->id }}">
        @unless($readOnly)
        <td>
            @if ($order->pos_order_id)
                <div class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="unsend_order_ids[]" value="{{ $order->id }}" class="unsend-checkbox" />
                    <span class="badge bg-success" title="POS Order ID: {{ $order->pos_order_id }}">POS</span>
                </div>
            @else
                <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" />
            @endif
        </td>
        @endunless
        <td>{{ $order->id }}</td>
        {{-- Website label for storefront orders and the WooCommerce profile with no seller user; otherwise show seller name --}}
        @if ($order->isWebsiteSale())
        <td>Website</td>
        @else
        <td class="{{ $order->seller ? '' : 'text-danger' }}">{{ $order->seller?->name ?? 'Maybe deleted' }}</td>
        @endif
        @if($order->account)
            <td>{{ $order->account->game->title }}</td>
            <td>{{ $order->account->mail }}</td>
            @if(! Auth::user()->roles->contains('name', 'accountant') )
            <td>{{ $order->account->password }}</td>
            @endif
        @elseif($order->card)
            <td>
                {{ $order->card->category?->name }}
                {{-- Code bought from the Next Load supplier at sale time --}}
                @if ($order->card->isFromNextLoad())
                    <span class="badge badge-info bg-info ml-1">Next Load</span>
                @endif
            </td>
            @if(! Auth::user()->roles->contains('name', 'accountant') )
            <td>{{ $order->card->code }}</td>
            @endif
            <td>--</td>
        @endif

        <td>{{ $order->buyer_phone }}</td>
        <td>{{ $order->buyer_name }}</td>

        <td>{{ $order->price }}</td>
        <td>{{ $order->sold_item }}</td>
        @if ($isReportView)
            {{-- Report pages: sell log note, report note, reporter and last report action side by side --}}
            @php
                $report = $order->reports;
                $lastAction = $report?->latestAction;
            @endphp
            <td>{{ $order->notes ?: '—' }}</td>
            <td>{{ $report?->note ?: '—' }}</td>
            <td>{{ $report?->seller?->name ?? '—' }}</td>
            <td>
                @if ($lastAction)
                    {{ $lastAction->label() }} by {{ $lastAction->user?->name ?? 'deleted user' }}, {{ $lastAction->created_at?->format('Y-m-d H:i') }}
                @else
                    —
                @endif
            </td>
        @else
        <td>
            @if (! isset($status))
                {{ $order->notes }}
            @else
            {{ optional($order->reports)->note }}
            @endif
        </td>
        @endif
        <td>{{ $order->created_at }}</td>
        @unless($readOnly)
        <td>
            @if (isset($status) && 'needs_return' === $status)
                <!-- Button for orders with 'needs_return' -->
                <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}"
                    data-sold-item="{{ $order->sold_item }}" data-report-id="{{ $order->reports->id }}">
                    Undo
                </button>
                <button class="btn btn-outline-secondary btn-sm unreport-report" data-report-id="{{ $order->reports->id }}">
                    Unreport
                </button>
            @elseif(isset($status) && 'has_problem' === $status)
                <button class="btn btn-success btn-sm solve-problem" data-report-id="{{ $order->reports->id }}">
                    Mark as Solved
                </button>
                <button class="btn btn-secondary btn-sm archive-report" data-report-id="{{ $order->reports->id }}">
                    Archive
                </button>
                <button class="btn btn-outline-secondary btn-sm unreport-report" data-report-id="{{ $order->reports->id }}">
                    Unreport
                </button>
            @elseif(isset($status) && 'solved' === $status)
                @can('undo-orders')
                <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}" data-sold-item="{{ $order->sold_item }}">
                    Undo
                </button>
                @endcan
                <button class="btn btn-secondary btn-sm archive-report" data-report-id="{{ $order->reports->id }}">
                    Archive
                </button>
                <button class="btn btn-outline-secondary btn-sm unreport-report" data-report-id="{{ $order->reports->id }}">
                    Unreport
                </button>
            @elseif(isset($status) && 'archived' === $status)
                <!-- Undo archive: move report back to solved; Unreport: remove report entirely -->
                <button class="btn btn-primary btn-sm unarchive-report" data-report-id="{{ $order->reports->id }}">
                    Undo
                </button>
                <button class="btn btn-outline-secondary btn-sm unreport-report" data-report-id="{{ $order->reports->id }}">
                    Unreport
                </button>
            @else
                @can('undo-orders')
                <button class="btn btn-danger btn-sm undo-order" data-order-id="{{ $order->id }}" data-sold-item="{{ $order->sold_item }}">
                    Undo
                </button>
                @endcan
                @can('create-order-reports')
                <button class="btn btn-warning btn-sm report-order" data-order-id="{{ $order->id }}" data-toggle="modal" data-target="#reportOrderModal">
                    Actions
                </button>
                @endcan
            @endif
        </td>
        @endunless
    </tr>
@endforeach
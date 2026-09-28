{{-- Running statement: debit = purchases / opening balance, credit = payments --}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Statement</h5>
    <small class="text-muted">Voided entries are shown struck through and do not affect the balance.</small>
</div>

<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead class="thead-light">
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reference</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($statement as $row)
                <tr class="{{ $row['cancelled'] ? 'text-muted' : '' }}" @if ($row['cancelled']) style="text-decoration: line-through;" @endif>
                    <td>{{ $row['date']?->toDateString() }}</td>
                    <td>{{ $row['type_label'] }}@if ($row['cancelled']) (void)@endif</td>
                    <td>
                        @if ($row['url'])
                            <a href="{{ $row['url'] }}">{{ $row['reference'] }}</a>
                        @else
                            {{ $row['reference'] ?? '—' }}
                        @endif
                    </td>
                    <td>{{ $row['description'] }}</td>
                    <td class="text-right">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '' }}</td>
                    <td class="text-right">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                {{-- Empty state --}}
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No transactions yet.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($statement->isNotEmpty())
            <tfoot>
                <tr class="font-weight-bold">
                    <td colspan="6" class="text-right">Balance Due</td>
                    <td class="text-right">{{ number_format($totals['balance'], 2) }} EGP</td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>

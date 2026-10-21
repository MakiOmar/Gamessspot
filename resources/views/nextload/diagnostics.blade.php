{{-- Next Load diagnostics: read-only checks plus every HTTP exchange (secrets masked). Standalone, not indexed. --}}
@php
    $pretty = fn ($value) => is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $allOk = collect($report['checks'])->every(fn ($check) => $check['ok']);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Next Load diagnostics</title>
    <style>
        body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin: 0; background: #f4f6f9; color: #1f2937; }
        main { max-width: 1100px; margin: 0 auto; padding: 24px 16px 48px; }
        h1 { font-size: 1.5rem; margin: 0 0 4px; }
        h2 { font-size: 1.1rem; margin: 28px 0 10px; }
        .muted { color: #6b7280; font-size: .9rem; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        th { width: 220px; color: #4b5563; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: .8rem; font-weight: 600; }
        .ok { background: #dcfce7; color: #166534; }
        .fail { background: #fee2e2; color: #991b1b; }
        .summary { padding: 10px 14px; border-radius: 8px; font-weight: 600; margin-top: 14px; }
        pre { background: #0f172a; color: #e2e8f0; padding: 10px 12px; border-radius: 6px; overflow-x: auto; font-size: .82rem; margin: 6px 0 0; white-space: pre-wrap; word-break: break-word; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } th { width: 140px; } }
        a.button { display: inline-block; margin-top: 10px; padding: 8px 14px; background: #2563eb; color: #fff; border-radius: 6px; text-decoration: none; font-size: .9rem; }
    </style>
</head>
<body>
<main>
    <!-- Header and overall result -->
    <h1>Next Load diagnostics</h1>
    <div class="muted">Ran at {{ $ranAt->toDateTimeString() }} ({{ config('app.timezone') }}). Read-only: no orders are created. Passwords, tokens and card codes are masked.</div>
    <div class="summary {{ $allOk ? 'ok' : 'fail' }}">{{ $allOk ? 'All checks passed' : 'Some checks failed — see details below' }}</div>
    <a class="button" href="{{ request()->fullUrl() }}">Run again</a>

    <!-- Effective configuration (no secrets) -->
    <h2>Configuration</h2>
    <div class="card">
        <table>
            @foreach ($report['config'] as $name => $value)
                <tr>
                    <th>{{ $name }}</th>
                    <td>{{ is_bool($value) ? ($value ? 'yes' : 'no') : ($value ?? '—') }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <!-- Check results -->
    <h2>Checks</h2>
    <div class="card">
        <table>
            @foreach ($report['checks'] as $check)
                <tr>
                    <th>{{ $check['name'] }}</th>
                    <td>
                        <span class="badge {{ $check['ok'] ? 'ok' : 'fail' }}">{{ $check['ok'] ? 'OK' : 'FAIL' }}</span>
                        {{ $check['result'] }} <span class="muted">({{ $check['ms'] }} ms)</span>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>

    <!-- Raw HTTP exchanges in the order they were sent -->
    <h2>Requests and responses ({{ count($report['exchanges']) }})</h2>
    @forelse ($report['exchanges'] as $index => $exchange)
        <div class="card">
            <div>
                <strong>#{{ $index + 1 }} {{ $exchange['request']['method'] }} {{ $exchange['request']['url'] }}</strong>
                <span class="badge {{ ($exchange['response']['status'] ?? 0) >= 200 && ($exchange['response']['status'] ?? 0) < 300 ? 'ok' : 'fail' }}">
                    HTTP {{ $exchange['response']['status'] ?? 'no response' }}
                </span>
                <span class="muted">sent {{ $exchange['sent_at'] }} · {{ $exchange['ms'] }} ms</span>
            </div>
            <div class="grid">
                <div>
                    <div class="muted">Request headers</div>
                    <pre>{{ $pretty($exchange['request']['headers']) }}</pre>
                    <div class="muted">Request body</div>
                    <pre>{{ $exchange['request']['body'] === null ? '(empty)' : $pretty($exchange['request']['body']) }}</pre>
                </div>
                <div>
                    <div class="muted">Response headers</div>
                    <pre>{{ $pretty($exchange['response']['headers']) }}</pre>
                    <div class="muted">Response body</div>
                    <pre>{{ $pretty($exchange['response']['body']) }}</pre>
                </div>
            </div>
        </div>
    @empty
        <div class="card muted">No HTTP requests were made.</div>
    @endforelse
</main>
</body>
</html>

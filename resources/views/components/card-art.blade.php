{{-- Generated gift card artwork for categories without a poster (payload: CardArtPresenter). Markup and CSS mirror the storefront GiftCardArt component. --}}
@props(['art'])

@once
    <style>
        .gift-card-art{container-type:inline-size;width:100%}
        .gift-card-art__card{--gift-card-art-color:#4b5563;display:flex;flex-direction:column;aspect-ratio:1/1;overflow:hidden;border-radius:6cqw;background:var(--gift-card-art-color);color:#fff;font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,.15);text-align:start}
        .gift-card-art__top{display:flex;align-items:center;justify-content:space-between;gap:3cqw;padding:6cqw 7cqw;background:#fff;color:#111}
        .gift-card-art__kicker{display:flex;flex-direction:column;font-size:4cqw;font-weight:700;line-height:1.25;letter-spacing:.05em;white-space:nowrap}
        .gift-card-art__value-wrap{display:flex;align-items:center;gap:3cqw}
        .gift-card-art__value{font-size:11cqw;font-weight:800;line-height:1;white-space:nowrap}
        .gift-card-art__flag{width:13cqw;height:9.75cqw;border-radius:1.5cqw;object-fit:cover;box-shadow:0 0 0 1px rgba(0,0,0,.15)}
        .gift-card-art__body{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4cqw;padding:5cqw 6cqw;background:linear-gradient(160deg,rgba(255,255,255,.16),rgba(0,0,0,.28))}
        .gift-card-art__logo{width:28cqw;height:28cqw;object-fit:contain}
        .gift-card-art__brand{font-size:8cqw;font-weight:700;line-height:1.1;text-align:center}
        .gift-card-art__pill{padding:1.6cqw 4.5cqw;border:1px solid rgba(255,255,255,.6);border-radius:99cqw;font-size:3.8cqw;font-weight:600;letter-spacing:.12em}
    </style>
@endonce

<div {{ $attributes->merge(['class' => 'gift-card-art']) }}>
<div class="gift-card-art__card"
     style="--gift-card-art-color: {{ $art['color'] }}"
     role="img"
     aria-label="{{ $art['brand_label'] }} gift card {{ $art['value'] }}{{ $art['country_code'] ? ' ('.strtoupper($art['country_code']).')' : '' }}">
    {{-- White band: gift card title, face value and flag --}}
    <div class="gift-card-art__top">
        <div class="gift-card-art__kicker">
            <span lang="ar" dir="rtl">بطاقة هدايا</span>
            <span>GIFT CARD</span>
        </div>
        <div class="gift-card-art__value-wrap">
            <span class="gift-card-art__value">{{ $art['value'] }}</span>
            @if (! empty($art['flag_url']))
                <img class="gift-card-art__flag" src="{{ $art['flag_url'] }}" alt="" width="40" height="30" loading="lazy">
            @endif
        </div>
    </div>
    {{-- Brand area: logo (when bundled), brand name and code pill --}}
    <div class="gift-card-art__body">
        @if (! empty($art['logo_url']))
            <img class="gift-card-art__logo" src="{{ $art['logo_url'] }}" alt="" width="80" height="80" loading="lazy">
        @endif
        <span class="gift-card-art__brand">{{ $art['brand_label'] }}</span>
        <span class="gift-card-art__pill">DIGITAL CODE</span>
    </div>
</div>
</div>

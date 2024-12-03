
<div class="row">
    <!-- Card for PS4 Games -->
    <div class="col-md-4">
        <div class="card">
            <img src="{{ asset('assets/img/ps4.png') }}" class="card-img-top" alt="PS4 Games">
            <div class="card-body text-center">
                <h5 class="card-title">PS4 Games</h5>
                <p class="card-text">Explore and manage your PS4 games.</p>
                <a href="{{ route('manager.games.ps4') }}" class="btn btn-primary">Go to PS4 Games</a>
            </div>
        </div>
    </div>

    <!-- Card for PS5 Games -->
    <div class="col-md-4">
        <div class="card">
            <img src="{{ asset('assets/img/ps5.png') }}" class="card-img-top" alt="PS5 Games">
            <div class="card-body text-center">
                <h5 class="card-title">PS5 Games</h5>
                <p class="card-text">Explore and manage your PS5 games.</p>
                <a href="{{ route('manager.games.ps5') }}" class="btn btn-primary">Go to PS5 Games</a>
            </div>
        </div>
    </div>

    <!-- Card for Selling Gift Cards -->
    <div class="col-md-4">
        <div class="card">
            <img src="{{ asset('assets/img/giftcard.webp') }}" class="card-img-top" alt="Sell Gift Cards">
            <div class="card-body text-center">
                <h5 class="card-title">Sell Gift Cards</h5>
                <p class="card-text">Manage and sell gift cards to your customers.</p>
                <a href="{{ route('manager.sell-cards') }}" class="btn btn-primary">Go to Sell Gift Cards</a>
            </div>
        </div>
    </div>
</div>

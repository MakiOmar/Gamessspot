<div class="history">
    <div class="row d-flex justify-content-center">    
        <div class="main-card card">
            <div class="card-header">Recent Activities</div>
            <div class="card-body">
                <div class="vertical-timeline vertical-timeline--animate vertical-timeline--one-column">
                    @forelse($orders as $order)
                        <div class="vertical-timeline-item vertical-timeline-element">
                            <div>
                                <span class="vertical-timeline-element-icon bounce-in">
                                    <i class="badge 
                                        @if($order->status === 'completed') bg-success 
                                        @elseif($order->status === 'pending') bg-warning 
                                        @else bg-danger @endif 
                                        rounded-circle badge-dot-xl">
                                        &nbsp;
                                    </i>
                                </span>
                                <div class="vertical-timeline-element-content bounce-in">
                                    <h4 class="timeline-title">Order ID: {{ $order->id }}</h4>
                                    <p>
                                        <span class="text-success">{{ $order->buyer_name }}</span> has purchased
                                        @if($order->account)
                                            an account for the game <span class="text-error"><b>{{ $order->account->game->title ?? 'Unknown Game' }}</b></span>
                                            using the email <span class="text-warning">{{ $order->account->mail }}</span>.
                                        @elseif($order->card)
                                            a card in the category <span class="text-error"><b>{{ $order->card->category->name ?? 'Unknown Category' }}</b></span>.
                                            @if(! Auth::user()->roles->contains('name', 'accountant'))
                                                The card code is <span class="text-primary"><b>{{ $order->card->code }}</b></span>
                                            @endif
                                        @endif
                                        for {{ $order->price }} EGP.
                                        @if($order->seller)
                                            Seller: <span class="text-primary">{{ ucfirst($order->seller->name) }}</span>.
                                        @endif
                                    </p>
                                    <span class="vertical-timeline-element-date">
                                        {{ $order->created_at->format('h:i A') }} <br> {{ $order->created_at->format('M d Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center">No recent activities to display.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

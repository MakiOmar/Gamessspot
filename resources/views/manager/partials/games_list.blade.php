@foreach($psGames as $game)
    @include('manager.partials.game_card', [
        'game' => $game,
        'platform' => $n
    ])
@endforeach

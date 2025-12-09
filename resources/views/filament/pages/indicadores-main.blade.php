<?php
$team_id = \Filament\Facades\Filament::getTenant()->id;
?>
<style>
    #frame { zoom: 0.90; -moz-transform: scale(0.90); -moz-transform-origin: 0 0; }
</style>

<x-filament-panels::page>
    <div>
        <iframe id="frame" src="{{route('mainview',['team_id'=>$team_id,'tenantSlug' => $team_id])}}" width="100%" height="400px">
            Navegador No compátible
        </iframe>
    </div>
</x-filament-panels::page>

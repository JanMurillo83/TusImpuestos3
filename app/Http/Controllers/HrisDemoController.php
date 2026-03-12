<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class HrisDemoController extends Controller
{
    public function embed(Request $request, string $tenantSlug)
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);

        return view('filament.pages.hris-demo');
    }

    private function resolveTeam(string $tenantSlug): Team
    {
        if (ctype_digit($tenantSlug)) {
            $team = Team::find((int) $tenantSlug);
            if ($team) {
                return $team;
            }
        }

        $team = Team::where('taxid', $tenantSlug)->first();
        if (! $team) {
            abort(404);
        }

        return $team;
    }

    private function ensureTeamAccess(User $user, Team $team): void
    {
        if (! $user->teams()->where('teams.id', $team->id)->exists()) {
            abort(403);
        }
    }
}

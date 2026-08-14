<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StaffSession;
use App\Models\User;
use Carbon\Carbon;

class StaffSessionService
{
    /**
     * Start a new session for the given user.
     *
     * Closes any existing active sessions on the SAME device (session_id)
     * before creating a new one. Sessions on OTHER devices remain active.
     */
    public function startSession(User $user): ?StaffSession
    {
        $type = match ($user->role) {
            'cashier' => 'cashier',
            'kitchen' => 'kitchen',
            default => null,
        };

        if (! $type) {
            return null;
        }

        $this->closeAllSessionsForUser($user);

        return StaffSession::create([
            'user_id' => $user->id,
            'type' => $type,
            'session_id' => session()->getId(),
            'started_at' => now(),
            'last_activity_at' => now(),
            'is_active' => true,
        ]);
    }

    public function endSession(StaffSession $session): void
    {
        $session->ended_at = now();
        $session->is_active = false;
        $session->save();
    }

    public function closeExpiredSessions(int $idleMinutes = 30): int
    {
        $threshold = now()->subMinutes($idleMinutes);

        $expiredSessions = StaffSession::where('is_active', true)
            ->where('last_activity_at', '<', $threshold)
            ->get();

        $closedCount = 0;

        foreach ($expiredSessions as $session) {
            $session->ended_at = $session->last_activity_at;
            $session->is_active = false;
            $session->save();
            $closedCount++;
        }

        return $closedCount;
    }

    public function getActiveSession(User $user): ?StaffSession
    {
        return StaffSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    public function getOrderCount(StaffSession $session): int
    {
        $endTime = $session->ended_at ?? now();

        if ($session->type === 'cashier') {
            return Order::where('cashier_id', $session->user_id)
                ->whereBetween('created_at', [$session->started_at, $endTime])
                ->count();
        }

        return Order::where('processed_by', $session->user_id)
            ->where('status', Order::STATUS_SELESAI)
            ->whereBetween('created_at', [$session->started_at, $endTime])
            ->count();
    }

    public function updateActivity(StaffSession $session): void
    {
        $lastActivity = Carbon::parse($session->last_activity_at);

        if ($lastActivity->diffInSeconds(now()) < 60) {
            return;
        }

        $session->last_activity_at = now();
        $session->save();
    }

    /**
     * Close all active sessions for the user on the CURRENT device only.
     *
     * Uses the Laravel session ID to isolate sessions per device/browser.
     * Active sessions on other devices remain untouched.
     */
    private function closeAllSessionsForUser(User $user): void
    {
        StaffSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('session_id', session()->getId())
            ->update([
                'ended_at' => now(),
                'is_active' => false,
            ]);
    }
}

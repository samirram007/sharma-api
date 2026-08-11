<?php

namespace Modules\User\Services;

use App\Support\Services\BaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\User\Contracts\UserServiceInterface;
use Modules\User\Models\User;
use Modules\User\Models\UserNotificationPreference;
use Modules\User\Models\UserPrintPreference;

class UserService extends BaseService implements UserServiceInterface
{
    protected string $modelClass = User::class;

    protected array $defaultResource = ['roles'];

    public function findOrCreateSocialUser($socialUser, string $provider): User
    {
        // 1. Try to find by provider + provider_id (most reliable)
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($user) {
            // Update avatar/name in case they changed it
            $user->update([
                'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                'avatar' => $socialUser->getAvatar(),
                'email' => $socialUser->getEmail() ?? $user->email,
            ]);

            return $user;
        }

        // 2. If not found by provider_id, try by email (account linking)
        if ($email = $socialUser->getEmail()) {
            $user = User::where('email', $email)->first();
            if ($user) {
                // Link this social account to existing email/password user
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);

                return $user;
            }
        }

        // 3. Create brand new user
        return User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'password' => bcrypt(Str::random(32)),
            'email_verified_at' => $socialUser->getEmail() ? now() : null,
            'status' => 'active',
        ]);
    }

    public function syncAvatar(User $user, ?string $avatarUrl): void
    {
        if ($avatarUrl && $user->avatar !== $avatarUrl) {
            $user->update(['avatar' => $avatarUrl]);
        }
    }

    // ── Notification Preferences ────────────────────────────────

    public function getNotificationPreferences(int $userId): Collection
    {
        $prefs = UserNotificationPreference::where('user_id', $userId)->get();

        // Ensure all 4 types exist (create defaults for missing ones)
        $existingTypes = $prefs->pluck('type')->all();
        $allTypes = ['warning', 'error', 'info', 'success'];

        $newPrefs = collect();
        foreach ($allTypes as $type) {
            if (! in_array($type, $existingTypes)) {
                $newPrefs[] = UserNotificationPreference::create([
                    'user_id' => $userId,
                    'type' => $type,
                    'in_app' => true,
                ]);
            }
        }

        return $prefs->concat($newPrefs)->sortBy('type')->values();
    }

    public function updateNotificationPreferences(int $userId, array $preferences): Collection
    {
        foreach ($preferences as $pref) {
            UserNotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'type' => $pref['type']],
                ['in_app' => $pref['in_app'] ?? true],
            );
        }

        return $this->getNotificationPreferences($userId);
    }

    public function shouldNotify(int $userId, string $type): bool
    {
        $pref = UserNotificationPreference::where('user_id', $userId)
            ->where('type', $type)
            ->first();

        // Default to enabled if no preference set
        return $pref ? $pref->in_app : true;
    }

    // ── Print Preferences ─────────────────────────────────────────

    /**
     * Get the print receipt section visibility for a user, or null when the
     * user hasn't saved any preference yet (the client keeps its local values
     * until the first explicit save).
     */
    public function getPrintPreferences(int $userId): ?array
    {
        $prefs = UserPrintPreference::where('user_id', $userId)->first();

        if (! $prefs) {
            return null;
        }

        return [
            'showFareDetails' => $prefs->show_fare_details,
            'showDocumentInfo' => $prefs->show_document_info,
            'showAuthorizations' => $prefs->show_authorizations,
            'showPaidToAmount' => $prefs->show_paid_to_amount,
        ];
    }

    /**
     * Persist print section visibility for a user. Only the keys present in
     * the payload are updated; missing keys keep their current value.
     */
    public function updatePrintPreferences(int $userId, array $preferences): array
    {
        $prefs = UserPrintPreference::firstOrCreate(['user_id' => $userId]);

        $prefs->update([
            'show_fare_details' => $preferences['show_fare_details'] ?? $prefs->show_fare_details,
            'show_document_info' => $preferences['show_document_info'] ?? $prefs->show_document_info,
            'show_authorizations' => $preferences['show_authorizations'] ?? $prefs->show_authorizations,
            'show_paid_to_amount' => $preferences['show_paid_to_amount'] ?? $prefs->show_paid_to_amount,
        ]);

        return $this->getPrintPreferences($userId);
    }
}

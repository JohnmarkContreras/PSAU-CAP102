<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Arr;
    use Illuminate\Support\Facades\Log;

class UserArchiveController extends Controller
{
    // middleware ensures only admin and superadmin can access these routes
    public function __construct()
    {
        $this->middleware(['auth', 'can:archive-users']);
    }

    public function archive(User $user, Request $request)
    {
        $actor = $request->user()->email ?? 'system';
        $reason = $request->input('reason', 'archived by admin');

        $user->archive($actor, $reason, 'v1', [
            'email' => 'email',
            'username' => 'name',
        ]);

        return redirect()->back()->with('status', 'User archived');
    }


    public function restore(Request $request, $archiveId)
    {
        $actor = $request->user()->email ?? 'system';

        $archive = DB::table('user_archives')->find($archiveId);
        abort_if(! $archive, 404);

        $payload = json_decode($archive->payload, true) ?? [];
        $payload = $this->normalizePayloadForRestore($payload);

        DB::transaction(function () use ($payload, $archiveId, $archive, $actor) {
            // determine existing user by exact email
                $existing = User::where('email', $archive->email)->first();

                $payload = $this->normalizePayloadForRestore($payload);
                $safe = $this->filterSafeFields($payload);

                // if there is an existing active user and strategy is 'update', update it
                $strategy = request('conflict_strategy', 'update');

                if ($existing && $strategy === 'update') {
                    // update existing user with safe fields, keep their login intact
                    $existing->fill($safe);
                    $existing->save();
                    $restoredUserId = $existing->id;
                    $action = 'updated_existing';
                } elseif ($existing && $strategy === 'create_new') {
                    // admin asked to create a new user, so mutate email but record original
                    $originalEmail = $payload['email'] ?? $archive->email;
                    $payload = $this->resolveUniqueConflicts($payload); // will change email
                    $payload['previous_email'] = $originalEmail;
                    $safe = $this->filterSafeFields($payload);
                    $safe['password'] = $safe['password'] ?? Hash::make(Str::random(24));
                    $newUser = User::create($safe);
                    $restoredUserId = $newUser->id;
                    $action = 'created_restored_conflict';
                } else {
                    // no existing user -> create new with original email
                    $safe['password'] = $safe['password'] ?? Hash::make(Str::random(24));
                    $newUser = User::create($safe);
                    $restoredUserId = $newUser->id;
                    $action = 'created_restored';
                }

            // mark archive as restored (preserve row for provenance)
            DB::table('user_archives')->where('id', $archiveId)->update([
                'archive_reason' => 'restored',
                'updated_at'     => now(),
                'schema_version' => $archive->schema_version,
            ]);

            Log::info('user archive restored', [
                'archive_id' => $archiveId,
                'restored_user_id' => $restoredUserId,
                'actor' => $actor,
                'action' => $action,
                'archived_at' => $archive->archived_at,
            ]);
        });

        return redirect()->back()->with('status', 'User restored');
    }


    protected function normalizePayloadForRestore(array $payload): array
    {
        // Remove primary key and system-only fields
        unset($payload['id'], $payload['created_at'], $payload['updated_at']);
        // If password exists in payload leave it; otherwise mark for reset flow
        return $payload;
    }

    protected function filterSafeFields(array $payload): array
    {
        $allowed = [
            'name', 'email', 'phone', 'address', 'avatar', 'account_id'
        ];
        return array_filter(
            array_intersect_key($payload, array_flip($allowed)),
            function ($v) { return !is_array($v); }
        );
    }
    protected function resolveUniqueConflicts(array $payload): array
    {
        // Email conflict: append +restored timestamp if needed
        if (!empty($payload['email']) && \App\User::where('email', $payload['email'])->exists()) {
            $base = strstr($payload['email'], '@', true) ?: $payload['email'];
            $domain = strstr($payload['email'], '@') ?: '';
            $payload['email'] = $base . '+restored' . time() . $domain;
        }

        // If you previously stored username but don't have the column, create a handle from name
        if (empty($payload['username']) && !empty($payload['name'])) {
            $candidate = \Illuminate\Support\Str::slug($payload['name']);
            $candidate = $candidate ?: 'user' . time();
            $i = 0;

            // Ensure uniqueness against name and email (no username column)
            while (\App\User::where('name', $payload['name'])->exists() || \App\User::where('email', $payload['email'])->exists()) {
                $i++;
                $candidate = \Illuminate\Support\Str::slug($payload['name']) . '_r' . $i;
                // If your users table later gets a username column, you can check it here
                if (isset((new \App\User)->username)) {
                    // noop; kept for future compatibility
                }
                // break a pathological loop
                if ($i > 100) { $candidate .= '_' . time(); break; }
            }

            // Optionally store the generated handle in payload for other systems
            $payload['generated_handle'] = $candidate;
        } else {
            // If payload has username but your DB doesn't, move to generated_handle
            if (!empty($payload['username']) && ! \Schema::hasColumn('users', 'username')) {
                $payload['generated_handle'] = \Illuminate\Support\Str::slug($payload['username']) ?: $payload['username'];
                unset($payload['username']);
            }
        }

        return $payload;
    }

    public function index(Request $request)
    {
        $archives = DB::table('user_archives')->orderBy('archived_at', 'desc')->paginate(25);
        return view('user_archive.index', compact('archives'));
    }

    public function show($user_id)
    {
        $archives = DB::table('user_archives')->find($user_id);
        abort_if(! $archives, 404);
        return view('user_archive.show', ['archive' => $archives, 'payload' => json_decode($archives->payload, true)]);
    }
}

<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Support\Facades\DB;

class SyncSpatieToVoyagerRolesSeeder extends Seeder
{
    public function run()
    {
        $roles = SpatieRole::all();
        foreach ($roles as $r) {
            DB::table('roles')->updateOrInsert(
                ['name' => $r->name, 'guard_name' => $r->guard_name ?? config('auth.defaults.guard')],
                [
                    'display_name' => $r->getAttribute('display_name') ?? ucfirst(str_replace('_', ' ', $r->name)),
                    'description'  => $r->getAttribute('description') ?? null,
                    'created_at'   => $r->created_at ?? now(),
                    'updated_at'   => $r->updated_at ?? now(),
                ]
            );
        }
    }
}
<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait ArchivesModel
{
    public function archive(string $by = 'system', string $reason = null, string $schemaVersion = 'v1', array $denormalize = [])
    {
        DB::transaction(function () use ($by, $reason, $schemaVersion, $denormalize) {
            $payload = $this->toArray();

            $record = [
                'user_id'        => $this->id ?? null,
                'payload'        => json_encode($payload),
                'archived_by'    => $by,
                'archive_reason' => $reason,
                'schema_version' => $schemaVersion,
                'archived_at'    => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            foreach ($denormalize as $col => $path) {
                $value = data_get($payload, $path);

                // special-case: derive username from name when username not present
                if ($col === 'username' && empty($value)) {
                    $value = data_get($payload, 'name');
                }

                $record[$col] = is_scalar($value) ? $value : null;
            }


            DB::table('user_archives')->insert($record);

            if (method_exists($this, 'delete')) {
                $this->delete();
            } else {
                DB::table($this->getTable())->where('id', $this->id)->delete();
            }
        });
    }
}
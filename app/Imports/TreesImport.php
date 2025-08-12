<?php

namespace App\Imports;

use App\Tree;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class TreesImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
{
    if (empty($row['code'])) {
        return null;
    }

    $validTypes = ['sweet', 'semi_sweet', 'sour'];
    if (!in_array(strtolower($row['type']), $validTypes)) {
        return null;
    }

    // ✅ Use code as the unique identifier
    return Tree::updateOrCreate(
        ['code' => $row['code']], // Find by code
        [
            'type' => strtolower($row['type']),
            'age' => $row['age_years'],
            'height' => $row['height_m'],
            'stem_diameter' => $row['stem_diameter_cm'],
            'canopy_diameter' => $row['canopy_diameter_m'],
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
        ]
    );
}
}

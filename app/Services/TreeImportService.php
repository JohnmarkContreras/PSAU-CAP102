<?php

namespace App\Services;

use App\Imports\TreesImport;
use Maatwebsite\Excel\Facades\Excel;
use App\User;
class TreeImportService
{
    public function import($file)
    {
        Excel::import(new TreesImport, $file);
    }
}
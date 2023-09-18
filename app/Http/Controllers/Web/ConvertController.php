<?php

namespace App\Http\Controllers\Web;

use App\Traits\Loggable;
use Excel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ConvertController
{
    use Loggable;

    public function index(): View
    {
        return view('convert.index');
    }

    public function getFile(Request $request): View
    {
        $file = $request->file('file');

        $excel   = Excel::toArray(fn($data) => $data, $file);
        $columns = [];

        foreach ($excel as $items) {

            foreach ($items as $col => $row) {

                if ($col === 0) {
                    continue;
                } else if ($col === 1) {
                    $columns[] = array_filter($row, fn($item) => !empty($item));
                } else {
                    break;
                }

            }

        }

        $columns = array_merge(...$columns);

        return view('convert.index', compact('columns', 'file'));
    }
}

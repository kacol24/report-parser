<?php

namespace App\Http\Controllers;

use App\Exceptions\ReportTypeNotFoundException;
use App\Imports\MajooImporter;
use App\Imports\ReportImporter;
use Illuminate\Http\Request;

class ParserController extends Controller
{
    public function __invoke(Request $request)
    {
        switch ($request->report_type) {
            case 'majoo':
                app()->bind(ReportImporter::class, MajooImporter::class);
                break;
            default:
                throw new ReportTypeNotFoundException('Report type selected is not supported.', 500);
        }
    }
}

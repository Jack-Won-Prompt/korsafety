<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\Request;

/** 화면(브라우저)에서 난 웹스크립트 오류 접수 — 누구나 보낼 수 있으므로 내용만 받아 저장한다 */
class ClientErrorController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'kind' => 'nullable|string|max:60',
            'url' => 'nullable|string|max:1000',
            'file' => 'nullable|string|max:500',
            'line' => 'nullable|integer|min:0|max:99999999',
            'stack' => 'nullable|string|max:20000',
        ]);

        ErrorLog::captureClient($data);

        return response()->json(['ok' => true], 202);
    }
}

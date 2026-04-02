<?php

namespace App\Http\Controllers;

use App\Models\AiTrace;
use App\Models\WhatsappMessageLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiTraceController extends Controller
{
    public function show(Request $request, WhatsappMessageLog $log): View
    {
        abort_unless($log->user_id === $request->user()->id, 403);

        $trace = AiTrace::query()
            ->with(['incomingLog', 'outgoingLog'])
            ->where('outgoing_whatsapp_message_log_id', $log->id)
            ->firstOrFail();

        return view('whatsapp.ai-trace', [
            'log' => $log,
            'trace' => $trace,
        ]);
    }
}

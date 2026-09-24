<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->user()->notices();
        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }
        if ($request->query('show') === 'unread') {
            $query->whereNull('read_at');
        }

        return view('notices.index', [
            'notices' => $query->paginate(20)->withQueryString(),
            'unread' => $request->user()->unreadNoticeCount(),
        ]);
    }

    public function open(Request $request, Notice $notice)
    {
        abort_unless($notice->user_id === $request->user()->id, 404);
        if (! $notice->read_at) {
            $notice->update(['read_at' => now()]);
        }

        return $notice->link ? redirect($notice->link) : redirect()->route('notices.index');
    }

    public function readAll(Request $request)
    {
        $request->user()->notices()->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}

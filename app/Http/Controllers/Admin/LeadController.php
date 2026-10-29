<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status', 'all');
        abort_unless(in_array($status, ['all', 'new', 'contacted', 'enrolled'], true), 404);

        $teacherScope = fn ($query) => $query->when(
            $user->isTeacher(),
            fn ($q) => $q->where(function ($sub) use ($user) {
                $sub->whereNull('course_id')
                    ->orWhereHas('course', fn ($cq) => $cq->where('teacher_id', $user->teacher_id));
            })
        );

        $leads = $teacherScope(Lead::with('course'))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = $teacherScope(Lead::query())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.leads.index', compact('leads', 'status', 'counts'));
    }

    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($request->user()->canManageLead($lead), 403);

        $data = $request->validate([
            'status' => ['required', 'in:new,contacted,enrolled'],
        ]);

        $lead->update(['status' => $data['status']]);

        return back()->with('success', 'Murojaat holati yangilandi.');
    }

    public function destroy(Request $request, Lead $lead): RedirectResponse
    {
        abort_unless($request->user()->canManageLead($lead), 403);

        $lead->delete();

        return back()->with('success', 'Murojaat o\'chirildi.');
    }
}

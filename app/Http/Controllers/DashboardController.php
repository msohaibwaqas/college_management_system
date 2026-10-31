<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function clearCache()
    {
        Artisan::call('optimize:clear');
        return redirect()->back()->with('success', 'System cache cleared successfully!');
    }

    public function index()
    {
        $user = Auth::user();
        $activeRole = $this->dashboardService->resolveActiveRole($user);

        if ($activeRole === 'admin' || $activeRole === 'principal') {
            $data = $this->dashboardService->getAdminDashboardData();
            return view('dashboards.admin', $data);
        }

        if ($activeRole === 'teacher' || $activeRole === 'hod') {
            $staff = $user->staff;
            if (!$staff) {
                return view('dashboards.teacher', ['error' => 'No staff record linked to this user.']);
            }
            $data = $this->dashboardService->getTeacherDashboardData($staff);

            // Add HOD department info if user is HOD
            $isHod = $user->hasRole('hod') || !empty($user->getHodSubjectIds());
            $data['isHod'] = $isHod;
            if ($isHod) {
                $data['hodSubjects'] = \App\Models\Subject::where('hod_staff_id', $staff->id)
                    ->withCount(['staffSubjectSpecializations as teachers_count'])
                    ->get();
            } else {
                $data['hodSubjects'] = collect();
            }

            return view('dashboards.teacher', $data);
        }

        if (in_array($activeRole, ['clerk', 'accountant', 'hssc_clerk', 'hssc_admin', 'hssc_accountant', 'bs_clerk', 'bs_admin', 'bs_accountant'])) {
            $data = $this->dashboardService->getRoleData($activeRole);
            return view("dashboards.{$activeRole}", $data);
        }

        return redirect()->route('lms.dashboard');
    }
}

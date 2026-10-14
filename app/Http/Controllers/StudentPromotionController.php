<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\Admissions;
use Carbon\Carbon;

class StudentPromotionController extends Controller
{
    /**
     * Show the student promotion management page
     */
    public function index()
    {
        // Get statistics
        $currentDate = Carbon::now();
        
        // Students in Class 11
        $class11Students = Admissions::where('current_class', '11')
            ->whereIn('status', ['Admitted', 'Active'])
            ->count();
        
        // Students in Class 12
        $class12Students = Admissions::where('current_class', '12')
            ->whereIn('status', ['Admitted', 'Active'])
            ->count();
        
        // Students eligible for promotion (Class 11, admission year allows promotion)
        $eligibleForPromotion = Admissions::where('current_class', '11')
            ->whereIn('status', ['Admitted', 'Active'])
            ->whereRaw('DATE_ADD(CONCAT(admission_year, "-06-01"), INTERVAL 0 DAY) <= ?', [$currentDate->format('Y-m-d')])
            ->count();
        
        // Students eligible for graduation (Class 12, admission year allows graduation)
        $eligibleForGraduation = Admissions::where('current_class', '12')
            ->whereIn('status', ['Admitted', 'Active'])
            ->whereRaw('DATE_ADD(CONCAT(admission_year, "-04-30"), INTERVAL 2 YEAR) <= ?', [$currentDate->format('Y-m-d')])
            ->count();
        
        // Get recent promotions/graduations (last 30 days)
        $recentUpdates = Admissions::with('student')
            ->where('updated_at', '>=', $currentDate->subDays(30))
            ->whereIn('status', ['Admitted', 'Graduated'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
        
        return view('students.promotion', compact(
            'class11Students',
            'class12Students',
            'eligibleForPromotion',
            'eligibleForGraduation',
            'recentUpdates',
            'currentDate'
        ));
    }
    
    /**
     * Manually trigger student status update
     */
    public function update(Request $request)
    {
        $force = $request->has('force');
        
        try {
            // Run the artisan command
            $exitCode = Artisan::call('students:update-status', [
                '--force' => $force
            ]);
            
            // Get the output
            $output = Artisan::output();
            
            if ($exitCode === 0) {
                return redirect()->route('students.promotion')
                    ->with('success', 'Student status updated successfully!')
                    ->with('output', $output);
            } else {
                return redirect()->route('students.promotion')
                    ->with('error', 'Failed to update student status.')
                    ->with('output', $output);
            }
        } catch (\Exception $e) {
            return redirect()->route('students.promotion')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
}

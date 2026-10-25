<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Program;
use App\Models\Download;
use App\Models\Datesheet;
use App\Models\Album;
use App\Models\Update;
use App\Models\Principal;
use App\Models\SubjectGroup;
use App\Models\FeeVoucher;
use App\Models\StudentFine;
use App\Models\InstituteSetting;
use App\Services\PublicPortalService;
use App\Services\Voucher\VoucherFactory;

class PublicController extends Controller
{
    public function __construct(
        protected PublicPortalService $publicPortalService
    ) {}

    public function index()
    {
        $data = $this->publicPortalService->getHomeData();
        return view('public.home', $data);
    }

    public function staff()
    {
        $staffMembers = Staff::where('status', 'active')
            ->orderByRaw("FIELD(category, 'teaching', 'non_teaching')")
            ->orderBy('bps', 'desc')
            ->orderBy('entry_date_govt_service', 'asc')
            ->paginate(24);

        return view('public.staff', compact('staffMembers'));
    }

    public function programs()
    {
        $compulsorySubjects = Subject::where('type', 'Compulsory')
            ->whereIn('class_level', ['11', '12', 'both'])
            ->orderBy('name')
            ->get();

        $subjectGroups = SubjectGroup::with('subjects')->orderBy('name')->get();

        $programs = Program::with(['semesters.bsSubject'])
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('public.programs', compact('compulsorySubjects', 'subjectGroups', 'programs'));
    }

    public function department(Subject $subject)
    {
        $data = $this->publicPortalService->getDepartmentData($subject);
        return view('public.staff', $data);
    }

    public function downloads(Request $request)
    {
        $query = Download::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $downloads = $query->orderBy('published_date', 'desc')->paginate(50);
        return view('public.downloads', compact('downloads'));
    }

    public function datesheets()
    {
        $datesheets = Datesheet::where('status', 'active')
            ->orderBy('publish_date', 'desc')
            ->paginate(10);
            
        return view('public.datesheets', compact('datesheets'));
    }

    public function gallery()
    {
        $albums = Album::where('status', 1)
            ->with(['photos' => function($query) {
                $query->where('status', 1)->limit(1);
            }])
            ->withCount(['photos' => function($query) {
                $query->where('status', 1);
            }])
            ->orderBy('date', 'desc')
            ->paginate(12);
            
        return view('public.gallery', compact('albums'));
    }

    public function album($id)
    {
        $album = Album::where('status', 1)->with(['photos' => function($query) {
            $query->where('status', 1);
        }])->findOrFail($id);
        
        return view('public.album', compact('album'));
    }

    public function news()
    {
        $updates = Update::where('status', 1)
            ->orderBy('date', 'desc')
            ->paginate(10);
            
        return view('public.news', compact('updates'));
    }

    public function principals()
    {
        $principals = Principal::orderBy('is_current', 'desc')
            ->orderBy('from_date', 'desc')
            ->get();
            
        return view('public.principals', compact('principals'));
    }

    public function principalShow($id)
    {
        $principal = Principal::findOrFail($id);
        return view('public.principal-show', compact('principal'));
    }

    public function studentsCorner()
    {
        $data = $this->publicPortalService->getStudentsCornerData();
        return view('public.students-corner', $data);
    }

    public function searchStudent(Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('public.students-corner');
        }

        $request->validate([
            'admission_year' => 'required|integer',
            'search_value' => 'required|string',
            'program_type' => 'required|string',
            'captcha' => 'required|integer',
        ]);

        if ($request->captcha != session('captcha_answer')) {
            return back()->with('error', 'Incorrect CAPTCHA answer. Please try again.')->withInput();
        }

        $admissionYear = $request->admission_year;
        $searchValue = $request->search_value;
        $programType = $request->program_type;

        if ($programType === 'hssc') {
            $result = $this->publicPortalService->findHsscStudentProfile($admissionYear, $searchValue);
            if (isset($result['error'])) {
                return redirect()->route('public.students-corner')->with('error', $result['error']);
            }
            return view('public.student-profile', $result['data']);
        } else {
            $result = $this->publicPortalService->findHeStudentProfile($admissionYear, $searchValue, $programType);
            if (isset($result['error'])) {
                return redirect()->route('public.students-corner')->with('error', $result['error']);
            }
            return view('public.student-profile-he', $result['data']);
        }
    }

    public function downloadStudentVoucher(FeeVoucher $voucher)
    {
        $voucher->load(['items', 'student.admissions', 'student.heAdmissions', 'student.attribute', 'student.contact', 'application']);
        $settings = InstituteSetting::getSettings();
        $printable = VoucherFactory::create($voucher, $settings);
        
        return view('pdfs.fee-voucher', ['voucher' => $printable, 'settings' => $settings]);
    }

    public function downloadFineVoucher(StudentFine $fine)
    {
        $fine->load(['student.father', 'student.attribute', 'student.contact', 'student.admissions', 'student.vouchers', 'admission.student', 'fineRule']);
        $settings = InstituteSetting::getSettings();
        $printable = VoucherFactory::create($fine, $settings);
        
        return view('pdfs.fee-voucher', ['voucher' => $printable, 'settings' => $settings]);
    }

    public function about()
    {
        return view('public.about');
    }

    public function contact()
    {
        return view('public.contact');
    }

    public function dutyRosterLookup(Request $request)
    {
        $staffId = auth()->user()?->staff_id;
        $data = $this->publicPortalService->getDutyRosterForStaff($staffId);
        
        return view('public.duty_roster', $data);
    }

    public function serveImage($path)
    {
        $storagePath = storage_path('app/public');
        $fullPath = $storagePath . '/' . $path;

        if (!file_exists($fullPath) || strpos(realpath($fullPath), realpath($storagePath)) !== 0 || is_dir($fullPath)) {
            abort(404);
        }

        $file = file_get_contents($fullPath);
        $type = mime_content_type($fullPath);

        return response($file)->header('Content-Type', $type)
                              ->header('Cache-Control', 'public, max-age=86400');
    }
}

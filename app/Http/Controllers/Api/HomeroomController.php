<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Homeroom;
use App\Models\Student;
use Illuminate\Http\Request;

class HomeroomController extends Controller {
    public function index() {
        $assignments = Homeroom::with('staff:id,name')->get();
        $years = Student::where('status', 'Aktif')->distinct()->pluck('enrollment_year');
        return response()->json(['assignments' => $assignments, 'active_years' => $years]);
    }
    public function store(Request $request) {
        $validated = $request->validate([
            'enrollment_year' => 'required|digits:4',
            'staff_id' => 'required|exists:staff,id',
        ]);
        $homeroom = Homeroom::updateOrCreate(
            ['enrollment_year' => $validated['enrollment_year']],
            ['staff_id' => $validated['staff_id']]
        );
        return response()->json($homeroom->load('staff:id,name'), 201);
    }
}

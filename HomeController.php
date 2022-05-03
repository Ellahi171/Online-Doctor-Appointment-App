<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Forms\DoctorForm;
use App\Forms\ProfileForm;
use App\Schedule;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Kris\LaravelFormBuilder\FormBuilder;
use Yajra\DataTables\Facades\DataTables;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        parent::__construct();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $schedules = Schedule::count();
        $appointments = Appointment::count();
        $patients = User::where('role',User::DOCTOR)->count();
        $doctors = User::where('role',User::PATIENT)->count();

        return view('home',compact('schedules','appointments','patients','doctors'));
    }

    /**
     * @param FormBuilder $formBuilder
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function profile(FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(\App\Forms\ProfileForm::class, [
            'method' => 'POST',
            'url' => url('update_profile')
        ],['user' => $this->user]);

        return view('profile', compact('form'));
    }

    /**
     * @param Request $request
     * @param FormBuilder $formBuilder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request,FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(ProfileForm::class);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        $this->user->name = $request->name;
        $this->user->email = $request->email;
        $this->user->password = Hash::make($request->password);
        $this->user->save();

        return redirect()->back()->with('message', 'Profile Update Successfully');
    }

    public function patientList(Request $request)
    {
        if($request->ajax()){
            $patients = DB::table('users')
                ->where('role',User::PATIENT);

            return DataTables::of($patients)->make(true);
        }
        return view('patient_list');
    }
}

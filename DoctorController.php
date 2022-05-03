<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Forms\DoctorForm;
use App\Schedule;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kris\LaravelFormBuilder\FormBuilder;
use Yajra\DataTables\Facades\DataTables;

class DoctorController extends Controller
{
    /**
     * DoctorController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        parent::__construct();
    }

    public function doctorList(Request $request)
    {
        if($request->ajax()){

            $users = User::select(['id','name','email','phone_no','specialty','date_of_birth','degree'])
                ->where('role',User::DOCTOR);

            return DataTables::of($users)
                ->addColumn('action', function ($row){
                    return '<div class="btn-group" role="group" aria-label="Basic example">
                    <a data-id="'.$row->id.'" href="javascript:;" type="button" class="btn btn-primary btn-sm edit-doc">Edit</a>
                    <a href="'.url('delete_doctor').'/'.$row->id.'" type="button" class="btn btn-danger btn-sm">Delete</a>
                </div>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('doctor.doctor_list');
    }

    /**
     * @param FormBuilder $formBuilder
     * @return string
     */
    public function addDoctor(FormBuilder $formBuilder)
    {
        return form($formBuilder->create(\App\Forms\DoctorForm::class, [
            'method' => 'POST',
            'url' => url('create_doctor')
        ]));
    }

    /**
     * @param Request $request
     * @param FormBuilder $formBuilder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createDoctor(Request $request,FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(DoctorForm::class);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        User::create(
            [
                'role' => User::DOCTOR,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'name' => $request->name,
                'phone_no' => $request->phone_no,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'degree' => $request->degree,
                'specialty' => $request->specialty,
                'email_verified_at' => Carbon::now(),
            ]
        );

        return redirect()->back()->with('message', 'Doctor Added Successfully');
    }

    /**
     * @param FormBuilder $formBuilder
     * @param $id
     * @return string
     */
    public function editDoctor(FormBuilder $formBuilder, $id)
    {
        return form($formBuilder->create(\App\Forms\DoctorForm::class, [
            'method' => 'POST',
            'url' => url('edit_doctor')
        ],['user' => User::find($id)]));
    }

    /**
     * @param Request $request
     * @param FormBuilder $formBuilder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editDoctorPost(Request $request, FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(DoctorForm::class);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        $user = User::find($request->doctor_id);
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->name = $request->name;
        $user->phone_no = $request->phone_no;
        $user->address = $request->address;
        $user->date_of_birth = $request->date_of_birth;
        $user->degree = $request->degree;
        $user->specialty = $request->specialty;
        $user->save();

        return redirect()->back()->with('message', 'Doctor Updated Successfully');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteDoctor($id)
    {
        User::find($id)->delete();

        $schedules = Schedule::where('user_id',3)->get()->pluck('id')->toArray();

        Schedule::where('user_id', $id)->delete();

        Appointment::whereIn('schedule_id', $schedules)->delete();

        return redirect()->back()->with('message', 'Doctor Deleted Successfully');
    }
}

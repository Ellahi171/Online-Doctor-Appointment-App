<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Schedule;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class AppointmentController extends Controller
{
    /**
     * AppointmentController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth');
        parent::__construct();
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function appointmentData($id)
    {
        $schedule = DB::table('schedules as s')
            ->join('users as u', 'u.id','=','s.user_id')
            ->select(['s.id as schedule_id','s.*','u.*'])
            ->where('s.id', $id)
            ->first();

        return view('appointment._appointment_data',compact('schedule'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bookAppointment(Request $request)
    {
        if(Appointment::where('schedule_id',$request->schedule_id)->first()){
            return redirect()->back()->with('message', 'Appointment Already Exists');
        }

        Appointment::create([
            'user_id' => $this->user->id,
            'schedule_id' => $request->schedule_id,
            'appointment_reason' => $request->appointment_reason
        ]);

        return redirect()->back()->with('message', 'Appointment Added Successfully');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function appointmentList(Request $request)
    {
        if($request->ajax()){
            $appointments = DB::table('appointments as a')
                ->select(['a.id as appointment_id','p.name as patient_name','s.schedule_date',
                    's.start_time','d.name as doctor_name'])
                ->join('schedules as s', 's.id','=','a.schedule_id')
                ->join('users as p','p.id','=','a.user_id')
                ->join('users as d','d.id','=','s.user_id');

            if($this->user->role == User::PATIENT){
                $appointments->where('a.user_id', $this->user->id);
            }

            if($this->user->role == User::DOCTOR){
                $appointments->where('s.user_id', $this->user->id);
            }

            return DataTables::of($appointments)
                ->addColumn('appointment_day', function ($row){
                    return Carbon::parse($row->schedule_date)->dayName;
                })
                ->addColumn('action', function ($row){
                    return '<div class="btn-group" role="group" aria-label="Basic example">
                    <a href="'.url('delete_appointment').'/'.$row->appointment_id.'" type="button" class="btn btn-danger btn-sm">Delete</a>
                </div>';
                })
                ->rawColumns(['appointment_day','action'])
                ->make(true);
        }

        return view('appointment.appointment_list');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteAppointment($id)
    {
        Appointment::find($id)->delete();

        return redirect()->back()->with('message', 'Appointment Deleted Successfully');
    }
}

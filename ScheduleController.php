<?php

namespace App\Http\Controllers;

use App\Appointment;
use App\Forms\DoctorForm;
use App\Forms\ScheduleForm;
use App\Schedule;
use App\User;
use Illuminate\Http\Request;
use Kris\LaravelFormBuilder\FormBuilder;
use Yajra\DataTables\Facades\DataTables;

class ScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        parent::__construct();
    }

    /**
     * @param Request $request
     * @param FormBuilder $formBuilder
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function doctorSchedule(Request $request, FormBuilder $formBuilder)
    {
        if($request->ajax() && !$request->has('draw')){
            return form($formBuilder->create(\App\Forms\ScheduleForm::class, [
                'method' => 'POST',
                'url' => url('create_schedule')
            ]));
        }

        if($request->ajax() && $request->has('draw')){
            $schedules = Schedule::select(['id','schedule_date','start_time','end_time','duration']);

            if($this->user->role == User::DOCTOR){
                $schedules->where('user_id',$this->user->id);
            }

            return DataTables::of($schedules)
                ->addColumn('action', function ($row){
                    return '<div class="btn-group" role="group" aria-label="Basic example">
                    <a data-id="'.$row->id.'" href="javascript:;" type="button" class="btn btn-primary btn-sm edit-sche">Edit</a>
                    <a href="'.url('delete_schedule').'/'.$row->id.'" type="button" class="btn btn-danger btn-sm">Delete</a>
                </div>';
                })
                ->addColumn('duration', function ($row){
                    return $row->duration.' Minutes';
                })
                ->rawColumns(['action', 'duration'])
                ->make(true);
        }

        return view('doctor.doctor_schedule');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createSchedule(Request $request, FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(ScheduleForm::class);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        Schedule::where('schedule_date',$request->schedule_date)->delete();

        $data = $request->except('_token');
        $data['user_id'] = $this->user->id;

        Schedule::create($data);

        return redirect()->back()->with('message', 'Schedule Added Successfully');
    }

    /**
     * @param FormBuilder $formBuilder
     * @param $id
     * @return string
     */
    public function editSchedule(FormBuilder $formBuilder, $id)
    {
        return form($formBuilder->create(\App\Forms\ScheduleForm::class, [
            'method' => 'POST',
            'url' => url('edit_schedule')
        ],['schedule' => Schedule::find($id)]));
    }

    /**
     * @param Request $request
     * @param FormBuilder $formBuilder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editSchedulePost(Request $request, FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(ScheduleForm::class);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        Schedule::where('schedule_date', $request->schedule_date)
            ->where('id','!=',$request->schedule_id)->delete();

        $schedule = Schedule::find($request->schedule_id);
        $schedule->schedule_date = $request->schedule_date;
        $schedule->start_time = $request->start_time;
        $schedule->end_time = $request->end_time;
        $schedule->duration = $request->duration;
        $schedule->save();

        return redirect()->back()->with('message', 'Schedule Updated Successfully');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteSchedule($id)
    {
        Schedule::find($id)->delete();

        Appointment::where('schedule_id', $id)->delete();

        return redirect()->back()->with('message', 'Schedule Deleted Successfully');
    }
}

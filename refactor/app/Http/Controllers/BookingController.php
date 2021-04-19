<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $response = [];

        if ($user_id = (int)$request->get('user_id')) {
            $response = $this->repository->getUsersJobs($user_id);
        }
        // get config variables from app config file instead of accessing it directly with `env()` helper method
        // one statement per line
        elseif (isset($request->__authenticatedUser) &&
            ($request->__authenticatedUser->user_type == config('app.ADMIN_ROLE_ID') ||
                $request->__authenticatedUser->user_type == config('app.SUPERADMIN_ROLE_ID'))
        ) {
            $response = $this->repository->getAll($request);
        }

        // response should be in json/xml and the proper status code
        // status code is extracted from \Illuminate\Http\Response
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        // check for the entity in the db and return 404 if it's not exists.
        if (!$job = $this->repository->with('translatorJobRel.user')->find($id)) {
            return response()->json(['message' => 'Job not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($job, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    // e.g StoreBookRequest and define validation rules in that file
    public function store(StoreBookRequest $request)
    {
        try {
            // use validated menthod instead and get the data validated from StoreBookRequest
            $response = $this->repository->store($request->__authenticatedUser, $request->validated());
            return response()->json($response, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            // log this exception
            Log::critical('BOOK_CREATION_FAILED', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'New book creation failed'], Response::HTTP_INTERNEL_SERVER_ERROR);
        }
    }

    /**
     * @param $id
     * @param UpdateBookRequest $request
     * @return mixed
     */
    // define validation rules in UpdateBookRequest
    public function update($id, UpdateBookRequest $request)
    {
        // get the validated data instead of ->all()
        // here just add $data and define fillables in the Model
        try {
            $response = $this->repository->updateJob($id, $request->validated(), $request->__authenticatedUser);
            return response()->json($response, Response::HTTP_OK);
        } catch (\Throwable $e) {
            // log this exception
            Log::critical('BOOK_UPDATION_FAILED', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'Book update failed'], Response::HTTP_INTERNEL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        try {
            $response = $this->repository->storeJobEmail($request->validated());
            return response()->json($response, Response::HTTP_OK);
        } catch (\Throwable $e) {
            // log this exception
            Log::critical('JOB_EMAIL_FAILED', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'Job email has been failed'], Response::HTTP_INTERNEL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $response = [];
        if ($user_id = $request->get('user_id')) {
            // $request should not be passed to the repository instead you can pass validated data
            // but i would not refactor it for now
            $response = $this->repository->getUsersJobsHistory($user_id, $request);
        }
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    // AcceptJobRequest
    public function acceptJob(AcceptJobRequest $request)
    {
        $response = $this->repository->acceptJob($request->validated(), $request->__authenticatedUser);
        return response()->json($response, Response::HTTP_OK);
    }

    public function acceptJobWithId(Request $request)
    {
        // variables should be named properly
        $job_id = (int)$request->get('job_id');
        $response = $this->repository->acceptJobWithId($job_id, $request->__authenticatedUser);
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $response = $this->repository->cancelJobAjax($request->validated(), $request->__authenticatedUser);
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $response = $this->repository->endJob($request->validated());
        return response()->json($response, Response::HTTP_OK);
    }

    public function customerNotCall(Request $request)
    {
        $response = $this->repository->customerNotCall($request->validated());
        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $response = $this->repository->getPotentialJobs($request->__authenticatedUser);
        return response()->json($response, Response::HTTP_OK);
    }

    /*
     * use Request validation rules instead and use the proper request class for this
     * DistanceFeedRequest
     * and validate all the params to this class and then get the validated data from it
     * with $request->validated() method
     */
    public function distanceFeed(DistanceFeedRequest $request)
    {
        $validated = $request->validated();

        // handle this in Request file with validation rules
//        if ($data['flagged'] == 'true') {
//            if($data['admincomment'] == '') return "Please, add comment";
//            $flagged = 'yes';
//        } else {
//            $flagged = 'no';
//        }

        if (isset($validated['time']) && isset($validated['distance'])) {
            Distance::where('job_id', '=', $validated['jobid'])->update([
                'distance' => $validated['distance'],
                'time' => $validated['time']
            ]);
        }

        if ($validated['admincomment'] || $validated['session_time'] || $validated['flagged'] || $validated['manually_handled'] || $validated['by_admin']) {
            Job::where('id', '=', $validated['jobid'])
                ->update([
                    'admin_comments'   => $validated['admincomment'],
                    'flagged'          => $validated['flagged'] === 'true' ? 'yes' : 'no',
                    'session_time'     => $validated['session_time'],
                    'manually_handled' => $validated['manually_handled'] === 'true' ? 'yes' : 'no',
                    'by_admin'         => $validated['by_admin'] === 'true' ? 'yes' : "no",
                ]);
        }

        return response()->json(['message' => 'Record updated!'], Response::HTTP_OK);
    }

    public function reopen(Request $request)
    {
        $response = $this->repository->reopen($request->validated());
        return response()->json($response, Response::HTTP_OK);
    }

    public function resendNotifications(Request $request)
    {
        // make `jobid` required in request file and get it like this $request->jobid instead of getting all request params
        // use try/catch as well
        $job = $this->repository->find($request->jobid);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');
        return response()->json(['message' => 'Notification sent!'], Response::HTTP_OK);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        // make `jobid` required in request file and get it like this $request->jobid instead of getting all request params
        $job = $this->repository->find($request->jobid);
        // remove unused jobdata
//        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response()->json(['message' => 'SMS sent!'], Response::HTTP_OK);
        } catch (\Throwable $e) {
            // log this exception
            Log::critical('SMS_NOTIFICATION_FAILED', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
                'line' => $e->getLine(),
            ]);
            return response()->json(['message' => 'sms notification has been failed'], Response::HTTP_INTERNEL_SERVER_ERROR);
        }
    }

}

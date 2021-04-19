# BookingController
### __construct 

1. `$this->repository` naming convension should be changed to some thing more appropriate e.g `$this->bookingRepository`

```php
// for example how do we know which repository we're reffering to by just looking at the following code, until we look into the constructor ?
$response = $this->repository->storeJobEmail($data);
```

### Universal Issues
1. `response()` helper missing proper response status codes.
2. `$request->all()` shouldn't be used, don't trust end users too much. this can break our application instead you can use `$request->validated()` after validation is performed.
3. No gurantee for authenticated user `$request->__authenticatedUser` and there is no proper exception handling for it.
4. No validation rules are defined.
5. No DTOs or Data is not type hinted anywhere in the application.
6. There are many useless variables are defined and never used.

### index

1. undefined variable `$response` would be thrown if one of those if statements is going to fail.
2. using directly `env()` helper is a bad practice and can leads to many unexpected issues, instead you can use `config()` helper function to the configuration values form config files.
3. please check `Universal issues` for other issues in this method.

### store
1. use `try/catch` block.

### update
1. `array_except()` shouldn't be used instead define fillables in the model.
2. `$cuser` is useless, instead `$request->__authenticatedUser` can be used directly.
3. use `try/catch` block for update method.
4. please check `Universal issues` for other issues in this method.

### distanceFeed
1. `else` statements are useles.
2. redundant code
3. please check `Universal issues` for other issues in this method.
4. use Request validation rules instead and use the proper request class for this `e.g DistanceFeedRequest` and validate all the params to this class and then get the validated data from it with `$request->validated()` method.

### immediateJobEmail
1. For email there should be the queue system instead of direct sending them.
2. dispatch the job and queue them into the `redis`

### resendNotifications
1. `$request->all()` shouldn't be used just to get the `jobid` instead we could use `$request->jobid` after validation.

## resendSMSNotifications
1. redundant and useless code. `$job_data` is not being used.

# BookingRepository

### __construct
1. There is a new instance of Mono logger is being used which is good to get the custom log response.

### Universal Issues
1. This repository is a total mess. needs more time to refactor it. also without debuging there are 99% chances that the application will break after refactoring BookingRepo.

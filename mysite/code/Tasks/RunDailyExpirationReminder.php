<?php

/**
 * Class for managing RunDailyExpirationReminder
 * @package ${PACKAGE}
 */
class RunDailyExpirationReminder extends BuildTask {

    protected $title = 'Daily Expiration Reminders';

    protected $description = 'Posts to Slack the upcoming certificates expiring';

    /**
     * @param SS_HTTPRequest $request
     * @throws ValidationException
     */
    public function run($request) {
        $cf = new DailyExpirationReminder();
        $cf->process();
    }
}
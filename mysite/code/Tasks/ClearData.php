<?php

/**
 * Class for managing ClearData.
 */
class ClearData extends BuildTask
{
    /**
     * Implement this method in the task subclass to
     * execute via the TaskRunner.
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        DB::query('TRUNCATE TABLE Domain');
        DB::query('TRUNCATE TABLE Certificate');
    }
}

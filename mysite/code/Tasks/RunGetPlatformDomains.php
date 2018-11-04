<?php

class RunGetPlatformDomains extends BuildTask
{
    protected $title = 'Add Domains from Platform';

    /**
     * @param SS_HTTPRequest $request
     *
     * @throws ValidationException
     */
    public function run($request)
    {
        $cf = new GetPlatformDomains();
        $cf->process();
    }
}

<?php

class RunCreateFreshdeskTickets extends BuildTask
{
    protected $title = 'Create Freshdesk Tickets';

    protected $description = 'Runs the CronTask that will create Freshdesk tickets for expiring domains, update the priority of existing tickets, or close tickets for domains with new certificates';

    /**
     * @param SS_HTTPRequest $request
     *
     * @throws ValidationException
     */
    public function run($request)
    {
        $cf = new CreateFreshdeskTickets();
        $cf->process();
    }
}

<?php

/**
 * Trait for managing some CronTask jobs.
 * This would've been an abstract class but sake didn't appear to like that and threw exceptions.
 */
trait CronTaskUtilities
{
    /**
     * Returns the closest key from an array using its values.
     *
     * @param int   $number
     * @param array $array
     */
    public function closestNumber($number, $array)
    {
        asort($array);
        foreach ($array as $key => $a) {
            if ($a >= $number) {
                return $key;
            }
        }
        end($array);

        return key($array);
    }

    /**
     * @param     $message
     * @param int $level
     */
    protected function log($message, $level = SS_Log::INFO)
    {
        SS_Log::log($message, $level);
        if (Director::is_cli()) {
            echo $message.PHP_EOL;
        } else {
            echo $message.'<br>'.PHP_EOL;
        }
    }
}

<?php

function get_version() {
    if (file_exists(__DIR__ . '/../version')) {
        return trim(file_get_contents(__DIR__ . '/../version'));
    }
    return 'Unknown';
}

function bugsnag():\Bugsnag\Client {
    $bugsnag = \Bugsnag\Client::make('2c207cc0a41f9f0bed4322516629afed');
    \Bugsnag\Handler::register($bugsnag);
    return $bugsnag;
}

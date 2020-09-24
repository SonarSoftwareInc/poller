<?php

function get_version() {
    if (file_exists(__DIR__ . '/../version')) {
        return trim(file_get_contents(__DIR__ . '/../version'));
    }
    return 'Unknown';
}

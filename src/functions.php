<?php

function get_version() {
    if (file_exists(__DIR__ . '/../version')) {
        return file_get_contents(__DIR__ . '/../version');
    }
    return 'Unknown';
}

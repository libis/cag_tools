<?php

if (!($omekaDir = getenv('SCRIPT_DIR'))) {
    putenv("SCRIPT_DIR=" . dirname(dirname(__FILE__)) . "/classes");
    $omekaDir = getenv('SCRIPT_DIR');
}

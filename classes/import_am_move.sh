#!/bin/bash
nohup php import_am_move.php > ../../shared/log/am_move.log 2> ../../shared/log/am_move_error.txt < /dev/null &
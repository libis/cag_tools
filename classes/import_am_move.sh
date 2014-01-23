#!/bin/bash
nohup php import_am_move.php > ../log/am_move.log 2> ../log/am_move_error.txt < /dev/null &
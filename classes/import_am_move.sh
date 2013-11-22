#!/bin/bash
nohup php import_am_move.php > /www/libis/web/lias_html/cag_tools-staging/shared/log/am_move.log 2> /www/libis/web/lias_html/cag_tools-staging/shared/log/am_move_error.txt < /dev/null &
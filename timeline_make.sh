#!/bin/bash
cd /www/libis/web/lias_html/cag_test
nohup php timeline_make.php > /www/libis/web/lias_html/collectiveaccess/cag_media/remake_timeline_ca_cag.log 2> /www/libis/web/lias_html/collectiveaccess/cag_media/remake_timeline_ca_cag_error.txt < /dev/null &
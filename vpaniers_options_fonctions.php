<?php

if (!defined("_ECRIRE_INC_VERSION")) {
    return;
}


function timestamp_vers_date($timestamp) {
	return $date = date('Y-m-d', $timestamp);
}

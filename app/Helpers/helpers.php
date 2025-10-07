<?php

    use App\Http\Controllers\havenUtils;

    if (!function_exists('getExpenceTypeOption')) {
        function getExpenceTypeOption($type) {
            return havenUtils::getExpenceTypeOption($type);
        }
    }
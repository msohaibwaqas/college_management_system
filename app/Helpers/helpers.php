<?php

if (!function_exists('ordinal')) {
    /**
     * Get the ordinal suffix for a number.
     *
     * @param int $number
     * @return string
     */
    function ordinal($number)
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return 'th';
        } else {
            return $ends[$number % 10];
        }
    }
}

if (!function_exists('numberToWords')) {
    /**
     * Convert a number to English words representation.
     *
     * @param int|float $number
     * @return string
     */
    function numberToWords($number)
    {
        $number = (int)$number;
        if ($number === 0) {
            return 'Zero';
        }

        $list1 = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven',
            'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        ];
        $list2 = ['', 'Ten', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $list3 = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

        $words = [];
        $levels = (int)( (strlen((string)$number) + 2) / 3 );
        $divider = pow(1000, $levels - 1);

        while ($number > 0) {
            $current = (int)($number / $divider);
            $number = $number % $divider;
            $divider = (int)($divider / 1000);

            if ($current > 0) {
                $hundreds = (int)($current / 100);
                $tens = $current % 100;

                if ($hundreds > 0) {
                    $words[] = $list1[$hundreds] . ' Hundred';
                }

                if ($tens < 20) {
                    if ($tens > 0) {
                        $words[] = $list1[$tens];
                    }
                } else {
                    $t = (int)($tens / 10);
                    $o = $tens % 10;
                    if ($o > 0) {
                        $words[] = $list2[$t] . '-' . $list1[$o];
                    } else {
                        $words[] = $list2[$t];
                    }
                }
                
                $levelName = $list3[$levels - 1];
                if ($levelName) {
                    $words[] = $levelName;
                }
            }
            $levels--;
        }

        return implode(' ', array_filter($words));
    }
}

<?php
/* ئامرازەکانی بەروار (میلادی بە ناوی مانگە کوردییەکان) و ڕەقەمە کوردییەکان */

/** ڕەقەمەکان بە شێوازی کوردی (٠١٢٣٤٥٦٧٨٩) */
function ku_digits($s): string
{
    return strtr((string)$s, ['0'=>'٠','1'=>'١','2'=>'٢','3'=>'٣','4'=>'٤','5'=>'٥','6'=>'٦','7'=>'٧','8'=>'٨','9'=>'٩',
        '۰'=>'٠','۱'=>'١','۲'=>'٢','۳'=>'٣','۴'=>'٤','۵'=>'٥','۶'=>'٦','۷'=>'٧','۸'=>'٨','۹'=>'٩']);
}

/** هەموو جۆرە ڕەقەمێک (کوردی / فارسی / لاتین) دەکات بە ڕەقەمی لاتین */
function en_digits($s): string
{
    return strtr((string)$s, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);
}

/** ژمارە لەگەڵ جیاکەرەوەی هەزاران و ڕەقەمی کوردی */
function ku_num($n): string
{
    return ku_digits(str_replace(',', '٬', number_format((float)$n, 0, '.', ',')));
}

/** ناوی مانگە میلادییەکان بە کوردیی سۆرانی */
function ku_months(): array
{
    return ['', 'کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران',
            'تەمموز', 'ئاب', 'ئەیلوول', 'تشرینی یەکەم', 'تشرینی دووەم', 'کانوونی یەکەم'];
}

function ku_weekdays(): array
{
    // شاخص = date('w')  (0 = یەکشەممە)
    return ['یەکشەممە', 'دووشەممە', 'سێشەممە', 'چوارشەممە', 'پێنجشەممە', 'هەینی', 'شەممە'];
}

/**
 * فۆرماتکردنی بەروار (میلادی) بە ناوی مانگی کوردی و ڕەقەمی کوردی
 * Y ساڵ | y ساڵی دوو ڕەقەمی | m مانگ (دوو ڕەقەم) | n مانگ | d ڕۆژ (دوو ڕەقەم) | j ڕۆژ | F ناوی مانگ | l ناوی ڕۆژی هەفتە | H کاتژمێر | i خولەک
 */
function kdate(int $ts, string $fmt = 'Y/m/d'): string
{
    $months = ku_months();
    $days = ku_weekdays();
    $out = '';
    $len = strlen($fmt);
    for ($i = 0; $i < $len; $i++) {
        $c = $fmt[$i];
        switch ($c) {
            case 'Y': $out .= date('Y', $ts); break;
            case 'y': $out .= date('y', $ts); break;
            case 'm': $out .= date('m', $ts); break;
            case 'n': $out .= date('n', $ts); break;
            case 'd': $out .= date('d', $ts); break;
            case 'j': $out .= date('j', $ts); break;
            case 'F': $out .= $months[(int)date('n', $ts)]; break;
            case 'l': $out .= $days[(int)date('w', $ts)]; break;
            case 'H': $out .= date('H', $ts); break;
            case 'i': $out .= date('i', $ts); break;
            default:  $out .= $c;
        }
    }
    return ku_digits($out);
}

/** timestamp ی سەرەتای مانگی ئێستا */
function month_start(): int
{
    return mktime(0, 0, 0, (int)date('n'), 1, (int)date('Y'));
}

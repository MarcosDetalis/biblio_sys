<?php
function strClean($cadena)
{
    $string = preg_replace(['/\s+/','/^\s|\s$/'],[' ',''], $cadena);
    $string = trim($string);
    $string = stripslashes($string);
    $string = str_ireplace('<script>', '', $string);
    $string = str_ireplace('</script>', '', $string);
    $string = str_ireplace('<script type=>', '', $string);
    $string = str_ireplace('<script src>', '', $string);
    $string = str_ireplace('SELECT * FROM', '', $string);
    $string = str_ireplace('DELETE FROM', '', $string);
    $string = str_ireplace('INSERT INTO', '', $string);
    $string = str_ireplace('SELECT COUNT(*) FROM', '', $string);
    $string = str_ireplace('DROP TABLE', '', $string);
    $string = str_ireplace("OR '1'='1", '', $string);
    $string = str_ireplace('OR ´1´=´1', '', $string);
    $string = str_ireplace('IS NULL', '', $string);
    $string = str_ireplace('LIKE "', '', $string);
    $string = str_ireplace("LIKE '", '', $string);
    $string = str_ireplace('LIKE ´', '', $string);
    $string = str_ireplace('OR "a"="a', '', $string);
    $string = str_ireplace("OR 'a'='a", '', $string);
    $string = str_ireplace('OR ´a´=´a', '', $string);
    $string = str_ireplace('--', '', $string);
    $string = str_ireplace('^', '', $string);
    $string = str_ireplace('[', '', $string);
    $string = str_ireplace(']', '', $string);
    $string = str_ireplace('==', '', $string);
    return $string;
}

/**
 * Recorta un texto UTF-8 a lo sumo $maxChars CARACTERES (no bytes), sin
 * depender de la extensión mbstring (que puede no estar instalada en
 * algunos hostings compartidos). Cortar por bytes a secas puede partir un
 * carácter acentuado a la mitad y corromper el texto siguiente.
 */
function recortarUtf8(string $texto, int $maxChars): string
{
    $len = strlen($texto);
    $charCount = 0;
    $bytePos = 0;
    while ($bytePos < $len) {
        $byte = ord($texto[$bytePos]);
        if ($byte < 0x80) $charLen = 1;
        elseif (($byte & 0xE0) === 0xC0) $charLen = 2;
        elseif (($byte & 0xF0) === 0xE0) $charLen = 3;
        elseif (($byte & 0xF8) === 0xF0) $charLen = 4;
        else $charLen = 1;
        if ($charCount >= $maxChars) {
            return substr($texto, 0, $bytePos) . '...';
        }
        $bytePos += $charLen;
        $charCount++;
    }
    return $texto;
}

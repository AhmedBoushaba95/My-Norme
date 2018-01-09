<?php
function	func_check_extension($file)
{
    if (!preg_match("#\w+[/\w+[.][c|h?]\z#", $file))
    {
        echo "\e[0;36m" . "$file" .
            ":\e[0;m Le fichier n'est pas un .c ou .h\n";
        return (false);
    }
    return (true);
}

function	func_check_path($argc, $argv)
{
    if ($argc != 2)
    {
	    echo "\e[0;31m" . "Usage" . ":\e[0;m php my_norme.php <path>.\n";
	    return (false);
    }
    else if (!file_exists($argv[1]))
    {
        echo "\e[0;37m" . "$argv[1]" .
            ":\e[0;m Le chemin spécifié est un introuvable.\n";
        return (false);
    }
    else if (!is_dir($argv[1]))
    {
        echo "\e[0;37m" . "$argv[1]" .
            ":\e[0;m Le chemin spécifié n'est pas un dossier.\n";
        return (false);
    }
    else if (!is_readable($argv[1]))
    {
        echo "\e[0;37m" . "$argv[1]" .
            ":\e[0;m L'accès au chemin spécifié est refusé.\n";
        return (false);
    }
    return (true);
}

function	func_count_number(&$struct)
{
    if (preg_match("#\w+\s+\w+\s*\([^)]*\)({)?$#", $struct['lines']))
        $struct['function_number']++;
}

function	func_column(&$struct)
{
    if (strlen($struct['lines']) >= 81)
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
            $struct['line'] . ":\e[0;m ligne de plus de 80 caractères\n";
        $struct['nb_error']++;
    }
}

function	func_define(&$struct)
{
    if (preg_match("@#(\s+)?(define)@", $struct['lines']))
    {
        if (preg_match("#\w+[/\w+[.][c]\z#", $struct['file']))
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] . ":\e[0;m 1 define dans un .c\n";
            $struct['nb_error']++;
        }
    }

}

function	func_declare(&$struct)
{
    $pattern = "#\w+\s+\w+(\s+)?=(\s+)?('|\")?\w+(\"|')?(\s+)?;$#";
    if (preg_match($pattern,$struct['lines']))
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
            $struct['line'] .
            ":\e[0;m déclaration et affectation à la même ligne\n";
        $struct['nb_error']++;
    }
}

function	func_double_jump(&$struct)
{
    if (trim($struct['lines']) != '' && $struct['jump'] == true)
        $struct['jump'] = false;
    else if (trim($struct['lines']) == '' && $struct['jump'] == false)
        $struct['jump'] = true;
    else if (trim($struct['lines']) == '' && $struct['jump'] == true)
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
            $struct['line'] . ":\e[0;m double saut de ligne\n";
        $struct['nb_error']++;
        $struct['jump'] = false;
    }
}

function	func_function_line(&$struct)
{
    if ($struct['bracket'] != 0 && $struct['function'] == true)
        $struct['function_line']++;
    if (preg_match("#\w+\s+\w+\s*\([^)]*\)$#", $struct['lines']))
        $struct['function'] = true;
    else if (preg_match("#\w+\s+\w+\s*\([^)]*\)({)?$#", $struct['lines']))
    {
        $struct['function'] = true;
        $struct['bracket']++;
    }
    else if (trim($struct['lines']) == '{' && $struct['function'] == true)
        $struct['bracket']++;
    else if (trim($struct['lines']) == '}' && $struct['function'] == true)
        $struct['bracket']--;
    else if ($struct['bracket'] == 0 && $struct['function'] == true)
    {
        if ($struct['function_line'] >= 21)
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] . ":\e[0;m fonction de plus de 25 lignes\n";
            $struct['nb_error']++;
        }
        $struct['function_line'] = 0;
        $struct['function'] = false;
    }

}

function	func_function_number($struct)
{
    if ($struct['function_number'] >= 6)
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] .
            ":\e[0;m plus de 5 fonctions dans le fichier\n";
        $struct['nb_error']++;
    }
}

function	func_include(&$struct)
{
    if (preg_match("@#include@", $struct['lines']))
    {
        $pattern = "@#include\s\"\w+.h\"|#include\s<\w+.h>@";
        if (!preg_match($pattern, $struct['lines']))
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] . ":\e[0;m mauvais include\n";
            $struct['nb_error']++;
        }
    }
}

function	func_initialise_struct(&$struct, $file)
{
    $struct['file'] = $file;
    $struct['line'] = 1;
    $struct['lines'] = '';
    $struct['jump'] = false;
    $struct['function'] = false;
    $struct['function_line'] = 0;
    $struct['function_number'] = 0;
    $struct['bracket'] = 0;
}

function	func_keywords()
{
    $keywords = ['auto', 'break', 'case', 'continue',
        'default', 'do','else' ,'enum' , 'extern',
        'for', 'goto', 'if', 'register',
        'return', 'struct', 'switch',
        'typedef', 'union', 'volatile', 'while'];
    return ($keywords);
}

function	func_print_result(&$struct)
{
    if (isset($struct['nb_error']))
    {
	if ($struct['nb_error'] == 0)
           echo "Vous avez fait \e[0;32m" . $struct['nb_error'] .
            "\e[0;m faute de norme.\n";
    	else
           echo "Vous avez fait \e[0;31m" . $struct['nb_error'] .
            "\e[0;m faute(s) de norme.\n";
    }
}

function	func_scan_file($file, $handle, &$struct)
{
    func_initialise_struct($struct, $file);
    echo "\e[0;33mScan : \e[0;m" . "$file" . "\n";
    while (!feof($handle))
    {
        $struct['lines'] = fgets($handle);
        func_column($struct);
        func_count_number($struct);
        func_declare($struct);
        func_define($struct);
        func_double_jump($struct);
        func_function_line($struct);
        func_include($struct);
        func_space_end($struct);
        func_space_keyword($struct);
        func_tab_declare($struct);
        $struct['line']++;
    }
    func_function_number($struct);
}

function	func_space_end(&$struct)
{
    if (preg_match("# \s+$#", $struct['lines']))
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
            $struct['line'] . ":\e[0;m espace en fin de ligne\n";
        $struct['nb_error']++;
    }
}	

function	func_space_keyword(&$struct)
{
    $i = 0;
    $no_space = false;
    $keywords = func_keywords();
    if (!preg_match("#print|echo#", $struct['lines']))
    {
        while (isset($keywords[$i]))
        {
            if (preg_match("#$keywords[$i]#", $struct['lines']))
            {
                if (!preg_match("#$keywords[$i]\s#", $struct['lines']))
                    $no_space = true;
            }
            $i++;
        }
        if ($no_space)
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] . ":\e[0;m espace manquant après le mot clé\n";
            $struct['nb_error']++;
        }
    }
}

function	func_struct()
{
    $struct = [
        'line' => 1,
        'lines' => '',
        'nb_error' => 0,
        'jump' => false,
        'function' => false,
        'function_line' => 0,
        'function_number' => 0,
        'bracket' => 0
    ];
    return ($struct);
}

function	func_tab_declare(&$struct)
{
    $i = 0;
    $key_here = false;
    $keywords = func_keywords();
    while (isset($keywords[$i]))
    {
        if (preg_match("#$keywords[$i]#", $struct['lines']))
            $key_here = true;
        $i++;
    }
    $pattern_func = "#\w+\s+\w+(\s+)?;$|" .
        "\w+\s+\w+\s*\([^)]*\)({|;)?$#";
    $pattern_func_tab = "#\w+\t+\w+(\s+)?;$|" .
        "\w+\t+\w+\s*\([^)]*\)({|;)?$#";
    if (preg_match($pattern_func, $struct['lines']) && !$key_here)
    {
        if (!preg_match($pattern_func_tab, $struct['lines']))
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] .
                 ":\e[0;m tabulations manquantes dans la déclaration.\n";
            $struct['nb_error']++;
        }
    }
}

/* Beginning */
if (func_check_path($argc, $argv))
{
    $i = 2;
    $files = scandir($argv[1]);
    $struct = func_struct();
    while (isset($files[$i]))
    {
        $file = $files[$i];
        $full_file = "./" . $argv[1] . $file;
        if (func_check_extension($file))
        {
            $handle = fopen($full_file, "r");
            if (!$handle)
            {
                echo "\e[0;36m" . "$file" .
                 ":\e[0;m Echec lors de l'ouverture du fichier.\n";
            }
            else
            {
                // On commence le scan du fichier
                func_scan_file($file, $handle, $struct);
            }
            fclose($handle);
        }
        $i++;
        echo "\n";
    }
    func_print_result($struct);
}
?>
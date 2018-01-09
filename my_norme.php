<?php
function func_check_extension($file)
{
    if (!preg_match("#\w+[/\w+[.][c|h?]\z#", $file))
    {
        echo "\e[0;36m" . "$file" . ":\e[0;m Le fichier n'est pas un .c ou .h\n";
        return (false);
    }
    return (true);
}

function func_check_path($argv)
{
    if (!file_exists($argv[1]))
    {
        echo "\e[0;37m" . "$argv[1]" . ":\e[0;m Le chemin spécifié est un introuvable.\n";
        return (false);
    }
    else if (!is_dir($argv[1]))
    {
        echo "\e[0;37m" . "$argv[1]" . ":\e[0;m Le chemin spécifié n'est pas un dossier.\n";
        return (false);
    }
    else if (!is_readable($argv[1]))
    {
        echo "\e[0;37m" . "$argv[1]" . ":\e[0;m L'accès au chemin spécifié est refusé.\n";
        return (false);
    }
    return (true);
}

function	func_column(&$struct)
{
    if (strlen($struct['lines']) >= 81)
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m ligne de plus de 80 caractères.\n";
        $struct['nb_error']++;
    }
}

function	func_define(&$struct)
{
    if (preg_match("@#define@", $struct['lines']))
    {
        if (preg_match("#\w+[/\w+[.][c]\z#", $struct['file']))
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m 1 define dans un .c.\n";
            $struct['nb_error']++;
        }
    }

}

function	func_declare(&$struct)
{
    if (preg_match("#[a-z]+\s+\w+\s?=\s?\"?'?\w+\"?'?;$#", $struct['lines']))
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m déclaration et affectation à la même ligne.\n";
        $struct['nb_error']++;
    }
}

function func_double_jump(&$struct)
{
    if (ltrim($struct['lines']) != '' && $struct['jump'] == true)
        $struct['jump'] = false;
    else if (ltrim($struct['lines']) == '' && $struct['jump'] == false)
        $struct['jump'] = true;
    else if (ltrim($struct['lines']) == '' && $struct['jump'] == true)
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m double saut de ligne.\n";
        $struct['nb_error']++;
        $struct['jump'] = false;
    }
}

function func_include(&$struct)
{
    if (preg_match("@#include@", $struct['lines']))
    {
        if (!preg_match("@#include\s\"\w+.h\"|#include\s<\w+.h>@", $struct['lines']))
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m mauvais include.\n";
            $struct['nb_error']++;
        }
    }
}

function func_keywords()
{
    $keywords = ['auto', 'break', 'case','char', 'const', 'continue',
        'default', 'do', 'double', 'else' ,'enum' , 'extern',
        'float', 'for', 'goto', 'if', 'int', 'long', 'register',
        'return', 'short', 'signed', 'sizeof', 'static', 'struct', 'switch',
        'typedef', 'union', 'unsigned', 'void', 'volatile', 'while'];
    return ($keywords);
}

function func_print_result(&$struct)
{
    if ($struct['nb_error'] == 0)
        echo "Vous avez fait \e[0;32m" . $struct['nb_error'] . "\e[0;m faute de norme.\n";
    else
        echo "Vous avez fait \e[0;31m" . $struct['nb_error'] . "\e[0;m faute(s) de norme.\n";
}

function func_scan_file($file, $handle, &$struct)
{

    $struct = func_struct($file);
    echo "\e[0;33mScan : \e[0;m" . "$file" . "\n";
    while (!feof($handle))
    {
        $struct['lines'] = fgets($handle);
        func_column($struct);
        func_declare($struct);
        func_define($struct);
        func_double_jump($struct);
        func_include($struct);
        func_space_end($struct);
        func_space_keyword($struct);
        func_tab_declare($struct);
        $struct['line']++;
    }
}

function	func_space_end(&$struct)
{
    if (preg_match("# \s+$#", $struct['lines']))
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m espace en fin de ligne.\n";
        $struct['nb_error']++;
    }
}	

function func_space_keyword(&$struct)
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
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m espace manquant après le mot clé.\n";
            $struct['nb_error']++;
        }
    }
}

function func_struct($file)
{
    $line = 1;
    $nb_error = 0;
    $struct = [
        'file' => $file,
        'line' => $line,
        'nb_error' => $nb_error,
        'jump' => false
    ];
    return ($struct);
}

function	func_tab_declare(&$struct)
{
    if (preg_match("#[a-z]+\s+\w+;$#", $struct['lines']))
    {
        if (!preg_match("#[a-z]+\t\w+;$#", $struct['lines']))
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " . $struct['line'] . ":\e[0;m tabulations manquantes dans la déclaration.\n";
            $struct['nb_error']++;
        }
    }
}

/* Beginning */
if (func_check_path($argv))
{
    $i = 2;
    $files = scandir($argv[1]);
    $struct = [];
    while (isset($files[$i]))
    {
        $file = $files[$i];
        $full_file = "./" . $argv[1] . $file;
        if (func_check_extension($file))
        {
            $handle = fopen($full_file, "r");
            if (!$handle)
                echo "\e[0;36m" . "$file" . ":\e[0;m Echec lors de l'ouverture du fichier.\n";
            else
            {
                // On commence le scan du fichier
                func_scan_file($file, $handle, $struct);
            }
        }
        $i++;
        echo "\n";
    }
    func_print_result($struct);
}
?>
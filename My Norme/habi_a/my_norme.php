#!/usr/bin/env php
<?php
// my_norme.php for my_norme in /Users/habi_a/PhpstormProjects/my_norme
//
// Made by HABI Acal
// Login   <habi_a@etna-alternance.net>
//
// Started on  Wed Jan 10 16:16:26 2018 HABI Acal
// Last update Wed Jan 10 16:16:59 2018 HABI Acal
//
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
	    echo "\e[0;35m" . "Usage" . ":\e[0;m php my_norme.php 'path'.\n";
	    return (false);
    }
    else if (!file_exists($argv[1]))
    {
        echo "\e[0;35m" . "$argv[1]" .
            ":\e[0;m Le chemin spécifié est un introuvable.\n";
        return (false);
    }
    else if (!is_dir($argv[1]))
    {
        echo "\e[0;35m" . "$argv[1]" .
            ":\e[0;m Le chemin spécifié n'est pas un dossier.\n";
        return (false);
    }
    else if (!is_readable($argv[1]))
    {
        echo "\e[0;35m" . "$argv[1]" .
            ":\e[0;m L'accès au chemin spécifié est refusé.\n";
        return (false);
    }
    return (true);
}

function	func_count_function(&$struct)
{
    if (preg_match("#\w+\s+\w+\s*\([^)]*\)(\s+)?({)?#", $struct['lines']))
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
    $pattern = "#\w+\s+\w+(\s+)?=(\s+)?('|\")?\w+(\"|')?(\s+)?;(\s+)?$#";
    if (preg_match($pattern,$struct['lines']))
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
            $struct['line'] .
            ":\e[0;m déclaration et affectation à la même ligne\n";
        $struct['nb_error']++;
    }
}

function	func_declare_jump(&$struct)
{
    $pattern = "#\w+\s+\w+(\s+)?;(\s+)?$|" .
        "\w+\s+\w+(\s+)?=(\s+)?('|\")?\w+(\"|')?(\s+)?;(\s+)?$#";
    if (preg_match($pattern, $struct['lines']))
        $struct['declare'] = true;
    else
    {
        if ($struct['declare'])
        {
            if (trim($struct['lines']) != '')
            {
                echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                    $struct['line'] .
                    ":\e[0;m saut de ligne manquant après les déclarations\n";
                $struct['nb_error']++;
            }
            $struct['declare'] = false;
        }
    }
}

function	func_double_jump(&$struct)
{
    if (trim($struct['lines']) != '' && $struct['jump'])
        $struct['jump'] = false;
    else if (trim($struct['lines']) == '' && !$struct['jump'])
        $struct['jump'] = true;
    else if (trim($struct['lines']) == '' && $struct['jump'])
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
            $struct['line'] . ":\e[0;m double retour à la ligne\n";
        $struct['nb_error']++;
        $struct['jump'] = false;
    }
}

function    func_function_argument(&$struct)
{
    if (preg_match("#\w+\s+\w+\s*\([^)]*\)(\s+)?({)?$#", $struct['lines']))
    {
        $i = 0;
        preg_match_all("#,#", $struct['lines'], $matches);
        while (isset($matches[0][$i]))
        {
            $i++;
        }
        if ($i >= 4)
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                        $struct['line'] .
                        ":\e[0;m fonction avec plus de 4 paramètres\n";
            $struct['nb_error']++;
        }
    }
}

function	func_function_line(&$struct)
{
    if ($struct['bracket'] != 0 && $struct['function'])
        $struct['function_line']++;
    if (trim($struct['lines']) == '{' && $struct['function'])
        $struct['bracket']++;
    elseif (trim($struct['lines']) == '}' && $struct['function'])
        $struct['bracket']--;
    elseif ($struct['bracket'] == 0 && $struct['function'])
    {
        if ($struct['function_line'] >= 26)
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] . ":\e[0;m fonction de plus de 25 lignes\n";
            $struct['nb_error']++;
        }
        $struct['function_line'] = 0;
        $struct['function'] = false;
        $struct['function_end'] = true;
    }
    if (preg_match("#\w+\s+\w+\s*\([^)]*\)(\s+)?$#", $struct['lines']))
        $struct['function'] = true;
    elseif (preg_match("#\w+\s+\w+\s*\([^)]*\)(\s+)?({)?$#", $struct['lines']))
    {
        $struct['function'] = true;
        $struct['bracket']++;
    }
}

function	func_function_return(&$struct)
{
    if ($struct['function_end'])
    {
        if (trim($struct['lines']) != '')
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] .
                ":\e[0;m retour à la ligne après fonction manquant\n";
            $struct['nb_error']++;
        }
        $struct['function_end'] = false;
    }
}

function	func_function_number(&$struct)
{
    if ($struct['function_number'] >= 6)
    {
        echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] .
            ":\e[0;m plus de 5 fonctions dans le fichier\n";
        $struct['nb_error']++;
    }
}

function	func_header(&$struct)
{

    if ($struct['line'] == 1)
    {
        if(!preg_match("#^(\/\*)$#", $struct['lines']))
            $struct['bad_header'] = true;
    }
    if ($struct['line'] == 2)
    {
        $pattern_two = "#^\*\* \w+[.][c|h] for \S+ in \S+$#";
        if(!preg_match($pattern_two, $struct['lines']))
            $struct['bad_header'] = true;
    }
    if ($struct['line'] == 3)
    {
        $pattern_two = "#^(\*\*) $#";
        if(!preg_match($pattern_two, $struct['lines']))
            $struct['bad_header'] = true;
    }
    func_header_two($struct);
}

function	func_header_two(&$struct)
{
    if ($struct['line'] == 4)
    {
        if(!preg_match("#^(\*\*) Made by .+$#", $struct['lines']))
            $struct['bad_header'] = true;
    }
    if ($struct['line'] == 5)
    {
        $pattern_two = "#^(\*\*) Login   <\S+[_]\S+[@]etna-alternance.net>$#";
        if(!preg_match($pattern_two, $struct['lines']))
            $struct['bad_header'] = true;
    }
    if ($struct['line'] == 6)
    {
        $pattern_two = "#^(\*\*) $#";
        if(!preg_match($pattern_two, $struct['lines']))
            $struct['bad_header'] = true;
    }
    func_header_three($struct);
}

function	func_header_three(&$struct)
{
    if ($struct['line'] == 7)
    {
        $pattern = "#^(\*\*) Started on  \w{3} \w{3}(\s)+[0-9]+ " .
            "[0-9]{2}[:][0-9]{2}[:][0-9]{2} [0-9]{4} .+$#";
        if(!preg_match($pattern, $struct['lines']))
            $struct['bad_header'] = true;
    }
    if ($struct['line'] == 8)
    {
        $pattern_two = "#^(\*\*) Last update \w{3} \w{3}(\s)+[0-9]+ " .
            "[0-9]{2}[:][0-9]{2}[:][0-9]{2} [0-9]{4} .+$#";
        if(!preg_match($pattern_two, $struct['lines']))
            $struct['bad_header'] = true;
    }
    if ($struct['line'] == 9)
    {
        $pattern_two = "#^(\*\/)$#";
        if(!preg_match($pattern_two, $struct['lines']))
            $struct['bad_header'] = true;
    }
    func_header_four($struct);
}

function	func_header_four(&$struct)
{
    if ($struct['line'] == 10)
    {
        $struct['end_header'] = true;
        $struct['stop_header'] = true;
    }
    if ($struct['bad_header'] && !$struct['stop_header'])
    {
        echo "\e[0;31mTRICHE: MAUVAIS HEADER\e[0;m\n";
        $struct['nb_error']++;
        $struct['bad_header'] = false;
        $struct['stop_header'] = true;
    }
}

function	func_include(&$struct)
{
    if ($struct['end_header'])
    {
        if(trim($struct['lines']) != '' &&
            !preg_match("@#(\s+)?(include)@", $struct['lines']))
            $struct['end_header'] = false;
    }
    else
    {
        if (preg_match("@#(\s+)?(include)@", $struct['lines']))
        {
            echo "\e[0;31mErreur:\e[0;34m " . $struct['file'] . ": ligne " .
                $struct['line'] . ":\e[0;m ordonnancement des includes\n";
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
    $struct['function_end'] = false;
    $struct['function_line'] = 0;
    $struct['function_number'] = 0;
    $struct['declare'] = 0;
    $struct['bracket'] = 0;
    $struct['bad_header'] = false;
    $struct['stop_header'] = false;
    $struct['end_header'] = false;
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
        func_header($struct);
        //var_dump($struct);
        if ($struct['line'] > 9)
        {
            func_column($struct);
            func_count_function($struct);
            func_declare($struct);
            func_declare_jump($struct);
            func_define($struct);
            func_double_jump($struct);
            func_function_argument($struct);
            func_function_line($struct);
            func_function_return($struct);
            func_include($struct);
            func_space_end($struct);
            func_space_keyword($struct);
            func_tab_declare($struct);
        }
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
        'function_end' => false,
        'function_line' => 0,
        'function_number' => 0,
        'declare' => false,
        'bracket' => 0,
        'bad_header' => false,
        'stop_header' => false,
        'end_header' =>false
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
    $path = $argv[1];
    $files = scandir($path);
    $struct = func_struct();
    while (isset($files[$i]))
    {
        $file = $files[$i];
        $full_file = "./" . $path . $file;
        if (func_check_extension($file))
        {
            $handle = fopen($full_file, "r");
            if (!$handle)
            {
                echo "\e[0;36m" . "$file" .
                 ":\e[0;m Echec lors de l'ouverture du fichier.\n";
            }
            else
                func_scan_file($file, $handle, $struct);
            fclose($handle);
        }
        $i++;
        echo "\n";
    }
    func_print_result($struct);
}
?>

<?php
function my_define($file, $lines, $line, &$nb_error)
{
    if (preg_match("/#define/", $lines))
    {
        if (preg_match("#\w+[/\w+[.][c]\z#", $file))
        {
            echo "\e[0;31mErreur:\e[0;34m $file: ligne $line:\e[0;m 1 define dans un .c\n";
            $nb_error++;
        }
    }

}

function my_declare($file, $lines, $line, &$nb_error)
{
    if (preg_match("/[a-z]+\s+\w+\s?=\s?\"?'?\w+\"?'?;$/", $lines))
    {
        echo "\e[0;31mErreur:\e[0;34m $file: ligne $line:\e[0;m déclaration et affectation à la même ligne\n";
        $nb_error++;
    }
}

function my_column($file, $lines, $line, &$nb_error)
{
    if (strlen($lines) > 80)
    {
        echo "\e[0;31mErreur:\e[0;34m $file: ligne $line:\e[0;m Ligne de plus de 80 caractères.\n";
        $nb_error++;
    }
}

function my_tab_declare($file, $lines, $line, &$nb_error)
{
    ;
}

// Gestion d'erreur pour l'argument spécifié
if (!file_exists($argv[1]))
    echo "\e[0;36m" . "$argv[1]" . ":\e[0;m Le chemin spécifié est un introuvable.\n";
else if (!is_dir($argv[1]))
    echo "\e[0;36m" . "$argv[1]" . ":\e[0;m Le chemin spécifié n'est pas un dossier.\n";
else if (!is_readable($argv[1]))
    echo "\e[0;36m" . "$argv[1]" . ":\e[0;m L'accès au chemin spécifié est refusé.\n";
else
{
    // Scan des fichiers présents dans le dossier
    $i = 2;
    $files = scandir($argv[1]);
    $nb_error = 0;
    while (isset($files[$i]))
    {
        $file = $files[$i];
        $full_file = "./" . $argv[1] . $file;
        if (!preg_match("#\w+[/\w+[.][c|h?]\z#", $file))
            echo "\e[0;36m" . "$file" . ":\e[0;m Le fichier n'est pas un .c ou .h\n";
        else
        {
            $handle = fopen($full_file, "r");
            if (!$handle)
                echo "$file" . ": Echec lors de l'ouverture du fichier.\n";
            else
            {
                // On commence le scan du fichier
                $line = 1;
                echo "\e[0;33mScan : \e[0;m" . "$file" . "\n";
                while (!feof($handle))
                {
                    $lines = fgets($handle);
                    my_column($file, $lines, $line, $nb_error);
                    my_declare($file, $lines, $line, $nb_error);
                    my_define($file, $lines, $line, $nb_error);
                    $line++;
                }
            }
        }
        $i++;
        echo "\n";
    }
    if ($nb_error == 0)
        echo "Vous avez fait \e[0;32m" . "$nb_error" . "\e[0;m faute de norme.\n";
    else
        echo "Vous avez fait \e[0;31m" . "$nb_error" . "\e[0;m faute(s) de norme.\n";
}
?>
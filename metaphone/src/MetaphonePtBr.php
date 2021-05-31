<?php

namespace Metaphone;

/**
 *
 * Metaphone para Português do Brasil
 * Esta função recebe um texto em Português do Brasil e a retorna no formato
 * de chave metafônica.
 *
 *
 */
class MetaphonePtBr
{
    public static function metaphone($string, $especialChars = true, $length = 255)
    {
        $string = self::removerAcento($string);
        $string = preg_replace('/[-]/', null, $string);
        $string = preg_replace('/[^a-zA-Z0-9çÇ]/', ' ', $string);
        $string = explode(' ', $string);

        $palavraFinal = null;
        foreach ($string as $s) {
            $palavraFinal .= ' ' . self::chave($s, $especialChars, $length);
        }
        return trim($palavraFinal);
    }

    public static function chave($string, $especialChars, $length)
    {

        //inicializa a chave metafônica
        $meta_key = "";

        //configura o tamanho máximo da chave metafônica
        $key_length = (int) $length;

        //coloca a posição no começo
        $current_pos = (int) 0;

        $original_string = $string;

        /*
         * vamos repor alguns caracteres portugueses facilmente confundidos, substituindo
         * os números, não confundir com os encontros consonantais (RR), dígrafos (LH, NH) e
         * o C-cedilha:
         * LH -> 1
         * RR -> 2
         * NH -> 3
         * Ç  -> SS
         * CH -> X
         */
        if (!$especialChars) {
            $original_string = preg_replace('/\d/', ' ', $original_string);
        }

        $original_string = preg_replace('/(y|Y)/', 'I', $original_string);
        $original_string = preg_replace('/(sç|SÇ)/u', 'SS', $original_string); //cresço, nasço, desçam
        $original_string = preg_replace('/(ç|Ç)/u', 'SS', $original_string);

        //converte a string para caixa alta
        $original_string = strtoupper($original_string);

        /*
         * Faz substituições:
         * olho, ninho, carro, exceção, cabeça
         */
        $original_string = preg_replace('/LH/', '1', $original_string);
        $original_string = preg_replace('/NH/', '3', $original_string);
        $original_string = preg_replace('/RR/', '2', $original_string);

        //diminui uma letra para facilitar comparação de palavras de 5 letras.
        //ex: Willy (UL) -> Wily (UILI), Kelle (KL) -> Kele (kele), Ellen (ELM) -> Elen (ELEM)
        $original_string = preg_replace('/LL/', 'L', $original_string);

        //regra genéria demais funciona para exceção (essessão)  mas não para excluir (essluir)
        //    $original_string = preg_replace('/XC/', 'SS', $original_string);

        /*
         * A correção do SCH e do TH por conta dos nomes próprios:
         * SCHIFFER, THEODORA, OPHELIA...
         */
        $original_string = preg_replace('/TH\b/', 'TE', $original_string); //RUTH -> RUTE
        $original_string = preg_replace('/TH/', 'T', $original_string);
        $original_string = preg_replace('/PH/', 'F', $original_string);
        $original_string = preg_replace('/SH/', 'X', $original_string);

        //H antes de consoante não tem som em Português
        $original_string = preg_replace('/H(?=[BCDFGJKLMNPQRSTVXWYZ])/', null, $original_string); //SAHRA vira SARA com 4 letras

        //H no final ou no começo não tem som em Português. Sem esta linha Sarah (SR) != Sara (SARA)
        $original_string = preg_replace('/\bH|H\b/', null, $original_string);

        //SS no início de uma palavra não existe em Português
        $original_string = preg_replace('/\bSS/', 'S', $original_string);

        $original_string = trim($original_string);

        //recupera o tamanho máximo da string
        $string_length = (int) strlen($original_string);

        //configura o final da string
        $end_of_string_pos = $string_length - 1;

        while (strlen($meta_key) < $key_length) {
            //sai do loop se maior que o tamanho da string
            if ($current_pos >= $string_length) {
                break;
            }

            //pega um caracter da string
            $current_char = substr($original_string, $current_pos, 1);

            /*
             *    se é uma vogal e faz parte do começo da string,
             *    coloque-a como parte da metachave
             */
            if (self::is_vowel($original_string, $current_pos) && (($current_pos == 0) || (self::string_at($original_string, $current_pos - 1, 1, array(" "))))) {
                $meta_key .= $current_char;
                $current_pos++;
            }
            /*
             * Se é uma vogal e a palavra tem até 4 letras
             * coloque-a como parte da metachave
             */
            else if (self::is_vowel($original_string, $current_pos) && $string_length <= 4) {
                $meta_key .= $current_char;
                $current_pos++;
            }
            /*
             * Procurar por consoantes que tem um único som, ou que
             * que já foram substituídas ou soam parecido, como
             * 'Ç' para 'SS' e 'NH' para '1'
             */
            else if (self::string_at($original_string, $current_pos, 1, array('1', '2', '3', 'B', 'D', 'F', 'J', 'K', 'L', 'M', 'P', 'T', 'V', ' '))) {
                $meta_key .= $current_char;

                //incrementar por 2 se uma letra repetida for encontrada
                if (substr($original_string, $current_pos + 1, 1) == $current_char) {
                    $current_pos += 2;
                }
                //senão incrementa em 1
                else {
                    $current_pos++;
                }

            } else {
                switch ($current_char) {
                    case 'G':

                        switch (substr($original_string, ($current_pos + 1), 1)) {
                            case 'E':
                            case 'I':
                                $meta_key .= 'J';
                                $current_pos += 2;
                                break;

                            case 'R':
                                $meta_key .= 'GR';
                                $current_pos += 2;
                                break;

                            default:
                                $meta_key .= 'G';
                                $current_pos++;
                                break;
                        }
                        break;

                    case 'U':
                        if (self::is_vowel($original_string, $current_pos - 1)) { //ex: AUmento
                            $current_pos++;
                            $meta_key .= 'L';
                            break;
                        } else {
                            $current_pos++;
                            break;
                        }

                    case 'R':
                        if (($current_pos == 0) || (substr($original_string, ($current_pos - 1), 1) == ' ')) { //inicia com R
                            $current_pos++;
                            $meta_key .= '2';
                            break;
                        } else if ($current_pos == $end_of_string_pos || substr($original_string, $current_pos + 1, 1) == ' ') { //termina com R
                            $current_pos++;
                            $meta_key .= '2';
                            break;
                        } else if (!self::is_vowel($original_string, $current_pos + 1)) { //R antes de consoante
                            $current_pos++;
                            $meta_key .= '2';
                            break;
                        } else {
                            $current_pos++;
                            $meta_key .= 'R';
                            break;
                        }

                    case 'Z':
                        if ($current_pos >= (strlen($original_string) - 1)) { //z no final da palavra ex: xadrez
                            $current_pos++;
                            $meta_key .= 'S';
                            break;
                        } else if (substr($original_string, ($current_pos + 1), 1) == 'Z') { //ZZ - removendo repetição
                            $meta_key .= 'Z';
                            $current_pos += 2;
                            break;
                        }
                        $current_pos++;
                        $meta_key .= 'Z';
                        break;

                    case 'N':
                        if ($current_pos >= (strlen($original_string) - 1)) { //N no final da palavra vira M
                            $meta_key .= 'M';
                            $current_pos++;
                        }
                        //duplo N no final será substituído por apenas um M. ex: Carmenn para Carmem
                        else if ((substr($original_string, $current_pos + 1, 1) == 'N') && ((substr($original_string, $current_pos + 2, 1) == ' ') || $current_pos + 2 >= strlen($original_string))) {
                            $meta_key .= 'M';
                            $current_pos += 2;
                        } else if (substr($original_string, $current_pos + 1, 1) == 'N') { //NN - removendo repetição (quando não for final da string)
                            $meta_key .= 'N';
                            $current_pos += 2;
                        } else {
                            $meta_key .= 'N';
                            $current_pos++;
                        }
                        break;

                    case 'S':
                        //todo corrigir o problema do campos barra == KMPSB2
                        if (
                            (substr($original_string, $current_pos + 1, 1) == 'S') || //se a próxima for um s === Esta regra não faz sentido. Palavras terminadas em SS?? (Não encontrei exemplos nem com nome de pessoa) ===
                            ($current_pos == $end_of_string_pos) || //se for o final da string
                            (substr($original_string, ($current_pos + 1), 1) == ' ') //se depois vier um espaço
                        ) {
                            $meta_key .= 'S';
                            $current_pos += 2;
                        } else if (($current_pos == 0) || (substr($original_string, ($current_pos - 1), 1) == ' ')) { //inicio da linha ou inicio de uma palavra
                            $meta_key .= 'S';
                            $current_pos++;
                        }
                        //entre vogais. ex: Asa
                        else if ((self::is_vowel($original_string, $current_pos - 1)) && (self::is_vowel($original_string, $current_pos + 1))) {
                            $meta_key .= 'Z';
                            $current_pos++;
                        }
                        //Ascender, Lascivia
                        else if (
                            (substr($original_string, ($current_pos + 1), 1) == 'C') &&
                            (
                                (substr($original_string, ($current_pos + 2), 1) == 'E') ||
                                (substr($original_string, ($current_pos + 2), 1) == 'I')
                            )
                        ) {
                            $meta_key .= 'S';
                            $current_pos += 3;
                        }
                        //Asco, Auscutar, Mascavo
                        else if (
                            (substr($original_string, ($current_pos + 1), 1) == 'C') &&
                            self::string_at($original_string, ($current_pos + 2), 1, array('A', 'O', 'U'))) {
                            $meta_key .= 'SC';
                            $current_pos += 3;
                        } else {
                            $meta_key .= 'S';
                            $current_pos++;
                        }

                        break;

                    case 'X':
                        /*
                         * A letra x pode representar os seguintes sons
                         * s: sexto, texto, expectativa
                         * ch: xarope, enxame, vexame
                         * ss: auxílio, máximo, próximo
                         * cs: sexo, látex, tóxico
                         *
                         * alguns formados por dígrafos:
                         * exceção, excesso, exceto
                         */
                        //exceção

                        //Após ditongos "x" com som de "ch"
                        //auxílio
                        if (self::string_at($original_string, ($current_pos - 2), 2, array('AU')) && (substr($original_string, ($current_pos + 1), 1) == 'I')) {
                            $meta_key .= 'SS';
                            $current_pos++;
                        }
                        //sintaxe
                        else if (self::string_at($original_string, ($current_pos - 2), 2, array('TA')) && (substr($original_string, ($current_pos + 1), 1) == 'E')) {
                            $meta_key .= 'SS';
                            $current_pos++;
                        }
                        //máximino
                        else if (self::string_at($original_string, ($current_pos - 1), 1, array('A')) && self::string_at($original_string, ($current_pos + 1), 3, array('IMI'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //máximo, aproximar, proximidade
                        else if (self::string_at($original_string, ($current_pos - 1), 1, array('A', 'O')) && self::string_at($original_string, ($current_pos + 1), 3, array('IMA', 'IME', 'IMO', 'IMI'))) {
                            $meta_key .= 'SS';
                            $current_pos++;
                        }
                        //"excluir", "excursão" é exceção para a regra de xc -> ss
                        else if (self::string_at($original_string, ($current_pos + 1), 2, array('CL', 'CU'))) {
                            $meta_key .= 'S';
                            $current_pos++;
                        }
                        //depois do x vem uma consoante. X fica com som de "s". Ex: expansão
                        else if (($current_pos != $end_of_string_pos || (substr($original_string, ($current_pos + 1), 1) == ' ')) && !self::is_vowel($original_string, $current_pos + 1)) {
                            $meta_key .= 'S';
                            $current_pos++;
                        } else if (substr($original_string, ($current_pos + 1), 1) == 'C') {
                            $meta_key .= 'SS';
                            $current_pos += 2;
                        }
                        //x com som de z se começar com a vogal "e" seguido de "x" e depois outra vogal
                        else if (($current_pos == 1 || (substr($original_string, ($current_pos - 2), 1) == ' ')) && (substr($original_string, ($current_pos - 1), 1) == 'E') && self::is_vowel($original_string, $current_pos + 1)) {
                            $meta_key .= 'Z';
                            $current_pos++;
                        }
                        //x entre vogais desde que x não seja a segunda letra
                        else if (($current_pos != 1 || (substr($original_string, ($current_pos - 2), 1) == ' ')) && (self::is_vowel($original_string, $current_pos - 1)) && (self::is_vowel($original_string, $current_pos - 2))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        }
                        //x depois do prefixo "en" tem som de "ch"
                        else if (($current_pos == 2 || (substr($original_string, ($current_pos - 3), 1) == ' ')) && self::string_at($original_string, ($current_pos - 2), 2, array('EN'))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        }
                        //x depois da palavra inicial "me" tem som de "ch"
                        else if (($current_pos == 2 || (substr($original_string, ($current_pos - 3), 1) == ' ')) && self::string_at($original_string, ($current_pos - 2), 2, array('ME'))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        }
                        //x na primeira letra tem som de ch, xique-xique
                        //                    else if(($current_pos == 0 || $current_pos == 1) || ((substr($original_string, ($current_pos -1), 1) == '-') || (substr($original_string, ($current_pos -1), 1) == ' ') || (substr($original_string, ($current_pos -2), 1) == ' '))) {
                        else if (($current_pos == 0) || ((substr($original_string, ($current_pos - 1), 1) == '-') || (substr($original_string, ($current_pos - 1), 1) == ' ') || (substr($original_string, ($current_pos - 2), 1) == ' '))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        }
                        //termina com x tem som de ks. climax, fênix
                        else if (($current_pos == $end_of_string_pos) || (substr($original_string, ($current_pos + 1), 1) == ' ')) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //toxina, inicia com 'o' para não quebrar a palavra faxina
                        else if ((substr($original_string, ($current_pos - 1), 1) == 'O') && self::string_at($original_string, ($current_pos + 1), 3, array('INA'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //axila
                        else if (self::string_at($original_string, ($current_pos + 1), 3, array('ILA'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }

                        /*
                         * Exceções para letra x
                         * Não há regra porque são palavras que vieram de outros idiomas
                         */
                        //washington -> waxington
                        else if (self::string_at($original_string, ($current_pos + 1), 2, array('IN'))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        }
                        //asfixia (grego)
                        else if (self::string_at($original_string, ($current_pos + 1), 2, array('IA'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //fixo
                        else if (self::string_at($original_string, ($current_pos - 2), 2, array('FI'))) {
                            //                    else if(self::string_at($original_string, ($current_pos -2), 2, array('MI')) && (substr($original_string, ($current_pos +1), 1) == 'I')) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //saxofone, sexo, sexagésimo
                        else if (self::string_at($original_string, ($current_pos - 2), 2, array('SA', 'SE')) && self::string_at($original_string, ($current_pos + 1), 1, array('A', 'O'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //taxonomia, taxi :::: taxa
                        else if (self::string_at($original_string, ($current_pos - 2), 2, array('TA')) && self::string_at($original_string, ($current_pos + 1), 1, array('O', 'I'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //axioma, axe
                        else if (($current_pos == 1 || substr($original_string, $current_pos - 2, 1) == ' ') && self::string_at($original_string, ($current_pos + 1), 1, array('E', 'I'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //mixira, maxixe ::::saxofone, fixo, asfixia, taxonomia, axioma
                        else if (self::string_at($original_string, ($current_pos - 1), 1, array('A', 'I'))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        }
                        //bexiga
                        else if (self::string_at($original_string, ($current_pos + 1), 2, array('IG'))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        }
                        //flexão, flexões
                        else if ((substr($original_string, ($current_pos - 1), 1) == 'E') && self::string_at($original_string, ($current_pos + 1), 2, array('AO', 'OES'))) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //oxandrolona, óxido
                        else if (($current_pos == 1 || substr($original_string, $current_pos - 2, 1) == ' ') && (self::string_at($original_string, ($current_pos + 1), 2, array('AN'))) || (substr($original_string, ($current_pos + 1), 1) == 'I')) {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        //bruxa
                        else if (self::string_at($original_string, ($current_pos + 1), 1, array('A'))) {
                            $meta_key .= 'X';
                            $current_pos++;
                        } else {
                            $meta_key .= 'KS';
                            $current_pos++;
                        }
                        break;

                    case 'C':
                        //Cereja, Cinema
                        if (self::string_at($original_string, $current_pos, 2, array('CE', 'CI'))) {
                            $meta_key .= 'S';
                            $current_pos += 2;
                        }
                        //C com som de X
                        else if ((substr($original_string, ($current_pos + 1), 1) == 'H')) {
                            $meta_key .= 'X';
                            $current_pos += 2;
                        } else {
                            $meta_key .= 'K';
                            $current_pos++;
                        }
                        break;

                    case 'H':
                        /*
                         * Como a letra H é silenciosa no Português, vamos colocar a
                         * chave meta como a vogal logo após a letra H
                         */
                        if (self::is_vowel($original_string, $current_pos + 1)) {
                            $meta_key .= $original_string[$current_pos + 1];
                            $current_pos += 2;
                        } else {
                            $current_pos++;
                        }
                        break;

                    case 'Q':
                        if (substr($original_string, $current_pos + 1, 1) == 'U') {
                            $current_pos += 2;
                        } else {
                            $current_pos += 1;
                        }

                        $meta_key .= 'K';
                        break;

                    case 'W':
                        /*
                         * W tem som de "v" em palavras de origem germânica
                         * e som de "u" em palavras de origem britânica ou americana
                         */
                        if (self::string_at($original_string, $current_pos, 3, array('WAG', 'WAL', 'WAN', 'WEB'))) {
                            $meta_key .= 'V';
                        } else {
                            $meta_key .= 'U';
                        }
                        $current_pos++;
                        break;

                    default:
                        if ($especialChars && preg_match('/[^A-Z]/', $current_char)) {
                            $meta_key .= $current_char;
                        }

                        $current_pos++;
                        break;
                }
            }
        }
        return $meta_key;
    }

    public static function string_at($string, $start, $string_length, $list)
    {

        if ($start < 0 || $start >= strlen($string)) {
            return 0;
        }

        return in_array(substr($string, $start, $string_length), $list) ? 1 : 0;
    }

    public static function is_vowel($string, $pos)
    {
        return preg_match('/[AEIOU]/', substr($string, $pos, 1));
    }

    public static function removerAcento($txt)
    {
        $keys = array();
        $values = array();
        preg_match_all('/./u', 'áàãâéêíóôõúüÁÀÃÂÉÊÍÓÔÕÚÜ', $keys);
        preg_match_all('/./u', 'aaaaeeiooouuAAAAEEIOOOUU', $values);
        $mapping = array_combine($keys[0], $values[0]);

        return strtr($txt, $mapping);
    }

    public static function removerAcentoCompleto($txt)
    {
        $utf8 = array(
            '/[áàâãªä]/u' => 'a',
            '/[ÁÀÂÃÄ]/u' => 'A',
            '/[ÍÌÎÏ]/u' => 'I',
            '/[íìîï]/u' => 'i',
            '/[éèêë]/u' => 'e',
            '/[ÉÈÊË]/u' => 'E',
            '/[óòôõºö]/u' => 'o',
            '/[ÓÒÔÕÖ]/u' => 'O',
            '/[úùûü]/u' => 'u',
            '/[ÚÙÛÜ]/u' => 'U',
            '/ç/' => 'c',
            '/Ç/' => 'C',
            '/ñ/' => 'n',
            '/Ñ/' => 'N',
            '/–/' => '-', // UTF-8 hyphen to "normal" hyphen
            '/[’‘‹›‚]/u' => ' ', // Literally a single quote
            '/[“”«»„]/u' => ' ', // Double quote
            '/ /' => ' ', // nonbreaking space (equiv. to 0x160)
        );
        return preg_replace(array_keys($utf8), array_values($utf8), $txt);
    }
}
<?php
namespace App\Models;


class Funciones {
      
    public static function getAlert($tipo,$titulo,$mensaje){
        return ["tipo"=>$tipo,"titulo"=>$titulo,"mensaje"=>$mensaje];
    }
    
    public static function ToRoman($num){ 
    $n = intval($num); 
    $res = ''; 

    //array of roman numbers
    $romanNumber_Array = array( 
        'M'  => 1000, 
        'CM' => 900, 
        'D'  => 500, 
        'CD' => 400, 
        'C'  => 100, 
        'XC' => 90, 
        'L'  => 50, 
        'XL' => 40, 
        'X'  => 10, 
        'IX' => 9, 
        'V'  => 5, 
        'IV' => 4, 
        'I'  => 1); 

    foreach ($romanNumber_Array as $roman => $number){ 
        //divide to get  matches
        $matches = intval($n / $number); 

        //assign the roman char * $matches
        $res .= str_repeat($roman, $matches); 

        //substract from the number
        $n = $n % $number; 
    } 

    // return the result
    return $res; 
} 

static function list2array ($list) {
        $array = explode(',', $list);
        $return = array();
        foreach ($array as $value) {
            $explode2 = explode('-', $value);
            if (count($explode2) > 1) {
                $range = range($explode2[0], $explode2[1]); 
                $return = array_merge($return, $range);
            } else {
                $return[] = (int) $value;
            }
        }
        return $return;
    }

public static function sanear_string($string)
    {
 
            $string = trim($string);
         
            $string = str_replace(
                array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
                array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
                $string
            );
         
            $string = str_replace(
                array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
                array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
                $string
            );
         
            $string = str_replace(
                array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
                array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
                $string
            );
         
            $string = str_replace(
                array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
                array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
                $string
            );
         
            $string = str_replace(
                array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
                array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
                $string
            );
         
            $string = str_replace(
                array('ñ', 'Ñ', 'ç', 'Ç'),
                array('n', 'N', 'c', 'C',),
                $string
            );
         
            //Esta parte se encarga de eliminar cualquier caracter extraño         
         
            return $string;
    }
    
    /**
     * 
     * @param string $date
     * @param string $format [optional]
     * @param \DateTimeZone $datetime_zone [optional]
     * @return \DateTime
     */
    public static function createDateTimeObject($date,$format='d/m/Y',$datetime_zone=null) {
        if($datetime_zone===null)
            $datetime_zone = new \DateTimeZone(config('app.timezone'));
        try {
            return \DateTime::createFromFormat($format,$date,$datetime_zone);
        }
        catch(Excetpion $ex) {
            return null;
        }
    }
    

    

    public static function getExtension($name) {
        $array = explode(".",$name);
        return end($array);
    }
    
    public static function randPassword(){
    
    $cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
 
    $longitudCadena=strlen($cadena);
    
    $password = "";
    
    $longitudPass=10;
   
    for($i=1 ; $i<=$longitudPass ; $i++){
        
        $pos=rand(0,$longitudCadena-1);
     
         $password .= substr($cadena,$pos,1);
    }
    return $password;
}
}



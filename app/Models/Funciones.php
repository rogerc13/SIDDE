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
        catch(\Exception $ex) {
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
        
        $pos=random_int(0,$longitudCadena-1);
     
         $password .= substr($cadena,$pos,1);
    }
    return $password;
}

    public static function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Fold a search term so accents are ignored: "Ética" -> "etica".
     */
    public static function normalizeSearchTerm(string $value): string
    {
        return mb_strtolower(\Illuminate\Support\Str::ascii(trim($value)));
    }

    /**
     * Apply an accent-insensitive title/category-name search to a Course query.
     *
     * Matching relies on the database folding diacritics:
     * - MySQL/MariaDB use utf8mb4_unicode_ci, which is accent-insensitive.
     * - SQLite only folds ASCII case, so it uses the `strip_accents()` function
     *   registered in AppServiceProvider to fold the stored column in SQL.
     *
     * The term itself is folded in PHP first, so "Etica", "Ética" and "etica"
     * all match the same rows.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function applyCourseSearch($query, string $term)
    {
        $escaped = static::escapeLike($term);

        if ($query->getConnection()->getDriverName() === 'sqlite') {
            $folded = '%' . static::normalizeSearchTerm($escaped) . '%';

            return $query->where(function ($search) use ($folded) {
                $search->whereRaw('lower(strip_accents(title)) LIKE ?', [$folded])
                    ->orWhereHas('category', function ($categoryQuery) use ($folded) {
                        $categoryQuery->whereRaw('lower(strip_accents(name)) LIKE ?', [$folded]);
                    });
            });
        }

        $like = '%' . $escaped . '%';

        return $query->where(function ($search) use ($like) {
            $search->where('title', 'LIKE', $like)
                ->orWhereHas('category', function ($categoryQuery) use ($like) {
                    $categoryQuery->where('name', 'LIKE', $like);
                });
        });
    }

    /**
     * Format a date value for display. Storage/payloads keep ISO (Y-m-d);
     * this only affects what the user sees.
     *
     * @param  mixed  $value
     */
    public static function formatDate($value, string $format = 'd-m-Y'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}



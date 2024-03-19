<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Person;
class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        function createPerson($person)
        {
            foreach ($person as $key => $value) {
                Person::create([
                    'name' => $key['name'],
                    'last_name' => $key['last_name'],
                    'id_number' => $key['id_number'],
                    'phone' => $key['phone'],
                    'sex' => $key['sex'],
                ]);
            }
        }

        Person::create([
            'name' => 'Roger',
            'last_name' => 'Canache',
            'id_number' => '20634220',
            'id_type_id' => 1,
            'phone' => '123456789',
            'sex' => 'Masculino',
        ]);
        Person::create([
            'name' => 'Claudio',
            'last_name' => 'Cortinez',
            'id_number' => '20634222',
            'id_type_id' => 1,
            'phone' => '123456782',
            'sex' => 'Masculino',
        ]);
        Person::create([
            'name' => 'Marcos',
            'last_name' => 'Capriles',
            'id_number' => '20634223',
            'id_type_id' => 1,
            'phone' => '123456783',
            'sex' => 'Masculino',
        ]);
        Person::create([
            'name' => 'Camila',
            'last_name' => 'Maita',
            'id_number' => '20634224',
            'id_type_id' => 1,
            'phone' => '123456784',
            'sex' => 'Femenino',
        ]);
        Person::create([
            'name' => 'Jhonny',
            'last_name' => 'Prato',
            'id_number' => '20634225',
            'id_type_id' => 1,
            'phone' => '123456785',
            'sex' => 'Masculino',
        ]);
        Person::create([
            'name' => 'Manuel',
            'last_name' => 'Albani',
            'id_number' => '20375232',
            'id_type_id' => 1,
            'phone' => '123456785',
            'sex' => 'Masculino',
        ]);

        //person -> user
       /*  $person[] = ['id_number' => 10948096, 'name' => 'Luis Beltran', 'last_name' => 'Astudillo','phone' =>  '04248918341',
                    'id_number' => 11381289, 'name' => 'Ramón José', 'last_name' => 'Rodríguez Velásquez','phone' => '04266872009',
                    'id_number' => 15936375, 'name' => 'Jesus Javier', 'last_name' => 'Salazar','phone' => '04248448659',
                    'id_number' => 11382109, 'name' => 'Arelys Del Valle', 'last_name' => 'Lozada Salamanca','phone' => '04248104469',
                    'id_number' => 14597889, 'name' => 'Inmeria Josefina', 'last_name' => 'Cordova Salazar', 'phone' => '04124997664',
                    'id_number' => 20631457, 'name' => 'Cira Daniela', 'last_name' => 'Peña Rodriguez', 'phone' => '04163223967',
                    'id_number' => 9978548, 'name' => 'Marianela Josefina', 'last_name' => 'Pinto', 'phone' => '02939952370',
                    'id_number' => 6766960, 'name' => 'Alisander José', 'last_name' => 'Martínez Padrón', 'phone' => '04265825349',
                    'id_number' => 23684985, 'name' => 'Angel Andres', 'last_name' => 'Malave Rojas', 'phone' => '04162920644',
                    'id_number' => 20347071, 'name' => 'Winston Eleazar', 'last_name' => 'Morey Lozada', 'phone' => '04268852390',
                    'id_number' => 13572546, 'name' => 'Luis José', 'last_name' => 'Romero Hernández', 'phone' => '04168895557',
                    'id_number' => 15576976, 'name' => 'José Antonio', 'last_name' => 'Rosales González','phone' => '04248015176',
                    'id_number' => 20062545, 'name' => 'Adolfo José', 'last_name' => 'Marcano Milano', 'phone' => '04264806025',
                    'id_number' => 9274862, 'name' => 'Noel José', 'last_name' => 'Enriquez Maneiro', 'phone' => '02935147292',
                    'id_number' => 22621765, 'name' => 'Luis Ivan ', 'last_name' => 'Alemán Gandara', 'phone' => '04145633558',
                    'id_number' => 21011931, 'name' => 'Luis Enrique', 'last_name' => 'García García', 'phone' => '04165811468',
                    'id_number' => 24878543, 'name' => 'Pedro José', 'last_name' => 'Fernández Rivero','phone' => '04248826016',
                    'id_number' => 19762240, 'name' => 'Luis Felipe', 'last_name' => 'Márquez Pérez', 'phone' => '04248053039',
                    'id_number' => 17539534, 'name' => 'Lenar José', 'last_name' => 'Urbaneja Urbaneja','phone' =>  '04266831097',
                    'id_number' => 16203693, 'name' => 'Miguel José', 'last_name' => 'Fermín Maíz', 'phone' =>  '04248472915',
                    'id_number' => 17781740, 'name' => 'Angel Felix', 'last_name' => 'Alejandro Mata', 'phone' => '04165804240',
                    'id_number' => 18212199, 'name' => 'Keryin Eloina', 'last_name' => 'Rodríguez Seija','phone' =>  '04165952746',
                    'id_number' => 16701554, 'name' => 'Mirtza Yumar', 'last_name' => 'Seijas Gil', 'phone' => '04121915500',
                    'id_number' => 21011037, 'name' => 'Maurelys Del Carmen' ,'last_name' => 'González Alcalá', 'phone' => '04168833713',
                    'id_number' => 29721241, 'name' => 'Isairis Del Valle' ,'last_name' => 'Salazar Rincones', 'phone' => '04162700307',
                    'id_number' => 20196738, 'name' => 'José Gregorio', 'last_name' => 'Castro López', 'phone' => '04166106190',
                    'id_number' => 23433735, 'name' => 'Sofia Del Valle ' ,'last_name' => 'Millan Rodriguez', 'phone' => '04121918805',
                    'id_number' => 24594458, 'name' => 'Zoylimar Del Valle' ,'last_name' => 'Valerio Márquez', 'phone' => '04262813010',
                    'id_number' => 13360985, 'name' => 'Gabriel Jesús', 'last_name' => 'Zapata Lozada', 'phone' => '04248078347',
                    'id_number' => 27078685, 'name' => 'Jesus Paul', 'last_name' => 'Jimenez Marval', 'phone' => '04248501161'];
        createPerson($person);  */       
    }
}

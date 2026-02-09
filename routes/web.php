<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/', function () {
    return redirect()->route("home");
});

Auth::routes();

Route::get('login','App\Http\Controllers\Auth\LoginController@getViewLogin')->name('login');
Route::post('login','App\Http\Controllers\Auth\LoginController@login');
Route::get('logout','App\Http\Controllers\Auth\LoginController@logout')->name('logout');
Route::post('logout','App\Http\Controllers\Auth\LoginController@logout');

Route::get('home', 'App\Http\Controllers\HomeController@index')->name('home');

Route::get('prerequisite-test', 'App\Http\Controllers\CursoController@prerequisiteTest');
Route::get('assign-test', 'App\Http\Controllers\CursoProgramadoController@assignList');

Route::get('download/{id}/{type}','App\Http\Controllers\CursoController@download');


Route::group(['middleware' => 'auth'], function ()
{
    Route::group(['prefix' => 'u'], function()
    {
        Route::post('/codes', 'App\Http\Controllers\CursoController@codeCheck'); //ajax call to check if code already exists
        Route::post('/prerequisite', 'App\Http\Controllers\CursoController@prerequisiteList'); //ajax call to check if code already exists
        
        Route::group(['prefix' => 'pdf'], function()
        {
                Route::get('/courses', 'App\Http\Controllers\BrowserShotController@courses')->name('courses');
                Route::get('/scheduled', 'App\Http\Controllers\BrowserShotController@scheduled')->name('scheduled'); 
                Route::get('/users', 'App\Http\Controllers\BrowserShotController@users')->name('users');
                Route::get('/facilitators', 'App\Http\Controllers\BrowserShotController@facilitators')->name('facilitators');
                Route::get('/participants', 'App\Http\Controllers\BrowserShotController@participants')->name('participants');
                Route::get('/participants/{id}', 'App\Http\Controllers\BrowserShotController@courseParticipants')->name('course-participants');
                Route::get('/mycourses', 'App\Http\Controllers\BrowserShotController@myCourses')->name('my-courses');
        });

        
        
        Route::group(['prefix' => 'usuarios'], function()
        {
                Route::get('/', 'App\Http\Controllers\UserController@getAll')->name("usuarios");
                Route::get('/count', 'App\Http\Controllers\UserController@count');
                Route::get('/{id}', 'App\Http\Controllers\UserController@get');
                Route::post('/', 'App\Http\Controllers\UserController@store');
                Route::put('/{id}', 'App\Http\Controllers\UserController@update');
                Route::delete('/{id}', 'App\Http\Controllers\UserController@delete');
        });

        Route::group(['prefix' => 'facilitadores'], function()
        {
                Route::get('/', 'App\Http\Controllers\FacilitadorController@getAll')->name("facilitadores");
                Route::get('/count', 'App\Http\Controllers\FacilitadorController@count');
                Route::get('/{id}', 'App\Http\Controllers\FacilitadorController@get');
                Route::post('/', 'App\Http\Controllers\FacilitadorController@store');
                Route::put('/{id}', 'App\Http\Controllers\FacilitadorController@update');
                Route::delete('/{id}', 'App\Http\Controllers\FacilitadorController@delete');
                Route::get('/acciones/{id}', 'App\Http\Controllers\CursoProgramadoController@userCursos');
        });

        Route::group(['prefix' => 'participantes'], function()
        {
                Route::get('/', 'App\Http\Controllers\ParticipanteController@getAll')->name("participantes");
                Route::get('/count', 'App\Http\Controllers\ParticipanteController@count');
                Route::get('/{id}', 'App\Http\Controllers\ParticipanteController@get');
                Route::post('/', 'App\Http\Controllers\ParticipanteController@store');
                Route::put('/{id}', 'App\Http\Controllers\ParticipanteController@update');
                Route::delete('/{id}', 'App\Http\Controllers\ParticipanteController@delete');
                Route::get('/acciones/{id}', 'App\Http\Controllers\CursoProgramadoController@userCursos');
        });

        Route::group(['prefix' => 'areas'], function()
        {
                Route::get('/', 'App\Http\Controllers\CategoriaController@getAll')->name("areas");
                Route::get('/count', 'App\Http\Controllers\CategoriaController@count');
                Route::get('/{id}', 'App\Http\Controllers\CategoriaController@get');
                Route::post('/', 'App\Http\Controllers\CategoriaController@store');
                Route::put('/{id}', 'App\Http\Controllers\CategoriaController@update');
                Route::delete('/{id}', 'App\Http\Controllers\CategoriaController@delete');
        });

        Route::group(['prefix' => 'acciones_formacion'], function()
        {
                Route::get('/', 'App\Http\Controllers\CursoController@getAll')->name("acciones");
                Route::get('/count', 'App\Http\Controllers\CursoController@count');
                Route::get('/{id}', 'App\Http\Controllers\CursoController@get');

                Route::get('/details/{id}', 'App\Http\Controllers\CursoController@courseDetails'); //get course details with relationships
                //Route::post('/', 'App\Http\Controllers\CursoController@store');
                Route::post('/', 'App\Http\Controllers\CursoController@setCourse');
                Route::put('/{id}', 'App\Http\Controllers\CursoController@update');
                //Route::put('/{id}', 'App\Http\Controllers\CursoController@test');
                Route::delete('/{id}', 'App\Http\Controllers\CursoController@delete');

                Route::get('/onSubmitAlert/{request}', 'App\Http\Controllers\CursoController@onCourseSubmitAlert');
                
                

               
        });


        Route::group(['prefix' => 'af_programadas'], function()
        {
                Route::get('/', 'App\Http\Controllers\CursoProgramadoController@getAll')->name("programadas");
                Route::get('/count', 'App\Http\Controllers\CursoProgramadoController@count');
                Route::get('/{id}', 'App\Http\Controllers\CursoProgramadoController@get');
                Route::post('/', 'App\Http\Controllers\CursoProgramadoController@store');
                Route::put('/{id}', 'App\Http\Controllers\CursoProgramadoController@update');
                Route::delete('/{id}', 'App\Http\Controllers\CursoProgramadoController@delete');
                Route::put('/cancel/{id}', 'App\Http\Controllers\CursoProgramadoController@cancel');

                Route::post('/assignList', 'App\Http\Controllers\CursoProgramadoController@assignList');
                Route::post('/{id}/af_programadas/participantes/assignList', 'App\Http\Controllers\CursoProgramadoController@assignList');


                Route::get('/{id}/evaluacion', 'App\Http\Controllers\ParticipanteCursoController@getAllEvaluation');
                Route::get('/{id}/onSubmitAlert/{request}', 'App\Http\Controllers\ParticipanteCursoController@onEvaluationSubmitAlert');
                //Route::post('/evaluation', 'App\Http\Controllers\ParticipanteCursoController@participantEvaluationStatus');
                Route::get('/{id}/participantes', 'App\Http\Controllers\ParticipanteCursoController@getAllPorCurso');
               // Route::get('/count', 'ParticipanteCursoController@count');
                Route::get('/estado-participantes/{id}', 'App\Http\Controllers\ParticipanteCursoController@get');
                Route::post('/{id}/participantes', 'App\Http\Controllers\ParticipanteCursoController@store');
                Route::post('/{id}/asignarParticipante', 'App\Http\Controllers\ParticipanteCursoController@asignarParticipante');
                //Route::put('/{id}/participantes/{participanteId}', 'App\Http\Controllers\ParticipanteCursoController@update');
                Route::put('/estado-participantes/{id}', 'App\Http\Controllers\ParticipanteCursoController@update');
                Route::delete('/participantes/{id}', 'App\Http\Controllers\ParticipanteCursoController@delete');

                
                

        });


        Route::get('/mis_datos', 'App\Http\Controllers\UserController@misDatos')->name("mis_datos");
        Route::put('/mis_datos', 'App\Http\Controllers\UserController@misDatosUpdate')->name("mis_datos_update");
        Route::get('/mis_acciones', 'App\Http\Controllers\CursoProgramadoController@misCursos')->name("mis_acciones");

        

    });





    Route::group(['prefix' => 'acciones_formacion'], function()
    {

        Route::get('/{id}', 'App\Http\Controllers\CursoController@getAccionFormacion'); //view Ficha Tecnica
        Route::get('/documentos/{id}/{d}', 'App\Http\Controllers\CursoController@descargarDoc');
        Route::get('/{id}/documents', 'App\Http\Controllers\CursoController@downloadAllFiles');

    });
        Route::group(['middleware' => ['reports']], function () {
                Route::group(['prefix' => 'reports'], function () {
                        Route::get('/', function () {
                                return view('pages.admin.usuarios.reports');
                        });
                               Route::post('/pdf', 'App\Http\Controllers\ReportPdfController@download');
                        Route::post('/date', 'App\Http\Controllers\ReportController@byDate');
                        Route::post('/category', 'App\Http\Controllers\ReportController@byCategory');
                        Route::post('/status', 'App\Http\Controllers\ReportController@byStatus');
                        Route::post('/duration', 'App\Http\Controllers\ReportController@byCourseDuration');
                        Route::post('/canceled', 'App\Http\Controllers\ReportController@byCanceled');
                        Route::post('/participant-by-quantity', 'App\Http\Controllers\ReportController@courseByParticipantQuantity');
                        Route::post('/participant-by-status', 'App\Http\Controllers\ReportController@participantsByStatus');
                        Route::post('/gender', 'App\Http\Controllers\ReportController@participantsByGender');
                        Route::post('/participant-average', 'App\Http\Controllers\ReportController@participantAverage');
                        Route::post('/most-scheduled', 'App\Http\Controllers\ReportController@mostScheduled');
                        Route::post('/not-scheduled', 'App\Http\Controllers\ReportController@notScheduled');

                        Route::get('/participant-status-select', 'App\Http\Controllers\ReportController@participantStatus');
                });
        });

                Route::get('database', 'App\Http\Controllers\Admin\DatabaseController@index')->name('database.index');

                Route::get('database/capacities', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'capacities')->name('database.tables.capacities');
                Route::get('database/categories', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'categories')->name('database.tables.categories');
                Route::get('database/contents', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'contents')->name('database.tables.contents');
                Route::get('database/course_status', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'course_status')->name('database.tables.course_status');
                Route::get('database/courses', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'courses')->name('database.tables.courses');
                Route::get('database/facilitators', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'facilitators')->name('database.tables.facilitators');
                Route::get('database/files', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'files')->name('database.tables.files');
                Route::get('database/id_types', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'id_types')->name('database.tables.id_types');
                Route::get('database/modalities', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'modalities')->name('database.tables.modalities');
                Route::get('database/participant_status', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'participant_status')->name('database.tables.participant_status');
                Route::get('database/participants', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'participants')->name('database.tables.participants');
                Route::get('database/people', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'people')->name('database.tables.people');
                Route::get('database/prerequisites', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'prerequisites')->name('database.tables.prerequisites');
                Route::get('database/roles', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'roles')->name('database.tables.roles');
                Route::get('database/scheduled_course', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'scheduled_course')->name('database.tables.scheduled_course');
                Route::get('database/types', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'types')->name('database.tables.types');
                Route::get('database/users', 'App\Http\Controllers\Admin\DatabaseController@show')->defaults('table', 'users')->name('database.tables.users');

                Route::patch('database/users/{id}', 'App\Http\Controllers\Admin\DatabaseController@updateUser')
                        ->whereNumber('id')
                        ->name('database.users.update');

                Route::delete('database/courses/{id}', 'App\Http\Controllers\Admin\DatabaseController@destroyCourse')
                        ->whereNumber('id')
                        ->name('database.courses.destroy');

                Route::patch('database/courses/{id}', 'App\Http\Controllers\Admin\DatabaseController@updateCourse')
                        ->whereNumber('id')
                        ->name('database.courses.update');

                Route::delete('database/courses/{id}/force', 'App\Http\Controllers\Admin\DatabaseController@forceDestroyCourse')
                        ->whereNumber('id')
                        ->name('database.courses.forceDestroy');

                Route::patch('database/courses/{id}/restore', 'App\Http\Controllers\Admin\DatabaseController@restoreCourse')
                        ->whereNumber('id')
                        ->name('database.courses.restore');
    
    Route::post('/evaluation', 'App\Http\Controllers\ParticipanteCursoController@participantEvaluationStatus');
    
    

    
});


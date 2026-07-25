<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Course;
use App\Models\Category;
use App\Models\Capacity;
use App\Models\Content;
use App\Models\Funciones;
use App\Models\Modality;
use App\Models\Scheduled;
use App\Models\File;
use App\Models\Prerequisite;

use App\Http\Requests\CursoForm;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use \Illuminate\Support\Facades\File as IlluminateFile;
use stdClass;
use ZipArchive;

class CursoController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        if ($user->cannot('get', Course::class)) {
            return json_encode([]);
        }

        $curso = Course::with(['Content', 'File', 'Capacity', 'prerequisite'])->where('id', $id)->get();
        if (!$curso) {
            return json_encode([]);
        }

        return json_encode($curso);
    }

    public function getAccionFormacion($id)
    {
        $user = Auth::user();
        if (!$user || $user->cannot('getAccionFormacion', Course::class)) {
            return redirect()->route('login');
        }
        $curso = Course::with(['capacity', 'modality', 'content', 'category'])->where('id', $id)->first();
        if ($curso == null) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error", "La acción de formación no pudo ser encontrada"));
        }

        $files = File::where('course_id', $id)->get();

        if ($files->count() >= 1) {
            return view('pages.public.ficha_tecnica')->with('curso', $curso)->with('files', $files);
        } else {
            return view('pages.public.ficha_tecnica')->with('curso', $curso);
        }
    } //view Ficha Tecnica

    public function getAll(Request $request)
    {

        $user = Auth::user();
        if ($user->cannot('getAll', Course::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $titulos = $request->input('titulos');
        $id_areas = $request->input('id_areas');


        $lista = $categorias = Category::orderBy("name", "asc");
        $categorias = $lista->pluck('name', 'id');

        $modalities = Modality::orderBy('name', 'asc')->get();


        $cursos = Course::orderBy("title", "asc")->with('category');

        if ($titulos)
            $cursos = $cursos->where('title', 'LIKE', "%$titulos%");

        if ($id_areas)
            $cursos = $cursos->where('category_id', '=', $id_areas);

        return view('pages.admin.cursos.index')
            ->with('cursos', $cursos->paginate(10))
            ->with('categorias', $lista->get())
            //->with('categorias',$categorias)
            ->with('titulos', $titulos)
            ->with('busqueda_area', $id_areas)
            ->with('modalities', $modalities);
    }


    public function setCourse(CursoForm $request)
    {
        // return json_encode($request->prerequisite);
        $user = Auth::user();
        if ($user->can('store', Course::class)) {
            $data = array(
                'code' => $request->codigo,
                'title' => str_replace(['pdvsa', 'Y'], ['PDVSA', 'y'], ucwords(strtolower($request->titulo))),
                'category_id' => $request->categoria_id,
                'modality_id' => $request->modalidad_id,
                'objective' => $request->objetivo,
                'duration' => $request->duracion,
                'addressed' => $request->dirigido,
            );

            // Build unique content list (avoid accidental duplicates)
            $contentData = [];
            $seenContent = [];
            $contentList = explode(",", $request->content_data);  //turns string of content into an array

            foreach ($contentList as $content) {  //cycles content list and creates array to store into course_contents
                $content = trim($content);
                if ($content === '') {
                    continue;
                }
                if (isset($seenContent[$content])) {
                    // skip duplicates by text
                    continue;
                }
                $seenContent[$content] = true;
                $contentLength = strlen($content);
                //check if period is present at end of string
                if ($contentLength > 0 && ($content[$contentLength - 1]) != '.') {
                    $content = $content . '.';
                }
                $contentData[] = new Content([ //array to be stored
                    'text' => $content
                ]);
            }


            $path = [];
            if (null != ($request->file('manual_f'))) {
                $path[0]  = new File([
                    'path' => $request->file('manual_f')
                        ->storeAs($request->codigo, "Manual del Facilitador " . $request->titulo . "." . $request->file('manual_f')
                            ->getClientOriginalExtension()),
                    'type_id' => 1
                ]);
            }

            if (null != ($request->file('manual_p'))) {
                $path[1] = new File(['path' => $request->file('manual_p')
                    ->storeAs($request->codigo, "Manual del Participante " . $request->titulo . "." . $request->file('manual_p')
                        ->getClientOriginalExtension()), 'type_id' => 2]);
            }
            if (null != ($request->file('guia'))) {
                $path[2] = new File(['path' => $request->file('guia')
                    ->storeAs($request->codigo, "Guia del Curso " . $request->titulo . "." . $request->file('guia')
                        ->getClientOriginalExtension()), 'type_id' => 3]);
            }
            if (null != ($request->file('presentacion'))) {
                $path[3] = new File(['path' => $request->file('presentacion')
                    ->storeAs($request->codigo, "Presentacion " . $request->titulo . "." . $request->file('presentacion')
                        ->getClientOriginalExtension()), 'type_id' => 4]);
            }

            $response = Course::create($data); //inserts into course table

            if (isset($path)) {
                $response->file()->saveMany($path); //inserts path into course_files      
            }

            $capacity = new Capacity(['min' => $request->min, 'max' => $request->max]);
            $prerequisite = new Prerequisite([
                'prerequisite' => (isset($request->prerequisite) ? $request->prerequisite : null),
                'course_code' => $request->codigo
            ]);
            $response->prerequisite()->save($prerequisite);
            $response->capacity()->save($capacity);
            $response->content()->saveMany($contentData); //inserts intro course_contents table with course's id given relationship

        }

        return json_encode($response);
    } //end setCourse()

    public function update(CursoForm $request, $id)
    {
        //return json_encode(isset($request->prerequisite));

        if (isset($request->delete_flag)) {
            $deleteHelper = json_decode($request->delete_flag);
            //return json_encode($response = 'set');
        }
        //return json_encode($response = $deleteHelper->facilitator);
        $user = Auth::user();

        $curso = Course::find($id);

        if (!$curso) { //Course doesn't exists
            /* return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "El curso ingresada no existe."));
             */
            return json_encode($response = false);
        }
        if ($user->cannot('update', Course::class)) { //User has no clearance
            return json_encode($response = false);
        }
        /*
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));

 */
        $curso->code = $request->codigo;
        $curso->title = $request->titulo;
        $curso->category_id = $request->categoria_id;
        $curso->modality_id = $request->modalidad_id;
        $curso->duration = $request->duracion;
        $curso->addressed = $request->dirigido;
        $curso->objective = $request->objetivo;
        //$curso->contenido = $request->contenido;

        //delete all contents of the course if they exist on the content list table 
        $curso->content()->delete();

        // Build unique content list from request (avoid accidental duplicates)
        $contentData = [];
        $seenContent = [];
        $contentList = explode(",", $request->content_data);  //turns string of content into an array
        foreach ($contentList as $content) {  //cycles content list and creates array to store into course_contents
            $content = trim($content);
            if ($content === '') {
                continue;
            }
            if (isset($seenContent[$content])) {
                // skip duplicates by text
                continue;
            }
            $seenContent[$content] = true;
            $contentData[] = new Content([ //array to be stored
                'text' => $content
            ]);
        }

        $fileCollection = collect($curso->file);

        $path = [];
        foreach ($fileCollection as $file) {
            if (($file->type_id == 1) && ($request->file('manual_f'))) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                $path[0]  = ['file_path' => $request->file('manual_f')->storeAs($request->codigo, "Manual del Facilitador " . $request->titulo . "." . $request->file('manual_f')->getClientOriginalExtension()), 'type_id' => 1];
            } else if (isset($deleteHelper) && ($file->type_id == 1) && ($deleteHelper->facilitator === true)) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                //return json_encode($response = "file 1 deleted");
            }

            if (($file->type_id == 2) && ($request->file('manual_p'))) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                $path[1] = ['file_path' => $request->file('manual_p')->storeAs($request->codigo, "Manual del Participante " . $request->titulo . "." . $request->file('manual_p')->getClientOriginalExtension()), 'type_id' => 2];
            } else if (isset($deleteHelper) && ($file->type_id == 2) && ($deleteHelper->manual === true)) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                //return json_encode($response = "file 2 deleted");
            }
            if (($file->type_id == 3) && ($request->file('guia'))) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                $path[2] = ['file_path' => $request->file('guia')->storeAs($request->codigo, "Guia del Curso " . $request->titulo . "." . $request->file('guia')->getClientOriginalExtension()), 'type_id' => 3];
            } else if (isset($deleteHelper) && ($file->type_id == 3) && ($deleteHelper->guide === true)) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                //return json_encode($response = "file 3 deleted");
            }
            if (($file->type_id == 4) && ($request->file('presentacion'))) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                $path[3] = ['file_path' => $request->file('presentacion')->storeAs($request->codigo, "Presentacion " . $request->titulo . "." . $request->file('presentacion')->getClientOriginalExtension()), 'type_id' => 4];
            } else if (isset($deleteHelper) && ($file->type_id == 4) && ($deleteHelper->presentation === true)) {
                $file->delete();
                $result[] = Storage::delete($file->path);
                //return json_encode($response = "file 4 deleted");
            }
        }

        if (null != ($request->file('manual_f'))) {
            $path[0]  = new File(['path' => $request->file('manual_f')
                ->storeAs($request->codigo, "Manual del Facilitador " . $request->titulo . "." . $request->file('manual_f')
                    ->getClientOriginalExtension()), 'type_id' => 1]);
        }
        if (
            null != ($request->file('manual_p'))
        ) {
            $path[1] = new File(['path' => $request->file('manual_p')
                ->storeAs($request->codigo, "Manual del Participante " . $request->titulo . "." . $request->file('manual_p')
                    ->getClientOriginalExtension()), 'type_id' => 2]);
        }
        if (
            null != ($request->file('guia'))
        ) {
            $path[2] = new File(['path' => $request->file('guia')
                ->storeAs($request->codigo, "Guia del Curso " . $request->titulo . "." . $request->file('guia')
                    ->getClientOriginalExtension()), 'type_id' => 3]);
        }
        if (
            null != ($request->file('presentacion'))
        ) {
            $path[3] = new File(['path' => $request->file('presentacion')
                ->storeAs($request->codigo, "Presentacion " . $request->titulo . "." . $request->file('presentacion')
                    ->getClientOriginalExtension()), 'type_id' => 4]);
        }

        $response[] = $curso->save();
        if (count($contentData) > 0) {
            $response[] = $curso->content()->saveMany($contentData);
        }

        if (count($path) > 0) {
            $response[] = $curso->file()->saveMany($path);
        }
        if (isset($result)) {
            $response[] = $result;
        }

        $capacityUpdated = $curso->capacity()->update(['min' => $request->min, 'max' => $request->max]);
        if ($curso->prerequisite->count() > 0) {
            $prerequisiteUpdated = $curso->prerequisite()->update(['prerequisite' => (isset($request->prerequisite) ? $request->prerequisite : null)]);
        } else {
            $prerequisite = new Prerequisite([
                'prerequisite' => (isset($request->prerequisite) ? $request->prerequisite : null),
                'course_code' => $request->codigo
            ]);
            $prerequisiteUpdated = $curso->prerequisite()->save($prerequisite);
        }

        $success = $curso->exists && $capacityUpdated && $prerequisiteUpdated;

        return json_encode($success);


        //if update errors out return session flash alert


        /*
            return Redirect::back()
                ->with("alert",Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));
         */
        /*
        // 
        return Redirect::back()
            ->with("alert",Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
        */
    } //end update

    public function delete($id)
    {
        $user = Auth::user();

        if ($user->can('delete', Course::class)) {
            $curso = Course::findOrFail($id);
            if ($curso == null) {
                return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "Area ingresada no encontrada."));
            }


            if ($curso->scheduled->count() > 0) {
                return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error", "Esta acción de formación no puede ser eliminada, forma parte de cursos programados."));
            }

            if ($curso->delete()) {
                // Delete related uploaded files stored in storage/app (files table).
                $courseFiles = $curso->file()->get();
                foreach ($courseFiles as $courseFile) {
                    $storedPath = $courseFile->path ?? ($courseFile->file_path ?? null);
                    if (is_string($storedPath) && $storedPath !== '') {
                        Storage::delete($storedPath);
                    }

                    $courseFile->delete();
                }

                // Clean up the course directory used by storeAs($course->code, ...)
                if (is_string($curso->code) && $curso->code !== '') {
                    Storage::deleteDirectory($curso->code);
                }

                // Legacy uploads stored directly in public/uploads/documentos (older flow)
                foreach (['ficha_tecnica', 'manual_p', 'manual_f', 'guia', 'presentacion'] as $column) {
                    $filename = $curso->{$column} ?? null;
                    if (! is_string($filename) || $filename === '') {
                        continue;
                    }

                    $absolutePath = base_path('public/uploads/documentos/' . $filename);
                    if (IlluminateFile::exists($absolutePath)) {
                        IlluminateFile::delete($absolutePath);
                    }
                }

                return Redirect::back()
                    ->with('alert', Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
            }

            return Redirect::back()
                ->with('alert', Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar", "No tienes permisos para realizar esta accion."));
    }

    public function downloadAllFiles($id)
    {
        $user = Auth::user();
        if (!$user || $user->cannot('downloadAllFiles', Course::class)) {
            return redirect()->back();
        }
        $courseFiles = File::where('course_id', $id)->get();

        $files = [];
        foreach ($courseFiles as $courseFile) {
            $files[$courseFile->id] = base_path() . "/storage/app/" . $courseFile->path;
        }

        if (empty($files)) {
            return redirect()->back();
        }

        $zip = new ZipArchive;

        $zipFile = base_path() . "/public/uploads/zip/" . Course::find($id)->code . ".zip";

        if ($zip->open($zipFile, ZipArchive::CREATE) == TRUE) {
            foreach ($files as $key => $value) {
                $zip->addFile($value, basename($value));
            }
            $zip->close();
        }
        return response()->download($zipFile);
    }

    public function codeCheck(Request $request)
    {

        $codeValue = $request->input('codeValue');
        if ($codeValue === null || trim((string) $codeValue) === '') {
            return json_encode(false);
        }

        // Include soft-deleted rows so codes can't be reused.
        $query = Course::withTrashed()->where('code', $codeValue);

        $ignoreId = $request->input('ignore_id');
        if ($ignoreId !== null && is_numeric($ignoreId)) {
            $query->where('id', '!=', (int) $ignoreId);
        }

        // Soft-deleted rows are excluded by default via SoftDeletes global scope.
        return json_encode($query->exists());
    }

    public function courseDetails($id)
    {
        $user = Auth::user();
        $curso = Course::with(['File', 'Capacity', 'Prerequisite.prerequisite', 'Content'])/* ->with('File') */
            //->with('Capacity')
            //->with('prerequisite.prerequisite')
            //->with(DB::raw(Course::where('id', Prerequisite::where('course_id', $id)->select('prerequisite_id'))->select('title')->get()))
            /* ->with('Content') */->where('id', $id)
            ->get();

        if (!$curso || $user->cannot('get', Course::class)) {
            return json_encode([]);
        }

        return json_encode($curso);
    }

    public function onCourseSubmitAlert($request)
    { //redirects on course form submit
        //return json_encode($request);

        if ($request == 'true') {
            return redirect()->route('acciones')->with("alert", Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
            //if method = put 
            //return redirect()->route('acciones')->with("alert", Funciones::getAlert("success","Accion actualizadada exitosamente","Operacion Exitosa."));
        } else {
            return redirect()->route('acciones')->with("alert", Funciones::getAlert("danger", "Error al Intentar Crear Curso", "Operacion Erronea."));
        }


        //return Redirect::back()->with("alert",Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
    } //end onCourseSubmitAlert

    public function prerequisiteList()
    { //draws course prerequiste selector
        foreach (Course::orderBy('code', 'ASC')->get() as $course) {
            $courses[] = [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
            ];
        }
        return json_encode(['courses' => $courses]);
    }

    public function download($id, $type)
    {
        if ($type == '0') {
            $files = File::where('course_id', $id)->orderBy('type_id', 'Asc')->get();
            return json_encode(['files' => $files]);
        } else {

            $files = File::where('course_id', $id)->get();
            $helper = new stdClass();
            foreach ($files as $file) {
                if ($file->type_id == '1') {
                    $helper->type1 = $file->path;
                }
                if ($file->type_id == '2') {
                    $helper->type2 = $file->path;
                }
                if ($file->type_id == '3') {
                    $helper->type3 = $file->path;
                }
                if ($file->type_id == '4') {
                    $helper->type4 = $file->path;
                }
            }

            if ($files->count() >= 1) {
                switch ($type) {
                    case 1:
                        //return 'Manual de Facilitador';
                        if (isset($helper->type1))
                            return Storage::download($helper->type1);
                        break;
                    case 2:
                        //return 'Manual de Usuario';
                        if (isset($helper->type2))
                            return Storage::download($helper->type2);
                        break;
                    case 3:
                        //return 'Guia';
                        if (isset($helper->type3))
                            return Storage::download($helper->type3);
                        break;
                    case 4:
                        //return 'Presentacion';
                        if (isset($helper->type4))
                            return Storage::download($helper->type4);
                        break;
                    default:
                        break;
                }
            } else {
                return redirect()->back();
            }
        }
    }

}

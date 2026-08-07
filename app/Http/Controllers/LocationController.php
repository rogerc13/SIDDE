<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Funciones;
use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\LocationForm;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function get($id)
    {
        $user = Auth::user();
        $location = Location::with('floor.building')->find($id);
        if (!$location || $user->cannot('get', Location::class)) {
            return json_encode([]);
        }

        return json_encode($location);
    }

    public function getAll()
    {
        $user = Auth::user();
        if ($user->cannot('getAll', Location::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta acción."));
        }

        $name = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_STRING);

        $locations = Location::with('floor.building')
            ->orderBy('name', 'asc');

        if ($name) {
            $locations = $locations->where("name", "LIKE", "%$name%");
        }

        $locations = $locations->paginate(10);

        $buildings = Building::orderBy('name', 'asc')->get();
        $floors = Floor::with('building')->orderBy('name', 'asc')->get();

        return view('pages.admin.ubicaciones.index')
            ->with('locations', $locations)
            ->with('buildings', $buildings)
            ->with('floors', $floors)
            ->with('name', $name);
    }

    public function store(LocationForm $request)
    {
        $user = Auth::user();

        if ($user->can('store', Location::class)) {
            $location = new Location();
            $location->name = $request->nombre;
            $location->floor_id = $request->floor_id;

            if ($location->save()) {
                return Redirect::back()
                    ->with("alert", Funciones::getAlert("success", "Ingresado Exitosamente", "Operacion Exitosa."));
            }

            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Crear Ubicación", "Operacion Erronea."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
    }

    public function count()
    {
    }

    public function update(LocationForm $request, $id)
    {
        $user = Auth::user();

        $location = Location::find($id);

        if (!$location) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "La ubicación seleccionada no pudo ser encontrada."));
        }

        if ($user->cannot('update', Location::class)) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar editar", "No tienes permisos para realizar esta accion."));
        }

        $location->name = $request->nombre;
        $location->floor_id = $request->floor_id;

        if (!$location->save()) {
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea. Error actualizando los datos."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
    }

    public function delete($id)
    {
        $user = Auth::user();

        if ($user->can('delete', Location::class)) {
            $location = Location::find($id);
            if ($location == null) {
                return Redirect::back()
                    ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "La ubicación seleccionada no pudo ser encontrada."));
            }

            if ($location->sessions->count() > 0) {
                return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error", "Esta ubicación no puede ser eliminada, posee sesiones asignadas."));
            }

            if ($location->delete()) {
                return Redirect::back()->with('alert', Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
            }

            return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error al intentar eliminar", "Operacion errónea."));
        }

        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al Intentar Editar", "No tienes permisos para realizar esta accion."));
    }

    public function getBuilding($id)
    {
        $user = Auth::user();
        $building = Building::find($id);
        if (!$building || $user->cannot('get', Location::class)) {
            return json_encode([]);
        }

        return json_encode($building);
    }

    public function storeBuilding(Request $request)
    {
        $user = Auth::user();

        if ($user->cannot('store', Location::class)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permisos para realizar esta accion.'], 403);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:buildings,name',
        ]);

        $building = new Building();
        $building->name = $request->name;

        if ($building->save()) {
            if ($request->expectsJson()) {
                return response()->json($building);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Edificio creado exitosamente", "Operación exitosa."));
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Error al intentar crear edificio.'], 500);
        }
        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al intentar crear edificio", "Operación errónea."));
    }

    public function updateBuilding(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->cannot('update', Location::class)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permisos para realizar esta accion.'], 403);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
        }

        $building = Building::find($id);

        if (!$building) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'El edificio seleccionado no pudo ser encontrado.'], 404);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "El edificio seleccionado no pudo ser encontrado."));
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:buildings,name,' . $id,
        ]);

        $building->name = $request->name;

        if ($building->save()) {
            if ($request->expectsJson()) {
                return response()->json($building);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Operación errónea.'], 500);
        }
        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea."));
    }

    public function deleteBuilding($id)
    {
        $user = Auth::user();
        $request = request();

        if ($user->cannot('delete', Location::class)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permisos para realizar esta accion.'], 403);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
        }

        $building = Building::find($id);

        if (!$building) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'El edificio seleccionado no pudo ser encontrado.'], 404);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar eliminar", "El edificio seleccionado no pudo ser encontrado."));
        }

        if ($building->floors->count() > 0) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Este edificio no puede ser eliminado, posee pisos asignados.'], 422);
            }
            return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error", "Este edificio no puede ser eliminado, posee pisos asignados."));
        }

        if ($building->delete()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => true]);
            }
            return Redirect::back()->with('alert', Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Operación errónea.'], 500);
        }
        return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error al intentar eliminar", "Operación errónea."));
    }

    public function getFloor($id)
    {
        $user = Auth::user();
        $floor = Floor::find($id);
        if (!$floor || $user->cannot('get', Location::class)) {
            return json_encode([]);
        }

        return json_encode($floor);
    }

    public function storeFloor(Request $request)
    {
        $user = Auth::user();

        if ($user->cannot('store', Location::class)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permisos para realizar esta accion.'], 403);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'building_id' => 'required|integer|exists:buildings,id',
        ]);

        $floor = new Floor();
        $floor->name = $request->name;
        $floor->building_id = $request->building_id;

        if ($floor->save()) {
            if ($request->expectsJson()) {
                return response()->json($floor);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Piso creado exitosamente", "Operación exitosa."));
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Error al intentar crear piso.'], 500);
        }
        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al intentar crear piso", "Operación errónea."));
    }

    public function updateFloor(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->cannot('update', Location::class)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permisos para realizar esta accion.'], 403);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
        }

        $floor = Floor::find($id);

        if (!$floor) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'El piso seleccionado no pudo ser encontrado.'], 404);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "El piso seleccionado no pudo ser encontrado."));
        }

        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $floor->name = $request->name;

        if ($floor->save()) {
            if ($request->expectsJson()) {
                return response()->json($floor);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("success", "Editado exitosamente", "Operación exitosa."));
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Operación errónea.'], 500);
        }
        return Redirect::back()
            ->with("alert", Funciones::getAlert("danger", "Error al intentar editar", "Operación errónea."));
    }

    public function deleteFloor($id)
    {
        $user = Auth::user();
        $request = request();

        if ($user->cannot('delete', Location::class)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'No tienes permisos para realizar esta accion.'], 403);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al Intentar Acceder", "No tienes permisos para realizar esta accion."));
        }

        $floor = Floor::find($id);

        if (!$floor) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'El piso seleccionado no pudo ser encontrado.'], 404);
            }
            return Redirect::back()
                ->with("alert", Funciones::getAlert("danger", "Error al intentar eliminar", "El piso seleccionado no pudo ser encontrado."));
        }

        if ($floor->locations->count() > 0) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Este piso no puede ser eliminado, posee aulas asignadas.'], 422);
            }
            return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error", "Este piso no puede ser eliminado, posee aulas asignadas."));
        }

        if ($floor->delete()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => true]);
            }
            return Redirect::back()->with('alert', Funciones::getAlert("success", "Eliminado exitosamente", "Operación exitosa."));
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Operación errónea.'], 500);
        }
        return Redirect::back()->with('alert', Funciones::getAlert("danger", "Error al intentar eliminar", "Operación errónea."));
    }

    public function getForSelect()
    {
        $locations = Location::with('floor.building')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($locations);
    }
}

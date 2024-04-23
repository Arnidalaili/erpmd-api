<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreUserRoleRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Requests\DestroyUserRoleRequest;
use App\Http\Requests\StoreLogTrailRequest;

use App\Models\UserRole;
use App\Models\LogTrail;
use App\Models\Parameter;
use App\Models\Role;
use App\Models\User;

use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\ParameterController;
use App\Http\Requests\StoreAclRequest;
use Illuminate\Http\JsonResponse;

class UserRoleController extends Controller
{
    /**
     * @ClassName 
     */
    public function index(User $user): JsonResponse
    {
        $userRoles = new UserRole();

        return response()->json([
            'data' => $userRoles->get($user->roles()),
            'attributes' => [
                'totalRows' => $userRoles->totalRows,
                'totalPages' => $userRoles->totalPages
            ]
        ]);
    }

    public function detail()
    {
        $user = User::findOrFail(request()->user_id);

        return response([
            'data' => $user->roles
        ]);
    }

    /**
     * @ClassName 
     */
    public function store(StoreAclRequest $request, Role $role): JsonResponse
    {
        DB::beginTransaction();

        try {
            $role->acls()->detach();

            foreach ($request->aco_ids as $aco_id) {
                $role->acls()->attach($aco_id, [
                    'modifiedby' => auth('api')->user()->name
                ]);
            }

            $logTrail = [
                'namatabel' => strtoupper($role->getTable()),
                'postingdari' => 'ENTRY ROLE ACL',
                'idtrans' => $role->id,
                'nobuktitrans' => $role->id,
                'aksi' => 'ENTRY',
                'datajson' => $role->load('acls')->toArray(),
                'modifiedby' => $role->modifiedby
            ];

            $validatedLogTrail = new StoreLogTrailRequest($logTrail);
            $storedLogTrail = app(LogTrailController::class)->store($validatedLogTrail);

            DB::commit();

            return response()->json([
                'message' => 'Berhasil disimpan',
                'user' => $role->load('acls')
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }
    public function show(UserRole $userrole)
    {
        $data = User::select('user')
            ->where('id', '=',  $userrole['user_id'])
            ->first();
        $userrole['user'] = $data['user'];

        return response([
            'status' => true,
            'data' => $userrole
        ]);
    }

    /**
     * @ClassName 
     */
    public function update(UpdateUserRoleRequest $request, UserRole $userrole)
    {
        DB::beginTransaction();
        try {
            UserRole::where('user_id', $request->user_id)->delete();

            for ($i = 0; $i < count($request->role_id); $i++) {
                if ($request->status[$i] == 1) {
                    $userrole = new UserRole();
                    $userrole->user_id = $request->user_id;
                    $userrole->modifiedby = auth('api')->user()->name;
                    $userrole->role_id = $request->role_id[$i]  ?? 0;

                    if ($userrole->save()) {
                        $logTrail = [
                            'namatabel' => strtoupper($userrole->getTable()),
                            'postingdari' => 'EDIT USER ROLE',
                            'idtrans' => $userrole->id,
                            'nobuktitrans' => $userrole->id,
                            'aksi' => 'EDIT',
                            'datajson' => $userrole->toArray(),
                            'modifiedby' => $userrole->modifiedby
                        ];

                        $validatedLogTrail = new StoreLogTrailRequest($logTrail);
                        $storedLogTrail = app(LogTrailController::class)->store($validatedLogTrail);

                        DB::commit();
                    }
                }
            }

            /* Set position and page */
            $selected = $this->getPosition($userrole, $userrole->getTable());
            $userrole->position = $selected->position;
            $userrole->page = ceil($userrole->position / ($request->limit ?? 10));

            return response([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $userrole
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy($id,  Request $request)
    {
        DB::beginTransaction();

        try {
            $userRole = UserRole::where('id', $id)->first();
            $delete = UserRole::where('id', $id)->delete();

            if ($delete > 0) {
                $logTrail = [
                    'namatabel' => strtoupper($userRole->getTable()),
                    'postingdari' => 'DELETE USERROLE',
                    'idtrans' => $id,
                    'nobuktitrans' => '',
                    'aksi' => 'DELETE',
                    'datajson' => $userRole->toArray(),
                    'modifiedby' => $userRole->modifiedby
                ];

                $validatedLogTrail = new StoreLogTrailRequest($logTrail);
                app(LogTrailController::class)->store($validatedLogTrail);

                DB::commit();

                /* Set position and page */
                $selected = $this->getPosition($userRole, $userRole->getTable(), true);
                $userRole->position = $selected->position;
                $userRole->id = $selected->id;
                $userRole->page = ceil($userRole->position / ($request->limit ?? 10));

                return response([
                    'status' => true,
                    'message' => 'Berhasil dihapus',
                    'data' => $userRole
                ]);
            } else {
                dd($delete);
                DB::rollBack();

                return response([
                    'status' => false,
                    'message' => 'Gagal dihapus'
                ]);
            }
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }


    // public function destroy(UserRole $userrole, DestroyUserRoleRequest $request)
    // {
    //     DB::beginTransaction();

    //     try {
    //         $delete = UserRole::where('user_id', $request->user_id)->delete();

    //         if ($delete > 0) {
    //             $logTrail = [
    //                 'namatabel' => strtoupper($userrole->getTable()),
    //                 'postingdari' => 'DELETE USER ROLE',
    //                 'idtrans' => $userrole->id,
    //                 'nobuktitrans' => $userrole->id,
    //                 'aksi' => 'DELETE',
    //                 'datajson' => $userrole->toArray(),
    //                 'modifiedby' => $userrole->modifiedby
    //             ];

    //             $validatedLogTrail = new StoreLogTrailRequest($logTrail);
    //             $storedLogTrail = app(LogTrailController::class)->store($validatedLogTrail);

    //             DB::commit();
    //         }


    //         // $userrole->position = $data->row;
    //         // $userrole->id = $data->id;
    //         // if (isset($request->limit)) {
    //         //     $userrole->page = ceil($userrole->position / $request->limit);
    //         // }

    //         $del = 1;

    //         $data = $this->getid($request->user_id, $request, $del);
    //         $selected = $this->getPosition($userrole, $userrole->getTable(), true);
    //         $userrole->position = $selected->position;
    //         $userrole->id = $selected->id;
    //         $userrole->page = ceil($userrole->position / ($request->limit ?? 10));

    //         return response([
    //             'status' => true,
    //             'message' => 'Berhasil dihapus',
    //             'data' => $userrole
    //         ]);
    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }
    // }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('userrole')->getColumns();

        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    /**
     * @ClassName
     */
    public function export()
    {
        $response = $this->index();
        $decodedResponse = json_decode($response->content(), true);
        $useracls = $decodedResponse['data'];

        $columns = [
            [
                'label' => 'No',
            ],
            [
                'label' => 'ID',
                'index' => 'id',
            ],
            [
                'label' => 'User',
                'index' => 'user',
            ],
            [
                'label' => 'Nama User',
                'index' => 'name',
            ],
        ];

        $this->toExcel('User Role', $useracls, $columns);
    }

    public function detaillist(Request $request)
    {

        $param1 = $request->user_id;

        $controller = new ParameterController;
        $dataaktif = $controller->getparameterid('STATUS AKTIF', 'STATUS AKTIF', 'AKTIF');
        $datanonaktif = $controller->getparameterid('STATUS AKTIF', 'STATUS AKTIF', 'NON AKTIF');
        $aktif = $dataaktif->id;
        $nonaktif = $datanonaktif->id;


        $data = Role::select(
            DB::raw("role.id as role_id,
                    role.rolename as rolename,
                    (case when isnull(userrole.role_id,0)=0 then 
                    " . DB::raw($nonaktif) . " 
                    else 
                    " . DB::raw($aktif) . " 
                    end) as status
            ")
        )
            ->leftJoin('userrole', function ($join)  use ($param1) {
                $join->on('role.id', '=', 'userrole.role_id');
                $join->on('userrole.user_id', '=', DB::raw("'" . $param1 . "'"));
            })
            ->orderBy('role.id')
            ->get();

        return response([
            'data' => $data
        ]);
    }
}

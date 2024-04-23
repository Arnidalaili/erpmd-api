<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\DestroyUserRequest;
use App\Http\Requests\StoreLogTrailRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRoleRequest;
use App\Models\User;
use App\Models\Parameter;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * @ClassName 
     * user
     * @Detail1 AcosController
     */
    public function index()
    {
        // dd('test');
        $user = new User();
        return response([
            'data' => $user->get(),
            'attributes' => [
                'totalRows' => $user->totalRows,
                'totalPages' => $user->totalPages
            ]
            
        ]);
    }
    public function default()
    {
        $user = new User();
        return response([
            'status' => true,
            'data' => $user->default()
        ]);
    }

    public function getRoles(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->roles
        ]);
    }

    public function storeRoles(StoreUserRoleRequest $request, User $user): JsonResponse
    {
        DB::beginTransaction();

        try {


            // $user->roles()->detach();

            // if (is_array($request->role_ids)) {
            //     foreach ($request->role_ids as $role_id) {
            //         $user->roles()->attach($role_id, [
            //             'modifiedby' => auth('api')->user()->name
            //         ]);
            //     }
            // }


            // $logTrail = [
            //     'namatabel' => strtoupper($user->getTable()),
            //     'postingdari' => 'ENTRY USER ROLE',
            //     'idtrans' => $user->id,
            //     'nobuktitrans' => $user->id,
            //     'aksi' => 'ENTRY',
            //     'datajson' => $user->load('roles')->toArray(),
            //     'modifiedby' => $user->modifiedby
            // ];

            // $validatedLogTrail = new StoreLogTrailRequest($logTrail);
            // $storedLogTrail = app(LogTrailController::class)->store($validatedLogTrail);

            $role = (new Role())->processRoleStore([
                'role_ids' => $request->role_ids
            ],$user);
        

            DB::commit();

            return response()->json([
                'message' => 'Berhasil disimpan',
                'user' => $user->load('roles')
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
       
        DB::beginTransaction();

        try {
            $data = [
                'roleids' => [],
                'user' => strtoupper($request->user),
                'name' => strtoupper($request->name),
                'email' => strtoupper($request->email),
                'password' => $request->password,
                'dashboard' => strtoupper($request->dashboard),
                'customerid' => intval($request->customerid) ?? 0,
                'karyawanid' => intval($request->karyawanid) ?? 0,
                'status' => $request->status,
                'statusakses' => $request->statusakses,
            ];

        

            $user = (new User())->processStore($data);

            $user->position = $this->getPosition($user, $user->getTable())->position;
            if ($request->limit==0) {
                $user->page = ceil($user->position / (10));
            } else {
                $user->page = ceil($user->position / ($request->limit ?? 10));
            }

            DB::commit();

            if (isset($request->limit)) {
                $user->page = ceil($user->position / $request->limit);
            }

            return response()->json([
                'status' => true,
                'message' => 'Berhasil disimpan',
                'data' => $user
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    public function show(User $user)
    {
        return response([
            'status' => true,
            'data' => $user->load('roles')
        ]);
    }

    /**
     * @ClassName 
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        DB::beginTransaction();

        try {
            $data = [
                'user' => strtoupper($request->user),
                'name' => strtoupper($request->name),
                'email' => strtoupper($request->email),
                'password' => Hash::make($request->password),
                'dashboard' => strtoupper($request->dashboard),
                'customerid' => intval($request->customerid) ?? 0,
                'karyawanid' => intval($request->karyawanid) ?? 0,
                'status' => $request->status,
                'statusakses' => $request->statusakses,
            ];

          

            $user = (new User())->processUpdate($user, $data);
            $user->position = $this->getPosition($user, $user->getTable())->position;
            if ($request->limit==0) {
                $user->page = ceil($user->position / (10));
            } else {
                $user->page = ceil($user->position / ($request->limit ?? 10));
            }

            DB::commit();

            if (isset($request->limit)) {
                $user->page = ceil($user->position / $request->limit);
            }

            return response()->json([
                'status' => true,
                'message' => 'Berhasil diubah',
                'data' => $user
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function destroy(DestroyUserRequest $request, $id)
    {
        DB::beginTransaction();

        try {

            $user = (new User())->processDestroy($id);
            $selected = $this->getPosition($user, $user->getTable(), true);
            $user->position = $selected->position;
            $user->id = $selected->id;
            if ($request->limit==0) {
                $user->page = ceil($user->position / (10));
            } else {
                $user->page = ceil($user->position / ($request->limit ?? 10));
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil dihapus',
                'data' => $user
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            throw $th;
        }
    }

    /**
     * @ClassName 
     */
    public function report()
    {
    }

    /**
     * @ClassName 
     */
    // public function export(RangeExportReportRequest $request)
    // {
    //     if (request()->cekExport) {

    //         if (request()->offset == "-1" && request()->limit == '1') {

    //             return response([
    //                 'errors' => [
    //                     "export" => app(ErrorController::class)->geterror('DTA')->keterangan
    //                 ],
    //                 'status' => false,
    //                 'message' => "The given data was invalid."
    //             ], 422);
    //         } else {
    //             return response([
    //                 'status' => true,
    //             ]);
    //         }
    //     } else {

    //         $response = $this->index();
    //         $decodedResponse = json_decode($response->content(), true);
    //         $users = $decodedResponse['data'];


    //         $judulLaporan = $users[0]['judulLaporan'];

    //         // $judulLaporan = $users[0]['judulLaporan'];

    //         $i = 0;
    //         foreach ($users as $index => $params) {

    //             $status = $params['status'];
    //             $statusakses = $params['statusakses'];

    //             $result = json_decode($status, true);
    //             $resultAkses = json_decode($statusakses, true);

    //             $status = $result['MEMO'];
    //             $statusakses = $resultAkses['MEMO'];

    //             $users[$i]['status'] = $status;
    //             $users[$i]['statusakses'] = $statusakses;

    //             $i++;
    //         }


    //         $columns = [
    //             [
    //                 'label' => 'No',
    //             ],
    //             [
    //                 'label' => 'User',
    //                 'index' => 'user',
    //             ],
    //             [
    //                 'label' => 'Name',
    //                 'index' => 'name',
    //             ],
    //             [
    //                 'label' => 'Dashboard',
    //                 'index' => 'dashboard',
    //             ],
    //             [
    //                 'label' => 'Status aktif',
    //                 'index' => 'status',
    //             ],
    //             [
    //                 'label' => 'status akses',
    //                 'index' => 'statusakses',
    //             ],
    //         ];

    //         $this->toExcel($judulLaporan, $users, $columns);
    //     }
    // }

    public function fieldLength()
    {
        $data = [];
        $columns = DB::connection()->getDoctrineSchemaManager()->listTableDetails('user')->getColumns();

        foreach ($columns as $index => $column) {
            $data[$index] = $column->getLength();
        }

        return response([
            'data' => $data
        ]);
    }

    public function combostatus(Request $request)
    {
        $params = [
            'status' => $request->status ?? '',
            'grp' => $request->grp ?? '',
            'subgrp' => $request->subgrp ?? '',
        ];
        
        $temp = 'temp_' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        
        if ($params['status'] == 'entry') {
            $query = Parameter::select('id', 'text as keterangan')
                ->where('grp', "=", $params['grp'])
                ->where('subgrp', "=", $params['subgrp']);
        } else {
            DB::statement("
                CREATE TEMPORARY TABLE $temp
                (
                    id INT(11) DEFAULT NULL,
                    parameter VARCHAR(50) DEFAULT NULL,
                    param VARCHAR(50) DEFAULT NULL
                )
            ");
            DB::statement("
                INSERT INTO $temp (id, parameter, param)
                VALUES ('0', 'ALL', '')
            ");
            $query = "
                INSERT INTO $temp (id, parameter, param)
                SELECT id, text, text
                FROM parameter
                WHERE grp = '" . $params['grp'] . "'
                AND subgrp = '" . $params['subgrp'] . "'
            ";
            DB::statement($query);
        }
        $queryGetData = "SELECT * FROM $temp";
        $data = DB::select($queryGetData);
        return response([
            'data' => $data
        ]);
    }

    public function getuserid(Request $request)
    {

        $params = [
            'user' => $request->user ?? '',
        ];

        $query = User::select('id')
            ->where('user', "=", $params['user']);

        $data = $query->first();

        return response([
            'data' => $data
        ]);
    }

    public function confirmUser(Request $request)
    {
        $username = $request->username;
        $password = $request->password;

        $user = new User();
        $data = $user->getConfirmUser($username, $password);
        if($data == true){
            return response([
                'data' => $data
            ]);
        } else {
            return response([
                'message' => 'User Not Found !'
            ]);
        }
        
    }
}

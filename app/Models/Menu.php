<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Illuminate\Support\Facades\Schema;


class Menu extends MyModel
{
    use HasFactory;

    protected $table = 'menu';

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function get()
    {
        $this->setRequestParameters();

        $query = DB::table($this->table)
            ->from(DB::raw($this->table . " as menu"))
            ->select(
                'menu.id',
                'menu.menuname',
                'menu.menuseq',
                'menu.menuparent',
                'menu2.menuname as menuparent2',
                'menu.menuicon',
                'menu.aco_id',
                'menu.link',
                'menu.menuexe',
                'menu.menukode',
                'menu.modifiedby',
                'menu.created_at',
                'menu.updated_at',
                DB::raw("'Laporan Menu' as judulLaporan"),
                DB::raw(" 'User :" . auth('api')->user()->name . "' as usercetak")
            )
            ->leftJoin(DB::raw("menu as menu2"), 'menu2.id', '=', 'menu.menuparent')
            ->leftJoin(DB::raw("acos"), 'acos.id', '=', 'menu.aco_id');

        $this->totalRows = $query->count();
        $this->totalPages = request()->limit > 0 ? ceil($this->totalRows / request()->limit) : 1;
        
        $this->sort($query);
        $this->filter($query);
        $this->paginate($query);

        $data = $query->get();

        return $data;
    }


    public function selectColumns($query)
    {
        return $query->from(
            DB::raw($this->table)
        )
            ->select(
                DB::raw(
                    "$this->table.id,
                $this->table.menuname,
                COALESCE(menu2.menuname,'') as menuparent,
                $this->table.menuicon,
                COALESCE(acos.nama,'') as aco_id,
                $this->table.link,
                $this->table.menuexe,
                $this->table.menukode,
                $this->table.modifiedby,
                $this->table.created_at,
                $this->table.updated_at"
                )
            )
            ->leftJoin(DB::raw("menu as menu2"), 'menu2.id', '=', 'menu.menuparent')
            ->leftJoin(DB::raw("acos"), 'acos.id', '=', 'menu.aco_id');
    }

    public function createTemp(string $modelTable)
    {
        $this->setRequestParameters();
        $query = DB::table($modelTable);
        $query = $this->selectColumns($query);
        $query = $this->filter($query);
        $query = $this->sort($query);

      
        $temp = 'temp' . rand(1, getrandmax()) . str_replace('.', '', microtime(true));
        DB::statement("CREATE TEMPORARY TABLE $temp (
            id BIGINT NULL,
            menuname VARCHAR(50) NULL,
            menuparent VARCHAR(50) NULL,
            menuicon VARCHAR(50) NULL,
            aco_id VARCHAR(50) NULL,
            link VARCHAR(2000) NULL,
            menuexe VARCHAR(200) NULL,
            menukode VARCHAR(100) NULL,
            modifiedby VARCHAR(50) NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            position INT AUTO_INCREMENT PRIMARY KEY
        )");
      
        DB::table($temp)->insertUsing(["id","menuname",  "menuparent", "menuicon", "aco_id", "link", "menuexe", "menukode", "modifiedby", "created_at", "updated_at"],$query);

        return $temp;
    }


    public function sort($query)
    {
        return $query->orderBy($this->table . '.' . $this->params['sortIndex'], $this->params['sortOrder']);
    }

    public function filter($query, $relationFields = [])
    {
        if (count($this->params['filters']) > 0 && @$this->params['filters']['rules'][0]['data'] != '') {
            switch ($this->params['filters']['groupOp']) {
                case "AND":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'status') {
                            $query = $query->where('parameter.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'menuparent') {
                            $query = $query->where('menu2.menuname', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'aco_id') {
                            $query = $query->where('acos.nama', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->whereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            // $query = $query->where($this->table . '.' . $filters['field'], 'LIKE', "%$filters[data]%");
                            $query = $query->whereRaw($this->table  . '.' . $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%' escape '|'");
                        }
                    }

                    break;
                case "OR":
                    foreach ($this->params['filters']['rules'] as $index => $filters) {
                        if ($filters['field'] == 'status') {
                            $query = $query->orWhere('parameter.text', '=', $filters['data']);
                        } else if ($filters['field'] == 'menuparent') {
                            $query = $query->orWhere('menu2.menuname', 'LIKE', "%$filters[data]%");
                        } else if ($filters['field'] == 'aco_id') {
                            // $query = $query->orWhere('acos.nama', 'LIKE', "%$filters[data]%");
                            $query = $query->orWhereRaw("acos.nama like '%" . escapeLike($filters['data']) . "%' escape '|'");
                        } else if ($filters['field'] == 'created_at' || $filters['field'] == 'updated_at') {
                            $query = $query->OrwhereRaw("DATE_FORMAT($this->table.$filters[field], '%d-%m-%Y %H:%i:%s') LIKE ?", ['%' . $filters['data'] . '%']);
                        } else {
                            // $query = $query->orWhere($this->table . '.' . $filters['field'], 'LIKE', "%$filters[data]%");
                            $query = $query->OrwhereRaw($this->table  . '.' .  $filters['field'] . " LIKE '%" . escapeLike($filters['data']) . "%' escape '|'");
                        }
                    }

                    break;
                default:

                    break;
            }

            $this->totalRows = $query->count();
            $this->totalPages = $this->params['limit'] > 0 ? ceil($this->totalRows / $this->params['limit']) : 1;
        }

        return $query;
    }

    public function paginate($query)
    {
        return $query->skip($this->params['offset'])->take($this->params['limit']);
    }

    public function validasiNonController($menuname)
    {
        $validasiQuery = DB::table('menu')
            ->from(
                DB::raw("menu as a")
            )
            ->select(
                'a.aco_id'
            )
            ->where('a.menuname', '=', $menuname)
            ->first();

        return $validasiQuery;
    }

    public function get_class_methods($class, $comment = false)
    {
        $class = 'App\Http\Controllers\Api' . '\\' . $class;
        $r = new ReflectionClass($class);
        $methods = array();

        foreach ($r->getMethods() as $m) {
            if ($m->class == $class) {
                $arr = ['name' => $m->name];
                if ($comment === true) {
                    $arr['docComment'] = $this->get_method_comment($r, $m->name);

                    // if(array_key_exists("ClassName",$arr['docComment'])) { $arr['detail'] = $arr['docComment']['ClassName'];}   else  {$arr['detail'] = [];}
                }
                $methods[] = $arr;
            }
        }


        return $methods;
    }

    public function get_php_classes($php_code, $methods = false)
    {
        $classes = array();
        $tokens = token_get_all($php_code);

        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if ($tokens[$i - 2][0] == T_CLASS && $tokens[$i - 1][0] == T_WHITESPACE && $tokens[$i][0] == T_STRING) {
                $classes[] = $tokens[$i][1]; // assigning class name to classes array variable

            }
        }


        return $classes;
    }

    public function listFolderFiles($controller)
    {
        $dir = base_path('app/http') . '/controllers/api/';
        $ffs = scandir($dir);
        unset($ffs[0], $ffs[1]);

        $data = [];

        foreach ($ffs as $ff) {
            if (is_dir($dir . '/' . $ff)) {
                $data = array_merge($data, $this->listFolderFiles($controller, $dir . '/' . $ff));
            } elseif (is_file($dir . '/' . $ff) && strpos($ff, '.php') !== false) {
                $classes = $this->get_php_classes(file_get_contents($dir . '/' . $ff));
                foreach ($classes as $class) {
                    if ($class == $controller) {
                        if (!class_exists($class)) {
                            include_once($dir . $ff);
                        }

                        $methods = $this->get_class_methods($class, true);

                        foreach ($methods as $method) {
                            if (isset($method['docComment']['ClassName'])) {
                                if (isset($method['docComment']['Detail'])) {
                                    $detail = $method['docComment']['Detail'];
                                } else {
                                    $detail = [''];
                                }

                                // dd($detail);
                                $data[] = [
                                    'class' => $class,
                                    'method' => $method['name'],
                                    'name' => $method['name'] . ' ' . $class,
                                    'detail' => $detail,

                                ];
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }


    public function get_method_comment($obj, $method)
    {
        $comment = $obj->getMethod($method)->getDocComment();
        $pattern = "/@([a-zA-Z0-9_]+)([^\n@]*)/";

        preg_match_all($pattern, $comment, $matches, PREG_SET_ORDER);

        $comments = [];

        foreach ($matches as $match) {
            $tag = $match[1];
            $value = trim($match[2]);

            if (!isset($comments[$tag])) {
                $comments[$tag] = [];
            }

            if (!empty($value)) {
                $comments[$tag][] = $value;
            }
        }


        return $comments;
    }

    public function processStore(array $data): Menu
    {
      
        $class = $this->listFolderFiles($data['controller']);
       
        if ($class <> []) {
            foreach ($class as $value) {
                $namaclass = str_replace('controller', '', strtolower($value['class']));
                $namaclassheader = str_replace('controller', '', strtolower($value['class']));

                $dataaco = (new Acos())->processStore([
                    'class' => $namaclass,
                    'method' => $value['method'],
                    'nama' => $value['name'],
                    'idheader' => 0,
                    'modifiedby' => auth('api')->user()->user,
                ]);

                foreach ($value['detail'] as $detail) {

                    if ($detail != '') {
                        $classdetail1 = $this->listFolderFiles($detail);
                        foreach ($classdetail1 as $valuedetail1) {
                            $namaclass = str_replace('controller', '', strtolower($valuedetail1['class']));


                            $idheader = DB::table('acos')
                                ->select('id')
                                ->where('class', $namaclassheader)
                                ->where('method', 'index')
                                ->first()->id ?? 0;

                           
                            $dataaco = (new Acos())->processStore([
                                'class' => $namaclass,
                                'method' => $valuedetail1['method'],
                                'nama' => $valuedetail1['name'],
                                'idheader' => $idheader,
                                'modifiedby' => auth('api')->user()->user,
                            ]);
                        }
                    }

                    
                }
            }

            $list = Acos::select('id')
                ->where('class', '=', $namaclass)
                ->where('method', '=', 'index')
                ->orderBy('id', 'asc')
                ->first();
            $menuacoid = $list->id;
        } else {
            $menuacoid = 0;
        }

        $menu = new Menu();
        $menu->menuname = ucwords(strtolower($data['menuname']));
        $menu->menuseq = $data['menuseq'];
        $menu->menuparent = $data['menuparent'] ?? 0;
        $menu->menuicon = strtolower($data['menuicon']);
        $menu->menuexe = strtolower($data['menuexe']);
        $menu->modifiedby = auth('api')->user()->user;
        $menu->link = "";
        $menu->aco_id = $menuacoid;



        if (Menu::select('menukode')
            ->where('menuparent', '=', $data['menuparent'])
            ->exists()
        ) {


            if ($data['menuparent'] == 0) {

                $list = Menu::select('menukode')
                    ->where('menuparent', '=', '0')
                    ->where(DB::raw('right(menukode,1)'), '<>', '9')
                    ->where(DB::raw('left(menukode,1)'), '<>', 'Z')
                    ->orderBy('menukode', 'desc')
                    ->first();
                $menukode = chr(ord($list->menukode) + 1);
            } else {


                if (Menu::select('menukode')
                    ->where('menuparent', '=', $data['menuparent'])
                    ->where(DB::raw('right(menukode,1)'), '<>', 'Z')
                    ->exists()
                ) {


                    $list = Menu::select('menukode')
                        ->where('menuparent', '=', $data['menuparent'])
                        ->where(DB::raw('right(menukode,1)'), '<>', 'Z')
                        ->orderBy('menukode', 'desc')
                        ->first();

                    $kodeakhir = substr($list->menukode, -1);
                    $arrayangka = array('1', '2', '3', '4', '5', '6', '7', '8');

                    // dd($kodeakhir);
                    if (in_array($kodeakhir, $arrayangka)) {
                        // $menukode = $list->menukode + 1;
                        $kodeawal = substr($list->menukode, 0, strlen($list->menukode) - 1);
                        $nilai = substr($list->menukode,  -1) + 1;
                        $menukode = $kodeawal . $nilai;
                    } else if ($kodeakhir == '9') {
                        $kodeawal = substr($list->menukode, 0, strlen($list->menukode) - 1);
                        $menukode = $kodeawal . 'A';
                    } else {
                        $kodeawal = substr($list->menukode, 0, strlen($list->menukode) - 1);
                        $menukode = $kodeawal . chr((ord($kodeakhir) + 1));
                    }
                } else {

                    $list = Menu::select('menukode')
                        ->where('id', '=', $data['menuparent'])
                        ->where(DB::raw('right(menukode,1)'), '<>', '9')
                        ->orderBy('menukode', 'desc')
                        ->first();
                    $menukode = $list->menukode . '1';
                }
            }
        } else {

            if ($data['menuparent'] == 0) {
                $menukode = 0;
                $list = Menu::select('menukode')
                    ->where('menuparent', '=', '0')
                    ->where(DB::raw('right(menukode,1)'), '<>', 'Z')
                    ->orderBy('menukode', 'desc')
                    ->first();

                $arrayangka = array('1', '2', '3', '4', '5', '6', '7', '8', '9');
                $kodeakhir = $list->menukode;;
                if (in_array($kodeakhir, $arrayangka)) {

                    $menukode = $list->menukode + 1;
                } else {
                    $menukode =  chr((ord($kodeakhir) + 1));
                }
                $kodeakhir = substr($list->menukode, -1);
                $arrayangka = array('1', '2', '3', '4', '5', '6', '7', '8');
                if (in_array($kodeakhir, $arrayangka)) {

                    $menukode = $list->menukode + 1;
                } else if ($kodeakhir == '9') {
                    $menukode = 'A';
                } else {
                    $menukode = chr((ord($kodeakhir) + 1));
                }
            } else {
                // dd('test');
                $list = Menu::select('menukode')
                    ->where('id', '=', $data['menuparent'])
                    // ->where(DB::raw('right(menukode,1)'), '<>', '9')
                    ->orderBy('menukode', 'desc')
                    ->first();

                if (isset($list)) {
                    $menukode = $list->menukode . '1';
                }
            }
        }

        if (strtoupper($data['menuname']) == 'LOGOUT') {
            $menukode = 'Z';
        }
        // dd($menukode);
        $menu->menukode = $menukode;
        TOP:
        if (!$menu->save()) {
            throw new \Exception("Error storing menu.");
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($menu->getTable()),
            'postingdari' => 'ENTRY MENU',
            'idtrans' => $menu->id,
            'nobuktitrans' => $menu->id,
            'aksi' => 'ENTRY',
            'datajson' => $menu->toArray(),
            'modifiedby' => $menu->modifiedby
        ]);

        return $menu;
    }

    public function processUpdate(Menu $menu, array $data): Menu
    {

        $query = DB::table('menu')
            ->from(
                DB::raw("menu a")
            )
            ->select(
                DB::raw("trim(replace(b.nama,'index ','')) as controller")
            )
            ->join(DB::raw("acos b"), 'a.aco_id', 'b.id')
            ->where('a.id', '=', $data['id'])
            ->first();

        if ($query != null) {
            $controller = $query->controller;
        }

        if ($query != null) {

            $class = $this->listFolderFiles($controller);

            if ($class <> '') {

                foreach ($class as $value) {
                    $namaclass = str_replace('controller', '', strtolower($value['class']));
                    $queryacos = DB::table('acos')
                        ->from(
                            db::raw("acos a")
                        )
                        ->select(
                            'a.id'
                        )
                        ->where('a.class', '=', $namaclass)
                        ->where('a.method', '=', $value['method'])
                        ->where('a.nama', '=', $value['name'])
                        ->first();

                    if (!isset($queryacos)) {
                        if (Acos::select('id')
                            ->where('class', '=', $namaclass)
                            ->exists()
                        ) {
                            $dataaco = (new Acos())->processStore([
                                'class' => $namaclass,
                                'method' => $value['method'],
                                'nama' => $value['name'],
                                'idheader' => 0,
                                'modifiedby' => auth('api')->user()->user,
                            ]);
                        }
                    }
                    // cek detail
                    foreach ($value['detail'] as $detail) {
                        if ($detail != '') {
                            $classdetail1 = $this->listFolderFiles($detail);
                            foreach ($classdetail1 as $valuedetail1) {
                                $namaclass = str_replace('controller', '', strtolower($valuedetail1['class']));

                                $queryacos = DB::table('acos')
                                    ->from(
                                        db::raw("acos a")
                                    )
                                    ->select(
                                        'a.id'
                                    )
                                    ->where('a.class', '=', $namaclass)
                                    ->where('a.method', '=', $valuedetail1['method'])
                                    ->where('a.nama', '=', $valuedetail1['name'])
                                    ->first();
                                if (!isset($queryacos)) {
                                    // if (Acos::select('id')
                                    //     ->where('class', '=', $namaclass)
                                    //     ->exists()
                                    // ) {

                                    $dataaco = (new Acos())->processStore([
                                        'class' => $namaclass,
                                        'method' => $valuedetail1['method'],
                                        'nama' => $valuedetail1['name'],
                                        'idheader' => 0,
                                        'modifiedby' => auth('api')->user()->user,
                                    ]);
                                    // }
                                }
                            }
                        }
                    }
                }
            }
        }

        $menu = new Menu();
        $menu = Menu::lockForUpdate()->findOrFail($data['id']);
        $menu->menuname = ucwords(strtolower($data['menuname']));
        $menu->menuseq = $data['menuseq'];
        $menu->menuicon = strtolower($data['menuicon']);

        if (!$menu->save()) {
            throw new \Exception('Error updating menu.');
        }

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($menu->getTable()),
            'postingdari' => 'EDIT MENU',
            'idtrans' => $menu->id,
            'nobuktitrans' => $menu->id,
            'aksi' => 'EDIT',
            'datajson' => $menu->toArray(),
            'modifiedby' => $menu->modifiedby
        ]);

        return $menu;
    }

    public function processDestroy($id): Menu
    {
        $list = Menu::Select('aco_id')
            ->where('id', '=', $id)
            ->first();


        if (Acos::select('id')
            ->where('id', '=', $list->aco_id)
            ->exists()
        ) {
            $list = Acos::select('class')
                ->where('id', '=', $list->aco_id)
                ->first();

            Acos::where('class', $list->class)->delete();
        }

        $menu = new Menu();
        $menu = $menu->lockAndDestroy($id);

        (new LogTrail())->processStore([
            'namatabel' => strtoupper($menu->getTable()),
            'postingdari' => 'DELETE MENU',
            'idtrans' => $menu->id,
            'nobuktitrans' => $menu->id,
            'aksi' => 'DELETE',
            'datajson' => $menu->toArray(),
            'modifiedby' => $menu->modifiedby
        ]);

        return $menu;
    }
}
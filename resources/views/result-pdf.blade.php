<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resultado-Diagnostico</title>

    <style type="text/css">
        @page {
            margin: 0px;
        }
        body {
            margin: 0px;
        }
        * {
            font-family: Verdana, Arial, sans-serif;
        }
        a {
            color: #fff;
            text-decoration: none;
        }
        table {
            font-size: x-small;
        }
        tfoot tr td {
            font-weight: bold;
            font-size: x-small;
        }
        .invoice table {
            margin: 15px;
        }
        .invoice h3 {
            margin-left: 15px;
        }
        .information {
            background-color: #60A7A6;
            color: #FFF;
        }
        .information .logo {
            margin: 5px;
        }
        .information table {
            padding: 10px;
        }
    </style>

</head>
<body>

<div class="information">
    <table width="100%">
        <tr>
            <td align="left" style="width: 40%;">
                <h3>Paciente:{{ $patient->first_name ?? null }} {{ $patient->last_name ?? null }}</h3>
                <pre>
            </td>
            <td align="center">
                <img src="" alt="Logo" width="64" class="logo"/>
            </td>
            <td align="right" style="width: 40%;">
            </td>
        </tr>

    </table>
</div>
<div class="row text-center">
                                        <div class="col-12">
                                            <h4 class="m-3 p-3">Desayuno</h4><br>
                                        </div>
                                        <div class="col-3">
                                            <table id="example" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Carbohidratos</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cereales as $c)
                                                    @if($c->cho != 0 && $c->cho > 1)
                                                    <tr>
                                                        <td>{{ $c->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $c->cho,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example1" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Acompañamientos</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lacteos as $l)
                                                    @if($l->cho != 0 && $l->cho > 1)
                                                    <tr>
                                                        <td>{{ $l->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $l->cho,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example6" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Proteínas</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($proteinas as $l)
                                                    @if($l->cho > 0)
                                                    <tr>
                                                        <td>{{ $l->name ?? null }}</td>
                                                        <td>{{ round(regla_tres_prot($l->id,$diagnosis->isocaloric_protein, $l->protein,$l->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example7" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Grasas</th>
                                                        <th>Cant.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lipidos as $l)
                                            
                                                    <tr>
                                                        <td>{{ $l->name ?? null }}</td>
                                                        <td>{{ round(regla_tres_lip($diagnosis->isocaloric_lipido, $l->lipid,$l->gr),0) }}</td>
                                                    </tr>
                                                
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        
                                    </div>
                                    <div class="row text-center m-2" style="text-align:center !important; margin:auto;">
                                    <div class="col-12">
                                        <h4 class="m-3 p-3">Almuerzo</h4><br>
                                    </div>
                                    <div class="col-3">
                                            <table id="example2" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Carbohidratos</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cereal_leg as $c)
                                                    @if($c->cho > 0)
                                                    <tr>
                                                        <td>{{ $c->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $c->cho,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                    
                                        <div class="col-3">
                                            <table id="example3" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Acompañamientos</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($verduras as $v)
                                                    @if($v->cho > 0)
                                                    <tr>
                                                        <td>{{ $v->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $v->cho,$v->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example9" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Grasas</th>
                                                        <th>Cant.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lipids as $l)
                                                    <tr>
                                                        <td>{{ $l->name ?? null }}</td>
                                                        <td>{{ round(regla_tres_lip_80($diagnosis->isocaloric_lipido, $l->lipid,$l->gr),0) }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example8" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Proteínas</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($proteins as $p)
                                                    @if($p->cho > 0)
                                                    <tr>
                                                        <td>{{ $p->name ?? null }}</td>
                                                        <td>{{ round(regla_tres_prot($p->id,$diagnosis->isocaloric_protein,$p->protein,$p->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                    
                                        
                                    </div>
                                    <div class="row text-center m-2" style="text-align:center !important; margin:auto;">
                                        <div class="col-12">
                                            <h4 class="m-3 p-3">Cena</h4><br>
                                        </div>
                                        <div class="col-3">
                                            <table id="example4" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Carbohidratos</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($cereal_leg as $c)
                                                    @if($c->cho > 0)
                                                    <tr>
                                                        <td>{{ $c->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $c->cho,$c->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example5" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                            <thead>
                                                    <tr>
                                                        <th>Acompañamientos</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($verduras as $v)
                                                    @if($v->cho > 0)
                                                    <tr>
                                                        <td>{{ $v->name ?? null }}</td>
                                                        <td>{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $v->cho,$v->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example10" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Grasas</th>
                                                        <th>Cant.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($lipids as $l)
                                                    <tr>
                                                        <td>{{ $l->name ?? null }}</td>
                                                        <td>{{ round(regla_tres_lip_80($diagnosis->isocaloric_lipido, $l->lipid,$l->gr),0) }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                        <div class="col-3">
                                            <table id="example11" class="table table-striped table-bordered" style="width:100%;font-size:12px;">
                                                <thead>
                                                    <tr>
                                                        <th>Proteínas</th>
                                                        <th>Grs.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($proteins as $p)
                                                    @if($p->cho > 0)
                                                    <tr>
                                                        <td>{{ $p->name ?? null }}</td>
                                                        <td>{{ round(regla_tres_prot($p->id,$diagnosis->isocaloric_protein,$p->protein,$p->gr),0) }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                                </tbody>   
                                            </table>
                                        </div>
                                    </div>

</body>
</html>
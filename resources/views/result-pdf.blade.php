<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Resultado</title>

<style type="text/css">
.cajas{
    width: 100%;
}
.caja1{
    display: inline-block;
    height: 100px;
    width: 100%;
    border-bottom: 1px solid #3989c6;
    margin-bottom: 5px;
}

.company-details .name {
    margin-top: 0;
    margin-bottom: 0;
}

.caja2{
    background: #fff;
    height: 100px;
    width: 100%;
}

.invoice-details{
    position: relative;
    float: right;
    display: block;
    margin-left: 100px;
    width: 250px;
    
}

.caja3{
    height: 100px;
    width: 100%;
    margin-bottom: 5px;
    margin-left: 10px;
}

.unit {
    background: #f5f5f5;
    border: 1px solid black;
}

.total {
    background: #cae6fc;
    color: #000000
}


.tfoot-td {
    background: 0 0;
    border-bottom: none;
    white-space: nowrap;
    text-align: right;

    font-size: 14px;
    border-top: 1px solid #aaa
}

 .tfoot-first-child{
    border-top: none;
    border-bottom: none;
    white-space: nowrap;
    text-align: right;
    color: #fff;
    background-color:#aaa;
    font-size: 14px;
}

 .tfoot-last-child{
    color: #000000;
    background-color:#ebebeb;
    border-bottom: none;
    white-space: nowrap;
    text-align: right;
    font-size: 14px;
    border-top: 1px solid #3989c6
}


.contenido {
  font-family: arial, sans-serif;
  font-size: 14px;
  border-collapse: collapse;
  width: 100%;
}

.contenido  td,.contenido  th {
  border: 1px solid #666666;
  text-align: left;
  padding: 8px;
  font-size: 10px;
}
.text-description{
    width: 250px;
}

.text-customer{
    width: 150px;
    text-align: center;
    font-size: 14px;
}


</style>

</head>
<body>

  <table width="100%">
    <tr>
        <td valign="top"><div style="margin-top:-70px;margin-left:200px;text-align: center"><h4><b>RESULTADOS</b></h4></div></td>
        <td align="right" style="font-size: 14px">
            <p class="name">DATOS DEL PACIENTE:</p>
        </td>
    </tr>
    <tr>
        <td align="right" style="font-size: 14px"><p>NOMBRE COMPLETO:</p></td>
        <td align="right" style="font-size: 14px"><b>{{ $patient->first_name ?? '' }} {{ $patient->last_name ?? '' }}</b></td>
    </tr>
    <tr>
        <td align="right" style="font-size: 14px"><p>RUT:</p></td>
        <td align="right" style="font-size: 14px"><b>{{ $patient->rut ?? '' }}</b></td>
    </tr>
    <tr>
        <td align="right" style="font-size: 14px"><p>Dirección:</p></td>
        <td align="right" style="font-size: 14px"><b>{{ $patient->address ?? '' }}</b></td>
    </tr>
  </table>
  <hr style="color: #3989c6;">
  <div>
        <div class="col-12">
            <h4 class="m-3 p-3">Desayuno</h4><br>
        </div>
  </div>

  <table class="contenido" border="0"  width="100%">
    <thead style="font-size:14px; background: #f3f3f3;color: rgb(0, 0, 0);">
        <tr>
            <th class="text-right">Carbohidratos</th>
            <th class="text-right">Grs.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cereales as $c)
            @if($c->cho != 0 && $c->cho > 1)
            <tr>
                <td style="width: 20px;">{{ $c->name ?? null }}</td>
                <td style="width: 20px;">{{ round(regla_tres($diagnosis->isocaloric_carbohydrate, $c->cho,$c->gr,$c->id),0) }}</td>
            </tr>
            @endif
        @endforeach
    </tbody>

  </table>
  <hr style="color: #3989c6;">
  <table class="contenido" border="0"  width="100%">
    <thead style="font-size:14px; background: #f3f3f3;color: rgb(0, 0, 0);">
        <tr>
            <th>Proteínas</th>
            <th>Grs.</th>
        </tr>
    </thead>
    <tbody>
            @foreach($proteins as $l)
            @if($l->cho > 0)
            <tr>
                <td style="width: 20px;">{{ $l->name ?? null }}</td>
                <td style="width: 20px;">{{ regla_tres_prot($l->id,$diagnosis->isocaloric_protein, $l->protein,$l->gr) }}</td>
            </tr>
            @endif
        @endforeach
    </tbody>

  </table>
  <hr style="color: #3989c6;">
  <table class="contenido" border="0"  width="100%">
    <thead style="font-size:14px; background: #f3f3f3;color: rgb(0, 0, 0);">
        <tr>
            <th>Acompañamientos</th>
            <th>Grs.</th>
        </tr>
    </thead>
    <tbody>
            @foreach($lacteos as $l)
            @if($l->cho != 0 && $l->cho > 1)
            <tr>
                <td style="width: 20px;">{{ $l->name ?? null }}</td>
                <td style="width: 20px;">{{ regla_tres_lac($diagnosis->isocaloric_carbohydrate,$l->cho,$c->gr,$c->id) }}</td>
            </tr>
            @endif
        @endforeach
    </tbody>

  </table>
  <hr style="color: #3989c6;">
  
</body>
</html>
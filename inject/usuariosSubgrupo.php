
<?php 
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$server = "localhost";
$user = "root";
$pass = "";
$bd = "simexamerica";
$idSubGrupo = $_GET['idSubGrupo'];//0;
//Creamos la conexión
$conexion = mysqli_connect($server, $user, $pass,$bd) 
or die("Ha sucedido un error inexperado en la conexion de la base de datos");


//generamos la consulta
$sql = "
SELECT 
`id_users`, 
`img_user`, 
`fecha`, 
`nombres`, 
`apellidos`, 
`email`, 
`telefono`, 
`pais`, 
`escenario`, 
`estado`, 
`horario`
FROM 
`users` 
WHERE 
`subgrupo`=$idSubGrupo
";

mysqli_set_charset($conexion, "utf8");

//mysqli_set_charset($conexion, "utf8"); //formato de datos utf8


if(!$result = mysqli_query($conexion,$sql)){
	echo("Error description: " . mysqli_error($conexion));
	echo "hay un error en la base de datos";
	die();
}


$msgprogramado = [];


while($row = mysqli_fetch_array($result)) 
{ 
    $id=$row['id_users'];
    $img_user=$row['img_user'];
    $nombre=$row['nombres'].' '.$row['apellidos'];
	$email=$row['email'];
	$telefono=$row['telefono'];
	$pais=$row['pais'];
	$escenario=$row['escenario'];
	$estado=$row['estado'];
	$horario=$row['horario'];

    
    
	array_push($msgprogramado,
		[
			"nombre"=> $nombre,
			"img_user"=> $img_user, 
			"email"=> $email,
			"telefono"=> $telefono,
			"pais"=> $pais,
			"escenario"=> $escenario,
			"estado"=> $estado,
			"horario"=> $horario,
			"id"=> intval($id)

		]
	);
}

    
//desconectamos la base de datos
$close = mysqli_close($conexion) 
or die("Ha sucedido un error inexperado en la desconexion de la base de datos");
  
//var_dump($msgprogramado);
//Creamos el JSON
$json_string = json_encode($msgprogramado, JSON_UNESCAPED_UNICODE );

echo $json_string;

//Si queremos crear un archivo json, sería de esta forma:
/*
$file = 'clientes.json';
file_put_contents($file, $json_string);
*/
    

?>
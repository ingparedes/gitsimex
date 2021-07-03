<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$server = "localhost";
$user = "root";
$pass = "";
$bd = "simexamerica";

$idMensaje = $_GET['idMensaje'];
//Creamos la conexión
$conexion = mysqli_connect($server, $user, $pass,$bd) 
or die("Ha sucedido un error inexperado en la conexion de la base de datos");


//generamos la consulta
$sql = "
SELECT
	users.nombres,
    users.apellidos,
    resmensaje.id_resmensaje,
    resmensaje.resmensaje,
	mensajes.id_inyect
FROM
	resmensaje
	INNER JOIN
	users
	ON 
		resmensaje.id_users = users.id_users
	INNER JOIN
	mensajes
	ON 
		resmensaje.id_inyect = mensajes.id_inyect
WHERE
	mensajes.id_inyect = $idMensaje
";

mysqli_set_charset($conexion, "utf8");

//mysqli_set_charset($conexion, "utf8"); //formato de datos utf8


if(!$result = mysqli_query($conexion,$sql)){
	echo("Error description: " . mysqli_error($conexion));
	die();
}

$clientes = array(); //creamos un array

$msgprogramado = [];


while($row = mysqli_fetch_array($result)) 
{ 
    $nombre=$row['nombres']." ".$row['nombres'];
    $id=$row['id_resmensaje'];
    $mensaje=$row['resmensaje'];
	$id_mensaje=$row['id_inyect'];
    
	array_push($msgprogramado,
		[
			"id"=> intval($id),
			"nombre"=> $nombre, 
			"mensaje"=> $mensaje,
			"idMensaje"=>intval($id_mensaje)
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
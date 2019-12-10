<?php
include "usefulFunctions.php";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sindmusi";

echo "<center> <a href='index.php'><b> Voltar à página de cadastro de registros </b></a> - 
	  <a href='atualiza_base_de_registros.php'><b> Atualizar Registros </b></a> </center>";

try{
	$conn = new PDO( "mysql:host=$servername; dbname=$dbname", $username, $password );
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	if( isset( $_GET["reg"] ) ){
		if( trim( $_GET["reg"] ) != "" ){
			$id = $_GET["reg"];
			
			$getRegistro = $conn->prepare( "select tipo, titulo 
											from registro where( id = ? )" );
			$getRegistro->execute( array( $id ) );
			
			$registro = $getRegistro->fetch(PDO::FETCH_ASSOC);
			
			if( $registro ){
				$getMidias = $conn->prepare( "select distinct m.id, m.nome
										      from registro r inner join midia m on( m.registroid = ? )" );
				$getMidias->execute( array( $id ) );
				$midias = $getMidias->fetchAll(PDO::FETCH_ASSOC);
				
				$midiasApagadas = 0;
				foreach( $midias as $m ){
					$extension = explode( ".", $m["nome"] );
					$extension = $extension[ count($extension) - 1 ];
					
					$midiaPath = "midia/".$registro["tipo"]."/".$m["id"].".".$extension;
					
					if( unlink( $midiaPath ) ){
						$midiasApagadas++;
					}
					else{
						echo "<br> Problemas ao apagar a mídia '$midiaPath'";
					}
				}
				
				$deletaMidias = $conn->prepare( "delete from midia where( registroid = ? )" );
				$deletaMidias->execute( array( $id ) );
				
				$deletaRegistro = $conn->prepare( "delete from registro where( id = ? )" );
				$deletaRegistro->execute( array( $id ) );
				
				echo "<br><br> Registro '".$registro["tipo"]." - ".$registro["titulo"]."' deletado com sucesso";
				echo "<br> $midiasApagadas/".count( $midias )." mídias apagadas com sucesso <br>";
			}
			else{
				echo "<br><br> Este registro não existe";
			}
		}
		else{
			echo "<br><br> Registro não especificado";
		}
	}
}
catch(PDOException $e){
	echo "<br> Error: " . $e->getMessage();
}
$conn = null;

?>

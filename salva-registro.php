<?php
include "usefulFunctions.php";

echo "Hello SindMusi!";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sindmusi";

$max_filename_size = 203; // Tamanho máximo de nome de arquivo no Windows

try{
    $conn = new PDO( "mysql:host=$servername;dbname=$dbname", $username, $password );
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	$tipo = $_POST["tipoReg"];
	$arquivos = $_FILES["arquivos"];
	$titulo = trim( $_POST["tituloReg"] );
	$origem = $_POST["origemReg"];
	$dataReg = $_POST["dataReg"];
	$horaReg = trim( $_POST["horaReg"] );
	$relevancia = $_POST["relevanciaReg"];
	$descricao = trim( $_POST["descricaoReg"] );
	
	if( set_and_nonEmpty([ $tipo, $titulo, $origem, $descricao ]) && isset( $arquivos ) ){
		
		// Isso ("prepared statements") previne sql injections
		$stmt = $conn->prepare( "insert into registro( tipo, titulo, origem, datareg, horareg, relevancia, descricao )
											 values  ( ?, ?, ?, ?, ? ,? ,? )" );
		$stmt->execute( array( $tipo, $titulo, $origem, $dataReg, $horaReg, $relevancia, $descricao ) );
		$id_registro = $conn->lastInsertId();
		
		// Upload e inserção de mídias
		$stmt = $conn->prepare( "insert into midia( registroid, nome ) values( ?, ? )" );
		$n = count( $arquivos["name"] );
		$problemas = 0;
		for( $i=0; $i < $n; $i++ ){
			$stmt->execute( array( $id_registro, $arquivos["name"][$i] ) );
			$id_midia = $conn->lastInsertId();
			
			$extensao = explode( ".", $arquivos["name"][$i] );
			$extensao = end( $extensao );
			if( !move_uploaded_file( $arquivos["tmp_name"][$i], "midia/".$tipo."/".$id_midia.".".$extensao ) ){
				// Impeço que arquivos não salvos fiquem registrados na base de dados de midia
				$conn->query( "delete from midia where( id='$id_midia' )" );
				
				echo "<br>Problemas ao enviar o arquivo ".$arquivos["name"][$i];
				$problemas++;
			}
		}
		echo "<br><br>".($n-$problemas)."/".$n." arquivos enviados com sucesso.";
		echo "<br> Registro salvo com sucesso";
	}
	else{
		echo "<br> Preencha as informações essenciais.";
	}
	echo "<br><br><a href='index.php'><b> Voltar à página de cadastro de registros </b></a>";
}
catch(PDOException $e){
	echo "<br> Error: " . $e->getMessage();
}
$conn = null;

?>
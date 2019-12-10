<?php
include "usefulFunctions.php";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sindmusi";

$max_filename_size = 203; // Tamanho máximo de nome de arquivo no Windows (acabou nem sendo útil)


$resultado = "<center> <a href='index.php'><b> Voltar à página de cadastro de registros </b></a> - 
			  <a href='atualiza_base_de_registros.php'><b> Atualizar Registros </b></a> </center>";

if( isset( $_POST["idReg"] ) && trim( $_POST["idReg"] ) != "" ){
	try{
		$conn = new PDO( "mysql:host=$servername; dbname=$dbname", $username, $password );
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$regId = $_POST["idReg"];
		
		$resultado .= "<br><a href='edita-registro.php?reg=$regId'><b> <<< Voltar ao Registro </b></a>";
		
		$getRegistro = $conn->prepare( "select * 
										from registro where( id = ? )" );
		$getRegistro->execute( array( $regId ) );
		
		$registro = $getRegistro->fetch(PDO::FETCH_ASSOC);
		
		if( $registro ){// Se registro existe
			
			$updatesRegSql = [];
			$updatesRegValue = [];
			
			$novoTipoReg = "";
			if( isset( $_POST["tipoReg"] ) && trim( $_POST["tipoReg"] ) != "" ){
				$updatesRegSql[] = "tipo = ?";
				$updatesRegValue[] = trim( $_POST["tipoReg"] );
				$novoTipoReg = trim( $_POST["tipoReg"] );
			}
			if( isset( $_POST["tituloReg"] ) && trim( $_POST["tituloReg"] ) != "" ){
				$updatesRegSql[] = "titulo = ?";
				$updatesRegValue[] = trim( $_POST["tituloReg"] );
			}
			if( isset( $_POST["origemReg"] ) && trim( $_POST["origemReg"] ) != "" ){
				$updatesRegSql[] = "origem = ?";
				$updatesRegValue[] = trim( $_POST["origemReg"] );
			}
			if( isset( $_POST["dataReg"] ) ){
				$updatesRegSql[] = "datareg = ?";
				$updatesRegValue[] = trim( $_POST["dataReg"] );
			}
			if( isset( $_POST["horaReg"] ) ){
				$updatesRegSql[] = "horareg = ?";
				$updatesRegValue[] = trim( $_POST["horaReg"] );
			}
			if( isset( $_POST["relevanciaReg"] ) && trim( $_POST["relevanciaReg"] ) != "" ){
				$updatesRegSql[] = "relevancia = ?";
				$updatesRegValue[] = trim( $_POST["relevanciaReg"] );
			}
			if( isset( $_POST["descricaoReg"] ) && trim( $_POST["descricaoReg"] ) != "" ){
				$updatesRegSql[] = "descricao = ?";
				$updatesRegValue[] = trim( $_POST["descricaoReg"] );
			}
			$novasMidias = ( isset( $_FILES["arquivos"] ) && $_FILES["arquivos"]["name"][0] != "" );
			
			// Se algum campo foi preenchido
			if( count( $updatesRegSql ) > 0 || $novasMidias ){
				
				if( count( $updatesRegSql ) > 0 ){
					
					$stringUpdatesReg = "";
					$first = true;
					foreach( $updatesRegSql as $sql ){
						if( $first ){
							$stringUpdatesReg .= $sql;
							$first = false;
						}
						else{
							$stringUpdatesReg .= ", ".$sql;
						}
					}
					
					if( $novoTipoReg != "" && !$novasMidias ){
						
						$getMidias = $conn->prepare( "select distinct m.id, m.nome
													  from registro r inner join midia m on( m.registroid = ? )" );
						$getMidias->execute( array( $regId ) );
						$midias = $getMidias->fetchAll(PDO::FETCH_ASSOC);
						
						$midiasMovidas = 0;
						foreach( $midias as $m ){
							$extension = explode( ".", $m["nome"] );
							$extension = end( $extension );
							
							$OldMidiaPath = "midia/".$registro["tipo"]."/".$m["id"].".".$extension;
							$NewMidiaPath = "midia/".$novoTipoReg."/".$m["id"].".".$extension;
							
							if( rename( $OldMidiaPath, $NewMidiaPath ) ){
								$midiasMovidas++;
							}
						}
					}
					
					$updateRegistro = $conn->prepare( "update registro
													   set $stringUpdatesReg
													   where( id='$regId' )" );
					$updateRegistro->execute( $updatesRegValue );
					if( $updateRegistro->rowCount() == 1 ){
						$resultado .= "<br><br> Registro atualizado com sucesso <br>";
					}
					else{
						$resultado .= "<br><br> Nenhuma mudança efetuada nos campos do registro <br>";
					}
				}
				
				// Se alguma nova mídia foi inserida
				if( $novasMidias ){
					
					// Apaga mídias antigas
					$getMidias = $conn->prepare( "select distinct m.id, m.nome
												  from registro r inner join midia m on( m.registroid = ? )" );
					$getMidias->execute( array( $regId ) );
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
							$resultado .= "<br> Problemas ao apagar o arquivo '$midiaPath'";
						}
					}
					
					$deletaMidias = $conn->prepare( "delete from midia where( registroid = ? )" );
					$deletaMidias->execute( array( $regId ) );
					
					$resultado .= "<br> $midiasApagadas/".count( $midias )." mídias antigas apagadas com sucesso <br>";
					
					
					// Upload e inserção de novas mídias
					$arquivos = $_FILES["arquivos"];
					$insereNovaMidia = $conn->prepare( "insert into midia( registroid, nome ) values( ?, ? )" );
					
					$n = count( $arquivos["name"] );
					$midiasEnviadas = 0;
					for( $i=0; $i < $n; $i++ ){
						$insereNovaMidia->execute( array( $regId, $arquivos["name"][$i] ) );
						$id_midia = $conn->lastInsertId();
						
						$extensao = explode( ".", $arquivos["name"][$i] );
						$extensao = end( $extensao );
						if( $novoTipoReg != "" ){// Se novo Tipo foi definido
							$destinationPath = "midia/".$novoTipoReg."/".$id_midia.".".$extensao;
						}
						else{
							$destinationPath = "midia/".$registro["tipo"]."/".$id_midia.".".$extensao;
						}
						
						if( !move_uploaded_file( $arquivos["tmp_name"][$i], $destinationPath ) ){
							// Impeço que arquivos não salvos fiquem registrados na base de dados de midia
							$conn->query( "delete from midia where( id='$id_midia' )" );
							
							echo "<br> Problemas ao enviar o arquivo ".$arquivos["name"][$i];
						}
						else{
							$midiasEnviadas++;
						}
					}
					$resultado .= "<br>".$midiasEnviadas."/".$n." novas mídias enviadas com sucesso";
				}
			}
		}
		else{
			$resultado .= "<br><br> Este registro não existe";
		}
	}
	catch(PDOException $e){
		echo "<br> Error: " . $e->getMessage();
	}
	$conn = null;
	
}
else{
	$resultado .= "<br><br> Registro não especificado, não foi possível editar o mesmo";
}

echo $resultado;

?>
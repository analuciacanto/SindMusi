<?php
include "usefulFunctions.php";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sindmusi";

$resultado = "";

// Acesso aos .json contendo os dados de opções dos <select>
$tipos_json = file_get_contents_without_BOM( "tipos-de-registros.json" );
$tipos = json_decode( $tipos_json, true );

$tipos_de_registros = "";
foreach( $tipos as $t ){
	$tipos_de_registros .= "<option>".$t."</option>";
}
	
$origens = file_get_contents_without_BOM( "origem-dos-registros.json" );
$origens = json_decode( $origens, true );

$origem_dos_registros = "";
foreach( $origens as $o ){
	$origem_dos_registros .= "<option>".$o."</option>";
}

if( isset( $_POST["tipoReg"], $_POST["origemReg"], $_POST["conteudoReg"] ) ){
	
	$tipoReg = $_POST["tipoReg"];
	$origemReg = $_POST["origemReg"];
	$conteudoReg = $_POST["conteudoReg"];
	$visualizacaoSimples = isset( $_POST["visuSimples"] )?$_POST["visuSimples"]:"off"; //checkbox
	
	try{
		$conn = new PDO( "mysql:host=$servername; dbname=$dbname", $username, $password );
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$getRegistros = $conn->prepare( "select *
										 from registro
										 where( tipo like ? and origem like ? and 
												(titulo like ? or descricao like ? ) )
										 order by tipo" );
		$getMidias = $conn->prepare( "select distinct m.*
									  from registro r inner join midia m on( m.registroid = ? )" );
		
		$getRegistros->execute( array( "%$tipoReg%", "%$origemReg%", "%$conteudoReg%", "%$conteudoReg%" ) );
		
		$registros = $getRegistros->fetchAll(PDO::FETCH_ASSOC);
		
		$numero_de_resultados = count( $registros );
		if( $numero_de_resultados != 0 ){
			
			$resultado .= $numero_de_resultados." registro(s) encontrado(s) <br>";
			
			$indiceRegistro = 1;
			foreach( $registros as $reg ){
				$getMidias->execute( array( $reg["id"] ) );
				$midias = $getMidias->fetchAll(PDO::FETCH_ASSOC);
				
				$midiasDiv = "<div class='midiasDiv'>";
				foreach( $midias as $m ){
					$extension = explode( ".", $m["nome"] );
					$extension = $extension[ count($extension) - 1 ];
					
					$midiaPath = "midia/".$reg["tipo"]."/".$m["id"].".".$extension;
					$mimeType = mime_content_type( $midiaPath );
					
					if( $mimeType != false ){
						$fileType = explode( "/", $mimeType );
						$fileType = $fileType[0];
						
						if( $fileType == "image" ){
							$midiasDiv .= "<img src='$midiaPath' width='200' height='200' alt='".$m["nome"]."' >";
						}
						else if( $fileType == "video" ){
							$midiasDiv .= "<video width='300' height='200' controls>
											  <source src='$midiaPath' type='$mimeType'>
										   </video>";
						}
						else if( $fileType == "audio" ){
							$midiasDiv .= "<audio controls>
											  <source src='$midiaPath' type='$mimeType'>
										   </audio>";
						}
						else if( $fileType == "application" ){
							$midiasDiv .= 
							"<a href='$midiaPath' class='applicLink' type='$mimeType' target='_blank' >".$m["nome"]."</a>";
						}
					}
				}
				$midiasDiv .= "</div>";
				
				$dataReg = "Indeterminada";
				if( $reg["datareg"] != "0000-00-00" ){
					$dataReg = date_format( date_create( $reg["datareg"] ), "d/m/Y" );
					if( $reg["horareg"] != "" ){
						$dataReg .= " - ".$reg["horareg"];
					}
				}
				$descricaoReg = "<textarea rows='5' cols='100'>".$reg['descricao']."</textarea>";
				
				$relevancia = "Indeterminada";
				switch( $reg["relevancia"] ){
					case 1:
						$relevancia = "1 - Muito pequena";
						break;
					case 2:
						$relevancia = "2 - Pequena";
						break;
					case 3:
						$relevancia = "3 - Média/Normal";
						break;
					case 4:
						$relevancia = "4 - Significativa";
						break;
					case 5:
						$relevancia = "5 - Muito Significativa";
						break;
				}
				
				$confirmInfo = $reg["id"].', "'.$reg["titulo"].'"';
				
				if( $visualizacaoSimples == "on" ){
					$resultado .= "<div class='regDiv'><div class='indiceRegistro'>".$indiceRegistro."</div>"
									  .$reg["tipo"]." - ".$reg["titulo"]."<br><br>"
									  ."Origem: ".$reg["origem"]." , Data: ".$dataReg."<br><br>"
									  ."<span class='deletaRegistro' onclick='confirmDeletion( ".$confirmInfo." )'>"
											."Deletar </span></a>"
									  ."<a href='edita-registro.php?reg=".$reg["id"]."'>"
											."<span class='editaRegistro'> Editar </span></a>"
									  ."<br><br>"
								   ."</div>";
				}
				else{
					$resultado .= "<div class='regDiv'><div class='indiceRegistro'>".$indiceRegistro."</div>"
									  .$reg["tipo"]." - ".$reg["titulo"]."<br><br>"
									  ."<center> $midiasDiv </center>"
									  ."Origem: ".$reg["origem"]." , Data: ".$dataReg."<br><br>"
									  ."Relevância: ".$relevancia."<br><br>"
									  ."Descrição <br>".$descricaoReg."<br><br>"
									  ."<span class='deletaRegistro' onclick='confirmDeletion( ".$confirmInfo." )'>"
											."Deletar </span></a>"
									  ."<a href='edita-registro.php?reg=".$reg["id"]."'>"
											."<span class='editaRegistro'> Editar </span></a>"
									  ."<br><br>"
								   ."</div>";
				}
				$indiceRegistro++;
			}
			$resultado .= "<br>";
		}
		else{
			$resultado = "<br> Nenhum resultado encontrado para esta pesquisa";
		}
	}
	catch(PDOException $e){
		echo "<br> Error: " . $e->getMessage();
	}
	$conn = null;
}

?>
<html>
<head>
	<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">          
	
	<!-- jQuery 
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js" type="text/javascript"></script>
	-->
	
	<!-- CSS -->
	<link  href="atualiza-registro.css" rel="stylesheet" type="text/css">
	
	<script>
		
		function closeConfirmation(){
			document.getElementById("backgroundConfirmDeletion").remove();
			document.body.style.overflow = "auto";
		}
		
		function confirmDeletion( regId, regTitulo ){
			var fundo = document.createElement("div");
			fundo.id = "backgroundConfirmDeletion";
			
			var confirmDiv = document.createElement("div");
			confirmDiv.id = "confirmDeletion";
			confirmDiv.innerHTML = "<br>Tem certeza que deseja apagar o registro '"+regTitulo+"'?";
			confirmDiv.innerHTML += "<a href='deleta-registro.php?reg="+regId+"'><span id='confirm'> SIM </span></a>";
			confirmDiv.innerHTML += "<span id='cancel' onclick='closeConfirmation()'> NÃO </span>";
			
			fundo.appendChild( confirmDiv );
			document.body.appendChild( fundo );
			document.body.style.overflow = "hidden";
		}
		
	</script>
</head>
<body>
	<center>
		<a href='index.php'><b> Voltar à página de cadastro de registros </b></a>
		<h3>Pesquisa Registro</h3>
		<form method="post" action = "atualiza_base_de_registros.php" >
			Tipo: 
			<select id='tipoReg' name='tipoReg'>
				<option></option>
				<?php echo $tipos_de_registros; ?>
			</select>
			Origem: 
			<select name='origemReg'>
				<option></option>
				<?php echo $origem_dos_registros; ?>
			</select>
			<br>
			<input id="searchText" type="text" name='conteudoReg' placeholder="Parte do Título ou Descrição">
			<input type="checkbox" id="visuSimples" name="visuSimples" value="on" >
			<label style="cursor: pointer;" for="visuSimples"> Visualização Simples </label>
			
			<input type='submit' value='Pesquisar'>
		</form>
		<?php echo $resultado; ?>
	</center>
 </body>
</html>
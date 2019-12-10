<?php
include "usefulFunctions.php";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sindmusi";

$conteudo = "<center> <a href='index.php'><b> Voltar à página de cadastro de registros </b></a> - 
	  <a href='atualiza_base_de_registros.php'><b> Atualizar Registros </b></a> </center>";

try{
	$conn = new PDO( "mysql:host=$servername; dbname=$dbname", $username, $password );
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	if( isset( $_GET["reg"] ) ){
		if( trim( $_GET["reg"] ) != "" ){
			$regId = $_GET["reg"];
			
			$getRegistro = $conn->prepare( "select * 
											from registro where( id = ? )" );
			$getRegistro->execute( array( $regId ) );
			
			$registro = $getRegistro->fetch(PDO::FETCH_ASSOC);
			
			if( $registro ){
				$getMidias = $conn->prepare( "select distinct m.*
										  from registro r inner join midia m on( m.registroid = ? )" );
				$getMidias->execute( array( $regId ) );
				$midias = $getMidias->fetchAll(PDO::FETCH_ASSOC);
				
				$midiasDiv = "<div class='midiasDiv'>";
				foreach( $midias as $m ){
					$extension = explode( ".", $m["nome"] );
					$extension = $extension[ count($extension) - 1 ];
					
					$midiaPath = "midia/".$registro["tipo"]."/".$m["id"].".".$extension;
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
				
				$tipoReg = $registro["tipo"];
				$tituloReg = $registro["titulo"];
				$origemReg = $registro["origem"];
				$descricaoReg = $registro["descricao"];
				
				$dataReg = "Indeterminada";
				if( $registro["datareg"] != "0000-00-00" ){
					$dataReg = date_format( date_create( $registro["datareg"] ), "d/m/Y" );
					if( $registro["horareg"] != "" ){
						$dataReg .= " - ".$registro["horareg"];
					}
				}
				$relevanciaReg = "Indeterminada";
				switch( $registro["relevancia"] ){
					case 1:
						$relevanciaReg = "1 - Muito pequena";
						break;
					case 2:
						$relevanciaReg = "2 - Pequena";
						break;
					case 3:
						$relevanciaReg = "3 - Média/Normal";
						break;
					case 4:
						$relevanciaReg = "4 - Significativa";
						break;
					case 5:
						$relevanciaReg = "5 - Muito Significativa";
						break;
				}
				
				
				// Acesso aos .json contendo os dados de opções dos <select>
				$tipos_json = file_get_contents_without_BOM( "tipos-de-registros.json" );
				$tipos = json_decode( $tipos_json, true );
				
				if( !is_dir( "midia" ) ){//Se ainda não existe a pasta de mídias, crio
					mkdir( "midia" );
				}
				
				$tipos_de_registros = "";
				foreach( $tipos as $t ){
					$tipos_de_registros .= "<option>".$t."</option>";
					
					if( !is_dir( "midia/$t" ) ){
						//Garanto que para cada tipo haverá uma pasta de arquivos de mídia
						mkdir( "midia/$t" );
					}
				}
				
				$origens = file_get_contents_without_BOM( "origem-dos-registros.json" );
				$origens = json_decode( $origens, true );
				
				$origem_dos_registros = "";
				foreach( $origens as $o ){
					$origem_dos_registros .= "<option>".$o."</option>";
				}
				
				$conteudo .= "<center>
								 <h3>Edita Registro</h3>
								 <form action='salva-registro-editado.php' enctype='multipart/form-data' method='post'>
									<input type='hidden' name='idReg' value='$regId'>
									
									<div class='oldInfo'> Tipo atual: $tipoReg </div>
									Novo Tipo:
										<select id='tipo' name='tipoReg' >
											<option></option>
											$tipos_de_registros
										</select>
										
									<div class='oldInfo'> Mídias atuais </div>
									$midiasDiv <br>
									Selecione as Novas Mídias:
										<input id='midias' onchange='preview( this )' type='file' name='arquivos[]' multiple>
									<br><br>
									<div id='lista_de_midias'></div>
									<div id='limparMidias' onclick='limparMidias()'>Limpar Mídias</div><br>
									
									<div class='oldInfo'> Título atual: $tituloReg </div>
									Novo Título:
										<input id='titulo' type='text' name='tituloReg' ><br><br>
										
									<div class='oldInfo'> Origem atual: $origemReg </div>
									Nova Origem:
										<select name='origemReg' >
											<option></option>
											$origem_dos_registros
										</select> <br><br>
										
									<div class='oldInfo'> Data atual: $dataReg </div>
									Nova Data:
										<input type='date' name='dataReg' value='".$registro["datareg"]."' >
										<input type='time' name='horaReg' value='".$registro["horareg"]."' >
										<br><br>
										
									<div class='oldInfo'> Relevância atual: $relevanciaReg </div>
									Nova Relevância:
										<select name='relevanciaReg' >
											<option></option>
											<option value='1'> 1 - Muito pequena </option>
											<option value='2'> 2 - Pequena </option>
											<option value='3'> 3 - Média/Normal </option>
											<option value='4'> 4 - Significativa </option>
											<option value='5'> 5 - Muito Significativa </option>
										</select>
									<br><br>
									
									Nova Descrição (edite para atualizar)<br>
										<textarea rows='12' cols='100' name='descricaoReg' >$descricaoReg</textarea>
									<br><br>
									
									<input type='submit' value='Salvar'>
								 </form>
							 </center>";
					 
			}
			else{
				$conteudo .= "<br><br> Este registro não existe";
			}
		}
		else{
			$conteudo .= "<br><br> Registro não especificado";
		}
	}
}
catch(PDOException $e){
	echo "<br> Error: " . $e->getMessage();
}
$conn = null;

?>
<html>
<head>
	<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">          
	
	<!-- jQuery 
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js" type="text/javascript"></script>
	-->
	
	<!-- CSS -->
	<link  href="cria-registro.css" rel="stylesheet" type="text/css">
	
	<script>
		
		function limparMidias(){
			document.getElementById("midias").value = "";
			document.getElementById("lista_de_midias").innerHTML = "";
		}
		
		function cria_preview( f, posicao, list ){// Teve que ser recursiva porque usando for() não deu certo
			if( posicao < f.length ){
				var mime = f[ posicao ].type;
				var tipoArquivo = mime.split('/')[0];
				
				var reader = new FileReader();
				reader.onload = function(){
					var src = reader.result;
					
					if( tipoArquivo == "image" ){
						var element = document.createElement("img");
						element.src = src;
						
						element.width = 350;
						element.height = 350;
						list.appendChild(element);
					}
					else if( tipoArquivo == "video" ){
						list.innerHTML += "<video controls> <source src='"+src+"' type='"+mime+"'> </video>";
					}
					else if( tipoArquivo == "audio" ){
						list.innerHTML += "<audio controls> <source src='"+src+"' type='"+mime+"'> </audio>";
					}
					else if( tipoArquivo == "application" ){
						list.innerHTML += 
						"<object width='500' height='400' data='"+src+"' type='"+mime+"'></object>";
					}
				};
				reader.readAsDataURL( f[ posicao ] );
				
				posicao+=1;
				cria_preview( f, posicao, list );
			}
		}
		function preview( input ){
			if( input ){
				document.getElementById("lista_de_midias").innerHTML = "";// Limpa a div de preview
				var previewList = document.getElementById("lista_de_midias");
				cria_preview( input.files, 0, previewList );
			}
		}
	</script>
</head>
<body>
	<?php echo $conteudo; ?>
 </body>
</html>


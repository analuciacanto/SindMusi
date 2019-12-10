<?php
include "usefulFunctions.php";

// Navigation Bar
echo "Hello SindMusi!
	  <br><br>
	  <a href='atualiza_tipos_origem.php'><b> Atualizar lista de Tipos/Origens dos Registros </b></a>  
	  -  
	  <a href='atualiza_base_de_registros.php'><b> Atualizar Registros </b></a>";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sindmusi";

$max_filename_size = 203; // Tamanho máximo de nome de arquivo no Windows

try{
	$conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	$resultado = $conn->query( "select count(r.id) as numero_de_registros
								from registro r " );
	$numero_de_registros = $resultado->fetch();
	$numero_de_registros = $numero_de_registros["numero_de_registros"];
	
	$resultado = $conn->query( "select count(m.id) as numero_de_midias
								from midia m" );
	$numero_de_midias = $resultado->fetch();
	$numero_de_midias = $numero_de_midias["numero_de_midias"];
	
	$midias_por_registro = ($numero_de_registros > 0)?(number_format( $numero_de_midias/$numero_de_registros, 2 )):"0";
	
	echo "<br><br> Registros inclusos: ".$numero_de_registros;
	echo "<br>     Mídias inclusas:    ".$numero_de_midias;
	echo "<br>     Média de mídias/registro: ".$midias_por_registro;
	
	
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
	
	$conteudo = "<center>
					 <h3>Novo Registro</h3>
					 <form action='salva-registro.php' enctype='multipart/form-data' method='post'>
						*(Campos Obrigatórios)
						<br><br>
						*Tipo:
							<select id='tipo' name='tipoReg' required>
								<option></option>
								$tipos_de_registros
							</select> <br><br>
						*Selecione as Mídias:
							<input id='midias' onchange='preview( this )' type='file' name='arquivos[]' multiple required>
						<br><br>
						<div id='lista_de_midias'></div>
						<div id='limparMidias' onclick='limparMidias()'>Limpar Mídias</div><br>
						*Título:
							<input id='titulo' type='text' name='tituloReg' required><br><br>
						*Origem:
							<select name='origemReg' required>
								<option></option>
								$origem_dos_registros
							</select> <br><br>
						Data:
							<input type='date' name='dataReg'>
							<input type='time' name='horaReg'>
							<br><br>
						*Relevância:
							<select name='relevanciaReg' required>
								<option></option>
								<option value='1'> 1 - Muito pequena </option>
								<option value='2'> 2 - Pequena </option>
								<option value='3'> 3 - Média/Normal </option>
								<option value='4'> 4 - Significativa </option>
								<option value='5'> 5 - Muito Significativa </option>
							</select>
						<br><br>
						*Descrição<br>
							<textarea rows='12' cols='100' name='descricaoReg' required></textarea><br>
						 <br>
						 <input type='submit' value='Salvar'>
					 </form>
				 </center>";
	
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
	
		function append( tagId, conteudo ){
			document.getElementById(tagId).innerHTML += conteudo;
		}
		
		function close( element ){
			element.style.display = "none";
		}		
		function limparMidias(){
			document.getElementById("midias").value = "";
			document.getElementById("lista_de_midias").innerHTML = "";
		}
		
		function cria_preview( f, posicao, list ){// Teve que ser recursiva porque usando for() não deu certo
			if( posicao < f.length ){
				var name = f[ posicao ].name;
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

	 <!-- Javascript scripts -->
	 <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
    integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n"
    crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
    integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo"
    crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
    integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6"
    crossorigin="anonymous"></script>

 </body>
</html>
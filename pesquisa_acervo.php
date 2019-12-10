<?php
include "usefulFunctions.php";

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sindmusi";

$resultado = "";
$numero_de_resultados = 0;

// Acesso aos .json contendo os dados de opções dos <select>
$tipos_json = file_get_contents_without_BOM("tipos-de-registros.json");
$tipos = json_decode($tipos_json, true);

$tipos_de_registros = "";
foreach ($tipos as $t) {
	$tipos_de_registros .= "<option>" . $t . "</option>";
}

$origens = file_get_contents_without_BOM("origem-dos-registros.json");
$origens = json_decode($origens, true);

$origem_dos_registros = "";
foreach ($origens as $o) {
	$origem_dos_registros .= "<option>" . $o . "</option>";
}

if (isset($_POST["tipoReg"], $_POST["origemReg"], $_POST["conteudoReg"])) {

	$tipoReg = $_POST["tipoReg"];
	$origemReg = $_POST["origemReg"];
	$conteudoReg = $_POST["conteudoReg"];
	$dataInicio = $_POST["dataInicio"];
	$dataFim = $_POST["dataFim"];

	if ($dataInicio != "" && $dataFim != "" && $dataFim < $dataInicio) {
		$fim = $dataFim;
		$dataFim = $dataInicio;
		$dataInicio = $fim;
	}

	$executeArray = array("%$tipoReg%", "%$origemReg%", "%$conteudoReg%", "%$conteudoReg%");

	try {
		$conn = new PDO("mysql:host=$servername; dbname=$dbname", $username, $password);
		// set the PDO error mode to exception
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$getRegistros = $conn->prepare("select *
										 from registro
										 where( tipo like ? and origem like ? and 
										 ( titulo like ? or descricao like ? ) )
										 order by tipo asc, datareg desc");
		$getMidias = $conn->prepare("select distinct m.*
									  from registro r inner join midia m on( m.registroid = ? )");

		$getRegistros->execute($executeArray);

		$registros = $getRegistros->fetchAll(PDO::FETCH_ASSOC);

		if (count($registros) != 0) {

			foreach ($registros as $reg) {

				$filtraData = ($reg["datareg"] == "0000-00-00") || (($dataInicio == "" || $reg["datareg"] >= $dataInicio) && ($dataFim == "" || $reg["datareg"] <= $dataFim));

				if ($filtraData == true) {

					$numero_de_resultados++;

					$getMidias->execute(array($reg["id"]));
					$midias = $getMidias->fetchAll(PDO::FETCH_ASSOC);

					$midiasDiv = "<div class='midiasDiv'>";
					foreach ($midias as $m) {
						$extension = explode(".", $m["nome"]);
						$extension = $extension[count($extension) - 1];

						$midiaPath = "midia/" . $reg["tipo"] . "/" . $m["id"] . "." . $extension;
						$mimeType = mime_content_type($midiaPath);

						if ($mimeType != false) {
							$fileType = explode("/", $mimeType);
							$fileType = $fileType[0];

							if ($fileType == "image") {
								$midiasDiv .= "<img src='$midiaPath' width='200' height='200' alt='" . $m["nome"] . "' >";
							} else if ($fileType == "video") {
								$midiasDiv .= "<video width='300' height='200' controls>
												  <source src='$midiaPath' type='$mimeType'>
											   </video>";
							} else if ($fileType == "audio") {
								$midiasDiv .= "<audio controls>
												  <source src='$midiaPath' type='$mimeType'>
											   </audio>";
							} else if ($fileType == "application") {
								$midiasDiv .=
									"<a href='$midiaPath' class='applicLink' type='$mimeType' target='_blank' >" . $m["nome"] . "</a>";
							}
						}
					}
					$midiasDiv .= "</div>";

					$dataReg = "Indeterminada";
					if ($reg["datareg"] != "0000-00-00") {
						$dataReg = date_format(date_create($reg["datareg"]), "d/m/Y");
						if ($reg["horareg"] != "") {
							$dataReg .= " - " . $reg["horareg"];
						}
					}
					$descricaoReg = "<textarea rows='5' cols='100' readonly>" . $reg['descricao'] . "</textarea>";

					$relevancia = $reg["relevancia"];

					$confirmInfo = $reg["id"] . ', "' . $reg["titulo"] . '"';

					$resultado .=				
						"<div class='regDiv'>"
						. $reg["tipo"] . " - " . $reg["titulo"] . "<br><br>"
						. "<center> $midiasDiv </center>"
						. "Origem: " . $reg["origem"] . " , Data: " . $dataReg . "<br><br>"
						. "Descrição <br>" . $descricaoReg . "<br><br><br>"
						. "<a href='ver_registro.php?reg=" . $reg["id"] . "'>"
						. "<span class='verMais'> Ver mais </span></a>"
						. "<br><br><br>"
						. "</div>";
				}
			}
			$resultado .= "<br>";
		} else {
			$resultado = "<br> Nenhum resultado encontrado para esta pesquisa";
		}
	} catch (PDOException $e) {
		echo "<br> Error: " . $e->getMessage();
	}
	$conn = null;
}

?>
<html>

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- jQuery 
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.1/jquery.min.js" type="text/javascript"></script>
	-->

	<!-- CSS -->
	<link href="atualiza-registro.css" rel="stylesheet" type="text/css">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
	<link rel="stylesheet" href="acervo.css">
	<title>Linha do tempo</title>

	<script>
		function closeConfirmation() {
			document.getElementById("backgroundConfirmDeletion").remove();
			document.body.style.overflow = "auto";
		}

		function confirmDeletion(regId, regTitulo) {
			var fundo = document.createElement("div");
			fundo.id = "backgroundConfirmDeletion";

			var confirmDiv = document.createElement("div");
			confirmDiv.id = "confirmDeletion";
			confirmDiv.innerHTML = "<br>Tem certeza que deseja apagar o registro '" + regTitulo + "'?";
			confirmDiv.innerHTML += "<a href='deleta-registro.php?reg=" + regId + "'><span id='confirm'> SIM </span></a>";
			confirmDiv.innerHTML += "<span id='cancel' onclick='closeConfirmation()'> NÃO </span>";

			fundo.appendChild(confirmDiv);
			document.body.appendChild(fundo);
			document.body.style.overflow = "hidden";
		}
	</script>
</head>

<body>

	<header>
		<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #003882;">
			<div class="container">
				<div class="navbar-collapse" id="navbarSupportedContent">
					<ul class="navbar-nav ml-auto">
						<li class="nav-item active">
							<a class="nav-link" style="padding-right: 40px;" href="/SindMusi/index.html">Início<span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" style="padding-right: 40px;" href="/SindMusi/pesquisa_acervo.php">Acervo <span class="sr-only">(current)</span></a>
						</li>
						<li class="nav-item active">
							<a class="nav-link" href="#">Entrar<span class="sr-only">(current)</span></a>
						</li>
					</ul>
				</div>
			</div>
		</nav>
		<div class="header-extended">
			<div class="container">
				<div class="row">
					<div class="col-sm-1">
					</div>
					<div class="col-sm-3">
						<img class="logo" src="images/logo.jpg" style="height: 150px;" />
					</div>
					<div class="col-sm-6" style="padding-top: 30px;">
						<center>
							<h1>SINDICATO DOS MÚSICOS</h1>
							<h3>Acervo Geral</h3>
						</center>
					</div>
					<div class="col-sm-2">
					</div>
				</div>
			</div>
		</div>
	</header>
	<main>
		<div class="container" style="padding-top: 50px;">
			<div class="row">
				<div class="col-sm-1"></div>
				<div class="col-sm-10">
					<form method="post" action="pesquisa_acervo.php">
						<div class="form-row">
							<div class="col-md-5 mb-2">
								<label for="tipoReg">Tipo</label>
								<select id="tipoReg" name="tipoReg" class="form-control">
									<option></option>
									<?php echo $tipos_de_registros; ?>
								</select>

							</div>
							<div class="col-md-7 mb-2">
								<label for="origemReg">Origem</label>
								<select id="origemReg" name="origemReg" class="form-control">
									<option></option>
									<?php echo $origem_dos_registros; ?>
								</select>
							</div>
						</div>
						<div class="form-row">
							<div class="col-md-6 mb-3">
								<label for="validationCustom03">Parte do Título ou descrição</label>
								<input type="text" class="form-control" id="validationCustom03" name='conteudoReg'>
							</div>
							<div class="col-md-3 mb-3">
								<label for="validationCustom04">Data Início</label>
								<input type="date" class="form-control" id="formGroupExampleInput" name="dataInicio">
							</div>
							<div class="col-md-3 mb-3">
								<label for="validationCustom05">Data Fim</label>
								<input type="date" class="form-control" id="formGroupExampleInput" name="dataFim">

							</div>
						</div>

						<button class="btn btn-primary" type="submit" style="background-color: #003882">Pesquisar</button>
					</form>
					<?php
					if ($numero_de_resultados > 0)
						echo $numero_de_resultados . " registro(s) encontrado(s) <br>";
					echo $resultado;
					?>
				</div>
				<div class="col-sm-1"></div>
			</div>
		</div>
	</main>
	<footer>
		<div class="container">
			<div class="row">
				<div class="col-sm-4">
					<p class="white-text">
						Sindicato dos Músicos do Estado do Rio de Janeiro
						R. Álvaro Alvim, 24 / 405 - Centro - CEP 20031-010.
						Tels: (21) 3231-9850 Whatsaap:21998894152
					</p>
				</div>
				<div class=col-sm-1>
				</div>
				<div class="col-sm-3">
					<p class="white-text">
						Horário de Funcionamento:
						Segunda à Sexta das 12:30 às 18:00 horas.
					</p>
				</div>
				<div class=col-sm-1>
				</div>
				<div class="col-sm-3">
					<p class="white-text">
						Portais:
						www.sindmusi.org.br
						www.guiadomusico.com.br.
					</p>
				</div>
			</div>
			<div class="footer-copyright ">
				<div class=row>
					<div class=col-sm-6>
					</div>
					<div class=col-sm-6>
						<div class="container">
							<span>© Produzido e elaborado por Ana Lúcia Canto, Victor Lima e Bernardo Maiorano - UFRJ
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</footer>
</body>

</html>
<!DOCTYPE html>
<html lang="pt-BR">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="X-UA-Compatible" content="ie=edge">
		<script src="https://code.jquery.com/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
		<link rel="stylesheet" href="style.css">
		<title>Teste</title>
	</head>
	<body>
		<div class="container">
			<div class="jumbotron">
				<div style="padding: 5px; margin-bottom: 15px">
					<h3>Cadastro de produtos</h3>
					<form id="formProdutos">
						<div class="row">
							<div class="col-md-3">
								<div class="form-group">
									<label>Código</label>
									<input class="form-control" type="text" name="codigo" id="codigo" required/>
								</div>
							</div>
							<div class="col-md-3">
								<div class="form-group">
									<label>Preço</label>
									<input class="form-control" type="text" name="nome" id="nome" required/>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label>Descrição</label>
							<input class="form-control" type="text" name="descricao" id="descricao" required value="<?= $teste ?>"/>
						</div>
						<button class="btn btn-primary" type="submit" id="enviar">Enviar</button>
						<button class="btn btn-default" type="reset" id="btn-limpar">Limpar</button>
					</form>
				</div>
				<div>
					<table class="table">

						<thead>
							<tr>
								<th scope="col">Código</th>
								<th scope="col">Descrição</th>
								<th scope="col">Preço</th>
							</tr>
						</thead>
						<tbody id="table-produtos">
							<tr>
								<td>1</td>
								<td>dfgdfg</td>
								<td>19,90</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>	

		<script src="jquery.mask.js"></script>
		<script src="teste.js"></script>
	</body>
</html>
<?php
    if(isset($_SESSION['nubank'])){
        switch($_SESSION['nubank']){
            case "data":
                $msg = "Data Inicial é maior do que Data Final.";
            break;
            case "login":
                $msg = "Login e/ou senha incorretos.";
            break;
            case "resultado":
                $msg = "Sua busca não teve resultados.";
            break;
            default:
                unset($msg);
            break;
        }

        unset($_SESSION['nubank']);
    }
?>

<h1 class="title">Extração de Relatório</h1>
<p class="erro"><?php isset($msg) ? $msg : ''; ?></p>
<form action="/?api" method="POST">
    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="login">Login CallMiner:</label>
            <input type="text" class="form-control" name="login" id="login" required>
        </div>
        <div class="form-group col-md-6">
            <label for="senha">Senha CallMiner:</label>
            <input type="password" class="form-control" name="senha" id="senha"required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-6">
            <label for="datainicio">Data Inicio:</label>
            <input type="date" class="form-control" name="datainicio" id="datainicio" required>
        </div>
        <div class="form-group col-md-6">
            <label for="datafim">Data Fim:</label>
            <input type="date" class="form-control" name="datafim" id="datafim" required>
        </div>
    </div>
    <button type="submit" class="btn btn-mex">Extrair</button>
</form>

<script>
    document.querySelector('#datainicio').addEventListener('change', function(e){
        let datainicio = document.querySelector('#datainicio').value;
        document.querySelector('#datafim').setAttribute('min',datainicio); 
    });
</script>
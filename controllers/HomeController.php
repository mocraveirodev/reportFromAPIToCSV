<?php
    session_start();

    class HomeController{
        public function acao($rotas){
            switch($rotas){
                case "home":
                    $this->viewHome(); //Mostra pagina inicial
                break;
                case "api":
                    $this->api(); //Mostra pagina inicial
                break;
            }
        }

        private function viewLayout(){
            include "views/layout.php";
        }

        private function viewHome(){
            $_SESSION['page'] = 'home';

            $this->viewLayout();
        }

        private function api(){
            var_dump($_POST);
            die;
            unset($_SESSION['nubank']);

            $username = $_POST['login'];
            $password = $_POST['senha'];
            $StartDate = date('Y-m-d\TH:i:s', strtotime($_POST['datainicio']));
            $EndDate = date('Y-m-d\TH:i:s', strtotime($_POST['datafim']));
            $page = 1;

            if($EndDate < $StartDate){
                $_SESSION['nubank'] = "data";
                echo "<script>window.location.href = '/';</script>";
            }

            $jwt = $this->getJWT($username, $password);

            if($jwt['header'][0] != "HTTP/1.1 200 OK"){
                $_SESSION['nubank'] = "login";
                $db->relatorios($username,$StartDate,$EndDate,"Login e/ou senha incorretos.");
                echo "<script>window.location.href = '/?nubank';</script>";
                die;
            }

            $categories = $this->getCategories($jwt['body']);
            $nomeCategorias = [];

            foreach($categories['body'] as $cat){
                if(($cat['SectionName'] == "TOM DE VOZ _original") || ($cat['SectionName'] == "TOM DE VOZ")){
                    array_push($nomeCategorias,$cat['BucketFullname']);
                }
            }

            if($EndDate == $StartDate){
                $EndDate = date('Y-m-d\TH:i:s', strtotime("+1 day",strtotime($EndDate)));
            }

            $search = $this->getSearch($jwt['body'],$StartDate,$EndDate,$page);

            if(empty($search['body'])){
                $_SESSION['nubank'] = "resultado";
                echo "<script>window.location.href = '/';</script>";
                die;
            }

            header('Cache-Control: max-age=0');
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="export_CM.csv"');
            $output = fopen('php://output', 'w+');

            fputcsv($output, array("Eureka ID","source_id","agente","actor_squad","actor_affiliation","activity_type","Grade - Score TOM DE VOZ","Categories","Rating"));

            while(!empty($search['body'])){
                foreach($search['body'] as $contato){
                    $catHit = [];
                    $weight = 0;
                    
                    foreach($contato['Categories'] as $cat){
                        array_push($catHit,$cat['BucketFullName']);
                    }

                    foreach($contato['Scores'] as $score){
                        if($score['ScoreId'] == 61){
                            $weight = $score['Weight'];
                        }
                    }
            
                    foreach($nomeCategorias as $cat){
                        if(in_array($cat, $catHit)){
                            fputcsv($output, array($contato['Contact']['Id'],$contato['Others']['UDF_text_17'],$contato['Attributes']['Agent'],$contato['Attributes']['UDF_text_04'],$contato['Attributes']['UDF_text_13'],$contato['Attributes']['UDF_text_02'],$weight,$cat,"Hit"));
                        }else{
                            fputcsv($output, array($contato['Contact']['Id'],$contato['Others']['UDF_text_17'],$contato['Attributes']['Agent'],$contato['Attributes']['UDF_text_04'],$contato['Attributes']['UDF_text_13'],$contato['Attributes']['UDF_text_02'],$weight,$cat,"Miss"));
                        }
                    }
                }
                $page++;
                $search = $this->getSearch($jwt['body'],$StartDate,$EndDate,$page);
            }
            fclose( $output );
        }

        private function getJWT($username, $password){
            $method = 'POST';
            $header = "Content-type: application/json; charset=utf-8\r\n";
            $string = "{
                \"Username\": \"{$username}\",
                \"Password\": \"{$password}\",
                \"ApiKey\": \"nubank\"
            }";

            $endpoint = "https://sapi.callminer.net/security/getToken";
    
            $resposta = $this->callAPI($method, $header, $string, $endpoint);
    
            return $resposta;
        }

        private function callAPI($method, $header, $string, $endpoint){
            $context = stream_context_create(
                array(
                'http' => array(
                    'method' => $method,
                    'header' => $header,
                    'content' => $string                            
                    )
                )
            );

            $contents = file_get_contents($endpoint, null, $context);            
            $body = json_decode($contents,true);
            
            $resposta = ["header" => $http_response_header, "body" => $body];
            
            return $resposta;
        }

        private function getCategories($jwt){
            $header = "Authorization: JWT ".$jwt."\r\nContent-type: application/json; charset=utf-8\r\n";    
            $endpoint = "https://feapi.callminer.net/api/v2/categories";

            $resposta = $this->callAPI('GET',$header,"",$endpoint);
    
            return $resposta;
        }

        private function getSearch($jwt,$StartDate,$EndDate,$page){
            $header = "Authorization: JWT ".$jwt."\r\nContent-type: application/json; charset=utf-8\r\n";

            $endpoint = "https://feapi.callminer.net/api/v2/export/datesearch?startDate=$StartDate&stopDate=$EndDate&page=$page&useClientCaptureDate=true";
    
            $resposta = $this->callAPI('GET',$header,"",$endpoint);
    
            return $resposta;
        }
    }
?>
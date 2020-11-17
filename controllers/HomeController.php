<?php
    session_start();

    class HomeController{
        public function acao($rotas){
            switch($rotas){
                case "home":
                    $this->viewHome(); //Mostra pagina inicial
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
    }
?>